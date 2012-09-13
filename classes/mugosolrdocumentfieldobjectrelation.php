<?php

class mugoSolrDocumentFieldObjectRelation extends ezfSolrDocumentFieldBase
{
    public function isCollection()
    {
        return true;
    }

    /**
     * Get collection data. Returns list of ezfSolrDocumentFieldBase documents.
     *
     * @return array List of ezfSolrDocumentFieldBase objects.
     */
    public function getCollectionData()
    {
        $returnList = array();

        $content = $this->ContentObjectAttribute->content();
        foreach( $content['relation_list'] as $relationItem )
        {
            $subObjectID = $relationItem['contentobject_id'];
            if ( !$subObjectID )
                continue;
            $subObject = eZContentObjectVersion::fetchVersion( $relationItem['contentobject_version'], $subObjectID );
            if ( !$subObject )
                continue;

            $returnList = array_merge( $this->getBaseList( $subObject ),
                                       $returnList );
        }

        return $returnList;
    }

    /**
     * Get data to index, and field name to use. Returns an associative array
     * with field name and field value.
     * Example:
     * <code>
     * array( 'field_name_i' => 123 );
     * </code>
     *
     * @return array Associative array with fieldname and value.
     */
    public function getData()
    {
        $ccAttribute    = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldName      = self::getFieldName( $ccAttribute );
        $content        = $this->ContentObjectAttribute->attribute( 'content' );
        foreach( $content[ 'relation_list' ] as $relationItem )
        {
            $subObjectID = $relationItem['contentobject_id'];
            if ( !$subObjectID )
                continue;

            $subObjectVersion = $relationItem['contentobject_version'];
            $object = eZContentObject::fetch( $subObjectID );
            if( eZContentObject::recursionProtect( $subObjectID ) )
            {
                if ( !$object )
                {
                    continue;
                }
                $metaData[] = trim( $object->attribute( 'name' ) ) . " " . trim( $relationItem["xrefoptionaldata"] );
            }
        }

        return array( $fieldName => $metaData );
    }

    /**
     * Get ezfSolrDocumentFieldBase instances for all attributes of specified eZContentObjectVersion
     *
     * @param eZContentObjectVersion Instance of eZContentObjectVersion to fetch attributes from.
     *
     * @return array List of ezfSolrDocumentFieldBase instances.
     */
    function getBaseList( eZContentObjectVersion $objectVersion )
    {
        $returnList = array();
        // Get ezfSolrDocumentFieldBase instance for all attributes in related object
        if ( eZContentObject::recursionProtect( $this->ContentObjectAttribute->attribute( 'contentobject_id' ) ) )
        {
            foreach( $objectVersion->contentObjectAttributes( $this->ContentObjectAttribute->attribute( 'language_code' ) ) as $attribute )
            {
                if ( $attribute->attribute( 'contentclass_attribute' )->attribute( 'is_searchable' ) )
                {
                    $returnList[] = ezfSolrDocumentFieldBase::getInstance( $attribute );
                }
            }
        }
        return $returnList;
    }
}

?>
