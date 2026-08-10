<div id="work-area">

{load_interface file="edit_tabs.tpl"}

<h3>{$item._model.name}: <span class="light" class="item-name-update">{$item.editor_name}</span></h3>

{if $item.deleted}<div class="warning">Warning: This {$item._model.name|strtolower} is currently in the trash.</div>{/if}

<div class="instruction">You are viewing the unpublished property values of the {$item._model.name|strtolower} &quot;<strong class="item-name-update">{$item.name}</strong>&quot; <a href="{$domain}{$section}/getItemClassMembers?class_id={$item._model.id}" class="button small">Back to {$item._model.plural_name|lower}</a></div>

{if $model_type == 'SM_ITEMCLASS_MT1_SUB_MODEL'}
<div class="instruction">This <strong>{$item._model|lower}</strong> is attached to the {$parent_model.name|lower} &ldquo;{$parent_item.name}&rdquo; <a href="{$parent_item.action_url}" class="button">View {$parent_model.name|lower}</a> <a href="{$domain}datamanager/getSubModelItems?item_id={$parent_item.id}&amp;sub_model_id={$item._model.id}" class="button">See all {$item._model.plural_name|lower} for this {$parent_model.name|lower}</a></div>
{/if}

{load_interface file=$item_summary_tpl}

</div>

<div id="actions-area">

  <ul class="actions-list" id="non-specific-actions">
    <li><b>This {$item._model.name}</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="MODALS.load('datamanager/itemInfo?item_id={$item.id}', '{$item._model.name} info', true);" class="right-nav-link"><i class="fa fa-info-circle"></i>&nbsp;About this {$item._model.name}</a></li>
    {if $model_type == 'SM_ITEMCLASS_MT1_SUB_MODEL'}<li class="permanent-action"><a href="#" onclick="window.location='{$domain}{$section}/editItem?item_id={$parent_item.id}';"><i class="fa fa-cube"></i>&nbsp;Back to {$parent_model.name}</a></li>{/if}
    {if $allow_edit}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/editItem?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-pencil"></i>&nbsp;Edit this {$item._model.name}</a></li>
    {/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/approveItem?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-check"></i>&nbsp;Approve changes</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/addTodoItem?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-share-square-o"></i>&nbsp;Assign To-do</a></li>
    {if $default_metapage_id}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/preview?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-eye"></i>&nbsp;Preview it</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/publishItem?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-globe"></i>&nbsp;Publish it</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/duplicateItem?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-clipboard"></i>&nbsp;Duplicate it</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/toggleItemArchived?item_id={$item.id}';" class="right-nav-link"><i class="fa fa-archive"></i>&nbsp;{if $item.is_archived}Un-archive this {$item._model.name}{else}Archive this {$item._model.name}{/if}</a></li>
  </ul>
  
  <ul class="actions-list">
    <li><b>{$item._model.name} Options</b></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getItemClassMembers?class_id={$item._model.id}';" class="right-nav-link"><i class="fa fa-cubes"></i> Back to {$item._model.plural_name}</a></li>
    {if $model_type == 'SM_ITEMCLASS_MT1_SUB_MODEL'}<li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getItemClassMembers?class_id={$parent_model.id}';" class="right-nav-link"><i class="fa fa-cubes"></i> Back to {$parent_model.plural_name}</a></li>{/if}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/addItem?class_id={$item._model.id}';" class="right-nav-link"><i class="fa fa-plus-circle"></i> New {$item._model.name}</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}sets/addSet?class_id={$item._model.id}';" class="right-nav-link"><i class="fa fa-plus-square-o"></i> Create a new set of {$item._model.plural_name}</a></li>
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}{$section}/getItemClassProperties?class_id={$item._model.id}';" class="right-nav-link"><i class="fa fa-sliders"></i> Edit the properties of this model</a></li>
  </ul>

  <ul class="actions-list">
    <li><span style="color:#999">Recently edited {$item._model.plural_name|strtolower}</span></li>
    {foreach from=$recent_items item="recent_item"}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$recent_item.action_url}'"><i class="fa fa-cube"></i> {$recent_item.label|summary:"28"}</a></li>
    {/foreach}
  </ul>
  
</div>
