<?php
/**
 * mugoObjectRelationsUtils.php
 *
 */

class mugoObjectRelationsUtils
{
    const LISTSEPARATOR = "MugoObjectRelationListSEPARATOR";
    const PAIRSEPARATOR = "PAIRSEPARATOR";

    /**
     * Return a string for saving on the x-reference of a mugo object relations
     * attribute.
     *
     * The input is an array of pairs like:
     *
     * objectId -> xref data
     */
    public static function marshallXrefData( $xrefDataArray )
    {
        $string = "";
        $joiner = "";
        foreach( $xrefDataArray as $objectId => $xrefData )
        {
            $string .= $joiner . $objectId . mugoObjectRelationsUtils::PAIRSEPARATOR . $xrefData;
            $joiner = mugoObjectRelationsUtils::LISTSEPARATOR;
        }
        return $string;
    }
}
?>