<?php

/**
 * 
 */
class MugoObjectRelationsFetchFunctions
{

    function __construct()
    {
        ;
    }

    /**
     * Fetches the filtered related objects using the extra data
     * 
     * TPL usage
     * <pre><code>
     * {def $illustrations = fetch( 'mugoobjectrelations', 'filtered_relations', hash(
     * 		'attribute', $node.data_map.media,
     * 		'filter_by', hash( 'extra_fields', hash( 'meta_data_1', 'illustration' ) ),
     * ) )}
     * </code></pre>
     * 
     * @param eZContentObjectAttribute $attribute 
     * @param array $filterBy Filter to use in the attribute meta data
     * @return array
     */
    public static function fetchFunctionFilteredRelations( eZContentObjectAttribute $attribute, array $filterBy )
    {
        $result = array();

        if ( $attribute->attribute( 'data_type_string' ) !== 'mugoobjectrelationlist' )
        {
            // Unsupported attribute type
            return $result;
        }


        $attributeContent	 = $attribute->attribute( 'content' );
        $relatedContentList	 = $attributeContent['relation_list'];

        if ( empty( $filterBy ) )
        {
            // no filter, fetch all the related objects
            $filteredRelations = $relatedContentList;
        }
        else
        {
            // Filtering the related objects
            $filteredRelations = array_filter( $relatedContentList, function($relatedItem) use( $filterBy )
            {
                $result = false;
                foreach ( $filterBy as $key => $value )
                {
                    if ( isset( $relatedItem[$key] ) && $relatedItem[$key] )
                    {
                        foreach ( $value as $filterKey => $filterValue )
                        {
                            if ( isset( $relatedItem[$key][$filterKey] ) && $relatedItem[$key][$filterKey] && ($relatedItem[$key][$filterKey]['identifier'] === $filterValue) )
                            {
                                $result = true;
                            }
                        }
                    }

                    // If one of the fields match, no need to keep going
                    if ( $result )
                    {
                        break;
                    }
                }

                return $result;
            } );
        }

        // Fetching the filtered related objects
        foreach ( $filteredRelations as $relatedItem )
        {
            $tmpObject = eZFunctionHandler::execute( 'content', 'object', array(
                    'object_id' => $relatedItem['contentobject_id']
                ) ); /* @var $tmpObject eZContentObject */
            if ( $tmpObject instanceof eZContentObject )
            {
                $result[] = $tmpObject;
            }
            unset( $tmpObject );
        }

        return array( 'result' => $result );
    }

}
