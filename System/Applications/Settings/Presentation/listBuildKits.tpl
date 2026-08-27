<div id="work-area">

  <h3>Build Kits</h3>

  {if $num_buildkits}
  <ul class="apps small">
    {foreach from=$buildkits item="buildkit"}
    <li>
      <a class="icon" href="{$domain}smartest/site/new"><i class="fa fa-magic"></i></a>
      <a class="label" href="{$domain}smartest/site/new">{$buildkit.label}</a>
      <span class="form-hint">{$buildkit.shortname}</span>
      {if count($buildkit.unwritable_required_write_locations)}
      <span class="form-hint">Some required write locations are not writable.</span>
      {/if}
    </li>
    {/foreach}
  </ul>
  {else}
  <p>No Build Kits are installed.</p>
  {/if}

</div>
