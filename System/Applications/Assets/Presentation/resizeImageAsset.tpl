<div id="modal-work-area">
{if $asset_id && $can_resize}
  <form action="{$domain}assets/resizeImageAsset" method="post" id="smartest-image-resize-form">
    <input type="hidden" name="asset_id" value="{$asset.id}" />
    <input type="hidden" name="_confirm_downscale" value="1" />
    
    <script type="text/javascript">
    {literal}
    window.updateDownscaleDimensions = function(value){
      var originalWidth = {/literal}{$original_width}{literal};
      var originalHeight = {/literal}{$original_height}{literal};
      value = parseInt(value, 10);

      if(!originalWidth || !originalHeight || !value){
        $('target-image-size').update('');
        return;
      }

      var newHeight = Math.ceil(value / originalWidth * originalHeight);
      var percentage = Math.round((value / originalWidth * 100) * 10) / 10;
      $('target-image-size').update('Expected size: '+value+' x '+newHeight+' pixels ('+percentage+'%)');
    };
    {/literal}
    </script>
    
    <div id="edit-form-layout">
      <div class="edit-form-row">
        <div class="form-section-label">Original file</div>
        <div style="display:flex;gap:14px;align-items:center">
          <img src="{$asset.image.constrain_200x200.web_path}" alt="{$asset.label}" style="max-width:120px;max-height:120px" />
          <div>
            <strong>{$asset.label}</strong><br />
            <span class="form-hint">{$original_width} x {$original_height} pixels, {$asset.size}</span><br />
            <span class="form-hint">Smartest flags images wider than {$large_image_width_threshold}px as large.</span>
          </div>
        </div>
      </div>

      <div class="edit-form-row">
        <div class="form-section-label">New width</div>
        {slider name="max_width" value=$suggested_downscale_width min="1" max=$maximum_downscale_width value_unit="px" slidehook="updateDownscaleDimensions" changehook="updateDownscaleDimensions"}
        <span id="target-image-size" style="padding-left:10px">Expected size: {$suggested_downscale_width} x {$suggested_downscale_height} pixels ({$suggested_downscale_percentage}%)</span>
      </div>

      <div class="edit-form-row">
        <div class="form-section-label">&nbsp;</div>
        <span class="form-hint">The resized file will be created as a new derived asset. The original file will not be changed.</span>
      </div>

      <div class="edit-form-row">
        <div class="buttons-bar">
          <input type="button" value="Cancel" id="image-resize-cancel" onclick="if(window.MODALS){ MODALS.hideViewer(); } return false;" />
          <input type="submit" value="Create resized file" />
        </div>
      </div>
    </div>
    
    <script type="text/javascript">
    {literal}
    window.updateDownscaleDimensions($F('MaxWidth'));
    $('image-resize-cancel').observe('click', function(e){
      e.stop();
      if(window.MODALS){
        MODALS.hideViewer();
      }
    });
    {/literal}
    </script>
  </form>
{else}
  <div class="warning">
    <p>{$message}</p>
  </div>
{/if}
</div>
