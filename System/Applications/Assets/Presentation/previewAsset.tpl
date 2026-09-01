<div id="work-area">
    {load_interface file="edit_asset_tabs.tpl"}
 <h3>Preview of file <span class="light">&lsquo;{$asset.label}&rsquo;</span></h3>
 {if $preview_not_available}
 <div class="warning">This image format cannot be viewed in a web browser.</div>
 {if $asset.is_heic_source}
   {if $generated_jpeg_version}
   <div class="special-box">A JPEG version of this file, which can be viewed, has been generated: <a href="{$domain}{$section}/assetInfo?asset_id={$generated_jpeg_version.id}">{$generated_jpeg_version.label}</a>.</div>
   {elseif $heic_temporary_preview_url}
   <div class="instruction">{$heic_preview_message}</div>
   <div id="html-preview">
    <img src="{$domain}{$heic_temporary_preview_url}" alt="Temporary preview of {$asset.label}" style="max-width:100%;height:auto" />
    <div class="breaker"></div>
   </div>
   {if $asset.can_convert_to_jpeg}
   <form action="{$domain}smartest/file/{$asset.id}/convert-to-jpeg" method="post" class="buttons-bar">
    <input type="hidden" name="set_heic_thumbnail" value="0" />
    <label><input type="checkbox" name="set_heic_thumbnail" value="1" checked="checked" /> Use the JPEG as this HEIC file's thumbnail</label>
    <input type="submit" value="Create JPEG version" />
   </form>
   {/if}
   {elseif $asset.can_convert_to_jpeg}
   {if $heic_preview_message}<div class="warning">{$heic_preview_message}</div>{/if}
   <div class="special-box">
    A JPEG version can be created from this HEIC file.
    <form action="{$domain}smartest/file/{$asset.id}/convert-to-jpeg" method="post" style="display:inline-block;margin:8px 0 0 0">
     <input type="hidden" name="set_heic_thumbnail" value="0" />
     <label><input type="checkbox" name="set_heic_thumbnail" value="1" checked="checked" /> Use the JPEG as this HEIC file's thumbnail</label>
     <input type="submit" value="Create JPEG version" />
    </form>
   </div>
   {elseif $heic_preview_message}
   <div class="warning">{$heic_preview_message}</div>
   {/if}
 {/if}
 {else}
 <div class="instruction">The file will look something like this:</div>
 <div id="html-preview">
  {$html}
  <div class="breaker"></div>
 </div>
 {/if}
 <div class="buttons-bar"><input type="button" id="done-button" value="{$_l10n_global_strings.system_wide_buttons.done}" /><script type="text/javascript">{literal}$('done-button').observe('click', cancelForm);{/literal}</script></div>
</div>

<div id="actions-area">
  <ul class="actions-list" id="non-specific-actions">
    <li><b>Options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/assetInfo?asset_type={$asset_type.id}&amp;asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/information.png" alt=""/> About this file</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><img src="{$domain}Resources/Icons/pencil.png" alt=""/> Edit This File</a></li>
    {if $allow_source_edit}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editTextFragmentSource?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><img src="{$domain}Resources/Icons/page_edit.png" alt=""/> Edit This File's Source</a></li>{/if}
    {if $show_attachments}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/textFragmentElements?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><img src="{$domain}Resources/Icons/attach.png" alt=""/> Edit File Attachments</a></li>{/if}
    {if $allow_approve}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/approveAsset?asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><img src="{$domain}Resources/Icons/tick.png" alt=""/> Approve this file</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/addTodoItem?asset_id={$asset.id}{if $request_parameters.from}&amp;from={$request_parameters.from}{/if}'"><img src="{$domain}Resources/Icons/tick.png" alt=""/> Add a new to-do for this file</a></li>
    {if $show_publish}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishTextAsset?assettype_code={$asset_type.id}&amp;asset_id={$asset.id}'"><img src="{$domain}Resources/Icons/page_lightning.png" alt=""/> Publish This Text</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getAssetTypeMembers?asset_type={$asset_type.id}'"><img src="{$domain}Resources/Icons/folder_old.png" alt=""/> View all {$asset_type.label} files</a></li>
  </ul>
</div>
