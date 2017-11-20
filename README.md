# mugoobjectrelations

The Mugo Object Relations extension is meant to allow using
cross reference metadata on your ezpublish object relations.

## Installation
Works as provided:
1. Extract the contents under the extension/ directory of your 
   eZ Publish installation and activate it for all siteaccesses.
2. Regenerate the autoloads array and clear the cache

## TODO
- Add a "searchable" flag to the extra fields (both relation-level and attribute-level)
- javascript in dedicated file
- re-use parent PHP code in MugoObjectRelationListType
- replace any template 'section' operations to 'if/foreach'
