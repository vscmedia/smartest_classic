<div id="modal-work-area">
{if $asset_id}
  <form action="{$domain}ajax:assets/updateTextFragmentAttachmentDisplayData" method="post" id="smartest-attachment-lite-editor" data-editor-id="{$editor_id}" data-attachment-name="{$attachment_name}">
    <input type="hidden" name="asset_id" value="{$asset_id}" />
    <input type="hidden" name="attachment" value="{$attachment_name}" />
    
    <div id="edit-form-layout">
      <div class="edit-form-row">
        <div class="form-section-label">Attachment name</div>
        <code style="display:inline-block;padding-top:3px;font-size:14px">{$attachment_name}</code>
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Attached media</div>
        {if $has_attached_asset}
          <div class="smartest-attachment-lite-media">
            <div class="smartest-attachment-lite-preview">
              {if $attached_asset_is_image && $modal_thumbnail_url}
                <img src="{$modal_thumbnail_url|escape:html}" alt="" />
              {else}
                <span>{$preview_badge|escape:html}</span>
              {/if}
            </div>
            <div class="smartest-attachment-lite-details">
              <strong>{$attached_asset_label|escape:html}</strong>
              <span class="form-hint">{$preview_label|escape:html}</span>
              {if $image_is_resized && $resize_label}<span class="form-hint">Resized: {$resize_label|escape:html}</span>{/if}
              <code>{$attached_asset_url|escape:html}</code>
            </div>
          </div>
        {else}
          <span class="null-notice">No file is selected</span>
        {/if}
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Position</div>
        <a class="attachment-position-selector compact-attachment-position-selector{if $alignment == "left" && $float} selected{/if}" href="#float-left" id="attachment-lite-float-left" data-float="true" data-align="left"></a>
        <a class="attachment-position-selector compact-attachment-position-selector{if $alignment == "right" && $float} selected{/if}" href="#float-right" id="attachment-lite-float-right" data-float="true" data-align="right"></a>
        <a class="attachment-position-selector compact-attachment-position-selector{if $alignment == "center"} selected{/if}" href="#align-center" id="attachment-lite-align-center" data-float="false" data-align="center"></a>
        <a class="attachment-position-selector compact-attachment-position-selector{if $alignment == "left" && !$float} selected{/if}" href="#align-left" id="attachment-lite-align-left" data-float="false" data-align="left"></a>
        <a class="attachment-position-selector compact-attachment-position-selector{if $alignment == "right" && !$float} selected{/if}" href="#align-right" id="attachment-lite-align-right" data-float="false" data-align="right"></a>
        <input type="hidden" name="attached_file_alignment" id="attachment-lite-alignment" value="{$alignment}" />
        <input type="hidden" name="attached_file_float" id="attachment-lite-float" value="{if $float}true{else}false{/if}" />
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Caption</div>
        <textarea name="attached_file_caption" id="attachment-lite-caption" style="width:370px;height:60px">{$caption|escape:html}</textarea>
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Caption Alignment</div>
        <select name="attached_file_caption_alignment" id="attachment-lite-caption-alignment">
          <option value="left"{if $caption_alignment == "left"} selected="selected"{/if}>From Left</option>
          <option value="right"{if $caption_alignment == "right"} selected="selected"{/if}>From Right</option>
          <option value="center"{if $caption_alignment == "center"} selected="selected"{/if}>Centered</option>
        </select>
      </div>
      
      <div class="edit-form-row">
        <div class="form-section-label">Show a 1px grey border</div>
        {boolean name="attached_file_border" id="attachment-lite-border" value=$border}
      </div>
      
      <div class="edit-form-row">
        <div class="buttons-bar">
          <span class="feedback-error" id="attachment-lite-error" style="display:none;float:left">Attachment settings could not be saved</span>
          <input type="button" value="Cancel" id="attachment-lite-cancel" />
          <input type="button" value="Full editor" id="attachment-lite-full-editor" data-url="{$domain}{$full_editor_url|escape:html}" />
          <input type="submit" value="Save" id="attachment-lite-save" />
        </div>
      </div>
    </div>
  </form>
  
  <script type="text/javascript">
  {literal}
  (function(){
    var form = $('smartest-attachment-lite-editor');
    var fullEditorButton = $('attachment-lite-full-editor');
    
    if(!form){
      return;
    }
    
    if(form.readAttribute('data-smartest-bound') == 'true'){
      return;
    }
    
    form.writeAttribute('data-smartest-bound', 'true');
    
    var editorId = form.readAttribute('data-editor-id');
    var attachmentName = form.readAttribute('data-attachment-name');
    
    form.observe('click', function(e){
      var a = e.findElement('a.attachment-position-selector');
      
      if(a){
        e.stop();
        $$('#smartest-attachment-lite-editor a.attachment-position-selector').each(function(aa){
          aa.removeClassName('selected');
        });
        a.addClassName('selected');
        $('attachment-lite-float').value = a.readAttribute('data-float');
        $('attachment-lite-alignment').value = a.readAttribute('data-align');
      }
    });
    
    $('attachment-lite-cancel').observe('click', function(e){
      e.stop();
      MODALS.hideViewer();
    });
    
    fullEditorButton.observe('click', function(e){
      e.stop();
      if(window.SmartestTinyMceAttachmentEditor){
        if(!window.SmartestTinyMceAttachmentEditor.openFullDefinition){
          window.location = fullEditorButton.readAttribute('data-url');
          return;
        }
        window.SmartestTinyMceAttachmentEditor.openFullDefinition(editorId, attachmentName, fullEditorButton.readAttribute('data-url'));
      }else{
        window.location = fullEditorButton.readAttribute('data-url');
      }
    });
    
    form.observe('submit', function(e){
      e.stop();
      $('attachment-lite-error').hide();
      form.request({
        onSuccess: function(response){
          var data = response.responseJSON;
          
          if(data){
            if(data.success){
              if(window.SmartestTinyMceAttachmentEditor){
                if(window.SmartestTinyMceAttachmentEditor.applyAttachmentData){
                  window.SmartestTinyMceAttachmentEditor.applyAttachmentData(editorId, data.data);
                }
              }
              MODALS.hideViewer();
            }else{
              $('attachment-lite-error').show();
            }
          }else{
            $('attachment-lite-error').show();
          }
        },
        onFailure: function(){
          $('attachment-lite-error').show();
        }
      });
    });
  })();
  {/literal}
  </script>
{else}
  <div class="warning">
    <p>{$message}</p>
  </div>
{/if}
</div>
