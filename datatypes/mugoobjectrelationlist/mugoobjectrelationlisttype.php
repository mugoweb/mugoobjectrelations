<?php
//
// Definition of MugoObjectRelationListType class
// derived from eZObjectRelationListType class
/*!
  \class MugoObjectRelationListType mugoobjectrelationlisttype.php
  \ingroup eZDatatype
  \brief A content datatype which handles object relations with the addition of cross-reference data on the object relation itself
*/

class MugoObjectRelationListType extends eZDataType
{
    const DATA_TYPE_STRING = "mugoobjectrelationlist";
    const FIELDSEPARATOR = "|";
    const OPTIONSEPARATOR = ":";
    const RELATIONSEPARATOR = "&";
    const EXTRAFIELDSSEPARATOR = "-";

    /*!
     Initializes with a string id and a description.
    */
    function __construct()
    {
        //eZDebug::writeDebug('MugoObjectRelationListType');
        $this->eZDataType( self::DATA_TYPE_STRING, "Mugo object relations",
                    array(  'serialize_supported' => true,
                            'object_serialize_map' => array( 'data_text' => 'text' ) ) );
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('validateObjectAttributeHTTPInput');
        $inputParameters = $contentObjectAttribute->inputParameters();
        $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
        $parameters = $contentObjectAttribute->validationParameters();
        if ( isset( $parameters['prefix-name'] ) and
             $parameters['prefix-name'] )
            $parameters['prefix-name'][] = $contentClassAttribute->attribute( 'name' );
        else
            $parameters['prefix-name'] = array( $contentClassAttribute->attribute( 'name' ) );

        $status = eZInputValidator::STATE_ACCEPTED;
        $postVariableName = $base . "_data_object_relation_list_" . $contentObjectAttribute->attribute( "id" );
        $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
        $classContent = $contentClassAttribute->content();

        $content = $contentObjectAttribute->content();
        if ( $contentObjectAttribute->validateIsRequired() and count( $content['relation_list'] ) == 0 )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                 'Missing objectrelation list input.' ) );
            return eZInputValidator::STATE_INVALID;
        }

        for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
        {
            $relationItem = $content['relation_list'][$i];
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $subObjectVersion = $relationItem['contentobject_version'];
                $attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
                $object = eZContentObject::fetch( $subObjectID );
                if ( $object )
                {
                    $attributes = $object->contentObjectAttributes( true, $subObjectVersion );

                    $validationResult = $object->validateInput( $attributes, $attributeBase,
                                                                $inputParameters, $parameters );
                    $inputValidated = $validationResult['input-validated'];
                    $content['temp'][$subObjectID]['require-fixup'] = $validationResult['require-fixup'];
                    $statusMap = $validationResult['status-map'];
                    foreach ( $statusMap as $statusItem )
                    {
                        $statusValue = $statusItem['value'];
                        if ( $statusValue == eZInputValidator::STATE_INTERMEDIATE and
                             $status == eZInputValidator::STATE_ACCEPTED )
                            $status = eZInputValidator::STATE_INTERMEDIATE;
                        else if ( $statusValue == eZInputValidator::STATE_INVALID )
                        {
                            $contentObjectAttribute->setHasValidationError( false );
                            $status = eZInputValidator::STATE_INVALID;
                        }
                    }

                    $content['temp'][$subObjectID]['attributes'] = $attributes;
                    $content['temp'][$subObjectID]['object'] = $object;
                }
            }

            // Check that required extra fields are filled in
            $extraFieldsBase = $base . '_extra_fields_' . $contentObjectAttribute->attribute( "id" );
            $postedExtraFields = $http->postVariable( $extraFieldsBase, array() );
            foreach( $classContent['extra_fields'] as $extraFieldIdentifier => $extraField )
            {
                if( $extraField['required'] )
                {
                    if( !isset( $postedExtraFields[$i][$extraFieldIdentifier] ) || '' == trim( $postedExtraFields[$i][$extraFieldIdentifier] ) )
                    {
                        $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                        'Missing ' . $extraField['name'] . ' input.' ) );
                        return eZInputValidator::STATE_INVALID;
                    }
                }
            }
        }
        
        // Check that required extra fields (attribute-level) are filled in
        $extraFieldsBase = $base . '_extra_fields_attribute_level_' . $contentObjectAttribute->attribute( "id" );
        $postedExtraFields = $http->postVariable( $extraFieldsBase, array() );

        foreach( $classContent['extra_fields_attribute_level'] as $extraFieldIdentifier => $extraField )
        {
            if( $extraField['required'] )
            {
                if( !isset( $postedExtraFields[$extraFieldIdentifier] ) || '' == trim( $postedExtraFields[$extraFieldIdentifier] ) )
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                    'Missing ' . $extraField['name'] . ' input.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }

        $contentObjectAttribute->setContent( $content );
        return $status;
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function fixupObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixupObjectAttributeHTTPInput');
        $content = $contentObjectAttribute->content();
        for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
        {
            $relationItem = $content['relation_list'][$i];
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
                $object = $content['temp'][$subObjectID]['object'];
                $requireFixup = $content['temp'][$subObjectID]['require-fixup'];
                if ( $object and
                     $requireFixup )
                {
                    $attributes = $content['temp'][$subObjectID]['attributes'];
                    $object->fixupInput( $attributes, $attributeBase );
                }
            }
        }
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fetchObjectAttributeHTTPInput');

        $content = $contentObjectAttribute->content();
        // new object creation
        $newObjectPostVariableName = "attribute_" . $contentObjectAttribute->attribute( "id" ) . "_new_object_name";
        if ( $http->hasPostVariable( $newObjectPostVariableName ) )
        {
            $name = $http->postVariable( $newObjectPostVariableName );
            if ( !empty( $name ) )
            {
                $content['new_object'] = $name;
            }
        }
        $singleSelectPostVariableName = "single_select_" . $contentObjectAttribute->attribute( "id" );
        if ( $http->hasPostVariable( $singleSelectPostVariableName ) )
            $content['singleselect'] = true;

        $postVariableName = $base . "_data_object_relation_list_" . $contentObjectAttribute->attribute( "id" );
        $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
        $classContent = $contentClassAttribute->content();

        // We need to keep track of this to sort properly
        $selectedObjectIDArray = $http->hasPostVariable( $postVariableName ) ? $http->postVariable( $postVariableName ) : false;

        $contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );

        // To get the data from the post
        $extraFieldsBase = $base . '_extra_fields_' . $contentObjectAttributeID;
        $extraFieldList = array();
        if( $http->hasPostVariable( $extraFieldsBase ) )
        {
            $extraFieldList = $http->postVariable( $extraFieldsBase );
        }

        $priorityBase = $base . '_priority';
        $priorities = array();
        if( $http->hasPostVariable( $priorityBase ) )
        {
            $priorities = $http->postVariable( $priorityBase );
        }
        $reorderedRelationList = array();

        // Contains existing priorities
        $existsPriorities = array();
        for( $i=0; $i<count( $content['relation_list'] ); ++$i )
        {
            // sanitize
            $priorities[ $contentObjectAttributeID ][$i] = (int)$priorities[ $contentObjectAttributeID ][$i];
            $existsPriorities[ $i ] = $priorities[ $contentObjectAttributeID ][$i];

            // Change objects' priorities providing their uniqueness.
            for( $j = 0; $j < count( $content['relation_list'] ); ++$j )
            {
                if( $i == $j ) continue;
                if( $priorities[$contentObjectAttributeID][$i] == $priorities[$contentObjectAttributeID][$j] )
                {
                    $index = $priorities[$contentObjectAttributeID][$i];
                    while ( in_array( $index, $existsPriorities ) )
                        ++$index;
                    $priorities[$contentObjectAttributeID][$j] = $index;
                }
            }
        }
        for( $i=0; $i<count( $content['relation_list'] ); ++$i )
        {
            $relationItem = $content['relation_list'][$i];
            if( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
                $object = $content['temp'][$subObjectID]['object'];
                if( $object )
                {
                    $attributes = $content['temp'][$subObjectID]['attributes'];

                    $customActionAttributeArray = array();
                    $fetchResult = $object->fetchInput( $attributes, $attributeBase,
                                                        $customActionAttributeArray,
                                                        $contentObjectAttribute->inputParameters() );
                    $content['temp'][$subObjectID]['attribute-input-map'] = $fetchResult['attribute-input-map'];
                    $content['temp'][$subObjectID]['attributes'] = $attributes;
                    $content['temp'][$subObjectID]['object'] = $object;
                }
            }
            if ( isset( $priorities[$contentObjectAttributeID][$i] ) )
            {
                $relationItem['priority'] = $priorities[$contentObjectAttributeID][(int)array_search($relationItem['contentobject_id'], $selectedObjectIDArray)];
            }

            // Storing Extra fields
            foreach( $extraFieldList[(int)array_search($relationItem['contentobject_id'], $selectedObjectIDArray)] as $extraFieldIdentifier => $extraFieldValue )
            {
                $relationItem["extra_fields"][$extraFieldIdentifier] = self::getContentClassFieldOptionName( $contentObjectAttribute, $extraFieldIdentifier, $extraFieldValue );
            }

            $reorderedRelationList[$relationItem['priority']] = $relationItem;
        }

        ksort( $reorderedRelationList );
        unset( $content['relation_list'] );
        $content['relation_list'] = array();
        reset( $reorderedRelationList );
        $i = 0;
        foreach($reorderedRelationList as $key => $relationItem)
        {
            $content['relation_list'][] = $relationItem;
            $content['relation_list'][$i]['priority'] = $i + 1;
            ++$i;
        }

        // Store extra fields (attribute-level) fields
        $extraFieldsBase = $base . '_extra_fields_attribute_level_' . $contentObjectAttributeID;
        $extraFieldList = array();
        if( $http->hasPostVariable( $extraFieldsBase ) )
        {
            $extraFieldList = $http->postVariable( $extraFieldsBase );
        }
        
        if( $extraFieldList )
        {
            foreach( $extraFieldList as $extraFieldIdentifier => $extraFieldValue )
            {
                $content['extra_fields_attribute_level'][$extraFieldIdentifier] = self::getContentClassFieldOptionName( $contentObjectAttribute, $extraFieldIdentifier, $extraFieldValue, true );
            }
        }

        $contentObjectAttribute->setContent( $content );
        return true;
    }

    function createNewObject( $contentObjectAttribute, $name )
    {
        //eZDebug::writeDebug('createNewObject');
        $classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
        $classContent = $classAttribute->content();
        $classID = $classContent['object_class'];
        if ( !isset( $classID ) or !is_numeric( $classID ) )
            return false;

        $defaultPlacementNode = ( is_array( $classContent['default_placement'] ) and isset( $classContent['default_placement']['node_id'] ) ) ? $classContent['default_placement']['node_id'] : false;
        if ( !$defaultPlacementNode )
        {
            eZDebug::writeError( 'Default placement is missing', 'MugoObjectRelationListType::createNewObject' );
            return false;
        }

        $node = eZContentObjectTreeNode::fetch( $defaultPlacementNode );
        // Check if current user can create a new node as child of this node.
        if ( !$node or !$node->canCreate() )
        {
            eZDebug::writeError( 'Default placement is wrong or the current user can\'t create a new node as child of this node.', 'MugoObjectRelationListType::createNewObject' );
            return false;
        }

        $classList = $node->canCreateClassList( false );
        $canCreate = false;
        // Check if current user can create object of class (with $classID)
        foreach ( $classList as $class )
        {
            if ( $class['id'] == $classID )
            {
                $canCreate = true;
                break;
            }
        }
        if ( !$canCreate )
        {
            eZDebug::writeError( 'The current user is not allowed to create objects of class (ID=' . $classID . ')', 'MugoObjectRelationListType::createNewObject' );
            return false;
        }

        $class = eZContentClass::fetch( $classID );
        if ( !$class )
            return false;

        $currentObject = $contentObjectAttribute->attribute( 'object' );
        $sectionID = $currentObject->attribute( 'section_id' );
        //instantiate object, same section, currentuser as owner (i.e. provide false as param)
        $newObjectInstance = $class->instantiate( false, $sectionID );
        $nodeassignment = $newObjectInstance->createNodeAssignment( $defaultPlacementNode, true );
        $nodeassignment->store();
        $newObjectInstance->sync();
        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $newObjectInstance->attribute( 'id' ), 'version' => 1 ) );
        // so it updates the attributes
        $newObjectInstance->rename( $name );

        return $newObjectInstance->attribute( 'id' );
    }

    function storeObjectAttribute( $attribute )
    {
        //eZDebug::writeDebug('storeObjectAttribute START');
        $content = $attribute->content();
        if ( isset( $content['new_object'] ) )
        {
            $newID = $this->createNewObject( $attribute, $content['new_object'] );
            // if this is a single element selection mode (radio or dropdown), then the newly created item is the only one selected
            if ( $newID )
            {
                if ( isset( $content['singleselect'] ) )
                    $content['relation_list'] = array();
                $content['relation_list'][] = $this->appendObject( $newID, 0, $attribute );
            }
            unset( $content['new_object'] );
            $attribute->setContent( $content );
        }

        $contentClassAttributeID = $attribute->ContentClassAttributeID;
        $contentObjectID = $attribute->ContentObjectID;
        $contentObjectVersion = $attribute->Version;

        $obj = $attribute->object();
        //get eZContentObjectVersion
        $currVerobj = $obj->version( $contentObjectVersion );

        // create translation List
        // $translationList will contain for example eng-GB, ita-IT etc.
        $translationList = $currVerobj->translations( false );

        // get current language_code
        $langCode = $attribute->attribute( 'language_code' );
        // get count of LanguageCode in translationList
        $countTsl = count( $translationList );
        // order by asc
        sort( $translationList );

        if ( ( $countTsl == 1 ) or ( $countTsl > 1 and $translationList[0] == $langCode ) )
        {
             eZContentObject::fetch( $contentObjectID )->removeContentObjectRelation( false, $contentObjectVersion, $contentClassAttributeID, eZContentObject::RELATION_ATTRIBUTE );
        }

        foreach( $content['relation_list'] as $relationItem )
        {
            // Installing content object, postUnserialize is not called yet,
            // so object's ID is unknown.
            if ( !$relationItem['contentobject_id'] || !isset( $relationItem['contentobject_id'] ) )
                continue;

            $subObjectID = $relationItem['contentobject_id'];
            $subObjectVersion = $relationItem['contentobject_version'];

            eZContentObject::fetch( $contentObjectID )->addContentObjectRelation( $subObjectID, $contentObjectVersion, $contentClassAttributeID, eZContentObject::RELATION_ATTRIBUTE );

            if ( $relationItem['is_modified'] && isset( $content['temp'][$subObjectID]['object' ] ) )
            {
                // handling sub-objects
                $object = $content['temp'][$subObjectID]['object'];
                if ( $object )
                {
                    $attributes = $content['temp'][$subObjectID]['attributes'];
                    $attributeInputMap = $content['temp'][$subObjectID]['attribute-input-map'];
                    $object->storeInput( $attributes,
                                         $attributeInputMap );
                    $version = eZContentObjectVersion::fetchVersion( $subObjectVersion, $subObjectID );
                    if ( $version )
                    {
                        $version->setAttribute( 'modified', time() );
                        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
                        $version->store();
                    }

                    $object->setAttribute( 'status', eZContentObject::STATUS_DRAFT );
                    $object->store();
                }
            }
        }
        //eZDebug::writeDebug('storeObjectAttribute END');
        return MugoObjectRelationListType::storeObjectAttributeContent( $attribute, $content );
    }

    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        //eZDebug::writeDebug('onPublish START');
        $content = $contentObjectAttribute->content();
        foreach( $content['relation_list'] as $key => $relationItem )
        {
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $subObjectVersion = $relationItem['contentobject_version'];
                $object = eZContentObject::fetch( $subObjectID );

                if ( $object )
                {
                    $class = $object->contentClass();
                    $time = time();

                    // Make the previous version archived
                    $currentVersion = $object->currentVersion();
                    $currentVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_ARCHIVED );
                    $currentVersion->setAttribute( 'modified', $time );
                    $currentVersion->store();

                    $version = eZContentObjectVersion::fetchVersion( $subObjectVersion, $subObjectID );
                    $version->setAttribute( 'modified', $time );
                    $version->setAttribute( 'status', eZContentObjectVersion::STATUS_PUBLISHED );
                    $version->store();
                    $object->setAttribute( 'status', eZContentObject::STATUS_PUBLISHED );
                    if ( !$object->attribute( 'published' ) )
                        $object->setAttribute( 'published', $time );
                    $object->setAttribute( 'modified', $time );
                    $object->setAttribute( 'current_version', $version->attribute( 'version' ) );
                    $object->setAttribute( 'is_published', true );
                    $objectName = $class->contentObjectName( $object, $version->attribute( 'version' ) );
                    $object->setName( $objectName, $version->attribute( 'version' ) );
                    $object->store();
                }
                if ( $relationItem['parent_node_id'] > 0 )
                {
                    if ( !eZNodeAssignment::fetch( $object->attribute( 'id' ), $object->attribute( 'current_version' ), $relationItem['parent_node_id'], false ) )
                    {
                        $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $object->attribute( 'id' ),
                                                                           'contentobject_version' => $object->attribute( 'current_version' ),
                                                                           'parent_node' => $relationItem['parent_node_id'],
                                                                           'sort_field' => eZContentObjectTreeNode::SORT_FIELD_PUBLISHED,
                                                                           'sort_order' => eZContentObjectTreeNode::SORT_ORDER_DESC,
                                                                           'is_main' => 1 ) );
                        $nodeAssignment->store();
                    }
                    $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ),
                                                                                                 'version' => $object->attribute( 'current_version' ) ) );
                    $objectNodeID = $object->attribute( 'main_node_id' );
                    $content['relation_list'][$key]['node_id'] = $objectNodeID;
                }
                else
                {
                    if ( !eZNodeAssignment::fetch( $object->attribute( 'id' ), $object->attribute( 'current_version' ), $contentObject->attribute( 'main_node_id' ), false ) )
                    {
                        $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $object->attribute( 'id' ),
                                                                           'contentobject_version' => $object->attribute( 'current_version' ),
                                                                           'parent_node' => $contentObject->attribute( 'main_node_id' ),
                                                                           'sort_field' => eZContentObjectTreeNode::SORT_FIELD_PUBLISHED,
                                                                           'sort_order' => eZContentObjectTreeNode::SORT_ORDER_DESC,
                                                                           'is_main' => 1 ) );
                        $nodeAssignment->store();
                    }
                }
                $content['relation_list'][$key]['is_modified'] = false;
            }
        }
        MugoObjectRelationListType::storeObjectAttributeContent( $contentObjectAttribute, $content );
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->store();
        //eZDebug::writeDebug('onPublish END');
    }

    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        //eZDebug::writeDebug('initializeObjectAttribute');
        static $copiedRelatedAccordance;
        if ( !isset( $copiedRelatedAccordance ) )
            $copiedRelatedAccordance = array();

        if ( $currentVersion != false )
        {
            $dataText = $originalContentObjectAttribute->attribute( 'data_text' );
            $contentObjectAttribute->setAttribute( 'data_text', $dataText );
            $contentObjectID = $contentObjectAttribute->attribute( 'contentobject_id' );
            $originalContentObjectID = $originalContentObjectAttribute->attribute( 'contentobject_id' );

            if ( $contentObjectID != $originalContentObjectID )
            {
                $classContent = MugoObjectRelationListType::defaultClassAttributeContent();
                if ( !$classContent['default_placement'] )
                {
                    $content = $originalContentObjectAttribute->content();
                    $contentModified = false;

                    foreach ( $content['relation_list'] as $key => $relationItem )
                    {
                        // create related object copies only if they are subobjects
                        $object = eZContentObject::fetch( $relationItem['contentobject_id'] );
                        if ( !$object instanceof eZContentObject )
                        {
                            unset( $content['relation_list'][$key] );
                            $contentModified = true;
                            continue;
                        }

                        $mainNode = $object->attribute( 'main_node' );
                        if ( $mainNode instanceof eZContentObjectTreeNode )
                        {
                            $node = ( is_numeric( $relationItem['node_id'] ) and $relationItem['node_id'] ) ?
                                      eZContentObjectTreeNode::fetch( $relationItem['node_id'] ) : null;

                            if ( !$node or $node->attribute( 'contentobject_id' ) != $relationItem['contentobject_id'] )
                            {
                                $content['relation_list'][$key]['node_id'] = $mainNode->attribute( 'node_id' );
                                $node = $mainNode;
                                $contentModified = true;
                            }

                            if ( $node instanceof eZContentObjectTreeNode )
                                $parentNodeID =  $node->attribute( 'parent_node_id' );
                            else
                                $parentNodeID = -1;

                            if ( $relationItem['parent_node_id'] != $parentNodeID )
                            {
                                $content['relation_list'][$key]['parent_node_id'] = $parentNodeID;
                                $contentModified = true;
                            }
                        }
                        else
                        {
                            if ( !isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ] ) )
                                $copiedRelatedAccordance[ $relationItem['contentobject_id'] ] = array();

                            if ( isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ] ) and
                                 isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ] ) )
                            {
                                $newObjectID = $copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ][ 'to' ];
                            }
                            else
                            {
                                $newObject = $object->copy( true );
                                $newObjectID = $newObject->attribute( 'id' );
                                $copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ] = array( 'to' => $newObjectID,
                                                                                                                           'from' => $originalContentObjectID );
                            }
                            $content['relation_list'][$key]['contentobject_id'] = $newObjectID;
                            $contentModified = true;
                        }
                    }

                    if ( $contentModified )
                    {
                        $contentObjectAttribute->setContent( $content );
                        $contentObjectAttribute->store();
                    }
                }
            }
        }
    }

    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        //eZDebug::writeDebug('validateClassAttributeHTTPInput');
        return eZInputValidator::STATE_ACCEPTED;
    }

    function fixupClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        //eZDebug::writeDebug('fixupClassAttributeHTTPInput');
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        //eZDebug::writeDebug('fetchClassAttributeHTTPInput: Read http variables ');
        $content = $classAttribute->content();
        $postVariable = 'ContentClass_mugoobjectrelationlist_class_list_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $postVariable ) )
        {
            $constrainedList = $http->postVariable( $postVariable );
            $constrainedClassList = array();
            foreach ( $constrainedList as $constraint )
            {
                if ( trim( $constraint ) != '' )
                    $constrainedClassList[] = $constraint;
            }
            $content['class_constraint_list'] = $constrainedClassList;
        }
        $typeVariable = 'ContentClass_mugoobjectrelationlist_type_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $typeVariable ) )
        {
            $type = $http->postVariable( $typeVariable );
            $content['type'] = $type;
        }

        //Check whether we have the same fields as before
        if( $http->hasPostVariable( $base . "_". self::DATA_TYPE_STRING . "_extra_fields_name_" . $classAttribute->attribute( 'id' ) ) )
        {
            // Note that we only reset the fields if we have some post information. This means that you cannot remove the last field
            $content['extra_fields'] = array();
            $extraFieldsNames = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_name_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsIdentifiers = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_identifier_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsTypes = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_type_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsRequired = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_required_" . $classAttribute->attribute( 'id' ), array() );

            foreach( $extraFieldsNames as $key => $fieldName )
            {
                $fieldIdentifier = $extraFieldsIdentifiers[$key];
                $content['extra_fields'][$fieldIdentifier]['name'] = $fieldName;
                $content['extra_fields'][$fieldIdentifier]['type'] = $extraFieldsTypes[$key];
                if( isset( $extraFieldsRequired[$key] ) )
                {
                    $content['extra_fields'][$fieldIdentifier]['required'] = 1;
                }
                else
                {
                    $content['extra_fields'][$fieldIdentifier]['required'] = 0;
                }

                //if type is selection
                $content['extra_fields'][$fieldIdentifier]['options'] = array();
                if($extraFieldsTypes[$key] == 'selection')
                {
                    //parse the selection variable ContentClass_mugoobjectrelationlist_extra_fields_new_options_424_0_name[]
                    $newOptionsNames = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_new_options_" . $classAttribute->attribute( 'id' ) . "_" . $key . "_name" );
                    $newOptionsIdentifiers = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_new_options_" . $classAttribute->attribute( 'id' ) . "_" . $key . "_identifier" );
                    foreach( $newOptionsNames as $nameKey => $nameValue )
                    {
                        $optionIdentifier = $newOptionsIdentifiers[$nameKey];
                        $content['extra_fields'][$fieldIdentifier]['options'][$optionIdentifier] = $newOptionsNames[$nameKey];
                    }

                }
            }
        }
        
        //Check whether we have the same fields as before (attribute-level)
        if( $http->hasPostVariable( $base . "_". self::DATA_TYPE_STRING . "_extra_fields_attribute_level_name_" . $classAttribute->attribute( 'id' ) ) )
        {
            // Note that we only reset the fields if we have some post information. This means that you cannot remove the last field
            $content['extra_fields_attribute_level'] = array();
            $extraFieldsNames = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_name_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsIdentifiers = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_identifier_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsTypes = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_type_" . $classAttribute->attribute( 'id' ) );
            $extraFieldsRequired = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_required_" . $classAttribute->attribute( 'id' ), array() );

            foreach( $extraFieldsNames as $key => $fieldName )
            {
                $fieldIdentifier = $extraFieldsIdentifiers[$key];
                $content['extra_fields_attribute_level'][$fieldIdentifier]['name'] = $fieldName;
                $content['extra_fields_attribute_level'][$fieldIdentifier]['type'] = $extraFieldsTypes[$key];
                if( isset( $extraFieldsRequired[$key] ) )
                {
                    $content['extra_fields_attribute_level'][$fieldIdentifier]['required'] = 1;
                }
                else
                {
                    $content['extra_fields_attribute_level'][$fieldIdentifier]['required'] = 0;
                }

                //if type is selection
                $content['extra_fields_attribute_level'][$fieldIdentifier]['options'] = array();
                if($extraFieldsTypes[$key] == 'selection')
                {
                    //parse the selection variable ContentClass_mugoobjectrelationlist_extra_fields_attribute_level_new_options_424_0_name[]
                    $newOptionsNames = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_new_options_" . $classAttribute->attribute( 'id' ) . "_" . $key . "_name" );
                    $newOptionsIdentifiers = $http->postVariable( $base . "_" . self::DATA_TYPE_STRING . "_extra_fields_attribute_level_new_options_" . $classAttribute->attribute( 'id' ) . "_" . $key . "_identifier" );
                    foreach( $newOptionsNames as $nameKey => $nameValue )
                    {
                        $optionIdentifier = $newOptionsIdentifiers[$nameKey];
                        $content['extra_fields_attribute_level'][$fieldIdentifier]['options'][$optionIdentifier] = $newOptionsNames[$nameKey];
                    }

                }
            }
        }

        //Create the new extra field
        if ( $http->hasPostVariable( $base . "_" . self::DATA_TYPE_STRING . "_newfield_button_" . $classAttribute->attribute( 'id' ) ) )
        {
            // Set default values
            $fieldIdentifierNum = '';
            while( isset( $content['extra_fields']['field_identifier' . $fieldIdentifierNum] ) )
            {
               ++$fieldIdentifierNum;
            }
            $content['extra_fields']['field_identifier' . $fieldIdentifierNum] = array( 'name' => 'Field Name', 'type' => 'text', 'options' => array() );
        }

        //Create the new extra field (attribute-level)
        if ( $http->hasPostVariable( $base . "_" . self::DATA_TYPE_STRING . "_newfield_attribute_level_button_" . $classAttribute->attribute( 'id' ) ) )
        {
            // Set default values
            $fieldIdentifierNum = '';
            while( isset( $content['extra_fields_attribute_level']['field_identifier' . $fieldIdentifierNum] ) )
            {
               ++$fieldIdentifierNum;
            }
            $content['extra_fields_attribute_level']['field_identifier' . $fieldIdentifierNum] = array( 'name' => 'Field Name', 'type' => 'text', 'options' => array() );
        }

        $objectClassVariable = 'ContentClass_' . self::DATA_TYPE_STRING . '_object_class_' . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $objectClassVariable ) )
        {
            $content['object_class'] = $http->postVariable( $objectClassVariable );
        }

        $classAttribute->setContent( $content );
        $classAttribute->store();
        return true;
    }

    function initializeClassAttribute( $classAttribute )
    {
        //eZDebug::writeDebug('initializeClassAttribute');
        $xmlText = $classAttribute->attribute( 'data_text5' );
        if ( trim( $xmlText ) == '' )
        {
            $content = MugoObjectRelationListType::defaultClassAttributeContent();
            return MugoObjectRelationListType::storeClassAttributeContent( $classAttribute, $content );
        }
    }

    function preStoreClassAttribute( $classAttribute, $version )
    {
        //eZDebug::writeDebug('preStoreClassAttribute');
        $content = $classAttribute->content();
        return MugoObjectRelationListType::storeClassAttributeContent( $classAttribute, $content );
    }

    function storeClassAttributeContent( $classAttribute, $content )
    {
        //eZDebug::writeDebug('storeClassAttributeContent');
        if ( is_array( $content ) )
        {
            $doc = MugoObjectRelationListType::createClassDOMDocument( $content );
            MugoObjectRelationListType::storeClassDOMDocument( $doc, $classAttribute );
            return true;
        }
        return false;
    }

    function storeObjectAttributeContent( $objectAttribute, $content )
    {
        //eZDebug::writeDebug('storeObjectAttributeContent');
//        //eZDebug::writeDebug(var_export($content, true));
//        //eZDebug::writeDebug(var_export($objectAttribute, true));
        if ( is_array( $content ) )
        {
            $doc = MugoObjectRelationListType::createObjectDOMDocument( $content );
            MugoObjectRelationListType::storeObjectDOMDocument( $doc, $objectAttribute );
            return true;
        }
        return false;
    }

    static function storeClassDOMDocument( $doc, $classAttribute )
    {
        //eZDebug::writeDebug('storeClassDOMDocument');
        $docText = MugoObjectRelationListType::domString( $doc );
        $classAttribute->setAttribute( 'data_text5', $docText );
    }

    static function storeObjectDOMDocument( $doc, $objectAttribute )
    {
        //eZDebug::writeDebug('storeObjectDOMDocument START');
        $docText = MugoObjectRelationListType::domString( $doc );
        $objectAttribute->setAttribute( 'data_text', $docText );
        //eZDebug::writeDebug('storeObjectDOMDocument END');
    }

    /*!
     \static
     \return the XML structure in \a $domDocument as text.
             It will take of care of the necessary charset conversions
             for content storage.
    */
    static function domString( $domDocument )
    {
        //eZDebug::writeDebug('domString START');
        $ini = eZINI::instance();
        $xmlCharset = $ini->variable( 'RegionalSettings', 'ContentXMLCharset' );
        if ( $xmlCharset == 'enabled' )
        {
            $charset = eZTextCodec::internalCharset();
        }
        else if ( $xmlCharset == 'disabled' )
            $charset = true;
        else
            $charset = $xmlCharset;
        if ( $charset !== true )
        {
            $charset = eZCharsetInfo::realCharsetCode( $charset );
        }
        $domString = $domDocument->saveXML();
        //eZDebug::writeDebug('domString END');
        return $domString;
    }

    static function createClassDOMDocument( $content )
    {
        //eZDebug::writeDebug('createClassDOMDocument: Store the array as XML START');
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $constraints = $doc->createElement( 'constraints' );
        foreach ( $content['class_constraint_list'] as $constraintClassIdentifier )
        {
            unset( $constraintElement );
            $constraintElement = $doc->createElement( 'allowed-class' );
            $constraintElement->setAttribute( 'contentclass-identifier', $constraintClassIdentifier );
            $constraints->appendChild( $constraintElement );
        }
        $root->appendChild( $constraints );
        $constraintType = $doc->createElement( 'type' );
        $constraintType->setAttribute( 'value', $content['type'] );
        $root->appendChild( $constraintType );

        if( isset( $content['extra_fields'] ) )
        {
            $extraFields = $doc->createElement( 'extra_fields' );
            foreach( $content['extra_fields'] as $extraFieldIdentifier => $extraField )
            {
                /*
                <field name="Role" identifier="role" type="selection">
                    <options><option name="Administrative" identifier="administrative"></option><option name="Artists" identifier="artists"></option></options>
                </field>
                */
                $field = $doc->createElement( 'field' );

                $field->setAttribute( 'name', $extraField['name'] );
                $field->setAttribute( 'identifier', $extraFieldIdentifier );
                $field->setAttribute( 'type', $extraField['type'] );
                $field->setAttribute( 'required', $extraField['required'] );

                if( 'selection' == $extraField['type'] )
                {
                    // Create the options
                    if( !$extraField['options'] )
                    {
                        $extraField['options'] = array();
                    }
                    $fieldOptions = $doc->createElement( 'options' );
                    if( $extraField['options'] )
                    {
                        foreach( $extraField['options'] as $optionIdentifier => $optionName )
                        {
                            $fieldOption = $doc->createElement( 'option' );
                            $fieldOption->setAttribute( 'name', $optionName );
                            $fieldOption->setAttribute( 'identifier', $optionIdentifier );

                            $fieldOptions->appendChild( $fieldOption );
                        }
                    }
                    $field->appendChild( $fieldOptions );
                }

                //Put the elements in the tree
                $extraFields->appendChild( $field );
            }
            $root->appendChild( $extraFields );
        }
        
        if( isset( $content['extra_fields_attribute_level'] ) )
        {
            $extraFields = $doc->createElement( 'extra_fields_attribute_level' );
            foreach( $content['extra_fields_attribute_level'] as $extraFieldIdentifier => $extraField )
            {
                /*
                <field name="Role" identifier="role" type="selection">
                    <options><option name="Administrative" identifier="administrative"></option><option name="Artists" identifier="artists"></option></options>
                </field>
                */
                $field = $doc->createElement( 'field' );

                $field->setAttribute( 'name', $extraField['name'] );
                $field->setAttribute( 'identifier', $extraFieldIdentifier );
                $field->setAttribute( 'type', $extraField['type'] );
                $field->setAttribute( 'required', $extraField['required'] );

                if( 'selection' == $extraField['type'] )
                {
                    // Create the options
                    if( !$extraField['options'] )
                    {
                        $extraField['options'] = array();
                    }
                    $fieldOptions = $doc->createElement( 'options' );
                    if( $extraField['options'] )
                    {
                        foreach( $extraField['options'] as $optionIdentifier => $optionName )
                        {
                            $fieldOption = $doc->createElement( 'option' );
                            $fieldOption->setAttribute( 'name', $optionName );
                            $fieldOption->setAttribute( 'identifier', $optionIdentifier );

                            $fieldOptions->appendChild( $fieldOption );
                        }
                    }
                    $field->appendChild( $fieldOptions );
                }

                //Put the elements in the tree
                $extraFields->appendChild( $field );
            }
            $root->appendChild( $extraFields );
        }

        $objectClass = $doc->createElement( 'object_class' );
        $objectClass->setAttribute( 'value', $content['object_class'] );
        $root->appendChild( $objectClass );

        $placementNode = $doc->createElement( 'contentobject-placement' );
        if ( $content['default_placement'] )
        {
            $placementNode->setAttribute( 'node-id',  $content['default_placement']['node_id'] );
        }
        $root->appendChild( $placementNode );
        $doc->appendChild( $root );

        //eZDebug::writeDebug('createClassDOMDocument: Store the array as XML END');
        return $doc;
    }

    static function createObjectDOMDocument( $content )
    {
        //eZDebug::writeDebug('createObjectDOMDocument START');

        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $relationList = $doc->createElement( 'relation-list' );
        $attributeDefinitions = MugoObjectRelationListType::contentObjectArrayXMLMap();

        foreach ( $content['relation_list'] as $relationItem )
        {
            unset( $relationElement );

            $relationElement = $doc->createElement( 'relation-item' );
            foreach ( $attributeDefinitions as $attributeXMLName => $attributeKey )
            {
                if ( isset( $relationItem[$attributeKey] ) && $relationItem[$attributeKey] !== false )
                {
                    if( $attributeKey == 'extra_fields' )
                    {
                        $fieldsElement = $doc->createElement( 'fields' );
                        foreach( $relationItem[$attributeKey] as $fieldIdentifier => $field )
                        {
                            // Storing a selection
                            if( is_array( $field ) && isset( $field['identifier'] ) )
                            {
                                $field = $field['identifier'];
                            }
                            $node = $doc->createElement( $fieldIdentifier );
                            $node->setAttribute( 'value', $field );

                            $fieldsElement->appendChild( $node );
                        }
                        $relationElement->appendChild( $fieldsElement );
                    }
                    else
                    {
                        $value = $relationItem[$attributeKey];
                        $relationElement->setAttribute( $attributeXMLName, $value );
                    }
                }
            }

            $relationList->appendChild( $relationElement );
        }
        $root->appendChild( $relationList );
        
        $extraFieldsAttributeLevel = $doc->createElement( 'extra_fields_attribute_level' );

        if( isset( $content['extra_fields_attribute_level'] ) && $content['extra_fields_attribute_level'] )
        {
            foreach( $content['extra_fields_attribute_level'] as $extraFieldAttributeLevelIdentifier => $extraFieldAttributeLevelValue )
            {
                // Storing a selection
                if( is_array( $extraFieldAttributeLevelValue ) && isset( $extraFieldAttributeLevelValue['identifier'] ) )
                {
                    $extraFieldAttributeLevelValue = $extraFieldAttributeLevelValue['identifier'];
                }
                
                $node = $doc->createElement( $extraFieldAttributeLevelIdentifier );
                $node->setAttribute( 'value', $extraFieldAttributeLevelValue );

                $extraFieldsAttributeLevel->appendChild( $node );
            }
        }
        
        $root->appendChild( $extraFieldsAttributeLevel );

        $doc->appendChild( $root );
        //eZDebug::writeDebug('createObjectDOMDocument END');
        return $doc;
    }

    static function contentObjectArrayXMLMap()
    {
        //eZDebug::writeDebug('contentObjectArrayXMLMap START-END');
        return array( 'identifier' => 'identifier'
                    , 'priority' => 'priority'
                    , 'in-trash' => 'in_trash'
                    , 'contentobject-id' => 'contentobject_id'
                    , 'contentobject-version' => 'contentobject_version'
                    , 'node-id' => 'node_id'
                    , 'parent-node-id' => 'parent_node_id'
                    , 'contentclass-id' => 'contentclass_id'
                    , 'contentclass-identifier' => 'contentclass_identifier'
                    , 'is-modified' => 'is_modified'
                    , 'contentobject-remote-id' => 'contentobject_remote_id'
                    , 'extra_fields' => 'extra_fields'
            );
    }

    function deleteStoredObjectAttribute( $objectAttribute, $version = null )
    {
        //eZDebug::writeDebug('deleteStoredObjectAttribute');
        $content = $objectAttribute->content();
        if ( is_array( $content ) and
             is_array( $content['relation_list'] ) )
        {
            $db = eZDB::instance();
            $db->begin();
            foreach ( $content['relation_list'] as $deletionItem )
            {
                MugoObjectRelationListType::removeRelationObject( $objectAttribute, $deletionItem );
            }
            $db->commit();
        }
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        //eZDebug::writeDebug('customObjectAttributeHTTPAction');
        $contentobjectID = false;
        if ( eZDataType::fetchActionValue( $action, 'new_class', $classID ) or
             $action == 'new_class' )
        {
            if ( $action == 'new_class' )
            {
                $base = $parameters['base_name'];
                $classVariableName = $base . '_new_class';
                if ( $http->hasPostVariable( $classVariableName ) )
                {
                    $classVariable = $http->postVariable( $classVariableName );
                    $classID = $classVariable[$contentObjectAttribute->attribute( 'id' )];
                    $class = eZContentClass::fetch( $classID );
                }
                else
                    return false;
            }
            else
                $class = eZContentClass::fetch( $classID );
            if ( $class )
            {
                $classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
                $class_content = $classAttribute->content();
                $content = $contentObjectAttribute->content();
                $priority = 0;
                for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
                {
                    if ( $content['relation_list'][$i]['priority'] > $priority )
                        $priority = $content['relation_list'][$i]['priority'];
                }

                $base = $parameters['base_name'];
                $nodePlacement = false;
                $nodePlacementName = $base . '_object_initial_node_placement';
                if ( $http->hasPostVariable( $nodePlacementName ) )
                {
                    $nodePlacementMap = $http->postVariable( $nodePlacementName );
                    if ( isset( $nodePlacementMap[$contentObjectAttribute->attribute( 'id' )] ) )
                        $nodePlacement = $nodePlacementMap[$contentObjectAttribute->attribute( 'id' )];
                }
                $relationItem = MugoObjectRelationListType::createInstance( $class,
                                                                          $priority + 1,
                                                                          $contentObjectAttribute,
                                                                          $nodePlacement );
                if ( $class_content['default_placement'] )
                {
                    $relationItem['parent_node_id'] = $class_content['default_placement']['node_id'];
                }

                $content['relation_list'][] = $relationItem;

                $hasAttributeInput = false;
                $attributeInputVariable = $base . '_has_attribute_input';
                if ( $http->hasPostVariable( $attributeInputVariable ) )
                {
                    $attributeInputMap = $http->postVariable( $attributeInputVariable );
                    if ( isset( $attributeInputMap[$contentObjectAttribute->attribute( 'id' )] ) )
                        $hasAttributeInput = $attributeInputMap[$contentObjectAttribute->attribute( 'id' )];
                }

                if ( $hasAttributeInput )
                {
                    $object = $relationItem['object'];
                    $attributes = $object->contentObjectAttributes();
                    foreach ( $attributes as $attribute )
                    {
                        $attributeBase = $base . '_ezorl_init_class_' . $object->attribute( 'contentclass_id' ) . '_attr_' . $attribute->attribute( 'contentclassattribute_id' );
                        $oldAttributeID = $attribute->attribute( 'id' );
                        $attribute->setAttribute( 'id', false );
                        if ( $attribute->fetchInput( $http, $attributeBase ) )
                        {
                            $attribute->setAttribute( 'id', $oldAttributeID );
                            $attribute->store();
                        }
                    }
                }

                $contentObjectAttribute->setContent( $content );
                $contentObjectAttribute->store();
            }
            else

                eZDebug::writeError( "Unknown class ID $classID, cannot instantiate object",
                                     'MugoObjectRelationListType::customObjectAttributeHTTPAction' );
        }
        else if ( eZDataType::fetchActionValue( $action, 'edit_objects', $contentobjectID ) or
                  $action == 'edit_objects' or
                  $action == 'remove_objects' )
        {
            $base = $parameters['base_name'];
            $selectionBase = $base . '_selection';
            $selections = array();
            $http = eZHTTPTool::instance();
            if ( $http->hasPostVariable( $selectionBase ) )
            {
                $selectionMap = $http->postVariable( $selectionBase );
                $selections = $selectionMap[$contentObjectAttribute->attribute( 'id' )];
            }
            if ( $contentobjectID !== false )
                $selections[] = $contentobjectID;
            if ( $action == 'edit_objects' or
                 eZDataType::fetchActionValue( $action, 'edit_objects', $contentobjectID ) )
            {
                $content = $contentObjectAttribute->content();
                foreach ( $content['relation_list'] as $key => $relationItem )
                {
                    if ( !$relationItem['is_modified'] and
                         in_array( $relationItem['contentobject_id'], $selections ) )
                    {
                        $object = eZContentObject::fetch( $relationItem['contentobject_id'] );
                        if ( $object->attribute( 'can_edit' ) )
                        {
                            $content['relation_list'][$key]['is_modified'] = true;
                            $version = $object->createNewVersion();
                            $content['relation_list'][$key]['contentobject_version'] = $version->attribute( 'version' );
                        }
                    }
                }
                $contentObjectAttribute->setContent( $content );
                $contentObjectAttribute->store();
            }
            else if ( $action == 'remove_objects' )
            {
                $content = $contentObjectAttribute->content();
                $relationList = $content['relation_list'];
                $newRelationList = array();
                foreach( $relationList as $relationItem )
                {
                    if ( in_array( $relationItem['contentobject_id'], $selections ) )
                    {
                        MugoObjectRelationListType::removeRelationObject( $contentObjectAttribute, $relationItem );
                    }
                    else
                    {
                        $newRelationList[] = $relationItem;
                    }
                }
                $content['relation_list'] = $newRelationList;
                $contentObjectAttribute->setContent( $content );
                $contentObjectAttribute->store();
            }
        }
        else if ( $action == 'browse_objects' )
        {
            $module = $parameters['module'];
            $redirectionURI = $parameters['current-redirection-uri'];

            $ini = eZINI::instance( 'content.ini' );
            $browseType = 'AddRelatedObjectListToDataType';
            $browseTypeINIVariable = $ini->variable( 'ObjectRelationDataTypeSettings', 'ClassAttributeStartNode' );
            foreach ( $browseTypeINIVariable as $value )
            {
                list( $classAttributeID, $type ) = explode( ';',$value );
                if ( is_numeric( $classAttributeID ) and
                     $classAttributeID == $contentObjectAttribute->attribute( 'contentclassattribute_id' ) and
                     strlen( $type ) > 0 )
                {
                    $browseType = $type;
                    break;
                }
            }

            // Fetch the list of "allowed" classes .
            // A user can select objects of only those allowed classes when browsing.
            $classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
            $classContent   = $classAttribute->content();
            if ( isset( $classContent['class_constraint_list'] ) )
            {
                $classConstraintList = $classContent['class_constraint_list'];
            }
            else
            {
                $classConstraintList = array();
            }

            $browseParameters = array( 'action_name' => 'AddRelatedObject_' . $contentObjectAttribute->attribute( 'id' ),
                                       'type' =>  $browseType,
                                       'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $contentObjectAttribute->attribute( 'id' ) . '_set_object_relation_list]',
                                                                        'value' => $contentObjectAttribute->attribute( 'id' ) ),
                                       'persistent_data' => array( 'HasObjectInput' => 0 ),
                                       'from_page' => $redirectionURI );
            $base = $parameters['base_name'];
            $nodePlacementName = $base . '_browse_for_object_start_node';
            if ( $http->hasPostVariable( $nodePlacementName ) )
            {
                $nodePlacement = $http->postVariable( $nodePlacementName );
                if ( isset( $nodePlacement[$contentObjectAttribute->attribute( 'id' )] ) )
                    $browseParameters['start_node'] = eZContentBrowse::nodeAliasID( $nodePlacement[$contentObjectAttribute->attribute( 'id' )] );
            }
            if ( count($classConstraintList) > 0 )
                $browseParameters['class_array'] = $classConstraintList;

            eZContentBrowse::browse( $browseParameters,
                                     $module );
        }
        else if ( $action == 'set_object_relation_list' )
        {
            if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
            {
                $selectedObjectIDArray = $http->postVariable( "SelectedObjectIDArray" );
                $content = $contentObjectAttribute->content();
                $priority = 0;
                for ( $i = 0; $i < count( $content['relation_list'] ); ++$i )
                {
                    if ( $content['relation_list'][$i]['priority'] > $priority )
                        $priority = $content['relation_list'][$i]['priority'];
                }

                foreach ( $selectedObjectIDArray as $objectID )
                {
                    // Check if the given object ID has a numeric value, if not go to the next object.
                    if ( !is_numeric( $objectID ) )
                    {
                        eZDebug::writeError( "Related object ID (objectID): '$objectID', is not a numeric value.",
                                             "MugoObjectRelationListType::customObjectAttributeHTTPAction" );

                        continue;
                    }

                    /* Here we check if current object is already in the related objects list.
                     * If so, we don't add it again.
                     * FIXME: Stupid linear search. Maybe there's some better way?
                     */
                    $found = false;
                    foreach ( $content['relation_list'] as $i )
                    {
                        if ( $i['contentobject_id'] == $objectID )
                        {
                            $found = true;
                            break;
                        }
                    }
                    if ( $found )
                        continue;

                    ++$priority;
                    $content['relation_list'][] = $this->appendObject( $objectID, $priority, $contentObjectAttribute );
                    $contentObjectAttribute->setContent( $content );
                    $contentObjectAttribute->store();
                }
            }
        }
        else
        {
            eZDebug::writeError( "Unknown custom HTTP action: " . $action,
                                 'MugoObjectRelationListType' );
        }
    }

    function handleCustomObjectHTTPActions( $http, $attributeDataBaseName,
                                            $customActionAttributeArray, $customActionParameters )
    {
        //eZDebug::writeDebug('handleCustomObjectHTTPActions');
        $contentObjectAttribute = $customActionParameters['contentobject_attribute'];
        $content = $contentObjectAttribute->content();
        foreach( $content['relation_list'] as $relationItem )
        {
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $subObjectVersion = $relationItem['contentobject_version'];

                $attributeBase = $attributeDataBaseName . '_ezorl_edit_object_' . $subObjectID;
                if ( eZContentObject::recursionProtect( $subObjectID ) )
                {
                    if ( isset ( $content['temp'] ) )
                        $object = $content['temp'][$subObjectID]['object'];
                    else
                        $object = eZContentObject::fetch( $subObjectID );
                    if ( $object )
                        $object->handleAllCustomHTTPActions( $attributeBase,
                                                             $customActionAttributeArray,
                                                             $customActionParameters,
                                                             $subObjectVersion );
                }
            }
        }
    }

    /*!
     \static
     \return \c true if the relation item \a $relationItem exist in the content tree.
    */
    static function isItemPublished( $relationItem )
    {
        //eZDebug::writeDebug('isItemPublished');
        return is_numeric( $relationItem['node_id'] ) and $relationItem['node_id'] > 0;
    }

    /*!
     \private
     Removes the relation object \a $deletionItem if the item is owned solely by this
     version and is not published in the content tree.
    */
    function removeRelationObject( $contentObjectAttribute, $deletionItem )
    {
        //eZDebug::writeDebug('removeRelationObject');
        if ( self::isItemPublished( $deletionItem ) )
        {
            return;
        }

        $hostObject = $contentObjectAttribute->attribute( 'object' );
        $hostObjectVersions = $hostObject->versions();
        $isDeletionAllowed = true;

        // check if the relation item to be deleted is unique in the domain of all host-object versions
        foreach ( $hostObjectVersions as $version )
        {
            if ( $isDeletionAllowed and
                 $version->attribute( 'version' ) != $contentObjectAttribute->attribute( 'version' ) )
            {
                $relationAttribute = eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
                                                                           null,
                                                                           array( 'version' => $version->attribute( 'version' ),
                                                                                  'contentobject_id' => $hostObject->attribute( 'id' ),
                                                                                  'contentclassattribute_id' => $contentObjectAttribute->attribute( 'contentclassattribute_id' ) ) );

                if ( count( $relationAttribute ) > 0 )
                {
                    $relationContent = $relationAttribute[0]->content();
                    if ( is_array( $relationContent ) and
                         is_array( $relationContent['relation_list'] ) )
                    {
                        foreach( $relationContent['relation_list'] as $relationItem )
                        {
                            if ( $deletionItem['contentobject_id'] == $relationItem['contentobject_id'] &&
                                 $deletionItem['contentobject_version'] == $relationItem['contentobject_version'] )
                            {
                                $isDeletionAllowed = false;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        if ( $isDeletionAllowed )
        {
            $subObjectVersion = eZContentObjectVersion::fetchVersion( $deletionItem['contentobject_version'],
                                                                      $deletionItem['contentobject_id'] );
            if ( $subObjectVersion instanceof eZContentObjectVersion )
            {
                $subObjectVersion->removeThis();
            }
            else
            {
                eZDebug::writeError( 'Cleanup of subobject-version failed. Could not fetch object from relation list.\n' .
                                     'Requested subobject id: ' . $deletionItem['contentobject_id'] . '\n' .
                                     'Requested Subobject version: ' . $deletionItem['contentobject_version'],
                                     'MugoObjectRelationListType::removeRelationObject' );
            }
        }
    }

    function createInstance( $class, $priority, $contentObjectAttribute, $nodePlacement = false )
    {
        //eZDebug::writeDebug('createInstance');
        $currentObject = $contentObjectAttribute->attribute( 'object' );
        $sectionID = $currentObject->attribute( 'section_id' );
        $object = $class->instantiate( false, $sectionID );
        if ( !is_numeric( $nodePlacement ) or $nodePlacement <= 0 )
            $nodePlacement = false;
        $object->sync();
        $relationItem = array(
                  'identifier'              => false
                , 'priority'                => $priority
                , 'in_trash'                => false
                , 'contentobject_id'        => $object->attribute( 'id' )
                , 'contentobject_version'   => $object->attribute( 'current_version' )
                , 'contentobject_remote_id' => $object->attribute( 'remote_id' )
                , 'node_id'                 => false
                , 'parent_node_id'          => $nodePlacement
                , 'contentclass_id'         => $class->attribute( 'id' )
                , 'contentclass_identifier' => $class->attribute( 'identifier' )
                , 'is_modified'             => true
                , 'extra_fields'            => 'extra_fields'
            );
        $relationItem['object'] = $object;
        return $relationItem;
    }

    function appendObject( $objectID, $priority, $contentObjectAttribute, $extra_fields=false )
    {
        //eZDebug::writeDebug('appendObject START');
        $object = eZContentObject::fetch( $objectID );
        $class = $object->attribute( 'content_class' );
        $sectionID = $object->attribute( 'section_id' );
        $relationItem = array(
                  'identifier'              => false
                , "extra_fields"            => $extra_fields // this is used to init a new relation
                , 'priority'                => $priority
                , 'in_trash'                => false
                , 'contentobject_id'        => $object->attribute( 'id' )
                , 'contentobject_version'   => $object->attribute( 'current_version' )
                , 'contentobject_remote_id' => $object->attribute( 'remote_id' )
                , 'node_id'                 => $object->attribute( 'main_node_id' )
                , 'parent_node_id'          => $object->attribute( 'main_parent_node_id' )
                , 'contentclass_id'         =>  $class->attribute( 'id' )
                , 'contentclass_identifier' =>  $class->attribute( 'identifier' )
                , 'is_modified'             => false
            );
        $relationItem['object'] = $object;
        //eZDebug::writeDebug('appendObject END');
        return $relationItem;
    }


    function fixRelatedObjectItem ( $contentObjectAttribute, $objectID, $mode )
    {
        //eZDebug::writeDebug('fixRelatedObjectItem');
        switch ( $mode )
        {
            case 'move':
            {
                MugoObjectRelationListType::fixRelationsMove( $objectID, $contentObjectAttribute );
            } break;

            case 'trash':
            {
                MugoObjectRelationListType::fixRelationsTrash( $objectID, $contentObjectAttribute );
            } break;

            case 'restore':
            {
                MugoObjectRelationListType::fixRelationsRestore( $objectID, $contentObjectAttribute );
            } break;

            case 'remove':
            {
                MugoObjectRelationListType::fixRelationsRemove( $objectID, $contentObjectAttribute );
            } break;

            case 'swap':
            {
                MugoObjectRelationListType::fixRelationsSwap( $objectID, $contentObjectAttribute );
            } break;

            default:
            {
                eZDebug::writeWarning( $mode, 'Unknown mode MugoObjectRelationListType::fixRelatedObjectItem()' );
            } break;
        }
    }

    function fixRelationsMove ( $objectID, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixRelationsMove');
        $this->fixRelationsSwap( $objectID, $contentObjectAttribute );
    }

    function fixRelationsTrash ( $objectID, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixRelationsTrash');
        $content = $contentObjectAttribute->attribute( 'content' );
        foreach ( array_keys( $content['relation_list'] ) as $key )
        {
            if ( $content['relation_list'][$key]['contentobject_id'] == $objectID )
            {
                $content['relation_list'][$key]['in_trash'] = true;
                $content['relation_list'][$key]['node_id'] = null;
                $content['relation_list'][$key]['parent_node_id']= null;
            }
        }
        MugoObjectRelationListType::storeObjectAttributeContent( $contentObjectAttribute, $content );
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->storeData();
    }

    function fixRelationsRestore ( $objectID, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixRelationsRestore');
        $content = $contentObjectAttribute->content();

        foreach ( array_keys( $content['relation_list'] ) as $key )
        {
            if ( $content['relation_list'][$key]['contentobject_id'] == $objectID )
            {
                $priority = $content['relation_list'][$key]['priority'];
                $extraFields = isset( $content['relation_list'][$key]['extra_fields'] ) ? $content['relation_list'][$key]['extra_fields'] : array();
                $content['relation_list'][$key] = $this->appendObject( $objectID, $priority, $contentObjectAttribute, $extraFields );
            }
        }
        MugoObjectRelationListType::storeObjectAttributeContent( $contentObjectAttribute, $content );
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->storeData();
    }

    function fixRelationsRemove ( $objectID, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixRelationsRemove');
        $this->removeRelatedObjectItem( $contentObjectAttribute, $objectID );
        $contentObjectAttribute->storeData();
    }

    function fixRelationsSwap ( $objectID, $contentObjectAttribute )
    {
        //eZDebug::writeDebug('fixRelationsSwap');
        $content =& $contentObjectAttribute->content();

        foreach ( array_keys( $content['relation_list'] ) as $key )
        {
            $relatedObject =& $content['relation_list'][$key];
            if ( $relatedObject['contentobject_id'] == $objectID )
            {
                $priority = $content['relation_list'][$key]['priority'];
                $extraFields = isset( $content['relation_list'][$key]['extra_fields'] ) ? $content['relation_list'][$key]['extra_fields'] : array();
                $content['relation_list'][$key] = $this->appendObject( $objectID, $priority, $contentObjectAttribute, $extraFields );
            }
        }

        MugoObjectRelationListType::storeObjectAttributeContent( $contentObjectAttribute, $content );
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->storeData();
    }


    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        ////eZDebug::writeDebug('objectAttributeContent');
        $xmlText = $contentObjectAttribute->attribute( 'data_text' );
        if ( trim( $xmlText ) == '' )
        {
            $objectAttributeContent = self::defaultObjectAttributeContent();
            return $objectAttributeContent;
        }
        $doc = self::parseXML( $xmlText );
        $content = self::createObjectContentStructure( $doc );

        $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
        $classContent = $contentClassAttribute->content();

        // Get the value of the identifier
        foreach($content['relation_list'] as $relatedItemKey => $relatedItem)
        {
            if( $relatedItem && isset( $relatedItem['extra_fields'] ) && 0 < count( $relatedItem['extra_fields'] ) )
            {
                foreach( $relatedItem['extra_fields'] as $fieldKey => $field )
                {
                    // Only store the field if it's part of the class definition
                    if( isset( $classContent['extra_fields'][$fieldKey] ) )
                    {
                        $content['relation_list'][$relatedItemKey]['extra_fields'][$fieldKey] = self::getContentClassFieldOptionName( $contentObjectAttribute, $fieldKey, $field );
                    }
                    else
                    {
                        unset( $content['relation_list'][$relatedItemKey]['extra_fields'][$fieldKey] );
                    }
                }
            }
        }

        if( isset( $content['extra_fields_attribute_level'] ) &&  0 < count( $content['extra_fields_attribute_level'] ) )
        {
            foreach( $content['extra_fields_attribute_level'] as $fieldKey => $field )
            {
                // Only store the field if it's part of the class definition
                if( isset( $classContent['extra_fields_attribute_level'][$fieldKey] ) )
                {
                    $content['extra_fields_attribute_level'][$fieldKey] = self::getContentClassFieldOptionName( $contentObjectAttribute, $fieldKey, $field, true );
                }
                else
                {
                    unset( $content['extra_fields_attribute_level'][$fieldKey] );
                }
            }
        }

        return $content;
    }

    /**
     *
     * getContentClassFieldOptionName($context, 'size', 'nike')
     *
     * // returns Nike
     *
     * getContentClassFieldOptionName($context, 'colour', NULL)
     *
     * // returns false
     *
     * @param type $contentObjectAttribute object attribute
     * @param type $fieldIdentifier Field to lookup
     * @param type $fieldValue Identifier of the option to return the name (or text field value)
     * @param type $isAttributeLevel, defaults to false
     * @return If found return the name otherwise return false
     */
    function getContentClassFieldOptionName( $contentObjectAttribute, $fieldIdentifier, $fieldValue, $isAttributeLevel = false )
    {
        $classContent = $contentObjectAttribute->attribute( 'class_content' );

        if( $isAttributeLevel )
        {
            if( isset( $classContent['extra_fields_attribute_level'][$fieldIdentifier] ) )
            {
                if( 'selection' == $classContent['extra_fields_attribute_level'][$fieldIdentifier]['type'] )
                {
                    return array( 'value' => $classContent['extra_fields_attribute_level'][$fieldIdentifier]['options'][$fieldValue], 'identifier' => $fieldValue );
                }
                else
                {
                    return $fieldValue;
                }
            }
        }
        else
        {
            if( isset( $classContent['extra_fields'][$fieldIdentifier] ) )
            {
                if( 'selection' == $classContent['extra_fields'][$fieldIdentifier]['type'] )
                {
                    return array( 'value' => $classContent['extra_fields'][$fieldIdentifier]['options'][$fieldValue], 'identifier' => $fieldValue );
                }
                else
                {
                    return $fieldValue;
                }
            }
        }
    }

    function classAttributeContent( $classAttribute )
    {
        //eZDebug::writeDebug('classAttributeContent');
        $xmlText = $classAttribute->attribute( 'data_text5' );
        if ( trim( $xmlText ) == '' )
        {
            return MugoObjectRelationListType::defaultClassAttributeContent();
        }
        $doc = MugoObjectRelationListType::parseXML( $xmlText );
        return MugoObjectRelationListType::createClassContentStructure( $doc );
    }

    static function parseXML( $xmlText )
    {
        //eZDebug::writeDebug('parseXML');
        $dom = new DOMDocument( '1.0', 'utf-8' );
        $dom->loadXML( $xmlText );
        return $dom;
    }

    function defaultClassAttributeContent()
    {
        //eZDebug::writeDebug('defaultClassAttributeContent');
        return array(
                  'object_class'            => ''
                , 'type'                    => 0
                , 'extra_fields'            => array()
                , 'class_constraint_list'   => array()
                , 'default_placement'       => false
            );
    }

    function defaultObjectAttributeContent()
    {
        //eZDebug::writeDebug('defaultObjectAttributeContent START-END');
        return array( 'relation_list' => array() );
    }

    /**
     * takes a DOMDocument from a class definition and parses it into a content array
     */
    function createClassContentStructure( $doc )
    {
        //eZDebug::writeDebug("createClassContentStructure: Pass the variables from XML to the template");
        $content = MugoObjectRelationListType::defaultClassAttributeContent();
        $root = $doc->documentElement;
        $objectPlacement = $root->getElementsByTagName( 'contentobject-placement' )->item( 0 );

        if ( $objectPlacement and $objectPlacement->hasAttributes() )
        {
            $nodeID = $objectPlacement->getAttribute( 'node-id' );
            $content['default_placement'] = array( 'node_id' => $nodeID );
        }
        $constraints = $root->getElementsByTagName( 'constraints' )->item( 0 );
        if ( $constraints )
        {
            $allowedClassList = $constraints->getElementsByTagName( 'allowed-class' );
            foreach( $allowedClassList as $allowedClass )
            {
                $content['class_constraint_list'][] = $allowedClass->getAttribute( 'contentclass-identifier' );
            }
        }
        $type = $root->getElementsByTagName( 'type' )->item( 0 );
        if ( $type )
        {
            $content['type'] = $type->getAttribute( 'value' );
        }
        //Get all the field elements
        $xpath = new DOMXpath( $doc );
        $fields = $xpath->query('//related-objects/extra_fields/field');

        foreach( $fields as $field )
        {
            /*
                <field name="Role" identifier="role" type="selection">
                    <options><option name="Administrative" identifier="administrative"></option><option name="Artists" identifier="artists"></option></options>
                </field>
            */
            $fieldName = $field->getAttribute( 'name' );
            $fieldIdentifier = $field->getAttribute( 'identifier' );
            $fieldType = $field->getAttribute( 'type' );
            $fieldRequired = $field->getAttribute( 'required' );
            $content['extra_fields'][$fieldIdentifier] = array( 'name' => $fieldName, 'type' => $fieldType, 'required' => $fieldRequired );

            // get the children (only options for now)
            for( $fieldChildCount = 0; $fieldChildCount < $field->childNodes->length; $fieldChildCount++ )
            {
                $fieldChild = $field->childNodes->item( $fieldChildCount ); //this is an element
                $fieldChildName = $fieldChild->tagName;
                if( 'options' == $fieldChildName )
                {
                    if( $fieldChild->childNodes->length > 0 )
                    {
                        $options = $fieldChild->childNodes;
                        for($optionsCount = 0; $optionsCount < $options->length; $optionsCount++)
                        {
                            $option = $options->item($optionsCount);
                            $optionName = $option->getAttribute( 'name' );
                            $optionIdentifier = $option->getAttribute( 'identifier' );
                            $content['extra_fields'][$fieldIdentifier]['options'][$optionIdentifier] = $optionName;
                        }
                    }
                }
            }
        }
        
        $fields = $xpath->query('//related-objects/extra_fields_attribute_level/field');

        foreach( $fields as $field )
        {
            /*
                <field name="Role" identifier="role" type="selection">
                    <options><option name="Administrative" identifier="administrative"></option><option name="Artists" identifier="artists"></option></options>
                </field>
            */
            $fieldName = $field->getAttribute( 'name' );
            $fieldIdentifier = $field->getAttribute( 'identifier' );
            $fieldType = $field->getAttribute( 'type' );
            $fieldRequired = $field->getAttribute( 'required' );
            $content['extra_fields_attribute_level'][$fieldIdentifier] = array( 'name' => $fieldName, 'type' => $fieldType, 'required' => $fieldRequired );

            // get the children (only options for now)
            for( $fieldChildCount = 0; $fieldChildCount < $field->childNodes->length; $fieldChildCount++ )
            {
                $fieldChild = $field->childNodes->item( $fieldChildCount ); //this is an element
                $fieldChildName = $fieldChild->tagName;
                if( 'options' == $fieldChildName )
                {
                    if( $fieldChild->childNodes->length > 0 )
                    {
                        $options = $fieldChild->childNodes;
                        for($optionsCount = 0; $optionsCount < $options->length; $optionsCount++)
                        {
                            $option = $options->item($optionsCount);
                            $optionName = $option->getAttribute( 'name' );
                            $optionIdentifier = $option->getAttribute( 'identifier' );
                            $content['extra_fields_attribute_level'][$fieldIdentifier]['options'][$optionIdentifier] = $optionName;
                        }
                    }
                }
            }
        }

        $objectClass = $root->getElementsByTagName( 'object_class' )->item( 0 );
        if ( $objectClass )
        {
            $content['object_class'] = $objectClass->getAttribute( 'value' );
        }

        return $content;
    }

    /**
     * Transform XMLs to PHP array
     */
    function createObjectContentStructure( $doc )
    {
        //eZDebug::writeDebug('createObjectContentStructure');

        $content = self::defaultObjectAttributeContent();
        $root = $doc->documentElement;
        $relationList = $root->getElementsByTagName( 'relation-list' )->item( 0 );
        if ( $relationList )
        {
            $contentObjectArrayXMLMap = self::contentObjectArrayXMLMap();
            $relationItems = $relationList->getElementsByTagName( 'relation-item' );
            foreach ( $relationItems as $relationItem )
            {
                $hash = array();
                foreach( $contentObjectArrayXMLMap as $attributeXMLName => $attributeKey )
                {
                    // Recover the extrafields from the XML
                    if( $attributeXMLName == 'extra_fields' )
                    {
                        $fields = ( $relationItem->childNodes->length > 0 ) ? $relationItem->childNodes->item( 0 ) : false;
                        if( $fields )
                        {
                            $extraFields = array();
                            for( $counter = 0; $counter < $fields->childNodes->length; $counter++ )
                            {
                                $node = $fields->childNodes->item( $counter );
                                $extraFields[$node->tagName] = $node->getAttribute( 'value' );
                            }
                            $hash[$attributeKey] = $extraFields;
                        }
                    }
                    else
                    {
                        $attributeValue = $relationItem->hasAttribute( $attributeXMLName ) ? $relationItem->getAttribute( $attributeXMLName ) : false;
                        $hash[$attributeKey] = $attributeValue;
                    }
                }
                $content['relation_list'][] = $hash;
            }
        }

        $extraFieldsAttributeLevel = $root->getElementsByTagName( 'extra_fields_attribute_level' )->item( 0 );

        if( $extraFieldsAttributeLevel->childNodes->length > 0 )
        {
            $extraFields = array();
            for( $counter = 0; $counter < $extraFieldsAttributeLevel->childNodes->length; $counter++ )
            {
                $node = $extraFieldsAttributeLevel->childNodes->item( $counter );
                $extraFields[$node->tagName] = $node->getAttribute( 'value' );
            }
            $content['extra_fields_attribute_level'] = $extraFields;
        }

        return $content;
    }

    function customClassAttributeHTTPAction( $http, $action, $classAttribute )
    {
        //eZDebug::writeDebug('customClassAttributeHTTPAction');
        switch ( $action )
        {
            case 'browse_for_placement':
            {
                $module = $classAttribute->currentModule();
                $customActionName = 'CustomActionButton[' . $classAttribute->attribute( 'id' ) . '_browsed_for_placement]';
                eZContentBrowse::browse( array( 'action_name' => 'SelectObjectRelationListNode',
                                                'content' => array( 'contentclass_id' => $classAttribute->attribute( 'contentclass_id' ),
                                                                    'contentclass_attribute_id' => $classAttribute->attribute( 'id' ),
                                                                    'contentclass_version' => $classAttribute->attribute( 'version' ),
                                                                    'contentclass_attribute_identifier' => $classAttribute->attribute( 'identifier' ) ),
                                                'persistent_data' => array( $customActionName => '',
                                                                            'ContentClassHasInput' => false ),
                                                'description_template' => 'design:class/datatype/browse_objectrelationlist_placement.tpl',
                                                'from_page' => $module->currentRedirectionURI() ),
                                         $module );
            } break;
            case 'browsed_for_placement':
            {
                $nodeSelection = eZContentBrowse::result( 'SelectObjectRelationListNode' );
                if ( $nodeSelection and count( $nodeSelection ) > 0 )
                {
                    $nodeID = $nodeSelection[0];
                    $content = $classAttribute->content();
                    $content['default_placement'] = array( 'node_id' => $nodeID );
                    $classAttribute->setContent( $content );
                }
            } break;
            case 'disable_placement':
            {
                $content = $classAttribute->content();
                $content['default_placement'] = false;
                $classAttribute->setContent( $content );
            } break;
            default:
            {
                eZDebug::writeError( "Unknown objectrelationlist action '$action'", 'eZContentObjectRelationListType::customClassAttributeHTTPAction' );
            } break;
        }
    }

    /*!
     \return string representation of a content object attribute data for simplified export

    */
    function toString( $contentObjectAttribute )
    {
        //eZDebug::writeDebug('toString');
        $toString = '';
        $objectAttributeContent = $contentObjectAttribute->attribute( 'content' );
        $objectIDList = array();
        foreach( $objectAttributeContent['relation_list'] as $i => $objectInfo )
        {
            $relationData = array();
            $relationData[0] = $objectInfo['contentobject_id'];
            $extraFieldsData = array();
            if( isset( $objectInfo['extra_fields'] ) )
            {
                foreach( $objectInfo['extra_fields'] as $extraFieldIdentifier => $extraField )
                {
                    // Output a selection
                    if( is_array( $extraField ) && isset( $extraField['identifier'] ) )
                    {
                        $extraField = $extraField['identifier'];
                    }
                    $extraFieldsData[] = eZStringUtils::implodeStr( array( $extraFieldIdentifier, $extraField ), self::OPTIONSEPARATOR );
                }
            }
            $relationData[1] = eZStringUtils::implodeStr( $extraFieldsData, self::FIELDSEPARATOR );
            $objectIDList[] = implode( $relationData, self::FIELDSEPARATOR );
        }

        $toString = eZStringUtils::implodeStr( $objectIDList, self::RELATIONSEPARATOR );
        
        if( isset( $objectAttributeContent['extra_fields_attribute_level'] ) )
        {
            if( $objectAttributeContent['extra_fields_attribute_level'] )
            {
                $toString .= self::EXTRAFIELDSSEPARATOR;
                $extraFieldsAttributeLevelData = array();
                foreach( $objectAttributeContent['extra_fields_attribute_level'] as $extraFieldIdentifier => $extraField )
                {
                    $fieldSeparator = '';
                    $extraFieldsData = array();
                    // Output a selection
                    if( is_array( $extraField ) && isset( $extraField['identifier'] ) )
                    {
                        $extraField = $extraField['identifier'];
                    }
                    $extraFieldsAttributeLevelData[] = eZStringUtils::implodeStr( array( $extraFieldIdentifier, $extraField ), self::OPTIONSEPARATOR );
                }
                $toString .= eZStringUtils::implodeStr( $extraFieldsAttributeLevelData, self::FIELDSEPARATOR );
            }
        }
        
        return $toString;
    }

    /**
     * accept a specially built string of data and initialize the attribute
     *
     *
     * <object_id>|Black|nike
     *  Run a test import
     *  $attributes = array();
     *  $attributes['name'] = 'Name of object';
     *  $attributes['mugo_object_relations'] = '7878|colour:Black|size:nike|brand:nike&7863|colour:Blue|size:reebok|brand:reebok-max_purchases:3|include_brochure:1';
     *  $attributes['mugo_object_relations'] = '7878|colour:Black|size:nike|brand:nike&7863|colour:Blue|size:reebok|brand:reebok-max_purchases:3|include_brochure:1';
     *  $newNodeID = ContentClass_Handler::create( $attributes, $parentNodeID, $contentClassIdentifier );
     *
     * "&" Relation separator
     * "|" Field separator
     * ":" Option separator
     * "-" Extra fields (attribute-level) separator
     *
     */
    function fromString( $contentObjectAttribute, $string )
    {
        //eZDebug::writeDebug('fromString START');
        if( $string == '' )
        {
            //eZDebug::writeDebug('fromString empty string END');
            return true;
        }

        $classContent = $contentObjectAttribute->attribute( 'class_content' );
        
        // Explode out extra fields (attribute-level)
        $splitExtraFields = eZStringUtils::explodeStr( $string, self::EXTRAFIELDSSEPARATOR );

        // Explode by relations
        $relationList = eZStringUtils::explodeStr( $splitExtraFields[0], self::RELATIONSEPARATOR );

        //Explode by fields
        foreach($relationList as $relationKey => $relation)
        {
            $extraFields = eZStringUtils::explodeStr( $relation, self::FIELDSEPARATOR );
            $objectID[] = array_shift( $extraFields );

            $extraFieldArray = array();
            foreach( $extraFields as $extraField )
            {
                // Note that it does not enforce whether a field is required
                $extraFieldElements = eZStringUtils::explodeStr( $extraField, self::OPTIONSEPARATOR );
                // Make sure we have a selection item defined
                if(    isset( $classContent['extra_fields'][$extraFieldElements[0]] )
                    && 'selection' == $classContent['extra_fields'][$extraFieldElements[0]]['type']
                  )
                {
                    if( isset( $classContent['extra_fields'][$extraFieldElements[0]]['options'][$extraFieldElements[1]] ) )
                    {
                        $extraFieldArray[$extraFieldElements[0]] = $extraFieldElements[1];
                    }
                    else
                    {
                        eZDebug::writeWarning( $extraFieldElements[1], 'No such selection option defined' );
                    }
                }
                else
                {
                    $extraFieldArray[$extraFieldElements[0]] = $extraFieldElements[1];
                }
            }
            $relationList[$relationKey] = $extraFieldArray;
        }

        $content = self::defaultObjectAttributeContent();

        $priority = 0;

        foreach( $relationList as $relationKey => $extraFields )
        {
            $object = eZContentObject::fetch( $objectID[$relationKey] );
            if ( $object )
            {
                ++$priority;
                $content['relation_list'][] = $this->appendObject( $objectID[$relationKey], $priority, $contentObjectAttribute, $extraFields );
            }
            else
            {
                eZDebug::writeWarning( $objectID, "Cannot create relation because object is missing" );
            }
        }

        // Import extra fields (attribute-level)
        if( isset( $splitExtraFields[1] ) )
        {
            $extraFields = eZStringUtils::explodeStr( $splitExtraFields[1], self::FIELDSEPARATOR );
            $extraFieldArray = array();
            foreach( $extraFields as $extraField )
            {
                // Note that it does not enforce whether a field is required
                $extraFieldElements = eZStringUtils::explodeStr( $extraField, self::OPTIONSEPARATOR );
                // Make sure we have a selection item defined
                if(    isset( $classContent['extra_fields_attribute_level'][$extraFieldElements[0]] )
                    && 'selection' == $classContent['extra_fields_attribute_level'][$extraFieldElements[0]]['type']
                  )
                {
                    if( isset( $classContent['extra_fields_attribute_level'][$extraFieldElements[0]]['options'][$extraFieldElements[1]] ) )
                    {
                        $extraFieldArray[$extraFieldElements[0]] = $extraFieldElements[1];
                    }
                    else
                    {
                        eZDebug::writeWarning( $extraFieldElements[1], 'No such selection option defined' );
                    }
                }
                else
                {
                    $extraFieldArray[$extraFieldElements[0]] = $extraFieldElements[1];
                }
            }
            
            $content['extra_fields_attribute_level'] = $extraFieldArray;
        }

        $contentObjectAttribute->setContent( $content );
        //eZDebug::writeDebug('fromString END');
        return true;
    }




    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        //eZDebug::writeDebug('hasObjectAttributeContent');
        $content = $contentObjectAttribute->content();
        return count( $content['relation_list'] ) > 0;
    }

    function isIndexable()
    {
        //eZDebug::writeDebug('isIndexable');
        return true;
    }

    /*!
     Returns the content of the string for use as a title,
     for simplicity this is the name of the first object referenced or false.
    */
    function title( $contentObjectAttribute, $name = null )
    {
        //eZDebug::writeDebug('title');
        $objectAttributeContent = $this->objectAttributeContent( $contentObjectAttribute );

        if ( count( $objectAttributeContent['relation_list'] ) > 0 )
        {
            $target = $objectAttributeContent['relation_list'][0];
            $targetObject = eZContentObject::fetch( $target['contentobject_id'], false );
            return $targetObject['name'];
        }
        else
        {
            return false;
        }
    }

    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        //eZDebug::writeDebug("serializeContentClassAttribute");
        $dom = $attributeParametersNode->ownerDocument;
        $content = $classAttribute->content();
        if ( $content['default_placement'] )
        {
            $defaultPlacementNode = $dom->createElement( 'default-placement' );
            $defaultPlacementNode->setAttribute( 'node-id', $content['default_placement']['node_id'] );
            $attributeParametersNode->appendChild( $defaultPlacementNode );
        }

        $type = is_numeric( $content['type'] ) ? $content['type'] : '0';
        $typeNode = $dom->createElement( 'type' );
        $typeNode->appendChild( $dom->createTextNode( $type ) );
        $attributeParametersNode->appendChild( $typeNode );

        $classConstraintsNode = $dom->createElement( 'class-constraints' );
        $attributeParametersNode->appendChild( $classConstraintsNode );
        foreach ( $content['class_constraint_list'] as $classConstraint )
        {
            $classConstraintIdentifier = $classConstraint;
            $classConstraintNode = $dom->createElement( 'class-constraint' );
            $classConstraintNode->setAttribute( 'class-identifier', $classConstraintIdentifier );
            $classConstraintsNode->appendChild( $classConstraintNode );
        }

        //Serialize the extrafields
        if ( isset( $content['extra_fields'] ) && is_array( $content['extra_fields'] ) )
        {
            $extra_fields = $dom->createElement( 'extra_fields' );
            foreach( $content['extra_fields'] as $extraFieldIdentifier => $extraField )
            {
                $field = $dom->createElement( 'field' );
                $field->setAttribute( 'name', $extraField['name'] );
                $field->setAttribute( 'identifier', $extraFieldIdentifier );
                $field->setAttribute( 'type', $extraField['type'] );
                $field->setAttribute( 'required', $extraField['required'] );

                if( 'selection' == $extraField['type'] )
                {
                    // Create the options
                    if( !$extraField['options'] )
                    {
                        $extraField['options'] = array();
                    }
                    $fieldOptions = $dom->createElement( 'options' );
                    if( $extraField['options'] )
                    {
                        foreach( $extraField['options'] as $optionIdentifier => $optionName )
                        {
                            $fieldOption = $dom->createElement( 'option' );
                            $fieldOption->setAttribute( 'name', $optionName );
                            $fieldOption->setAttribute( 'identifier', $optionIdentifier );

                            $fieldOptions->appendChild( $fieldOption );
                        }
                    }
                    $field->appendChild( $fieldOptions );
                }
                //Put the elements in the tree
                $extra_fields->appendChild( $field );
            }
            $attributeParametersNode->appendChild( $extra_fields );
        }

        if ( isset( $content['object_class'] ) && is_numeric( $content['object_class'] ) )
        {
            $objectClassNode = $dom->createElement( 'object-class' );
            $objectClassNode->appendChild( $dom->createTextNode( $content['object_class'] ) );
            $attributeParametersNode->appendChild( $objectClassNode );
        }
    }

    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        //eZDebug::writeDebug("unserializeContentClassAttribute");
        $content = $classAttribute->content();
        $defaultPlacementNode = $attributeParametersNode->getElementsByTagName( 'default-placement' )->item( 0 );
        $content['default_placement'] = false;

        if ( $defaultPlacementNode )
        {
            $content['default_placement'] = array( 'node_id' => $defaultPlacementNode->getAttribute( 'node-id' ) );
        }
        $content['type'] = $attributeParametersNode->getElementsByTagName( 'type' )->item( 0 )->textContent;
        $classConstraintsNode = $attributeParametersNode->getElementsByTagName( 'class-constraints' )->item( 0 );
        $classConstraintList = $classConstraintsNode->getElementsByTagName( 'class-constraint' );
        $content['class_constraint_list'] = array();
        foreach ( $classConstraintList as $classConstraintNode )
        {
            $classIdentifier = $classConstraintNode->getAttribute( 'class-identifier' );
            $content['class_constraint_list'][] = $classIdentifier;
        }

        $objectClassNode = $attributeParametersNode->getElementsByTagName( 'object-class' )->item( 0 );
        if ( $objectClassNode )
        {
            $content['object_class'] = $objectClassNode->textContent;
        }

        // Get all the field elements <extra_fields><field></field><field></field></extra_fields>
        $fields = $attributeParametersNode->getElementsByTagName( 'field' );
        foreach( $fields as $field )
        {
            $fieldName = $field->getAttribute( 'name' );
            $fieldIdentifier = $field->getAttribute( 'identifier' );
            $fieldType = $field->getAttribute( 'type' );
            $fieldRequired = $field->getAttribute( 'required' );

            $content['extra_fields'][$fieldIdentifier] = array( 
                'name' => $fieldName, 
                'type' => $fieldType, 
                'required' => $fieldRequired 
            );
            // get the children (only options for now)
            for( $fieldChildCount = 0; $fieldChildCount < $field->childNodes->length; $fieldChildCount++ )
            {
                $fieldChild = $field->childNodes->item( $fieldChildCount ); //this is an element
                $fieldChildName = $fieldChild->tagName;
                if( 'options' == $fieldChildName )
                {
                    if( $fieldChild->childNodes->length > 0 )
                    {
                        $options = $fieldChild->childNodes;
                        for($optionsCount = 0; $optionsCount < $options->length; $optionsCount++)
                        {
                            $option = $options->item($optionsCount);
                            $optionName = $option->getAttribute( 'name' );
                            $optionIdentifier = $option->getAttribute( 'identifier' );
                            $content['extra_fields'][$fieldIdentifier]['options'][$optionIdentifier] = $optionName;
                        }
                    }
                }
            }
        }

        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );
    }

    /*!
     For each relation export its priority and content object remote_id, like this:
      <related-objects>
        <relation-list>
          <relation-item priority="1"
                         contentobject-remote-id="faaeb9be3bd98ed09f606fc16d144eca" />
          <relation-item priority="2"
                         contentobject-remote-id="1bb4fe25487f05527efa8bfd394cecc7" />
        </relation-list>
     To do this we fetch content XML and strip all the relation attributes except of "priority" from there,
     and add "contentobject-remote-id" attribute.
    */
    function serializeContentObjectAttribute( $package, $objectAttribute )
    {
        //eZDebug::writeDebug('serializeContentObjectAttribute');

        $node = $this->createContentObjectAttributeDOMNode( $objectAttribute );

        //eZDebug::writeDebug( $objectAttribute->attribute( 'data_text' ), 'xml string from data_text field' );
        if ( $objectAttribute->attribute( 'data_text' ) === null )
        {
            $content = array( 'relation_list' => array() );
            $dom = MugoObjectRelationListType::createObjectDOMDocument( $content );
        }
        else
        {
            $dom = new DOMDocument( '1.0', 'utf-8' );
            $success = $dom->loadXML( $objectAttribute->attribute( 'data_text' ) );
        }
        $rootNode = $dom->documentElement;
        $relationList = $rootNode->getElementsByTagName( 'relation-list' )->item( 0 );
        if ( $relationList )
        {
            $relationItems = $relationList->getElementsByTagName( 'relation-item' );
            for ( $i = 0; $i < $relationItems->length; $i++ )
            {
                $relationItem = $relationItems->item( $i );
                // Add related object remote id as attribute to the relation item.
                $relatedObjectID = $relationItem->getAttribute( 'contentobject-id' );
                $relatedObject = eZContentObject::fetch( $relatedObjectID );
                $relatedObjectRemoteID = $relatedObject->attribute( 'remote_id' );
                $relationItem->setAttribute( 'contentobject-remote-id', $relatedObjectRemoteID );

                $attributes = $relationItem->attributes;
                // Remove all other relation item attributes except of "priority".
                // This loop intentionally starts with the last attribute, otherwise you will get unexpected results
                for ( $j = $attributes->length - 1; $j >= 0; $j-- )
                {
                    $attribute = $attributes->item( $j );
                    $attrName = $attribute->localName;

                    //eZDebug::writeDebug( $attrName );
                    if ( $attrName != 'priority' && $attrName != 'contentobject-remote-id' )
                    {
                        $success = $relationItem->removeAttribute( $attribute->localName );
                        if ( !$success )
                        {
                            eZDebug::writeError( 'failed removing attribute ' . $attrName . ' from relation-item element' );
                        }
                    }
                }
            }
        }

        //eZDebug::writeDebug( $dom->saveXML(), 'old xml doc' );

        $importedRootNode = $node->ownerDocument->importNode( $rootNode, true );
        $node->appendChild( $importedRootNode );

        return $node;
    }

    function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
    {
        //eZDebug::writeDebug('unserializeContentObjectAttribute');
        $rootNode = $attributeNode->getElementsByTagName( 'related-objects' )->item( 0 );
        $xmlString = $rootNode ? $rootNode->ownerDocument->saveXML( $rootNode ) : '';
        $objectAttribute->setAttribute( 'data_text', $xmlString );
    }

    function postUnserializeContentObjectAttribute( $package, $objectAttribute )
    {
        //eZDebug::writeDebug('postUnserializeContentObjectAttribute');

        $xmlString = $objectAttribute->attribute( 'data_text' );
        $doc = $this->parseXML( $xmlString );
        $rootNode = $doc->documentElement;

        $relationList = $rootNode->getElementsByTagName( 'relation-list' )->item( 0 );
        if ( !$relationList )
            return false;

        $relationItems = $relationList->getElementsByTagName( 'relation-item' );
        for ( $i = $relationItems->length - 1; $i >= 0; $i-- )
        {
            $relationItem = $relationItems->item( $i );
            $relatedObjectRemoteID = $relationItem->getAttribute( 'contentobject-remote-id' );
            $object = eZContentObject::fetchByRemoteID( $relatedObjectRemoteID );

            if ( $object === null )
            {
                eZDebug::writeWarning( "Object with remote id '$relatedObjectRemoteID' not found: removing the link.",
                                       'MugoObjectRelationListType::unserializeContentObjectAttribute()' );
                $relationItem->parentNode->removeChild( $relationItem );
                continue;
            }

            $relationItem->setAttribute( 'contentobject-id',        $object->attribute( 'id' ) );
            $relationItem->setAttribute( 'contentobject-version',   $object->attribute( 'current_version' ) );
            $relationItem->setAttribute( 'node-id',                 $object->attribute( 'main_node_id' ) );
            $relationItem->setAttribute( 'parent-node-id',          $object->attribute( 'main_parent_node_id' ) );
            $relationItem->setAttribute( 'contentclass-id',         $object->attribute( 'contentclass_id' ) );
            $relationItem->setAttribute( 'contentclass-identifier', $object->attribute( 'class_identifier' ) );
        }

        $newXmlString = $doc->saveXML( $rootNode );

        $objectAttribute->setAttribute( 'data_text', $newXmlString );
        return true;
    }

    /*!
     Removes objects with given ID from the relations list
    */
    function removeRelatedObjectItem( $contentObjectAttribute, $objectID )
    {
        //eZDebug::writeDebug('removeRelatedObjectItem');

        $xmlText = $contentObjectAttribute->attribute( 'data_text' );
        if ( trim( $xmlText ) == '' ) return;

        $doc = MugoObjectRelationListType::parseXML( $xmlText );

        $return = false;
        $root = $doc->documentElement;
        $relationList = $root->getElementsByTagName( 'relation-list' )->item( 0 );
        if ( $relationList )
        {
            $relationItems = $relationList->getElementsByTagName( 'relation-item' );
            if ( !empty( $relationItems ) )
            {
                foreach( $relationItems as $relationItem )
                {
                    if ( $relationItem->getAttribute( 'contentobject-id' ) == $objectID )
                    {
                        $relationList->removeChild( $relationItem );
                        $return = true;
                    }
                }
            }
        }
        MugoObjectRelationListType::storeObjectDOMDocument( $doc, $contentObjectAttribute );
        return $return;
    }

    function supportsBatchInitializeObjectAttribute()
    {
        //eZDebug::writeDebug('supportsBatchInitializeObjectAttribute');
        return true;
    }

    /// \privatesection
}

eZDataType::register( MugoObjectRelationListType::DATA_TYPE_STRING, "MugoObjectRelationListType" );

?>
