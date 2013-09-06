{* Class attribute edit template *}
{def $data_type_string = 'mugoobjectrelationlist'}
{def $content=$class_attribute.content
     $class_list=$content.class_constraint_list
     $default_placement=$content.default_placement
     $type=$content.type
     $all_class_list=fetch( 'class', 'list' )
     $extra_fields=$content.extra_fields
     $extra_fields_attribute_level=$content.extra_fields_attribute_level
}

<div class="block">
    <br />
    <fieldset>
            <legend>{'Extra Fields (attribute-level)'|i18n( 'design/standard/class/datatype' )}</legend>
        <div>
            <table class="mugoobjectrelationlist-extra-fields">
                {def $fieldNumber = 0}
                {def $optionNumber = 0}
                {if $extra_fields_attribute_level}
                    {foreach $extra_fields_attribute_level as $fieldIdentifier => $field}
                        {set $optionNumber = 0}
                        <tr id="extra_field_attribute_level{$class_attribute.id}_{$fieldNumber}">
                            <td style="vertical-align: top;">
                                <img class="mugoobjectrelationlist-move-down" src={'button-move_down.gif'|ezimage()} alt={'Down'|i18n( 'design/standard/class/datatype' )} />
                                <img class="mugoobjectrelationlist-move-up" src={'button-move_up.gif'|ezimage()} alt={'Up'|i18n( 'design/standard/class/datatype' )} />
                            </td>
                            <td style="vertical-align: top;">
                                <label>
                                    Name: <input id="ContentClass_{$data_type_string}_extra_fields_attribute_level_name_{$class_attribute.id}_{$fieldNumber}" type="text" name="ContentClass_{$data_type_string}_extra_fields_attribute_level_name_{$class_attribute.id}[{$fieldNumber}]" value="{$field.name}"/>
                                </label>
                            </td>
                            <td style="vertical-align: top;">
                                <label>
                                    Identifier: <input type="text" name="ContentClass_{$data_type_string}_extra_fields_attribute_level_identifier_{$class_attribute.id}[{$fieldNumber}]" value="{$fieldIdentifier}"/>
                                </label>
                            </td>
                            <td style="vertical-align: top;">
                                <table>
                                    <tr>
                                        <td>
                                            <label>
                                                <input type="checkbox"
                                                       name="ContentClass_{$data_type_string}_extra_fields_attribute_level_required_{$class_attribute.id}[{$fieldNumber}]"
                                                       value="1" {if eq( $field.required, 1 )}checked{/if}
                                                       />
                                                Required
                                            </label>
                                        </td>
                                        <td>
                                            <label>
                                                <input type="radio"
                                                       name="ContentClass_{$data_type_string}_extra_fields_attribute_level_type_{$class_attribute.id}[{$fieldNumber}]"
                                                       value="text" {if eq($field.type, "text")}checked{/if}
                                                       onclick="$('#ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}').hide();"
                                                       />
                                                Open text
                                            </label>
                                        </td>
                                        <td>
                                            <label>
                                                <input type="radio"
                                                       name="ContentClass_{$data_type_string}_extra_fields_attribute_level_type_{$class_attribute.id}[{$fieldNumber}]"
                                                       value="selection" {if ne($field.type, "text")}checked{/if}
                                                       onclick="$('#ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}').show();"
                                                       />
                                                Selection
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="vertical-align: top;">
                                <input
                                    type="button"
                                    class="button"
                                    onclick="$('#extra_field_attribute_level{$class_attribute.id}_{$fieldNumber}').empty().remove();$('#extra_field_attribute_level_options_{$class_attribute.id}_{$fieldNumber}').empty().remove();"
                                    value="Delete"
                                />
                            </td>
                        </tr>
                        <tr id="extra_field_attribute_level_options_{$class_attribute.id}_{$fieldNumber}"><td></td>
                            <td colspan="3">
                                {if eq($field.type, "text")}
                                    <div id="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}" style="display: none;">
                                {else}
                                    <div id="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}" style="">
                                {/if}
                                    <div id="newFields_attribute_level_{$class_attribute.id}_{$fieldNumber}" class="mugoobjectrelationlist-extra-fields-selection">
                                        {foreach $field.options as $option_key => $option}
                                            <div id="newOptionAttribute{$class_attribute.id}_{$fieldNumber}_{$optionNumber}">
                                                <img class="mugoobjectrelationlist-move-down" src={'button-move_down.gif'|ezimage()} alt={'Down'|i18n( 'design/standard/class/datatype' )} />
                                                <img class="mugoobjectrelationlist-move-up" src={'button-move_up.gif'|ezimage()} alt={'Up'|i18n( 'design/standard/class/datatype' )} />
                                                <label for="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_name" style="display: inline;" >
                                                    Selection name:
                                                </label>
                                                <input
                                                    id="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_name"
                                                    name="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_name[]"
                                                    type="text"
                                                    size="20"
                                                    value="{$option}"
                                                    />
                                                <label for="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_identifier" style="display: inline;" >
                                                    Selection identifier:
                                                </label>
                                                <input
                                                    id="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_identifier"
                                                    name="ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}_identifier[]"
                                                    type="text"
                                                    size="20"
                                                    value="{$option_key}"
                                                />
                                                <input type="button" class="button" onclick="$('#newOptionAttribute{$class_attribute.id}_{$fieldNumber}_{$optionNumber}').empty().remove();" value="Delete"/>
                                            </div>
                                            {set $optionNumber = $optionNumber|inc()}
                                        {/foreach}
                                    </div>
                                    <input
                                        class="button"
                                        type="button"
                                        name="ContentClass_{$data_type_string}_newoption_attribute_button_{$class_attribute.id}"
                                        title="{'Add a new option.'|i18n( 'design/standard/class/datatype' )}"
                                        onclick="addNewOptionAttribute{$class_attribute.id}('#newFields_attribute_level_{$class_attribute.id}_{$fieldNumber}','ContentClass_{$data_type_string}_extra_fields_attribute_level_new_options_{$class_attribute.id}_{$fieldNumber}');"
                                        value="{'New option'|i18n( 'design/standard/class/datatype' )}"
                                    />
                                </div>
                            </td>
                        </tr>
                        {set $fieldNumber = $fieldNumber|inc()}
                    {/foreach}
                {/if}
                {undef $fieldNumber $optionNumber}
            </table>
        </div>
        <script type="text/javascript">
            window.idCounterAttribute{$class_attribute.id} = 0;
            function addNewOptionAttribute{$class_attribute.id}( element, myId )
            {ldelim}
                window.idCounterAttribute{$class_attribute.id}++;
                //console.log( 'idCounterAttribute{$class_attribute.id}: ' + window.idCounterAttribute{$class_attribute.id} );
                $( element )
                .append( $( '<div />', {ldelim}id:'newOptionAttribute'+idCounterAttribute{$class_attribute.id}{rdelim})
                    .append( $( '<img>', {ldelim} class: 'mugoobjectrelationlist-move-down', src: {'button-move_down.gif'|ezimage()}, alt: '{'Down'|i18n( 'design/standard/class/datatype' )}'{rdelim} ) )
                    .append( ' ' )
                    .append( $( '<img>', {ldelim} class: 'mugoobjectrelationlist-move-up', src: {'button-move_up.gif'|ezimage()}, alt: '{'Up'|i18n( 'design/standard/class/datatype' )}'{rdelim} ) )
                    .append( ' ' )
                    .append( $( '<label>', {ldelim}for: myId + '_name' + window.idCounterAttribute{$class_attribute.id}, style:'display:inline;'}).text('Selection name: '))
                    .append( $( '<input />', {ldelim}type: 'text', id: myId+'_name' + window.idCounterAttribute{$class_attribute.id}, name: myId + '_name[]', size: 40{rdelim}))
                    .append( ' ')
                    .append( $( '<label>', {ldelim}for: myId + '_identifier' + window.idCounterAttribute{$class_attribute.id}, style:'display:inline;'{rdelim}).text('Selection identifier: '))
                    .append( $( '<input />', {ldelim}type: 'text', id: myId + '_identifier' + window.idCounterAttribute{$class_attribute.id}, name: myId + '_identifier[]', size: 40{rdelim}))
                    .append( ' ')
                    .append( $( '<input />', {ldelim}type: 'button', class: 'button', onclick: "$('#newOptionAttribute" + window.idCounterAttribute{$class_attribute.id} + "').empty().remove();", value: 'Delete'{rdelim}))
                );
            {rdelim}
        </script>
    </fieldset>
    <div style="margin-top: 3px;">
        <input
            class="button"
            type="submit"
            name="ContentClass_{$data_type_string}_newfield_attribute_level_button_{$class_attribute.id}"
            value="{'New field'|i18n( 'design/standard/class/datatype' )}"
            title="{'Add a new field.'|i18n( 'design/standard/class/datatype' )}" />
    </div>
</div>
<div class="block">
    <br />
    <fieldset>
            <legend>{'Extra Fields'|i18n( 'design/standard/class/datatype' )}</legend>
        <div>
            <table class="mugoobjectrelationlist-extra-fields">
                {def $fieldNumber = 0}
                {def $optionNumber = 0}
                {foreach $extra_fields as $fieldIdentifier => $field}
                    {set $optionNumber = 0}
                    <tr id="extra_field_{$class_attribute.id}_{$fieldNumber}">
                        <td style="vertical-align: top;">
                            <img class="mugoobjectrelationlist-move-down" src={'button-move_down.gif'|ezimage()} alt={'Down'|i18n( 'design/standard/class/datatype' )} />
                            <img class="mugoobjectrelationlist-move-up" src={'button-move_up.gif'|ezimage()} alt={'Up'|i18n( 'design/standard/class/datatype' )} />
                        </td>
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
                                            <input type="checkbox"
                                                   name="ContentClass_{$data_type_string}_extra_fields_required_{$class_attribute.id}[{$fieldNumber}]"
                                                   value="1" {if eq( $field.required, 1 )}checked{/if}
                                                   />
                                            Required
                                        </label>
                                    </td>
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
                                <div id="newFields_{$class_attribute.id}_{$fieldNumber}" class="mugoobjectrelationlist-extra-fields-selection">
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
            function addNewOption( element, myId )
            {
                window.idCounter++;
                //console.log('idCounter: ' + window.idCounter);
                $( element )
                .append($('<div />', {id:'newOption'+idCounter})
                    .append( $( '<img>', {{/literal} class: 'mugoobjectrelationlist-move-down', src: {'button-move_down.gif'|ezimage()}, alt: '{'Down'|i18n( 'design/standard/class/datatype' )}'{literal}} ) )
                    .append( ' ' )
                    .append( $( '<img>', {{/literal} class: 'mugoobjectrelationlist-move-up', src: {'button-move_up.gif'|ezimage()}, alt: '{'Up'|i18n( 'design/standard/class/datatype' )}'{literal}} ) )
                    .append( ' ' )
                    .append( $( '<label>', {for: myId + '_name' + window.idCounter, style:'display:inline;'}).text('Selection name: '))
                    .append( $( '<input />', {type: 'text', id: myId+'_name' + window.idCounter, name: myId + '_name[]', size: 40}))
                    .append( ' ' )
                    .append( $( '<label>', {for: myId + '_identifier' + window.idCounter, style:'display:inline;'}).text('Selection identifier: '))
                    .append( $( '<input />', {type: 'text', id: myId + '_identifier' + window.idCounter, name: myId + '_identifier[]', size: 40}))
                    .append( ' ' )
                    .append( $( '<input />', {type: 'button', class: 'button', onclick: "$('#newOption" + window.idCounter + "').empty().remove();", value: 'Delete'}))
                );
            }
            $( document ).ready( function()
            {
                // Sorting the extra fields; we bind the event to the table so that new elements don't need to have events re-bound
                $( 'table.mugoobjectrelationlist-extra-fields' ).click( function( event )
                {
                    var target = $( event.target );

                    if( target.is( 'td > .mugoobjectrelationlist-move-down' ) )
                    {
                        var row = target.closest( 'tr' );
                        var rowNext = row.next();
                        // There is a row for the selection options, so move past that one; start with the row closest to where you want to go
                        rowNext.insertAfter( rowNext.next().next() );
                        row.insertAfter( row.next().next() );
                    }
                    else if( target.is( 'td > .mugoobjectrelationlist-move-up' ) )
                    {
                        var row = target.closest( 'tr' );
                        var rowNext = row.next();
                        // There is a row for the selection options, so move past that one
                        row.insertBefore( row.prev().prev() );
                        rowNext.insertBefore( rowNext.prev().prev() );
                    }
                });
                
                $( 'div.mugoobjectrelationlist-extra-fields-selection' ).click( function( event )
                {
                    var target = $( event.target );

                    // Sorting selection options within extra fields
                    if( target.is( '.mugoobjectrelationlist-move-down' ) )
                    {
                        var row = target.closest( 'div' );
                        row.insertAfter( row.next() );
                    }
                    else if( target.is( '.mugoobjectrelationlist-move-up' ) )
                    {
                        var row = target.closest( 'div' );
                        row.insertBefore( row.prev() );
                    }
                });
            });
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