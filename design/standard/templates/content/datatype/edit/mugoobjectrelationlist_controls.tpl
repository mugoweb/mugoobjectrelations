
{*
  This file is included in the ezobjectrelationlist.tpl template file and can be overridden
  to set certain controller variables.


  Example #1 - Controlling whether creating of new objects is allowed or not.
  {set can_create=false()}

  Example #2 - Controlling where newly created objects are placed
  {set new_object_initial_node_placement=42}

  Example #3 - Controlling where the object browsing should start
  {set browse_object_start_node=42}
   You can also use the same text strings as in the browse.ini, e.g.
  {set browse_object_start_node='content'}
   other text strings are: users, media, setup

*}
