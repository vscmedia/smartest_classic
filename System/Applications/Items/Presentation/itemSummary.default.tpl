<div id="sets" class="special-box">
     Sets: {if count($sets)}{foreach from=$sets item="set"}<a href="{$domain}sets/previewSet?set_id={$set.id}&amp;from=editItem&amp;item_id={$item.id}">{$set.label}</a> <a href="{$domain}sets/transferSingleItem?item_id={$item.id}&amp;set_id={$set.id}&amp;transferAction=remove&amp;returnTo=editItem&amp;from=editItem&amp;item_id={$item.id}{if $current_page}&amp;page_id={$current_page.webid}{/if}" class="button">remove</a> {/foreach}{else}<em style="color:#666">None</em> <a href="{$domain}sets/addSet?class_id={$item._model.id}&amp;add_item={$item.id}" class="button small">Create one</a>{/if}
{if count($possible_sets)}
       <div>
         <form action="{$domain}sets/transferSingleItem" method="post">
           <input type="hidden" name="item_id" value="{$item.id}" />
           <input type="hidden" name="transferAction" value="add" />
           <input type="hidden" name="returnTo" value="editItem" />
           {if $current_page}<input type="hidden" name="page_id" value="{$current_page.id}" />{/if}
           Add this item to set:
           <select name="set_id">
{foreach from=$possible_sets item="possible_set"}
             <option value="{$possible_set.id}">{$possible_set.label}</option>
{/foreach}
           </select>
           <input type="submit" value="Go" />
         </form>
       </div>
{/if}
</div>

{if $item.has_metapage && count($metapages)}

<div class="special-box">
  {if $item.public == 'TRUE'}
  The primary web address for this {$item._model.name|strtolower} is <a href="{$item.absolute_url}" target="_blank">{$item.absolute_url}</a>
  {else}
  The primary web address for this {$item._model.name|strtolower} will be <strong>{$item.absolute_url}</strong>
  {/if} <a href="{$domain}websitemanager/editPage?page_id={$item._meta_page.webid}&amp;item_id={$item.id}#urls" class="button small">Edit URL structure</a>
</div>

{/if}

{if $item.has_metapage || $allow_edit_item_slug}

<div class="edit-form-row{if $allow_edit_item_slug && $item.public == "TRUE" && count($metapages)} warning{/if}">
  <div class="form-section-label">{$item._model.name} short name (Used in links and URLS)</div>
  {if $allow_edit_item_slug}
  <input type="text" name="item_slug" value="{$item.slug}" /><div class="form-hint">Numbers, lowercase letters and hyphens only, please</div>
  {else}
  <code>{$item.slug}<code>
  {/if}
  {if $allow_edit_item_slug && $item.public == "TRUE" && count($metapages)}<div class="edit-form-sub-row">Warning: This {$item._model.name|strtolower} is live. Editing its short name may cause links to it to break.</div>{/if}
</div>

{/if}

<div class="edit-form-row">
  <div class="form-section-label">Current status</div>
  {if $item.public == "TRUE"}
    Live <a class="button" href="#publish" onclick="$('sm-form-submit-action').value='publish';$('edit-item-form').submit();return false;">Re-Publish</a>&nbsp;<a class="button" href="#un-publish" onclick="window.location='{$domain}{$section}/unpublishItem?item_id={$item.id}{if $request_parameters.page_id}&amp;page_id={$request_parameters.page_id}{/if}';">Un-publish</a>
  {else}
    Not live <a class="button" href="#publish" onclick="window.location='{$domain}{$section}/publishItem?item_id={$item.id}';">Publish</a>
  {/if}
</div>

{foreach from=$item._editable_properties key="pid" item="property"}
  <div class="edit-form-row">
    <div class="form-section-label"><strong>{$property.name}</strong></div>
    {* $item[$pid] *}
    {item_field_preview property=$property value=$item[$pid]}
  </div>
{/foreach}

  <div class="edit-form-row">
    <div class="form-section-label">Tags</div>
    <div class="edit-form-sub-row">
      <ul class="checkbox-array-list" id="item-tags-list">
{foreach from=$item.tags item="tag"}
        <li data-tagid="{$tag.id}"><label>{$tag.label}</label></li>
{/foreach}
      </ul>
      <span class="null-notice" id="no-tags-notice"{if count($item.tags)} style="display:none"{/if}>No tags attached to this item</span>
    </div>
    
  </div>

<div class="edit-form-row">
  <div class="form-section-label">Language</div>
  {$item.language}
</div>