{capture name="property_id" assign="property_id"}{$_input_data.id}{/capture}
{capture name="property_name" assign="property_name"}{$_input_data.name}{/capture}
{capture name="property_format" assign="property_format"}{$property.ml_text_format}{/capture}
{capture name="property_css_class" assign="property_css_class"}itemproperty_textinput itemproperty_ml_textinput itemproperty_ml_textinput_{$property_format}{/capture}

{textarea_input name=$property_name id=$property_id style="width:350px;height:80px" value=$value form_hint=$property.hint limit=255 word_count=true class=$property_css_class data_format=$property_format}
{if $property.is_formatted_ml_text}<div class="form-hint">{$property.ml_text_format_label} formatting will be parsed when this property is rendered.</div>{/if}
