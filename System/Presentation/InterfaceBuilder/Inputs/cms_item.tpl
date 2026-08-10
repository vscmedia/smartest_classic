<div id="{$_input_data.id}-container{if $_input_data.show_new_field}-{$_input_data.host_item_id}{/if}">
  <select name="{$_input_data.name}" id="{$_input_data.id}">
      {if !$_input_data.required}<option value="0"></option>{/if}
  {foreach from=$_input_data.options item="foreign_item"}
      <option value="{$foreign_item.id}"{if $_input_data.value.id==$foreign_item.id} selected="selected"{/if}>{$foreign_item.name}{if $foreign_item.deleted} (DELETED){/if}</option>
  {/foreach}
      {if $_input_data.show_new_field}<option value="NEW">New...</option>{/if}
  </select>
  {if $_input_data.show_new_field}
  <input type="hidden" value="{$_input_data.id}" id="{$_input_data.id}-non-new-value" />
  <a href="#new-item" id="{$_input_data.id}-new-button" class="button round"><i class="fa fa-plus"></i></a>
  <span id="{$_input_data.id}-container-{$_input_data.host_item_id}-loading"></span>
  {/if}
  
  {if $_input_data.show_new_field}<div id="{$_input_data.id}-new-item-form-holder" style="padding-top:5px;display:none" class="edit-form-sub-row">
    <input type="text" name="{$_input_data.id}_new_item_name" id="{$_input_data.id}-new-item-name" />
    <input type="button" id="{$_input_data.id}-new-item-save-button" value="Save" disabled="disabled" />
    <input type="button" id="{$_input_data.id}-new-item-cancel-button" value="Cancel" />
  </div>{/if}
  
</div>

{if $_input_data.show_new_field}

<script type="text/javascript">

{literal}(function(ID, propertyID, hostItemId){
  
  $(ID).observe('change', function(e){
    if(this.value == 'NEW'){
      $(ID+'-new-item-form-holder').show();
      $(ID+'-new-item-name').activate();
    }else{
      $(ID+'-non-new-value').value = this.value;
      $(ID+'-new-item-form-holder').hide();
    }
  });
  
  $(ID+'-new-button').observe('click', function(e){
    $(ID+'-non-new-value').value = $(ID).value;
    $(ID).value = 'NEW';
    $(ID+'-new-item-form-holder').show();
    $(ID+'-new-item-name').activate();
  });
  
  $(ID+'-new-item-save-button').observe('click', function(){
    // submit
    new Smartest.IPVItemCreator({name: $(ID+'-new-item-name').value, property_id: propertyID, host_item_id: hostItemId});
  });
  
  $(ID+'-new-item-cancel-button').observe('click', function(){
    // cancel
    $(ID+'-new-item-form-holder').hide();
    $(ID+'-new-item-name').blur();
    $(ID).value = $(ID+'-non-new-value').value;
  });
  
  $(ID+'-new-item-name').observe('keyup', function(e){
    
    if(this.value.charAt(1)){
      $(ID+'-new-item-save-button').disabled = false;
    }else{
      $(ID+'-new-item-save-button').disabled = true;
    }
      
  });
  
  $(ID+'-new-item-name').observe('keypress', function(e){
    
    if(e.keyCode == 13){
      if(this.value.charAt(1)){
        // submit
        new Smartest.IPVItemCreator({name: $(ID+'-new-item-name').value, property_id: propertyID, host_item_id: hostItemId});
        e.stop();
      }else{
        // do nothing
        e.stop();
      }
    }
  });
  
}){/literal}('{$_input_data.id}', '{$_input_data.property_id}', '{$_input_data.host_item_id}');
  
</script>{/if}