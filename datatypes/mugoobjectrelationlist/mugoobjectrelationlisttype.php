<?php
//
// Definition of MugoObjectRelationListType class
// derived from eZObjectRelationListType class
/*!
  \class MugoObjectRelationListType mugoobjectrelationlisttype.php
  \ingroup eZDatatype
  \brief A content datatype which handles object relations with the addition of cross-reference data on the object relation itself
*/

class MugoObjectRelationListType extends eZObjectRelationListType
{
    const DATA_TYPE_STRING = "mugoobjectrelationlist";
    const FIELDSEPARATOR = "|";
    const OPTIONSEPARATOR = ":";
    const RELATIONSEPARATOR = "&";
    const EXTRAFIELDSSEPARATOR = "-";

    /**
     * MugoObjectRelationListType constructor.
     * Initializes with a string id and a description.
     */
    function __construct()
    {
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
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
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
            $priorities[ $contentObjectAttributeID ][$i] = (int )$priorities[ $contentObjectAttributeID ][$i];
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
                $relationItem['priority'] = $priorities[$contentObjectAttributeID][$i];
            }

            // Storing Extra fields
            foreach( $extraFieldList[$i] as $extraFieldIdentifier => $extraFieldValue )
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
        while ( list( $key, $relationItem ) = each( $reorderedRelationList ) )
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
            if( isset( $relatedItem['extra_fields'] ) && 0 < count( $relatedItem['extra_fields'] ) )
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
                        $fields = $relationItem->childNodes->item( 0 );
                        if( is_object( $fields ) && $fields->childNodes->length > 0 )
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

        //Serialize the extra fields

        if ( isset( $content['extra_fields'] ) )
        {
            $extra_fields = $dom->createElement( 'extra_fields' );
            $extra_fields->appendChild( $dom->createTextNode( $content['extra_fields'] ) );
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

        $extra_fields = $attributeParametersNode->getElementsByTagName( 'extra_fields' );
        foreach( $extra_fields as $option )
        {
            $content['extra_fields']['name'] = $option->getAttribute( 'value' );
        }

        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );
    }

    /// \privatesection
}

eZDataType::register( MugoObjectRelationListType::DATA_TYPE_STRING, "MugoObjectRelationListType" );

