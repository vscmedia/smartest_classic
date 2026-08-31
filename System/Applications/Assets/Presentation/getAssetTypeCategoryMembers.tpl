<script type="text/javascript">
  var assets = new Smartest.UI.OptionSet('pageViewForm', 'item_id_input', 'option', 'options_grid');
</script>

<div id="work-area">

  <h3>{$category_label}</h3>

<form id="pageViewForm" method="get" action="">
  <input type="hidden" name="asset_category" value="{$category_code}" />
  <input type="hidden" name="asset_id" id="item_id_input" value="" />
</form>

{if $num_assets < 1 && $mode != 2}
<div class="special-box">There are no {$category_label|strtolower} files yet.</div>
{else}
<div class="special-box">
  <form id="mode-form" method="get" action="">
    <input type="hidden" name="asset_category" value="{$category_code}" />
    <div class="special-box-key">Only show: </div><select name="mode" onchange="$('mode-form').submit();">
      <option value="1"{if $mode == 1} selected="selected"{/if}>{$category_label} files not in archive</option>
      <option value="0"{if $mode == 0} selected="selected"{/if}>All {$category_label} files</option>
      <option value="2"{if $mode == 2} selected="selected"{/if}>Archived {$category_label} files</option>
    </select>
  </form>
</div>

<div id="options-view-header">

  <div id="options-view-info">
    Found {$num_assets} file{if $num_assets != 1}s{/if}.
  </div>

  <div id="options-view-chooser">
    <a href="#list-view" onclick="return assets.setView('list', 'asset_list_style')" id="options-view-list-button" class="{if $list_view == "list"}on{else}off{/if}"></a>
    <a href="#grid-view" onclick="return assets.setView('grid', 'asset_list_style')" id="options-view-grid-button" class="{if $list_view == "grid"}on{else}off{/if}"></a>
  </div>

  <div class="breaker"></div>

</div>

<ul class="options-{$list_view}{if $category_code == "image"} images{/if}" style="margin-top:0px" id="options_grid">
{foreach from=$assets item="asset"}
{assign var=selected_action_type value="noneditableasset"}
{if $asset.is_editable}
  {assign var=selected_action_type value="editableasset"}
  {if isset($asset.type_info.source_editable) && $asset.type_info.source_editable}
    {assign var=selected_action_type value="sourceeditableasset"}
  {/if}
{/if}
<li>
    <a href="#select-file" class="option" id="{$selected_action_type}_{$asset.id}" onclick="return assets.setSelectedItem('{$asset.id}', '{$selected_action_type}');" ondblclick="assets.workWithItem('editAsset');">

{if $asset.is_binary_image}
    <img border="0" src="{$asset.image._ui_preview.web_path}" class="grid" />
{elseif $asset.type == "SM_ASSETTYPE_SVG_IMAGE" && $asset.web_path}
    <img border="0" src="{$asset.web_path}" class="grid" />
{elseif isset($asset.type_info.large_icon) && $asset.type_info.large_icon}
    <img border="0" src="{$domain}Resources/System/Images/{$asset.type_info.large_icon}" class="grid" />
{else}
    <img border="0" src="{$domain}Resources/Icons/blank_page.png" class="grid" />
{/if}

{if $asset.is_binary_image}
    <img border="0" src="{$asset.image.16x16.web_path}" class="list" />
{elseif $asset.type == "SM_ASSETTYPE_SVG_IMAGE" && $asset.web_path}
    <img border="0" src="{$asset.web_path}" class="list" />
{elseif $asset.small_icon}
    <img border="0" src="{$asset.small_icon}" class="list" />
{else}
    <img border="0" src="{$domain}Resources/Icons/blank_page.png" class="list" />
{/if}

<span class="asset label">{$asset.label}</span>
<span class="form-hint">{$asset.type_info.label}</span></a>

</li>

{/foreach}
</ul>
{/if}

</div>

<div id="actions-area">

<ul class="actions-list" id="noneditableasset-specific-actions" style="display:none">
  <li><b>Selected file</b></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('assetInfo');" class="right-nav-link"><i class="fa fa-info-circle"></i> About this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('addTodoItem');" class="right-nav-link"><i class="fa fa-check"></i> Add a new to-do</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('previewAsset');"><i class="fa fa-eye"></i> Preview this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('toggleAssetArchived');" class="right-nav-link"><i class="fa fa-folder-o"></i> Archive/unarchive this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('deleteAssetConfirm');" class="right-nav-link"><i class="fa fa-trash-o"></i> Delete this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('duplicateAsset');" class="right-nav-link"><i class="fa fa-clone"></i> Duplicate this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('downloadAsset');" class="right-nav-link"><i class="fa fa-download"></i> Download this file</a></li>
</ul>

<ul class="actions-list" id="editableasset-specific-actions" style="display:none">
  <li><b>Selected file</b></li>
  <li class="permanent-action"><a href="#" onclick="assets.workWithItem('assetInfo');" class="right-nav-link"><i class="fa fa-info-circle"></i> About this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('editAsset');" class="right-nav-link"><i class="fa fa-pencil"></i> Edit this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('previewAsset');"><i class="fa fa-eye"></i> Preview this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('addTodoItem');" class="right-nav-link"><i class="fa fa-check"></i> Add a new to-do</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('toggleAssetArchived');" class="right-nav-link"><i class="fa fa-folder-o"></i> Archive/unarchive this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('deleteAssetConfirm');" class="right-nav-link"><i class="fa fa-trash-o"></i> Delete this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('duplicateAsset');" class="right-nav-link"><i class="fa fa-clone"></i> Duplicate this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('downloadAsset');" class="right-nav-link"><i class="fa fa-download"></i> Download this file</a></li>
</ul>

<ul class="actions-list" id="sourceeditableasset-specific-actions" style="display:none">
  <li><b>Selected file</b></li>
  <li class="permanent-action"><a href="#" onclick="assets.workWithItem('assetInfo');" class="right-nav-link"><i class="fa fa-info-circle"></i> About this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('editAsset');" class="right-nav-link"><i class="fa fa-pencil"></i> Edit this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('previewAsset');"><i class="fa fa-eye"></i> Preview this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('editTextFragmentSource');" class="right-nav-link"><i class="fa fa-pencil-square-o"></i> Edit file source</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('addTodoItem');" class="right-nav-link"><i class="fa fa-check"></i> Add a new to-do</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('toggleAssetArchived');" class="right-nav-link"><i class="fa fa-folder-o"></i> Archive/unarchive this file...</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('deleteAssetConfirm');" class="right-nav-link"><i class="fa fa-trash-o"></i> Delete this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('duplicateAsset');" class="right-nav-link"><i class="fa fa-clone"></i> Duplicate this file</a></li>
	<li class="permanent-action"><a href="#" onclick="assets.workWithItem('downloadAsset');" class="right-nav-link"><i class="fa fa-download"></i> Download this file</a></li>
</ul>

<ul class="actions-list" id="non-specific-actions">
  <li><b>{$_l10n_strings.general.general_options_label}</b></li>
	<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/files/categories'" class="right-nav-link"><i class="fa fa-files-o"></i> View all file categories</a></li>
	<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/assets'" class="right-nav-link"><i class="fa fa-list"></i> View all files by type</a></li>
</ul>

{if count($asset_types)}
<ul class="actions-list" id="category-types-actions">
  <li><span style="color:#999">Add {$category_label|strtolower} file</span></li>
  {foreach from=$asset_types item="asset_type"}
  <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/addAsset?asset_type={$asset_type.id}'"><i class="fa fa-{if isset($asset_type.fa_iconname) && $asset_type.fa_iconname}{$asset_type.fa_iconname}{else}file-o{/if}"></i> {$asset_type.label}</a></li>
  {/foreach}
</ul>
{/if}

<ul class="actions-list" id="recent-files-actions">
  <li><span style="color:#999">Recent {$category_label|strtolower} files</span></li>
  {foreach from=$recent_assets item="recent_asset"}
  <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$recent_asset.action_url}'"><i class="fa fa-{if isset($recent_asset.type_info.fa_iconname) && $recent_asset.type_info.fa_iconname}{$recent_asset.type_info.fa_iconname}{else}file-o{/if}"></i> {$recent_asset.label|summary:"30"}</a></li>
  {/foreach}
</ul>

</div>
