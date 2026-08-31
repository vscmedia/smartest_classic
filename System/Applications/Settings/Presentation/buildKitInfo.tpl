<div id="work-area">

  <h3>{$buildkit.label}</h3>

  {if $buildkit.description}
  <div class="instruction">{$buildkit.description}</div>
  {/if}

  <div class="edit-form-row">
    <div class="form-section-label">Developer</div>
    {if $buildkit.developer}{$buildkit.developer}{else}<span class="form-hint">Not specified</span>{/if}
  </div>

  <div class="edit-form-row">
    <div class="form-section-label">Identifier</div>
    <code>{$buildkit.shortname}</code>
  </div>

  {if $buildkit.version}
  <div class="edit-form-row">
    <div class="form-section-label">Version</div>
    {$buildkit.version}
  </div>
  {/if}

  {if $buildkit.minimum_smartest_build}
  <div class="edit-form-row">
    <div class="form-section-label">Minimum build</div>
    {$buildkit.minimum_smartest_build}
  </div>
  {/if}

  {if $num_creation_summary_items}
  <h4>This Build Kit creates</h4>
  <ul>
    {foreach from=$buildkit.creates item="creation_item"}
    <li>{$creation_item}</li>
    {/foreach}
  </ul>
  {/if}

  {if $num_features}
  <h4>Setup sections</h4>
  <ul>
    {foreach from=$buildkit.features item="feature"}
    <li>{$feature.label}{if $feature.required} <span class="form-hint">Required</span>{else} <span class="form-hint">Optional</span>{/if}</li>
    {/foreach}
  </ul>
  {/if}

  {if $num_required_locations}
  <h4>Required write permissions</h4>
  {if $num_unwritable_locations}
  <div class="warning">Some locations needed by this Build Kit are not currently writable.</div>
  {/if}
  <ul>
    {foreach from=$buildkit.required_write_location_statuses item="location"}
    <li>
      <code>{$location.path}</code>
      {if !$location.writable}
      <span class="form-hint warning-color">Not writable</span>
      {else}
      <span class="form-hint">Writable</span>
      {/if}
    </li>
    {/foreach}
  </ul>
  {/if}

  {if $num_third_party_licenses}
  <h4>Third-party licenses</h4>
  <ul>
    {foreach from=$buildkit.third_party_licenses item="license"}
    <li>{$license.label}{if $license.developer} by {$license.developer}{/if}{if $license.purpose}: {$license.purpose}{/if}</li>
    {/foreach}
  </ul>
  {/if}

  <div class="buttons-bar">
    <input type="button" value="Create a site with this Build Kit" onclick="window.location='{$domain}smartest/site/new?use_buildkit={$buildkit.shortname}'" />
    <input type="button" value="{$_l10n_global_strings.system_wide_buttons.cancel}" onclick="window.location='{$domain}smartest/buildkits'" />
  </div>

</div>
