{if $has_unwritable_locations}
<div class="warning">
  <p>This Build Kit needs to write files to locations that are not currently writable:</p>
  <ul class="location-list">
    {foreach from=$unwritable_locations item="l"}
    <li><i class="fa fa-folder"></i> <code>{$l}</code></li>
    {/foreach}
  </ul>
</div>
{/if}

{if $has_general_configuration_options}<div class="form-section-label-full">General options</div>{/if}
{foreach from=$general_configuration_options item="config_option"}
<div class="edit-form-row">
  <div class="form-section-label">{$config_option.label}</div>
  {if $config_option.datatype == 'SM_DATATYPE_BOOLEAN'}
  {boolean name=$config_option.form_name id=$config_option.form_unique_id value=$config_option.default}
  {else}
    {if $config_option.has_options}
    <select name="{$config_option.form_name}">
      {foreach from=$config_option.options item="opt" key="key"}
      <option value="{$key}"{if $config_option.default == $key} selected="selected"{/if}>{$opt}</option>
      {/foreach}
    </select>
    {else}
    <input type="text" name="{$config_option.form_name}" value="{$config_option.default}" />
    {/if}
  {/if}
  {if $config_option.hint}<div class="form-hint">{$config_option.hint}</div>{/if}
</div>
{/foreach}

{if $buildkit_datastructure_available}
{if $buildkit_datastructure_configurable || !$buildkit_datastructure_required}<div class="form-section-label-full">Data structures</div>{/if}
{if !$buildkit_datastructure_required}
<div class="edit-form-row">
  <div class="form-section-label">Create models, sets and dropdown menus</div>
  {boolean name="execute_optional[data_structures]" id="execute-data-structures" value="TRUE" changehook="toggleDataStructureConfiguration"}
</div>
{/if}
{if $buildkit_datastructure_configurable}
<div id="datastructure-options">
  {foreach from=$buildkit_datastructure_options item="config_option"}
  <div class="edit-form-row">
    <div class="form-section-label">{$config_option.label}</div>
    {if $config_option.datatype == 'SM_DATATYPE_BOOLEAN'}
    {boolean name=$config_option.form_name id=$config_option.form_unique_id value=$config_option.default}
    {else}
      {if $config_option.has_options}
      <select name="{$config_option.form_name}">
        {foreach from=$config_option.options item="opt" key="key"}
        <option value="{$key}"{if $config_option.default == $key} selected="selected"{/if}>{$opt}</option>
        {/foreach}
      </select>
      {else}
      <input type="text" name="{$config_option.form_name}" value="{$config_option.default}" />
      {/if}
    {/if}
    {if $config_option.hint}<div class="form-hint">{$config_option.hint}</div>{/if}
  </div>
  {/foreach}
</div>
{/if}
{/if}

{if $buildkit_pagestructure_available}
{if $buildkit_pagestructure_configurable || !$buildkit_pagestructure_required}<div class="form-section-label-full">Page structures</div>{/if}
{if !$buildkit_pagestructure_required}
<div class="edit-form-row">
  <div class="form-section-label">Create pages and site structure</div>
  {boolean name="execute_optional[page_structures]" id="execute-page-structures" value="TRUE" changehook="togglePageStructureConfiguration"}
</div>
{/if}
{if $buildkit_pagestructure_configurable}
<div id="pagestructure-options">
  {foreach from=$buildkit_pagestructure_options item="config_option"}
  <div class="edit-form-row">
    <div class="form-section-label">{$config_option.label}</div>
    {if $config_option.datatype == 'SM_DATATYPE_BOOLEAN'}
    {boolean name=$config_option.form_name id=$config_option.form_unique_id value=$config_option.default}
    {else}
      {if $config_option.has_options}
      <select name="{$config_option.form_name}">
        {foreach from=$config_option.options item="opt" key="key"}
        <option value="{$key}"{if $config_option.default == $key} selected="selected"{/if}>{$opt}</option>
        {/foreach}
      </select>
      {else}
      <input type="text" name="{$config_option.form_name}" value="{$config_option.default}" />
      {/if}
    {/if}
    {if $config_option.hint}<div class="form-hint">{$config_option.hint}</div>{/if}
  </div>
  {/foreach}
</div>
{/if}
{/if}

{if $buildkit_templates_available}
{if $buildkit_templates_configurable || !$buildkit_templates_required}<div class="form-section-label-full">Templates, Javascript and essential CSS</div>{/if}
{if !$buildkit_templates_required}
<div class="edit-form-row">
  <div class="form-section-label">Use Build Kit templates</div>
  {boolean name="execute_optional[templates]" id="execute-templates" value="TRUE" changehook="toggleTemplatesConfiguration"}
</div>
{/if}
{if $buildkit_templates_configurable}
<div id="templates-options">
  {foreach from=$buildkit_templates_options item="config_option"}
  <div class="edit-form-row">
    <div class="form-section-label">{$config_option.label}</div>
    {if $config_option.datatype == 'SM_DATATYPE_BOOLEAN'}
    {boolean name=$config_option.form_name id=$config_option.form_unique_id value=$config_option.default}
    {else}
      {if $config_option.has_options}
      <select name="{$config_option.form_name}">
        {foreach from=$config_option.options item="opt" key="key"}
        <option value="{$key}"{if $config_option.default == $key} selected="selected"{/if}>{$opt}</option>
        {/foreach}
      </select>
      {else}
      <input type="text" name="{$config_option.form_name}" value="{$config_option.default}" />
      {/if}
    {/if}
    {if $config_option.hint}<div class="form-hint">{$config_option.hint}</div>{/if}
  </div>
  {/foreach}
</div>
{/if}
{/if}

{if $buildkit_content_available}
{if $buildkit_content_configurable || !$buildkit_content_required}<div class="form-section-label-full">Content and styling</div>{/if}
{if !$buildkit_content_required}
<div class="edit-form-row">
  <div class="form-section-label">Install Build Kit content</div>
  {boolean name="execute_optional[content]" id="execute-content" value="TRUE" changehook="toggleContentConfiguration"}
</div>
{/if}
{if $buildkit_content_configurable}
<div id="content-options">
  {foreach from=$buildkit_content_options item="config_option"}
  <div class="edit-form-row">
    <div class="form-section-label">{$config_option.label}</div>
    {if $config_option.datatype == 'SM_DATATYPE_BOOLEAN'}
    {boolean name=$config_option.form_name id=$config_option.form_unique_id value=$config_option.default}
    {else}
      {if $config_option.has_options}
      <select name="{$config_option.form_name}">
        {foreach from=$config_option.options item="opt" key="key"}
        <option value="{$key}"{if $config_option.default == $key} selected="selected"{/if}>{$opt}</option>
        {/foreach}
      </select>
      {else}
      <input type="text" name="{$config_option.form_name}" value="{$config_option.default}" />
      {/if}
    {/if}
    {if $config_option.hint}<div class="form-hint">{$config_option.hint}</div>{/if}
  </div>
  {/foreach}
</div>
{/if}
{/if}
