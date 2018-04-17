<?php

class mugoSolrDocumentFieldObjectRelation extends ezfSolrDocumentFieldBase
{
    public function isCollection()
    {
        return true;
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
        $fieldArray     = array( $fieldName => '' );

        foreach( $content[ 'relation_list' ] as $relationItem )
        {
            $objectID = $relationItem['contentobject_id'];
            if ( !$objectID )
            {
                continue;
            }

            $object = eZContentObject::fetch( $objectID );

            if ( !$object )
            {
                continue;
            }
            $fieldArray[$fieldName] .= trim( $object->attribute( 'name' ) ) . ' ';

            if( $relationItem['extra_fields'] )
            {
                foreach( $relationItem['extra_fields'] as $extraFieldKey => $extraFieldValue )
                {
                    // Storing a selection
                    if( is_array( $extraFieldValue ) && isset( $extraFieldValue['identifier'] ) )
                    {
                        $extraFieldValue = $extraFieldValue['identifier'];
                    }
                    // As a multi-string it won't end up in ez_df_text
                    $subAttributeFieldName = self::generateSubattributeFieldName( $ccAttribute, $extraFieldKey, 'string' );
                    $fieldArray[$subAttributeFieldName][] = $extraFieldValue;
                }
            }

            // Add meta fields of the related object
            $metaAttributeValues = eZSolr::getMetaAttributesForObject( $object );
            foreach( $metaAttributeValues as $metaInfo )
            {
                $fieldArray[ezfSolrDocumentFieldBase::generateSubmetaFieldName( $metaInfo['name'], $ccAttribute )][] = ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] );
            }
        }
        $fieldArray[$fieldName] = trim( $fieldArray[$fieldName] );
        
        // Add extra fields (attribute-level)
        if( $content['extra_fields_attribute_level'] )
        {
            foreach( $content['extra_fields_attribute_level'] as $extraFieldKey => $extraFieldValue )
            {
                // Storing a selection
                if( is_array( $extraFieldValue ) && isset( $extraFieldValue['identifier'] ) )
                {
                    $extraFieldValue = $extraFieldValue['identifier'];
                }
                // As a multi-string it won't end up in ez_df_text
                // Limitation: attribute identifiers for extra fields (relation-level) should be unique from identifiers for extra fields (attribute-level)
                $subAttributeFieldName = self::generateSubattributeFieldName( $ccAttribute, $extraFieldKey, 'string' );
                $fieldArray[$subAttributeFieldName] = $extraFieldValue;
            }
        }

        return $fieldArray;
    }
}

