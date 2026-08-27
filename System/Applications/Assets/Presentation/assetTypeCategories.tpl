<div id="work-area">

  <h3>File categories</h3>

  {if count($locations)}
  <div class="warning">
    <p>{$_l10n_strings.warnings.storage_locations_unwriteable}</p>
    <ul class="location-list">
{foreach from=$locations item="l"}
      <li><i class="fa fa-folder"></i> <code>{$l}</code></li>
{/foreach}
    </ul>
    {help id="desktop:permissions"}Tell me more{/help}
  </div>
  {/if}

  {load_interface file="file_browse_tabs.tpl"}

  <ul class="apps">
{foreach from=$assetTypeCategories item="assetTypeCategory"}
    <li>
      <a class="icon" href="{$domain}smartest/files/category/{$assetTypeCategory.short_name}"><i class="fa fa-{if isset($assetTypeCategory.fa_iconname) && $assetTypeCategory.fa_iconname}{$assetTypeCategory.fa_iconname}{else}folder-open-o{/if}"></i></a>
      <a class="label" href="{$domain}smartest/files/category/{$assetTypeCategory.short_name}">{$assetTypeCategory.l10n_label}</a>
    </li>
{/foreach}
  </ul>

</div>

<div id="actions-area">
  
  {load_interface file="assets_front_sidebar.tpl"}
  
  <ul class="actions-list" id="non-specific-actions">
    <li><span style="color:#999">{$_l10n_strings.general.recently_edited_label}</span></li>
    {foreach from=$recent_assets item="recent_asset"}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$recent_asset.action_url}'"><i class="fa fa-{if isset($recent_asset.type_info.fa_iconname) && $recent_asset.type_info.fa_iconname}{$recent_asset.type_info.fa_iconname}{else}file-o{/if}"></i> {$recent_asset.label|summary:"30"}</a></li>
    {/foreach}
  </ul>

</div>
