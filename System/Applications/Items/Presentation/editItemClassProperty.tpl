<div id="work-area">
  
  {load_interface file="edit_property_tabs.tpl"}
  
  <h3>Edit model property</h3>

  <div id="instruction">You are editing the property &quot;{$property.name}&quot; of model &quot;{$model.plural_name}&quot;</div>

  <form action="{$domain}{$section}/updateItemClassProperty" method="post" enctype="multipart/form-data">
  
  <input type="hidden" name="class_id" value="{$model.id}" />
  <input type="hidden" name="itemproperty_id" value="{$property.id}" />

    <div class="edit-form-row">
        <div class="form-section-label">Name</div>
        {$property.name} (Accessed as <strong>{$property.varname}</strong>)
    </div>

    <div class="edit-form-row">
        <div class="form-section-label">Data type</div>
        {$data_types[$property.datatype].label} <span style="color:#999">({$property.datatype})</span>
    </div>
    
{if $property.datatype == 'SM_DATATYPE_ASSET' || $property.datatype == 'SM_DATATYPE_ASSET_DOWNLOAD'}
    
    <div class="edit-form-row">
        <div class="form-section-label">Accepted file types</div>
        {$file_type} <span style="color:#999">({$property.foreign_key_filter})</span>
    </div>
    
    <div class="edit-form-row">
        <div class="form-section-label">Restrict selection to a file group?</div>
        <input type="hidden" name="itemproperty_filter_type" value="ASSET_GROUP" />
        <select name="itemproperty_filter">
          <option value="NONE">No restriction</option>
{foreach from=$possible_groups item="group"}
          <option value="{$group.id}"{if $group.id == $property.option_set_id} selected="selected"{/if}>{$group.label}</option>
{/foreach}

        </select>
    </div>

{/if}

{if $property.datatype == 'SM_DATATYPE_CMS_ITEM' || $property.datatype == 'SM_DATATYPE_CMS_ITEM_SELECTION'}

    <div class="edit-form-row">
        <div class="form-section-label">Restrict selection to a data set?</div>
        <input type="hidden" name="itemproperty_filter_type" value="DATA_SET" />
        <select name="itemproperty_filter">
          <option value="NONE">No restriction</option>
{foreach from=$possible_sets item="set"}
          <option value="{$set.id}"{if $set.id == $property.option_set_id} selected="selected"{/if}>{$set.label}</option>
{/foreach}

        </select>
    </div>

{/if}

  <div class="edit-form-row">
    <div class="form-section-label">Hint text</div>
    <input type="text" name="itemproperty_hint" value="{$property.hint.html_escape}" />
  </div>

{if $property.datatype == 'SM_DATATYPE_ML_TEXT'}

  <div class="edit-form-row">
    <div class="form-section-label">Text format</div>
    <select name="itemproperty_ml_text_format">
{foreach from=$property.ml_text_format_options item="format"}
      <option value="{$format.id}"{if $format.id == $property.ml_text_format} selected="selected"{/if}>{$format.label}</option>
{/foreach}
    </select>
    <div class="form-hint">Plain text preserves the existing multi-line text behaviour. Markdown and Textile are parsed when this property is rendered.</div>
  </div>

{/if}
  
{if $property._type_info.valuetype != 'manytomany' && $property._type_info.valuetype != 'auto'}

  <script type="text/javascript">
  {literal}
  function toggleDefaultValueField(useDefault){
    if($('itemproperty-default-value-row')){
      if(useDefault){
        $('itemproperty-default-value-row').show();
      }else{
        $('itemproperty-default-value-row').hide();
      }
    }
  }
  {/literal}
  </script>

  <div class="edit-form-row">
    <div class="form-section-label">Use default value</div>
    {boolean name="itemproperty_use_default_value" id="itemproperty-use-default-value" value=$property.use_default_value changehook="toggleDefaultValueField"}
    <div class="form-hint">When enabled, this value is pre-filled on new item forms. Turning it off keeps the saved default here without applying it automatically.</div>
  </div>

  <div class="edit-form-row" id="itemproperty-default-value-row" style="display:{if $property.use_default_value}block{else}none{/if}">
    <div class="form-section-label">Default value</div>
    {item_field property=$property value=$property.stored_default_value name="itemproperty_default_value"}
  </div>
  
{/if}
    
    <div class="edit-form-row">
        <div class="form-section-label">Required</div>
        {boolean name="itemproperty_required" id="is-required" value=$property.required}
        <div class="form-hint">Making a property required means the item cannot be published unless a value has been entered</div>
    </div>
    
    <div class="edit-form-row">
        <div class="buttons-bar">
            <input type="button" value="Cancel" onclick="cancelForm();" />
            <input type="submit" value="Save Changes" />
        </div>
    </div>

  </form>

</div>

<div id="actions-area">
  
</div>
