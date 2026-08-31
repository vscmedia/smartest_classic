{if $sm_user_agent.is_supported_browser}

<script type="text/javascript">
var suffixRegex = {if $file_suffix_regex}{$file_suffix_regex}{else}null{/if};
</script>

{if $post_max_size_warning}
<div class="warning">There is an issue in your PHP configration. Although upload_max_filesize has been configured to allow uploads of up to <strong>{$max_upload_size_in_megs} MB</strong>, the post_max_size directive in php.ini will still limit uploads to only <strong>{$post_max_size_in_megs} MB</strong>, and uploads will fail completely if they exceed this. To fix this, change the post_max_size directive to match upload_max_filesize.</div>
{/if}

<div class="error" style="display:none" id="upload-error">
  There was a problem with your file upload.
</div>
<div class="edit-form-row">
  <div class="form-section-label">File to upload</div>
  <div class="file-upload-picker">
    <label for="asset-file" class="file-upload-choose-button">Choose file</label>
    <span id="asset-file-name" class="file-upload-file-name">No file selected</span>
    <input type="file" name="new_file" id="asset-file" class="file-upload-native-input" />
  </div>
  <div class="file-upload-or">or</div>
  <div id="asset-drop-zone" class="file-upload-drop-zone">
    <img src="" alt="" id="asset-drop-zone-preview" class="file-upload-preview" style="display:none" />
    <i class="fa fa-check-circle" id="asset-drop-zone-ready-icon" style="display:none"></i>
    <strong id="asset-drop-zone-title">Drag file here</strong>
    <span id="asset-drop-zone-file">Drop one matching file to upload it</span>
  </div>
  <div class="form-hint" style="color:#c30;display:none" id="asset-type-warning">The file you have selected is not the correct file type.</div>
  <div class="form-hint">Please do not upload files larger than <strong>{if $post_max_size_warning}{$post_max_size_in_megs}{else}{$max_upload_size_in_megs}{/if} MB</strong></div>
</div>
<div class="edit-form-row" id="upload-progress-bar-holder" style="display:none">
  <div class="form-section-label"> </div>
  <div class="progress-bar-outer" id="upload-progress-outer">
    <div class="progress-bar-inner" id="upload-progress-inner" style="width:0px;display:none"> </div>
  </div>
</div>

<script type="text/javascript">
var uploadUrl = sm_domain+'ajax:assets/uploadNewFileViaBrowserAjaxRequest';
{literal}
document.observe('dom:loaded', function(){

  if(!window.FormData){
    $('no-filereader-warning').show();
    $('asset-file').removeClassName('file-upload-native-input');
    $('asset-file-name').hide();

    if($('asset-drop-zone')){
      $('asset-drop-zone').hide();
    }

    if($$('div.file-upload-or').first()){
      $$('div.file-upload-or').first().hide();
    }

    return;
  }

  var selectedUploadFile = null;

  var uploadFileNameIsSupported = function(fileName){
    return !fileName.length || !suffixRegex || fileName.match(suffixRegex);
  };

  var getSelectedUploadFileName = function(){
    if(selectedUploadFile){
      return selectedUploadFile.name;
    }

    return $F('asset-file');
  };

  var showUploadWarning = function(message){
    $('asset-type-warning').update(message);
    $('asset-type-warning').appear({duration: 0.3});
  };

  var updateUploadReadiness = function(showMissingFileWarning){
    var fileName = getSelectedUploadFileName();

    if(!fileName.length){
      $('confirm-asset-create').disabled = true;

      if(showMissingFileWarning){
        showUploadWarning('Choose or drop a file to upload.');
      }else if($('asset-type-warning').visible()){
        $('asset-type-warning').fade({duration: 0.3});
      }

      return;
    }

    if(uploadFileNameIsSupported(fileName)){
      $('confirm-asset-create').disabled = false;
      if($('asset-type-warning').visible()){
        $('asset-type-warning').fade({duration: 0.3});
      }
      if(!$('confirm-asset-create').visible()){
        $('confirm-asset-create').appear({duration: 0.3});
      }
    }else{
      $('confirm-asset-create').disabled = true;
      showUploadWarning('The file you have selected is not the correct file type.');
      $('confirm-asset-create').fade({duration: 0.3});
    }
  };

  var setSelectedUploadFile = function(file){
    selectedUploadFile = file;

    if(file){
      var fileIsSupported = uploadFileNameIsSupported(file.name);
      $('asset-file-name').update(file.name);
      $('asset-drop-zone-file').update(file.name);
      $('asset-drop-zone').removeClassName('drag-over');
      $('asset-drop-zone').addClassName('has-file');
      $('asset-drop-zone').removeClassName('invalid-file');
      $('asset-drop-zone-title').update(fileIsSupported ? 'Ready to upload' : 'File type not accepted');

      if(fileIsSupported){
        $('asset-drop-zone-ready-icon').show();
      }else{
        $('asset-drop-zone').addClassName('invalid-file');
        $('asset-drop-zone-ready-icon').hide();
      }

      if($('asset-drop-zone-preview') && window.FileReader && file.type && file.type.match(/^image\//) && fileIsSupported){
        var reader = new FileReader();
        reader.onload = function(evt){
          if(selectedUploadFile == file){
            $('asset-drop-zone-preview').src = evt.target.result;
            $('asset-drop-zone-preview').show();
          }
        };
        reader.readAsDataURL(file);
      }else if($('asset-drop-zone-preview')){
        $('asset-drop-zone-preview').hide();
        $('asset-drop-zone-preview').src = '';
      }
    }else{
      $('asset-file-name').update('No file selected');
      $('asset-drop-zone-file').update('Drop one matching file to upload it');
      $('asset-drop-zone').removeClassName('has-file');
      $('asset-drop-zone').removeClassName('invalid-file');
      $('asset-drop-zone-title').update('Drag file here');
      $('asset-drop-zone-ready-icon').hide();

      if($('asset-drop-zone-preview')){
        $('asset-drop-zone-preview').hide();
        $('asset-drop-zone-preview').src = '';
      }
    }
  };

  $('asset-file').observe('change', function(){
    setSelectedUploadFile($('asset-file').files && $('asset-file').files.length ? $('asset-file').files[0] : null);
    updateUploadReadiness();
  });

  updateUploadReadiness(false);

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
        updateUploadReadiness();
      }
    });
  }
  
  var startBrowserUpload = function(e){
    if(e && e.stop){
      e.stop();
    }

    var uploadInfo = {};
    uploadInfo.asset_label = $F('new-asset-name');
    
    $$('input.purpose_inputs').each(function(ipt){
      uploadInfo[ipt.name] = ipt.value;
    });
    
    if(($('new-asset-name').getValue() == itemNameFieldDefaultValue) || $('new-asset-name').getValue() == ''){
      $('new-asset-name').addClassName('error');
      return false;
    }else{
      
      var uploadComplete = function(evt) {
        $('buttons-bar').update('<input type="button" value="Continue" id="cancel-asset-create" onclick="finishTask();" />');
        new Effect.Fade('upload-progress-outer', {duration: 0.4});
      }
      
      var uploadFailed = function(evt) {
        $('buttons-bar').update('<input type="button" value="Go back" id="cancel-asset-create" onclick="cancelForm();" />');
        $('upload-error').show();
      }
      
      var uploadProgress = function(evt) {
        
        if (evt.lengthComputable) {
            var percentComplete = Math.round(evt.loaded * 100 / evt.total);
          
            if(!$('upload-progress-inner').visible()){
              $('upload-progress-inner').show();
            }
          
            var cssWidthValue = percentComplete.toString() + '%';
            $('upload-progress-inner').setStyle({width: cssWidthValue});
        }
        
      }
      
      var formdata = new FormData();
      var file = selectedUploadFile || ($('asset-file').files && $('asset-file').files.length ? $('asset-file').files[0] : null);

      if(!file || !uploadFileNameIsSupported(getSelectedUploadFileName())){
        updateUploadReadiness(true);
        return false;
      }
      
      formdata.append("new_file", file);
      
      $H(uploadInfo).each(function(iterator){
        formdata.append(iterator.key, iterator.value);
      });
      
      $H(uploadInfo).each(function(iterator){
        console.log(iterator.key+':'+iterator.value);
      });
      
      $('upload-progress-bar-holder').show();
      
      var xhr = new XMLHttpRequest;
      xhr.open('POST', uploadUrl, true);
      xhr.upload.addEventListener("progress", uploadProgress, false);
      xhr.addEventListener("load", uploadComplete, false);
      xhr.addEventListener("error", uploadFailed, false);
      xhr.send(formdata);
      
    }
  };

  $('confirm-asset-create').observe('click', startBrowserUpload);
  $('new-asset-form').observe('submit', startBrowserUpload);
});
{/literal}
</script>
{else}

{if $post_max_size_warning}
<div class="warning">There is an issue in your PHP configration. Although upload_max_filesize has been configured to allow uploads of up to <strong>{$max_upload_size_in_megs} MB</strong>, the post_max_size directive in php.ini will still limit uploads to only <strong>{$post_max_size_in_megs} MB</strong>, and uploads will fail completely if they exceed this. To fix this, change the post_max_size directive to match upload_max_filesize.</div>
{/if}

<div style="margin-top:8px;margin-bottom:8px" id="uploader" class="special-box">
  <div class="edit-form-row">
    <div class="form-section-label">Choose a file on your computer to upload</div>
    <input type="file" name="new_file" id="asset-file" />
    <div class="form-hint" style="color:#c30;display:none" id="asset-type-warning">The file you have selected is not the correct file type.</div>
    <div class="form-hint">Please do not upload files larger than <strong>{if $post_max_size_warning}{$post_max_size_in_megs}{else}{$max_upload_size_in_megs}{/if} MB</strong></div>
    <div class="breaker"></div>
  </div>
</div>

<script type="text/javascript">
  
var suffixRegex = {if $file_suffix_regex}{$file_suffix_regex}{else}null{/if};

{literal}$('asset-file').observe('change', function(){
  if(!suffixRegex || $('asset-file').value.match(suffixRegex)){
    if($('asset-type-warning').visible()){
      $('asset-type-warning').fade({duration: 0.3});
    }
    if(!$('confirm-asset-create').visible()){
      $('confirm-asset-create').appear({duration: 0.3});
    }
  }else{
    $('asset-type-warning').appear({duration: 0.3});
    $('confirm-asset-create').fade({duration: 0.3});
  }
});{/literal}
  
</script>

{/if}
