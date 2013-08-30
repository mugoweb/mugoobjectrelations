<div>
    {let
        class_content=$attribute.class_content
        class_list=fetch( class, list, hash( class_filter, $class_content.class_constraint_list ) )
        can_create=true()
        new_object_initial_node_placement=false()
        browse_object_start_node=false()}

    {if is_set( $attribute.class_content.default_placement.node_id )}
        {set browse_object_start_node=$attribute.class_content.default_placement.node_id}
    {/if}
    {* Optional controls. *}
    {include uri='design:content/datatype/edit/mugoobjectrelationlist_controls.tpl'}

    {* Advanced interface. *}
    {section show=eq( ezini( 'BackwardCompatibilitySettings', 'AdvancedObjectRelationList' ), 'enabled' )}
        {section show=$attribute.content.relation_list}
            <table class="list" cellspacing="0">
                <tr class="bglight">
                    <th class="tight">
                        <img
                            src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/standard/content/datatype' )}"
                            onclick="ezjs_toggleCheckboxes( document.editform, '{$attribute_base}_selection[{$attribute.id}][]' ); return false;"
                            title="{'Invert selection.'|i18n( 'design/standard/content/datatype' )}"
                        />
                    </th>
                    <th>{'Name'|i18n( 'design/standard/content/datatype' )}</th>
                    <th>{'Type'|i18n( 'design/standard/content/datatype' )}</th>
                    <th>{'Section'|i18n( 'design/standard/content/datatype' )}</th>
                    <th class="tight">{'Order'|i18n( 'design/standard/content/datatype' )}</th>
                </tr>
                {section name=Relation loop=$attribute.content.relation_list sequence=array( bglight, bgdark )}
                    <tr class="{$:sequence}">
                        {section show=$:item.is_modified}
                            {* Remove. *}
                            <td>
                                <input
                                    id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_remove_{$Relation:index}"
                                    class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}"
                                    type="checkbox"
                                    name="{$attribute_base}_selection[{$attribute.id}][]"
                                    value="{$:item.contentobject_id}"
                                />
                            </td>

                            <td colspan="3">
                                {let
                                    object  = fetch( content, object, hash( object_id, $:item.contentobject_id, object_version, $:item.contentobject_version ) )
                                    version = fetch( content, version, hash( object_id, $:item.contentobject_id, version_id, $:item.contentobject_version ) )}
                                <fieldset>
                                    <legend>
                                        {'Edit <%object_name> [%object_class]'|i18n( 'design/standard/content/datatype',, hash( '%object_name', $Relation:object.name, '%object_class', $Relation:object.class_name ) )|wash}
                                    </legend>
                                    {section name=Attribute loop=$:version.contentobject_attributes}
                                        <div class="block">
                                            {section show=$:item.display_info.edit.grouped_input}
                                                <fieldset>
                                                    <legend>
                                                        {$:item.contentclass_attribute.name}
                                                    </legend>
                                                    {attribute_edit_gui attribute_base=concat( $attribute_base, '_ezorl_edit_object_', $Relation:item.contentobject_id ) html_class='half' attribute=$:item}
                                                </fieldset>
                                            {section-else}
                                                <label>
                                                    {$:item.contentclass_attribute.name}:
                                                </label>
                                                {attribute_edit_gui attribute_base=concat( $attribute_base, '_ezorl_edit_object_', $Relation:item.contentobject_id ) html_class='half' attribute=$:item}
                                            {/section}
                                        </div>
                                    {/section}
                                </fieldset>
                                {/let}
                            </td>
                            {* Order. *}
                            <td>
                                <input
                                    id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_order"
                                    class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}"
                                    size="2"
                                    type="text"
                                    name="{$attribute_base}_priority[{$attribute.id}][]"
                                    value="{$:item.priority}"
                                />
                            </td>
                        {section-else}
                            {let object=fetch( content, object, hash( object_id, $:item.contentobject_id, object_version, $:item.contentobject_version ) )}
                            {* Remove. *}
                            <td>
                                <input
                                    id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_remove_{$Relation:index}"
                                    class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}"
                                    type="checkbox"
                                    name="{$attribute_base}_selection[{$attribute.id}][]"
                                    value="{$:item.contentobject_id}"
                                />
                            </td>
                            {* Name *}
                            <td>
                                {$Relation:object.name|wash()}
                            </td>
                            {* Type *}
                            <td>
                                {$Relation:object.class_name|wash()}
                            </td>
                            {* Section *}
                            <td>
                                {fetch( section, object, hash( section_id, $Relation:object.section_id ) ).name|wash()}
                            </td>
                            {* Order. *}
                            <td>
                                <input size="2" type="text" name="{$attribute_base}_priority[{$attribute.id}][]" value="{$:item.priority}" />
                            </td>
                            {/let}
                        {/section}
                    </tr>


                    <tr class="{$:sequence}">
                        <td colspan="5" nowrap>
                            <b>Cross reference data:</b>
                        </td>
                    </tr>

                    {foreach $attribute.class_content.extra_fields as $field_identifier => $field}

                        <tr class="{$:sequence}">
                            <td colspan="2" style="text-align: right;">
                                <label for="field{$attribute.id}{$field_identifier}_{$Objects.index}">
                                    {$field.name}:
                                </label>
                            </td>
                            <td colspan="3">
                                {if eq( 'selection', $field.type )}
                                    <select
                                        id="field{$attribute.id}{$field_identifier}_{$Objects.index}"
                                        name="{$attribute_base}_extra_fields_{$attribute.id}[{dec($:item.priority)}][{$field_identifier}]"
                                        style="width: 200px;"
                                    >
                                        <option value=""></option>
                                        {foreach $field.options as $option_identifier => $option_value}
                                            <option
                                                value="{$option_identifier}"
                                                {if eq( $option_identifier, $:item.extra_fields[$field_identifier].identifier )} selected="selected"{/if}
                                                >
                                                {$option_value}
                                            </option>
                                        {/foreach}
                                    </select>
                                {else}
                                    <input
                                        id="field{$attribute.id}{$field_identifier}_{$Objects.index}"
                                        name="{$attribute_base}_extra_fields_{$attribute.id}[{dec($:item.priority)}][{$field_identifier}]"
                                        type="text"
                                        style="width: 200px;"
                                        value="{$:item.extra_fields[$field_identifier]}"
                                        />
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                {/section}
            </table>
        {section-else}
            <p>{'There are no related objects.'|i18n( 'design/standard/content/datatype' )}</p>
        {/section}

        {section show=$attribute.content.relation_list}
            <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_remove_objects]" value="{'Remove selected'|i18n( 'design/standard/content/datatype' )}" />&nbsp;
            <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_edit_objects]" value="{'Edit selected'|i18n( 'design/standard/content/datatype' )}" />
        {section-else}
            <input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_remove_objects]" value="{'Remove selected'|i18n( 'design/standard/content/datatype' )}" disabled="disabled" />&nbsp;
            <input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_edit_objects]" value="{'Edit selected'|i18n( 'design/standard/content/datatype' )}" disabled="disabled" />
        {/section}

        {section show=array( 0, 2 )|contains( $class_content.type )}
            <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_browse_objects]" value="{'Add objects'|i18n( 'design/standard/content/datatype' )}" />
            {section show=$browse_object_start_node}
                <input type="hidden" name="{$attribute_base}_browse_for_object_start_node[{$attribute.id}]" value="{$browse_object_start_node|wash}" />
            {/section}
        {section-else}
            <input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_browse_objects]" value="{'Add objects'|i18n( 'design/standard/content/datatype' )}" disabled="disabled" />
        {/section}

        {section show=and( $can_create, array( 0, 1 )|contains( $class_content.type ) )}
            <div class="block">
                <select id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" class="combobox" name="{$attribute_base}_new_class[{$attribute.id}]">
                    {section name=Class loop=$class_list}
                        <option value="{$:item.id}">{$:item.name|wash}</option>
                    {/section}
                </select>
                {section show=$new_object_initial_node_placement}
                    <input type="hidden" name="{$attribute_base}_object_initial_node_placement[{$attribute.id}]" value="{$new_object_initial_node_placement|wash}" />
                {/section}
                <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_new_class]" value="{'Create new object'|i18n( 'design/standard/content/datatype' )}" />
            </div>
        {/section}

    {* Simple interface. *}
    {section-else}
        {section show=$attribute.content.relation_list}
            <table class="list" cellspacing="0">
                <tr>
                    <th class="tight">
                        <img
                            src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/standard/content/datatype' )}"
                            onclick="ezjs_toggleCheckboxes( document.editform, '{$attribute_base}_selection[{$attribute.id}][]' ); return false;"
                            title="{'Invert selection.'|i18n( 'design/standard/content/datatype' )}"
                        />
                    </th>
                    <th>{'Name'|i18n( 'design/standard/content/datatype' )}</th>
                    <th>{'Type'|i18n( 'design/standard/content/datatype' )}</th>
                    <th>{'Section'|i18n( 'design/standard/content/datatype' )}</th>
                    <th>{'Published'|i18n( 'design/standard/content/datatype' )}</th>
                    <th class="tight">{'Order'|i18n( 'design/standard/content/datatype' )}</th>
                </tr>
                {section var=Objects loop=$attribute.content.relation_list sequence=array( bglight, bgdark )}
                    {let object=fetch( content, object, hash( object_id, $Objects.item.contentobject_id ) )}
                    <tr class="{$Objects.sequence}">
                        {* Remove. *}
                        <td><input type="checkbox" name="{$attribute_base}_selection[{$attribute.id}][]" value="{$Objects.item.contentobject_id}" /></td>
                            {* Name *}
                        <td>{$object.name|wash()}</td>
                        {* Type *}
                        <td>{$object.class_name|wash()}</td>
                        {* Section *}
                        <td>{fetch( section, object, hash( section_id, $object.section_id ) ).name|wash()}</td>
                        <td>
                            {if $Objects.item.in_trash|not()}
                                {'Yes'|i18n( 'design/standard/content/datatype' )}
                            {else}
                                {'No'|i18n( 'design/standard/content/datatype' )}
                            {/if}
                        </td>
                        {* Order. *}
                        <td>
                            <input
                                size="2"
                                type="text"
                                name="{$attribute_base}_priority[{$attribute.id}][]"
                                value="{$Objects.item.priority}"
                            />
                        </td>
                    </tr>

                    <tr class="{$Objects.sequence}">
                        <td colspan="7" nowrap>
                            <b>
                                Cross reference data:
                            </b>
                        </td>
                    </tr>
                    {foreach $attribute.class_content.extra_fields as $field_identifier => $field}
                        <tr class="bglight">
                            <td colspan="2" style="text-align: right;">
                                <label for="field{$attribute.id}{$field_identifier}">
                                    {$field.name}:
                                </label>
                            </td>
                            <td colspan="5">
                                {if eq( 'selection', $field.type )}
                                    <select
                                        id="field{$attribute.id}{$field_identifier}"
                                        name="{$attribute_base}_extra_fields_{$attribute.id}[{dec($Objects.item.priority)}][{$field_identifier}]"
                                        style="width: 200px;"
                                    >
                                        <option value=""></option>
                                        {foreach $field.options as $option_identifier => $option_value}
                                            <option value="{$option_identifier}" {if eq( $option_identifier, $Objects.extra_fields[$field_identifier].identifier )} selected="selected"{/if}>{$option_value}</option>
                                        {/foreach}
                                    </select>
                                {else}
                                    <input
                                        id="field{$attribute.id}{$field_identifier}"
                                        name="{$attribute_base}_extra_fields_{$attribute.id}[{dec($Objects.item.priority)}][{$field_identifier}]"
                                        type="text"
                                        style="width: 200px;"
                                        value="{$Objects.extra_fields[$field_identifier]}"
                                        />
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                {/let}
            {/section}
        </table>
    {section-else}
        <p>{'There are no related objects.'|i18n( 'design/standard/content/datatype' )}</p>
    {/section}

    {section show=$attribute.content.relation_list}
        <input
            class="button"
            type="submit"
            name="CustomActionButton[{$attribute.id}_remove_objects]"
            value="{'Remove selected'|i18n( 'design/standard/content/datatype' )}"
        />&nbsp;
        {section-else}
            <input class="button-disabled" type="submit" name="CustomActionButton[{$attribute.id}_remove_objects]" value="{'Remove selected'|i18n( 'design/standard/content/datatype' )}" disabled="disabled" />&nbsp;
        {/section}

        {section show=$browse_object_start_node}
            <input type="hidden" name="{$attribute_base}_browse_for_object_start_node[{$attribute.id}]" value="{$browse_object_start_node|wash}" />
        {/section}
        <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_browse_objects]" value="{'Add objects'|i18n( 'design/standard/content/datatype' )}" />
    {/section}
    {/let}
</div>