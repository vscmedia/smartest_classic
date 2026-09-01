<div id="work-area">
  
  {load_interface file="edit_asset_tabs.tpl"}
  <h3>{$_l10n_action_strings.main_h3.before_filename} <span class="light">{$asset.label}</span> {$_l10n_action_strings.main_h3.after_filename}</h3>
  
  {if $asset.deleted}<div class="warning">{$_l10n_action_strings.in_trash_warning.before_filetype} {$asset.type_info.label} {$_l10n_action_strings.in_trash_warning.after_filetype}</div>{/if}
  
  {if $asset.type_info.storage.type != "external_translated"}
    {if !$file_is_writable && $asset.type_info.editable}
      <div class="warning">This file is not currently writable by the web server, so it cannot be edited directly in Smartest.</div>
    {elseif !$dir_is_writable}
      <div class="warning">The directory where this file is stored is not currently writable by the web server, so this file cannot be edited directly in Smartest.</div>
    {/if}
  {/if}
  
  {if $asset.is_heic_source}
  <div class="warning">
    This image format can't be displayed on web pages.
    {if $asset.generated_jpeg_version}
      A JPEG version has been generated: <a href="{$domain}{$section}/editAsset?asset_id={$asset.generated_jpeg_version.id}">{$asset.generated_jpeg_version.label}</a>.
    {elseif $asset.can_convert_to_jpeg}
      <form action="{$domain}smartest/file/{$asset.id}/convert-to-jpeg" method="post" style="display:inline-block;margin:8px 0 0 0">
        <input type="hidden" name="set_heic_thumbnail" value="0" />
        <label><input type="checkbox" name="set_heic_thumbnail" value="1" checked="checked" /> Use the JPEG as this HEIC file's thumbnail</label>
        <input type="submit" value="Convert it to JPEG" class="button" />
      </form>
    {else}
      HEIC conversion is not currently available on this server.
    {/if}
  </div>
  {/if}

  {if $asset.can_create_downscaled_derivative}
  <div class="warning">At {$asset.dimensions} pixels and {$asset.size}, this image is very large for normal web use. <a href="#resize-image" onclick="MODALS.load('assets/resizeImageAsset?asset_id={$asset.id}', 'Create smaller image'); return false;" class="button">Create a smaller version</a></div>
  {/if}

  {if $asset.parent_asset}
  <div class="special-box">This file was generated from <a href="{$domain}{$section}/editAsset?asset_id={$asset.parent_asset.id}">{$asset.parent_asset.label}</a> ({$asset.parent_asset.type_info.label}){if $asset.derivation_summary}: {$asset.derivation_summary}{/if}.</div>
  {/if}

  {if $asset.has_derived_assets}
  <div class="special-box">Derived files: {foreach from=$asset.derived_assets item="derived_asset"}<a href="{$domain}{$section}/editAsset?asset_id={$derived_asset.id}">{$derived_asset.label}</a>{if !$derived_asset@last}, {/if}{/foreach}</div>
  {/if}
  
  <div class="instruction">{$_l10n_action_strings.you_are_editing.before_file} {$asset.type_info.label}: <code>{$asset.type_info.storage.location}</code><strong><code>{$asset.url}</code></strong> {$_l10n_action_strings.you_are_editing.after_file} </div>
  
  <div id="groups" class="special-box">
    {$_l10n_strings.file_groups_box.label} {if count($groups)}{foreach from=$groups item="group"}<a href="{$domain}{$section}/browseAssetGroup?group_id={$group.id}">{$group.label}</a> <a href="{$domain}{$section}/transferSingleAsset?asset_id={$asset.id}&amp;group_id={$group.id}&amp;transferAction=remove&amp;from=edit" class="button">{$_l10n_strings.file_groups_box.remove}</a> {/foreach}{else}<em style="color:#666">{$_l10n_strings.file_groups_box.none}</em>{/if}
{if count($possible_groups)}
        <div>
          <form action="{$domain}{$section}/transferSingleAsset" method="post">
            
            <input type="hidden" name="asset_id" value="{$asset.id}" />
            <input type="hidden" name="transferAction" value="add" />
            <input type="hidden" name="from" value="edit" />
            
            Add this file to group:
            <select name="group_id">
{foreach from=$possible_groups item="possible_group"}
              <option value="{$possible_group.id}">{$possible_group.label}</option>
{/foreach}
            </select>
            <input type="submit" value="Go" />
          </form>
        </div>
{/if}
  </div>
  
{load_interface file=$formTemplateInclude}

</div>

<div id="actions-area">
  <ul class="actions-list" id="non-specific-actions">
    <li><b>File options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/assetInfo?asset_type={$asset_type.id}&amp;asset_id={$asset.id}'"><i class="fa fa-info-circle"></i> {$_l10n_strings.sidebar_options.file_info}</a></li>
    {if $allow_source_edit}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editTextFragmentSource?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><i class="fa fa-file-code-o"></i> Edit file source</a></li>{/if}
    {if $show_attachments}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/textFragmentElements?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><i class="fa fa-paperclip"></i> Edit file attachments</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/previewAsset?asset_id={$asset.id}'"><i class="fa fa-eye"></i> Preview This File</a></li>
    {if $asset.is_heic_source && $asset.generated_jpeg_version}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editAsset?asset_id={$asset.generated_jpeg_version.id}'"><i class="fa fa-file-image-o"></i> View JPEG version</a></li>{elseif $asset.is_heic_source && $asset.can_convert_to_jpeg}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/file/{$asset.id}/convert-to-jpeg'"><i class="fa fa-exchange"></i> Convert to JPEG</a></li>{/if}
    {if $asset.can_create_downscaled_derivative}<li class="permanent-action"><a href="{dud_link}" onclick="MODALS.load('assets/resizeImageAsset?asset_id={$asset.id}', 'Create smaller image'); return false;"><i class="fa fa-compress"></i> Create smaller version</a></li>{/if}
    {if $show_publish}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishTextAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}'"><i class="fa fa-globe"></i> Publish This File</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getAssetTypeMembers?asset_type={$asset_type.id}'"><i class="fa fa-folder-open-o"></i> View all {$asset_type.label} files</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}smartest/assets'" class="right-nav-link"><i class="fa fa-files-o"></i> View all files by type</a></li>
  </ul>

  {load_interface file="related_template_usage.tpl"}

  <ul class="actions-list" id="non-specific-actions">
    <li><span style="color:#999">Recent {$asset.type_info.label|strtolower} files</span></li>
    {foreach from=$recent_assets item="recent_asset"}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$recent_asset.action_url}'"><i class="fa fa-{$recent_asset.type_info.fa_iconname}"></i> {$recent_asset.label|summary:"30"}</a></li>
    {/foreach}
  </ul>
</div>
