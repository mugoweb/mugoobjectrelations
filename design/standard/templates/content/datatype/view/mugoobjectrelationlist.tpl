
<div style="border: solid 1px #ddddee;">

<!-- content / view -->

{section show=$attribute.content.relation_list}
    {section var=Relations loop=$attribute.content.relation_list sequence=array( bglight, bgdark )}
        {section show=$Relations.item.in_trash|not()}

            {content_view_gui view=embed content_object=fetch( content, object, hash( object_id, $Relations.item.contentobject_id ) )}<br />

            <div style="margin: 2px 0px 0px 20px; padding: 3px 4px 4px 4px; background-color:#eeeeee;">
                <b>X-Reference data:</b><br/>
                {$Relations.xrefoptionaldata}
            </div>
        {/section}
    {/section}
{section-else}
    {'There are no related objects.'|i18n( 'design/standard/content/datatype' )}
{/section}

</div>