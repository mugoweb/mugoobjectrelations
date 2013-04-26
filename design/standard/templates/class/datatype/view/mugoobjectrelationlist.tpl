<div style="border: solid 1px #ddddee;">

<!-- class / view -->

{let content=$class_attribute.content}

{* Selection type. *}
<div class="block">
<label>{'Selection method'|i18n( 'design/standard/class/datatype' )}</label><div class="labelbreak"></div>
    <p>{$content.selection_type|choose( 'Browse'|i18n( 'design/standard/class/datatype' ),
                                        'Drop-down list'|i18n( 'design/standard/class/datatype' ),
                                        'List with radio buttons'|i18n( 'design/standard/class/datatype' ),
                                        'List with checkboxes'|i18n( 'design/standard/class/datatype' ),
                                        'Multiple selection list'|i18n( 'design/standard/class/datatype' ),
                                        'Template based, multi'|i18n( 'design/standard/class/datatype' ),
                                        'Template based, single'|i18n( 'design/standard/class/datatype' ),
                                         )}</p>
</div>

{* Type. *}
<div class="block">
    <label>{'Type'|i18n( 'design/standard/class/datatype' )}:</label>
    <p>
    {switch match=$content.type}
    {case match=0}{'New and existing objects'|i18n( 'design/standard/class/datatype' )}{/case}
    {case match=1}{'Only new objects'|i18n( 'design/standard/class/datatype' )}{/case}
    {case match=2}{'Only existing objects'|i18n( 'design/standard/class/datatype' )}{/case}
    {case}<i>{'Empty'|i18n( 'design/standard/class/datatype' )}</i>{/case}
    {/switch}
    </p>
</div>

{* Allowed classes. *}
<div class="block">
    <label>{'Allowed classes'|i18n( 'design/standard/class/datatype' )}:</label>
    {section show=$content.class_constraint_list|count|lt( 1 )}
    <p>{'Any'|i18n( 'design/standard/class/datatype' )}</p>
    {section-else}
    <ul>
    {section var=Classes loop=$content.class_constraint_list}<li>{$Classes.item}</li>{/section}
    </ul>
    {/section}
</div>

{* Object creation. *}
<div class="element">
<label>{'Object class'|i18n( 'design/standard/class/datatype' )}</label><div class="labelbreak"></div>
{section show=$content.object_class}
    {let filter_class=fetch( content, class, hash( class_id, $content.object_class ) )}
    <p>{$filter_class.name}</p>
    {/let}
{section-else}
    <p>(none)</p>
{/section}

{* Default location. *}
<div class="block">
<label>{'Default location'|i18n( 'design/standard/class/datatype' )}:</label>
{section show=$content.default_placement}
{let default_location=fetch( content, node, hash( node_id, $content.default_placement.node_id ) )}
<table class="list" cellspacing="0">
<tr>
    <th>{'Name'|i18n( 'design/standard/class/datatype' )}</th>
    <th>{'Type'|i18n( 'design/standard/class/datatype' )}</th>
    <th>{'Section'|i18n( 'design/standard/class/datatype' )}</th>
</tr>
<tr>
    <td>{$default_location.class_identifier|class_icon( small, $default_location.class_name )}&nbsp;<a href={$default_location.url_alias|ezurl}>{$default_location.name|wash}</a></td>
    <td>{$default_location.class_name|wash}</td>
    <td>{let section_object=fetch( section, object, hash( section_id, $default_location.object.section_id ) )}{section show=$section_object}<a href={concat( 'section/view/', $section_object.id)|ezurl}>{$section_object.name|wash}</a>{section-else}<i>{'Unknown section'|i18n( 'design/standard/class/datatype' )}</i>{/section}{/let}</td>
</tr>
</table>
{/let}
{section-else}
<p>{'New objects will not be placed in the content tree.'|i18n( 'design/standard/class/datatype' )}</p>
{/section}
</div>

{* Extra fields *}
<div class="block">
    <label>{'Extra fields'|i18n( 'design/standard/class/datatype' )}:</label>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Identifier</th>
                <th>Type</th>
                <th>Options</th>
            </tr>
        </thead>
        <tbody>
            {foreach $content.extra_fields as $extra_field_identifier => $extra_field}
                <tr>
                    <td style="vertical-align: top;">{$extra_field.name|wash()}</td>
                    <td style="vertical-align: top;">{$extra_field_identifier|wash()}</td>
                    <td style="vertical-align: top;">{$extra_field.type|wash()}</td>
                    <td style="vertical-align: top;">
                        {if eq( 'selection', $extra_field.type )}
                            <ul>
                                {foreach $extra_field.options as $option_identifier => $option_value}
                                    <li>{$option_value|wash()} ({$option_identifier|wash()})</li>
                                {/foreach}
                            </ul>
                        {else}
                            {'n/a'|i18n( 'design/standard/class/datatype' )}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
{/let}
</div>