{if $attribute.has_content}
    {def $class_content = $attribute.class_content}
    {if $attribute.content.extra_fields_attribute_level}
        {foreach $attribute.content.extra_fields_attribute_level as $extra_field_identifier => $extra_field_value}
            {if is_set( $class_content.extra_fields_attribute_level.$extra_field_identifier )}
                <strong>{$class_content.extra_fields_attribute_level.$extra_field_identifier.name}</strong>:
                {if eq( 'selection', $class_content.extra_fields_attribute_level.$extra_field_identifier.type )}
                    {$extra_field_value.value|wash()}
                {else}
                    {$extra_field_value|wash()}
                {/if}
            {/if}
            <br />
        {/foreach}
    {/if}
    {foreach $attribute.content.relation_list as $relation}
        {if $relation.in_trash|not()}
            {content_view_gui view='embed' content_object=fetch( 'content', 'object', hash( 'object_id', $relation.contentobject_id ) )}<br />
            {foreach $relation.extra_fields as $extra_field_identifier => $extra_field_value}
                {if is_set( $class_content.extra_fields.$extra_field_identifier )}
                    <strong>{$class_content.extra_fields.$extra_field_identifier.name}</strong>:
                    {if eq( 'selection', $class_content.extra_fields.$extra_field_identifier.type )}
                        {$extra_field_value.value|wash()}
                    {else}
                        {$extra_field_value|wash()}
                    {/if}
                {/if}
            {/foreach}
            {delimiter}<br />{/delimiter}
        {/if}
    {/foreach}
{else}
    {'There are no related objects.'|i18n( 'design/standard/content/datatype' )}
{/if}