    <textarea name="{$_input_data.name}" style="{$_input_data.style}" placeholder="{$_input_data.placeholder}" id="{$_input_data.id}"{if $_input_data.limit} maxlength="{$_input_data.limit}"{/if}>{$_input_data.value.html_escape}</textarea>{if $_input_data.limit} <span id="{$_input_data.id}-charcount" class="length-warn {$_input_data.limit_warning_class}">{$_input_data.value.length}</span>/{$_input_data.limit}{/if}

{if $_input_data.show_hint}    <div class="form-hint">{$_input_data.form_hint}</div>{/if}
{if $_input_data.word_count}    <div class="form-hint"><span id="{$_input_data.id}-wordcount">{$_input_data.value.wordcount}</span> words, <span id="{$_input_data.id}-paracount">{$_input_data.value.paracount}</span> paragraphs</div>{/if}

{if $_input_data.limit}
<script type="text/javascript">
{literal}(function(textAreaId, limit){
  var threshold = Math.floor(0.8 * limit);
  $(textAreaId).observe('keyup', function(){
    $(textAreaId+'-charcount').update($(textAreaId).textLength);
    if($(textAreaId).textLength < threshold){
      // cool
      $(textAreaId+'-charcount').removeClassName('warning');
      $(textAreaId+'-charcount').removeClassName('invalid');
      $(textAreaId).removeClassName('invalid');
    }else if($(textAreaId).textLength >= threshold && $(textAreaId).textLength < limit){
      // warning
      $(textAreaId+'-charcount').addClassName('warning');
      $(textAreaId+'-charcount').removeClassName('invalid');
      $(textAreaId).removeClassName('invalid');
    }else if($(textAreaId).textLength >= limit){
      // invalid
      $(textAreaId+'-charcount').removeClassName('warning');
      $(textAreaId+'-charcount').addClassName('invalid');
      $(textAreaId).addClassName('invalid');
    }
  });
}){/literal}('{$_input_data.id}', {$_input_data.limit})
</script>
{/if}
{if $_input_data.word_count}
<script type="text/javascript">
{literal}(function(textAreaId){
  $(textAreaId).observe('keyup', function(){
    $(textAreaId+'-wordcount').update($F(textAreaId).split(/[\n\s]+/).size());
    $(textAreaId+'-paracount').update($F(textAreaId).split(/[\r\n]+/).filter(function(v){return v.match(/[\w]+/)}).size());
  });
}){/literal}('{$_input_data.id}')
</script>
{/if}