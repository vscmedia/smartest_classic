<div id="work-area">

  <h3>Build Kits</h3>

  {if $num_buildkits}
  <ul class="apps small">
    {foreach from=$buildkits item="buildkit"}
    <li>
      <a class="icon" href="{$domain}smartest/buildkit/{$buildkit.shortname}"><i class="fa fa-magic"></i></a>
      <a class="label" href="{$domain}smartest/buildkit/{$buildkit.shortname}">{$buildkit.label}</a>
      {if count($buildkit.unwritable_required_write_locations)}
      <i class="fa fa-exclamation-triangle" style="color:#c30" title="Some required write locations are not writable."></i>
      {else}
      <i class="fa fa-check-circle" style="color:#690" title="Ready to run."></i>
      {/if}
    </li>
    {/foreach}
  </ul>
  {else}
  <p>No Build Kits are installed.</p>
  {/if}

</div>
