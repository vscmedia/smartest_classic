<div id="modal-work-area">
  
  <div class="instruction">Select or upload an image</div>
  <div class="warning" id="no-filereader-warning" style="display:none">To upload images here, you'll need an up-to-date browser that supports drag-and-drop uploads and the FormData API.</div>
  
  <ul class="file-thumbnails" id="image-list">
    {if $sm_user_agent.is_supported_browser}
    <li class="add" id="add-image-li">
      <a href="#add" id="add-image-button"><i>+</i>Upload a new image</a>
    </li>
    {/if}
  {foreach from=$assets item="asset"}
    <li style="background-image:url({if $sm_user_agent.is_supported_browser}{$asset.image.200x200.web_path}{else}{$asset.image.100x100.web_path}{/if})"><a href="#image-{$asset.id}" class="thumbnail {if $asset.id == $current_asset_id} selected{/if}" data-assetid="{$asset.id}"><span class="image-info">{$asset.label.xmlentities} ({$asset.image.width}x{$asset.image.height})</span></a></li>
  {/foreach}
  </ul>
  
  <div id="image-uploader" style="display:none">
    <form action="{$domain}ajax:assets/uploadNewImageFromMiniImageBrowser" method="post" enctype="multipart/form-data" id="new-image-upload-form">
      {if $for}
        <input type="hidden" name="for" id="for-input" value="{$for}" class="purpose_inputs" />
        {if $for == 'ipv' && $property_id}<input type="hidden" name="property_id" value="{$property_id}" class="purpose_inputs" />{/if}
        {if $for == 'ipv' && $item_id}<input type="hidden" name="item_id" value="{$item_id}" class="purpose_inputs" />{/if}
        {if $for == 'placeholder' && $placeholder_id}<input type="hidden" name="placeholder_id" value="{$placeholder_id}" class="purpose_inputs" />{/if}
        {if $for == 'user_profile_pic' && $user_id}<input type="hidden" name="user_id" value="{$user_id}" class="purpose_inputs" />{/if}
        {if $for == 'page_icon' && $page_id}<input type="hidden" name="page_id" value="{$page_id}" class="purpose_inputs" />{/if}
      {/if}
      <div class="form-section-label-full">Upload an image</div>
      <div class="edit-form-row">
        <div class="form-section-label">Give this image a label</div>
        <input type="text" name="asset_label" value="{$suggested_label}" id="asset-label" />
      </div>
      <div class="edit-form-row">
        <div class="form-section-label">Credit, if this is a photo</div>
        <input type="text" name="asset_credit" value="" id="asset-credit" />
      </div>
      <div class="edit-form-row" id="choose-file-row">
        <div class="form-section-label">Image file ({$image_file_suffix_label})</div>
        <div class="image-upload-picker">
          <label for="asset-file" class="image-upload-choose-button">Choose image file</label>
          <span id="asset-file-name" class="image-upload-file-name">No file selected</span>
          <input type="file" name="asset_file" id="asset-file" accept="{$image_file_accept_attribute}" class="image-upload-native-input" />
        </div>
        <div class="image-upload-or">or</div>
        <div id="asset-drop-zone" class="image-upload-drop-zone">
          <img src="" alt="" id="asset-drop-zone-preview" class="image-upload-preview" style="display:none" />
          <i class="fa fa-check-circle" id="asset-drop-zone-ready-icon" style="display:none"></i>
          <strong id="asset-drop-zone-title">Drag image file here</strong>
          <span id="asset-drop-zone-file">Drop one supported image file to upload it</span>
        </div>
        <div class="form-hint" style="color:#c30;display:none" id="asset-type-warning">The file you have selected is not a supported image type.</div>
      </div>
      <div class="v-spacer"> </div>
      <div class="progress-bar-outer" id="upload-progress-outer" style="display:none">
        <div class="progress-bar-inner" id="upload-progress-inner" style="width:0px;display:none"> </div>
      </div>
      <div class="buttons-bar">
        <a href="#upload" class="button" id="new-image-upload-cancel-button">Cancel</a>
        <a href="#upload" class="button" id="new-image-upload-button">Upload image</a>
      </div>
    </form>
  </div>
  
  <script type="text/javascript">// <![CDATA[

  var currentAssetId = {if $current_asset_id}{$current_asset_id}{else}null{/if};
  var purpose = '{$for}';
  inputId = '{$input_id}';
  var suffixRegex = {if $file_suffix_regex}{$file_suffix_regex}{else}null{/if};
  var selectedUploadFile = null;

  {literal}
  $$('ul.file-thumbnails li a.thumbnail').each(function(clickedThumbnail){
    clickedThumbnail.observe('click', function(e){
      e.stop();
      // Update visual selected status
      $$('ul.file-thumbnails li a').each(function(l){
        l.removeClassName('selected');
      });
      clickedThumbnail.addClassName('selected');
      // Update hidden element
      $(inputId).value = clickedThumbnail.readAttribute('data-assetid');
      // Update form display with new thumbnail
      var url = sm_domain+'ajax:assets/getReplacementThumbnailForMiniImageBrowser';
      $(inputId+'-edit-metadata').appear({duration: 0.2});
      $(inputId+'-button-clear').appear({duration: 0.2});
      new Ajax.Updater(inputId+'-thumbnail-area', url, {
        parameters: {
          asset_id: clickedThumbnail.readAttribute('data-assetid'),
          input_id: inputId,
          'for': purpose
        }
      });
      $(inputId).fire('image:chosen', {inputId: inputId});
      // Close modal
      MODALS.hideViewer();
    });
  });
  {/literal}

  {if $sm_user_agent.is_supported_browser}
  {literal}
  if($('add-image-button')){
    $('add-image-button').observe('click', function(e){
      e.stop();
      $('image-list').hide();
      $('image-uploader').show();
      $('asset-label').focus();
      MODALS.updateScroller();
    });
  }
  {/literal}
  {/if}

  {literal}
  var uploadFileNameIsSupported = function(fileName){
    return !fileName.length || !suffixRegex || fileName.match(suffixRegex);
  };

  var getSelectedUploadFileName = function(){
    if(selectedUploadFile){
      return selectedUploadFile.name;
    }

    return $F('asset-file');
  };

  var setSelectedUploadFile = function(file){
    selectedUploadFile = file;

    if(file){
      $('asset-file-name').update(file.name);
      $('asset-drop-zone-file').update(file.name);
      $('asset-drop-zone').removeClassName('drag-over');
      $('asset-drop-zone').addClassName('has-file');
      $('asset-drop-zone-title').update('Ready to upload');
      $('asset-drop-zone-ready-icon').show();

      if($('asset-drop-zone-preview') && window.FileReader && uploadFileNameIsSupported(file.name)){
        var reader = new FileReader();
        reader.onload = function(evt){
          if(selectedUploadFile == file){
            $('asset-drop-zone-preview').src = evt.target.result;
            $('asset-drop-zone-preview').show();
            MODALS.updateScroller();
          }
        };
        reader.readAsDataURL(file);
      }else if($('asset-drop-zone-preview')){
        $('asset-drop-zone-preview').hide();
        $('asset-drop-zone-preview').src = '';
      }

      if(!$F('asset-label').length){
        $('asset-label').value = file.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ');
      }
    }else{
      $('asset-file-name').update('No file selected');
      $('asset-drop-zone-file').update('Drop one supported image file to upload it');
      $('asset-drop-zone').removeClassName('has-file');
      $('asset-drop-zone-title').update('Drag image file here');
      $('asset-drop-zone-ready-icon').hide();

      if($('asset-drop-zone-preview')){
        $('asset-drop-zone-preview').hide();
        $('asset-drop-zone-preview').src = '';
      }
    }
  };

  var updateUploadFileTypeWarning = function(){
    if(uploadFileNameIsSupported(getSelectedUploadFileName())){
      $('asset-type-warning').hide();
    }else{
      $('asset-type-warning').show();
    }
  };

  $('asset-file').observe('change', function(){
    setSelectedUploadFile($('asset-file').files && $('asset-file').files.length ? $('asset-file').files[0] : null);
    updateUploadFileTypeWarning();
  });

  if($('asset-drop-zone')){
    ['dragenter', 'dragover'].each(function(eventName){
      $('asset-drop-zone').observe(eventName, function(e){
        e.stop();
        $('asset-drop-zone').addClassName('drag-over');
      });
    });

    ['dragleave', 'dragend'].each(function(eventName){
      $('asset-drop-zone').observe(eventName, function(e){
        e.stop();
        $('asset-drop-zone').removeClassName('drag-over');
      });
    });

    $('asset-drop-zone').observe('drop', function(e){
      e.stop();
      $('asset-drop-zone').removeClassName('drag-over');

      var dataTransfer = e.dataTransfer || (e.event && e.event.dataTransfer);

      if(dataTransfer && dataTransfer.files && dataTransfer.files.length){
        setSelectedUploadFile(dataTransfer.files[0]);
        updateUploadFileTypeWarning();
      }
    });
  }

  var startUpload = function(e){
    // e is an Event object
    if(e && e.stop){
      e.stop();
    }

    var uploadFileName = getSelectedUploadFileName();
    var file = selectedUploadFile || ($('asset-file').files && $('asset-file').files.length ? $('asset-file').files[0] : null);

    if($F('asset-label').length && uploadFileName.length && uploadFileNameIsSupported(uploadFileName) && file){

      var formdata = new FormData();

      formdata.append("asset_file", file);
      formdata.append("asset_label", $F('asset-label'));
      formdata.append("asset_credit", $F('asset-credit'));
    
      $$('input.purpose_inputs').each(function(ipt){
        formdata.append(ipt.name, ipt.value);
      });
    
      var uploadComplete = function(evt) {
        /* This event is raised when the server send back a response */
        var jsonResponse;
        
        try{
          jsonResponse = JSON.parse(evt.target.responseText);
        }catch(e){
          alert('The image could not be uploaded. The server returned an unexpected response.');
          $('upload-progress-outer').hide();
          return;
        }
        
        if(jsonResponse.error){
          alert(jsonResponse.message || 'The image could not be uploaded.');
          $('upload-progress-outer').hide();
          return;
        }
        
        var modalURL = 'assets/miniImageBrowser?input_id='+inputId;
      
        if(jsonResponse.for){
          modalURL += '&for='+jsonResponse.for;
        }
      
        if(jsonResponse.placeholder_id){
          modalURL += '&placeholder_id='+jsonResponse.placeholder_id;
        }
      
        if(jsonResponse.property_id){
          modalURL += '&property_id='+jsonResponse.property_id;
        }
      
        if(jsonResponse.user_id){
          modalURL += '&user_id='+jsonResponse.user_id;
        }
      
        modalURL += '&current_selection_id='+jsonResponse.asset_id;
      
        $(inputId).value = jsonResponse.asset_id;
      
        new Ajax.Updater(inputId+'-thumbnail-area', sm_domain+'ajax:assets/getReplacementThumbnailForMiniImageBrowser', {
          parameters: {
            asset_id: jsonResponse.asset_id,
            input_id: inputId,
            'for': purpose
          }
        });
        
        $(inputId).fire('image:chosen', {inputId: inputId});
        $(inputId+'-edit-metadata').appear({duration: 0.2});
        $(inputId+'-button-clear').appear({duration: 0.2});
        MODALS.hideViewer();

      }
    
      var uploadProgress = function (evt) {
      
          if (evt.lengthComputable) {
          
              var percentComplete = Math.round(evt.loaded * 100 / evt.total);
            
              if(!$('upload-progress-inner').visible()){
                $('upload-progress-inner').show();
              }
            
              var cssWidthValue = percentComplete.toString() + '%';
            
              $('upload-progress-inner').setStyle({width: cssWidthValue});
            
          }
      }
    
      // show progress bar
      $('upload-progress-outer').show();
    
      // Create XMLHttpRequest and upload file
      var xhr = new XMLHttpRequest;
      xhr.open('POST', $('new-image-upload-form').action, true);
      xhr.upload.addEventListener("progress", uploadProgress, false);
      xhr.addEventListener("load", uploadComplete, false);
      xhr.send(formdata);
      
    }else{

      if(!$F('asset-label').length){

        $('asset-label').addClassName('error');

        $('asset-label').observe('keyup', function(){
          if($F('asset-label').length){
            $('asset-label').removeClassName('error');
          }
        });

      }

      if(uploadFileName.length && !uploadFileNameIsSupported(uploadFileName)){
        $('asset-type-warning').show();
      }

    }

  }

  $('new-image-upload-cancel-button').observe('click', function(e){
    e.stop();
    $('image-list').show();
    $('image-uploader').hide();
    MODALS.updateScroller();
  });
  
  $('new-image-upload-form').observe('keypress', function(e){
    if(e.keyCode == 13){
      e.stop(e);
      startUpload();
    }
  });
  
  $('new-image-upload-button').observe('click', startUpload);
  
  {/literal}
  
  // ]]>
  </script>
  
</div>
