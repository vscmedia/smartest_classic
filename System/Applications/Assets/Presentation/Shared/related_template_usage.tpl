{if !empty($template_usage) && !empty($template_usage._count)}
  <ul class="actions-list" id="template-usage-actions">
    <li><b>Used by templates</b></li>
{if !empty($template_usage.explicit)}
    <li><span style="color:#999">Explicit stylesheet links</span></li>
{foreach from=$template_usage.explicit item="template"}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}templates/editTemplate?template={$template.id}'"><i class="fa fa-file-code-o"></i> {$template.label|summary:"30"}</a></li>
{/foreach}
{/if}
{if !empty($template_usage.selector_matches)}
    <li><span style="color:#999">Possible selector matches</span></li>
{foreach from=$template_usage.selector_matches item="template"}
    <li class="permanent-action"><a href="{dud_link}" onclick="window.location='{$domain}templates/editTemplate?template={$template.id}'"><i class="fa fa-search"></i> {$template.label|summary:"30"}</a></li>
{/foreach}
{/if}
  </ul>
{/if}
