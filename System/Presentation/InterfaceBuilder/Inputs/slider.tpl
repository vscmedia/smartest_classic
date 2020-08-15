<div id="track-{$_slider_input_data.varname}" class="sm-numeric-slider">
	<div id="handle-{$_slider_input_data.varname}" class="sm-numeric-slider-handle"></div>
</div>

<p id="value-{$_slider_input_data.varname}" style="display:inline-block;margin:0px">{$_slider_input_data.value}{$_slider_input_data.value_unit}</p>

<input type="hidden" id="{$_slider_input_data.varname}" name="{$_slider_input_data.name}" value="{$_slider_input_data.value}" />

<script type="text/javascript" language="javascript">
// <![CDATA[
		
var slider_{$_slider_input_data.varname} = {literal}new Control.Slider('handle-{/literal}{$_slider_input_data.varname}', 'track-{$_slider_input_data.varname}', {literal}{
	onSlide: function(v) {
    $('value-{/literal}{$_slider_input_data.varname}').innerHTML = Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum})) + '{$_slider_input_data.value_unit}';
    $('{$_slider_input_data.varname}').value = Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum}));
    {if $_slider_input_data.slidehook}{$_slider_input_data.slidehook}(Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum})), '{$_slider_input_data.varname}');{/if}
    
  {literal}},
	onChange: function(v) {
    $('value-{/literal}{$_slider_input_data.varname}').innerHTML = Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum})) + '{$_slider_input_data.value_unit}';
    $('{$_slider_input_data.varname}').value = Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum}));
    {if $_slider_input_data.changehook}{$_slider_input_data.changehook}(Math.round({$_slider_input_data.minimum} + v * ({$_slider_input_data.maximum} - {$_slider_input_data.minimum})), '{$_slider_input_data.varname}');{/if}
    
    {literal}}
});{/literal}

slider_{$_slider_input_data.varname}.setValue({$_slider_input_data.js_value});

// ]]>
</script>