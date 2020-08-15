{capture name="property_id" assign="property_id"}textarea-itemproperty-{$property.id}{/capture}
{capture name="property_name" assign="property_name"}item[{$property.id}]{/capture}

{textarea_input name=$property_name id=$property_id style="width:350px;height:80px" value=$value form_hint=$property.hint limit=255 word_count=true}