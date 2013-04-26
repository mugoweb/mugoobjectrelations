{* Class attribute edit template *}
{def $data_type_string = 'mugoobjectrelationlist'}
{let content=$class_attribute.content
     class_list=$content.class_constraint_list
     default_placement=$content.default_placement
     type=$content.type
     all_class_list=fetch( class, list )
    extra_fields=$content.extra_fields}

<div class="block">
    <br />
    <fieldset>
            <legend>{'Extra Fields'|i18n( 'design/standard/class/datatype' )}</legend>
        <div>
            <table>
                {def $fieldNumber = 0}
                {def $optionNumber = 0}
                {foreach $extra_fields as $fieldIdentifier => $field}
                    {set $optionNumber = 0}
                    <tr id="extra_field_{$class_attribute.id}_{$fieldNumber}">
                        <td style="vertical-align: top;">
                            <input
                                type="button"
                                class="button"
                                onclick="$('#extra_field_{$class_attribute.id}_{$fieldNumber}').empty().remove();$('#extra_field_options_{$class_attribute.id}_{$fieldNumber}').empty().remove();"
                                value="Delete"
                            />
                        </td>
                        <td style="vertical-align: top;">
                            <label>
                                Name: <input id="ContentClass_{$data_type_string}_extra_fields_name_{$class_attribute.id}_{$fieldNumber}" type="text" name="ContentClass_{$data_type_string}_extra_fields_name_{$class_attribute.id}[{$fieldNumber}]" value="{$field.name}"/>
                            </label>
                        </td>
                        <td style="vertical-align: top;">
                            <label>
                                Identifier: <input type="text" name="ContentClass_{$data_type_string}_extra_fields_identifier_{$class_attribute.id}[{$fieldNumber}]" value="{$fieldIdentifier}"/>
                            </label>
                        </td>
                        <td style="vertical-align: top;">
                            <table>
                                <tr>
                                    <td>
                                        <label>
                                            <input type="radio"
                                                   name="ContentClass_{$data_type_string}_extra_fields_type_{$class_attribute.id}[{$fieldNumber}]"
                                                   value="text" {if eq($field.type, "text")}checked{/if}
                                                   onclick="$('#ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}').hide();"
                                                   />
                                            Open text
                                        </label>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="radio"
                                                   name="ContentClass_{$data_type_string}_extra_fields_type_{$class_attribute.id}[{$fieldNumber}]"
                                                   value="selection" {if ne($field.type, "text")}checked{/if}
                                                   onclick="$('#ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}').show();"
                                                   />
                                            Selection
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr id="extra_field_options_{$class_attribute.id}_{$fieldNumber}"><td></td>
                        <td colspan="3">
                            {if eq($field.type, "text")}
                                <div id="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}" style="display: none;">
                            {else}
                                <div id="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}" style="">
                            {/if}
                                <div id="newFields_{$class_attribute.id}_{$fieldNumber}">
                                    {foreach $field.options as $option_key => $option}
                                        <div id="newOption{$class_attribute.id}_{$fieldNumber}_{$optionNumber}">
                                            <label for="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_name" style="display: inline;" >
                                                Selection name:
                                            </label>
                                            <input
                                                id="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_name"
                                                name="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_name[]"
                                                type="text"
                                                size="20"
                                                value="{$option}"
                                                />
                                            <label for="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_identifier" style="display: inline;" >
                                                Selection identifier:
                                            </label>
                                            <input
                                                id="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_identifier"
                                                name="ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}_identifier[]"
                                                type="text"
                                                size="20"
                                                value="{$option_key}"
                                            />
                                            <input type="button" class="button" onclick="$('#newOption{$class_attribute.id}_{$fieldNumber}_{$optionNumber}').empty().remove();" value="Delete"/>
                                        </div>
                                        {set $optionNumber = $optionNumber|inc()}
                                    {/foreach}
                                </div>
                                <input
                                    class="button"
                                    type="button"
                                    name="ContentClass_{$data_type_string}_newoption_button_{$class_attribute.id}"
                                    title="{'Add a new option.'|i18n( 'design/standard/class/datatype' )}"
                                    onclick="addNewOption('#newFields_{$class_attribute.id}_{$fieldNumber}','ContentClass_{$data_type_string}_extra_fields_new_options_{$class_attribute.id}_{$fieldNumber}');"
                                    value="{'New option'|i18n( 'design/standard/class/datatype' )}"
                                />
                            </div>
                        </td>
                    </tr>
                    {set $fieldNumber = $fieldNumber|inc()}
                {/foreach}
            </table>
        </div>
        <script type="text/javascript">
            {literal}
            window.idCounter = 0;
            function addNewOption(element, myId) {
                window.idCounter++;
                console.log('idCounter: ' + window.idCounter);
                $(element)
                .append($('<div />', {id:'newOption'+idCounter})
                    .append($('<label>', {for: myId + '_name' + window.idCounter, style:'display:inline;'}).text('Selection name: '))
                    .append($('<input />', {type: 'text', id: myId+'_name' + window.idCounter, name: myId + '_name[]', size: 40}))
                    .append(' ')
                    .append($('<label>', {for: myId + '_identifier' + window.idCounter, style:'display:inline;'}).text('Selection identifier: '))
                    .append($('<input />', {type: 'text', id: myId + '_identifier' + window.idCounter, name: myId + '_identifier[]', size: 40}))
                    .append(' ')
                    .append($('<input />', {type: 'button', class: 'button', onclick: "$('#newOption" + window.idCounter + "').empty().remove();", value: 'Delete'}))
                );
            }
            {/literal}
        </script>
    </fieldset>
    <div style="margin-top: 3px;">
        <input
            class="button"
            type="submit"
            name="ContentClass_{$data_type_string}_newfield_button_{$class_attribute.id}"
            value="{'New field'|i18n( 'design/standard/class/datatype' )}"
            title="{'Add a new field.'|i18n( 'design/standard/class/datatype' )}" />
    </div>
</div>
<div class="block">

{section show=eq( ezini( 'BackwardCompatibilitySettings', 'AdvancedObjectRelationList' ), 'enabled' )}
<div class="block">
    <label>{'Type'|i18n( 'design/standard/class/datatype' )}:</label>
    <select name="ContentClass_{$data_type_string}_type_{$class_attribute.id}">
    <option value="0" {section show=eq( $type, 0 )}selected="selected"{/section}>{'New and existing objects'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="1" {section show=eq( $type, 1 )}selected="selected"{/section}>{'Only new objects'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="2" {section show=eq( $type, 2 )}selected="selected"{/section}>{'Only existing objects'|i18n( 'design/standard/class/datatype' )}</option>
    </select>
</div>
{section-else}
    <input type="hidden" name="ContentClass_{$data_type_string}_type_{$class_attribute.id}" value="2" />
{/section}

<div class="block">
    <label>{'Allowed classes'|i18n( 'design/standard/class/datatype' )}:</label>
    <select name="ContentClass_{$data_type_string}_class_list_{$class_attribute.id}[]" multiple="multiple" title="{'Select which classes user can create'|i18n( 'design/standard/class/datatype' )}">
    <option value="" {section show=$class_list|lt(1)}selected="selected"{/section}>{'Any'|i18n( 'design/standard/class/datatype' )}</option>
    {section name=Class loop=$all_class_list}
    <option value="{$:item.identifier|wash}" {section show=$class_list|contains($:item.identifier)}selected="selected"{/section}>{$:item.name}</option>
    {/section}
    </select>
</div>

<div class="block">
<fieldset>
<legend>{'New Objects'|i18n( 'design/standard/class/datatype' )}</legend>
<table>
  <tr>
     <td>
         <p>{'Object class'|i18n( 'design/standard/class/datatype' )}:</p>
     </td>
     <td>
         <select name="ContentClass_{$data_type_string}_object_class_{$class_attribute.id}">
         {let classes=fetch( 'class', 'list' )}
         <option value="" {eq( $content.object_class, "" )|choose( '', 'selected="selected"' )}>{'(none)'|i18n('design/standard/class/datatype')}</option>
         {section loop=$:classes}
               <option value="{$:item.id}" {eq( $content.object_class, $:item.id )|choose( '', 'selected="selected"' )}>{$:item.name}</option>
         {/section}
         {/let}
         </select>
     </td>
  </tr>
  <tr>
     <td>
         <p>{'Placing new objects under'|i18n( 'design/standard/class/datatype' )}:</p>
     </td>
     <td>
         {section show=$default_placement}
             {let default_location=fetch( content, node, hash( node_id, $default_placement.node_id ) )}
               {$default_location.class_identifier|class_icon( small, $default_location.class_name )}&nbsp;{$default_location.name|wash}
             {/let}
         {/section}
     <i>({'See'|i18n( 'design/standard/class/datatype' )} '{'Default location'|i18n( 'design/standard/class/datatype' )}')</i>
     </td>
  </tr>
</table>
</fieldset>
</div>

<div class="block">
<fieldset>
<legend>{'Default location'|i18n( 'design/standard/class/datatype' )}</legend>
{section show=$default_placement}
{let default_location=fetch( content, node, hash( node_id, $default_placement.node_id ) )}
<table class="list" cellspacing="0">
<tr>
    <th>{'Name'|i18n( 'design/standard/class/datatype' )}</th>
    <th>{'Type'|i18n( 'design/standard/class/datatype' )}</th>
    <th>{'Section'|i18n( 'design/standard/class/datatype' )}</th>
</tr>
<tr>
    <td>{$default_location.class_identifier|class_icon( small, $default_location.class_name )}&nbsp;{$default_location.name|wash}</td>
    <td>{$default_location.class_name|wash}</td>
    <td>{let section_object=fetch( section, object, hash( section_id, $default_location.object.section_id ) )}{section show=$section_object}{$section_object.name|wash}{section-else}<i>{'Unknown section'|i18n( 'design/standard/class/datatype' )}</i>{/section}{/let}</td>
</tr>
</table>
{/let}

<input type="hidden" name="ContentClass_{$data_type_string}_placement_{$class_attribute.id}" value="{$default_placement.node_id}" />
{section-else}
<p>{'New objects will not be placed in the content tree.'|i18n( 'design/standard/class/datatype' )}</p>
<input type="hidden" name="ContentClass_{$data_type_string}_placement_{$class_attribute.id}" value="" />
{/section}

{section show=$default_placement}
    <input class="button" type="submit" name="CustomActionButton[{$class_attribute.id}_disable_placement]" value="{'Remove location'|i18n('design/standard/class/datatype')}" />
{section-else}
    <input class="button-disabled" type="submit" name="CustomActionButton[{$class_attribute.id}_disable_placement]" value="{'Remove location'|i18n('design/standard/class/datatype')}" disabled="disabled" />
{/section}

<input class="button" type="submit" name="CustomActionButton[{$class_attribute.id}_browse_for_placement]" value="{'Select location'|i18n('design/standard/class/datatype')}" />

</fieldset>

</div>
{/let}
