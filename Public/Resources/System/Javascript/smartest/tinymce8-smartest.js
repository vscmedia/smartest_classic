(function(){
  if(!window.tinymce || window.tinymce.smartestTinyMce8CompatibilityLoaded){
    return;
  }

  window.tinymce.smartestTinyMce8CompatibilityLoaded = true;

  var unsupportedPlugins = {
    contextmenu: true,
    paste: true,
    print: true
  };

  var attachmentTagPattern = /<\?sm:attachment\s+([\s\S]*?)\s*:\?>/gi;
  var protectedAttachmentTagPattern = /<!--PROTECTED-SMARTEST-TAG:attachment\s+([\s\S]*?)\s*:PROTECTED-SMARTEST-TAG-->/gi;
  var attachmentFigurePattern = /<figure\b([^>]*)\bclass=(["'])(?=[^"']*\bsm-attachment-proxy\b)([^"']*)\2([^>]*)>[\s\S]*?<\/figure>/gi;

  var attachmentContentStyle = [
    'figure.sm-attachment-proxy{',
    'display:block;',
    'box-sizing:border-box;',
    'border:1px solid #b9c5d3;',
    'background:#eef3f7;',
    'color:#253447;',
    'padding:9px 12px;',
    'margin:10px 0;',
    'border-radius:4px;',
    'font:13px/1.35 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;',
    'box-shadow:inset 3px 0 0 #7f9bb7;',
    '}',
    'figure.sm-attachment-proxy.sm-attachment-float-left{float:left;max-width:46%;margin:4px 14px 12px 0;}',
    'figure.sm-attachment-proxy.sm-attachment-float-right{float:right;max-width:46%;margin:4px 0 12px 14px;}',
    'figure.sm-attachment-proxy.sm-attachment-align-center{max-width:70%;margin-left:auto;margin-right:auto;text-align:center;}',
    'figure.sm-attachment-proxy.sm-attachment-align-right{max-width:70%;margin-left:auto;}',
    'figure.sm-attachment-proxy strong{font-weight:600;}',
    'figure.sm-attachment-proxy img{display:block;max-width:100%;height:auto;margin:7px 0;}',
    'figure.sm-attachment-proxy.sm-attachment-align-center img{margin-left:auto;margin-right:auto;}',
    '.sm-attachment-proxy-graphic{display:block;box-sizing:border-box;width:112px;height:72px;margin:7px 0;padding-top:22px;text-align:center;border:1px solid #b9c5d3;background:#f7fafc;color:#526477;font-weight:700;letter-spacing:0;font-size:13px;}',
    '.sm-attachment-proxy-graphic-oembed{background:#f3f7fb;}',
    '.sm-attachment-proxy-graphic-embed{background:#f8f6f0;}',
    'figure.sm-attachment-proxy.sm-attachment-align-center .sm-attachment-proxy-graphic{margin-left:auto;margin-right:auto;}',
    '.sm-attachment-proxy-meta{display:block;margin-top:3px;color:#526477;font-size:12px;}',
    '.sm-attachment-proxy-type{display:block;margin-top:2px;color:#718092;font-size:11px;}',
    '.sm-attachment-proxy-empty{display:block;margin-top:4px;color:#7b8793;font-style:italic;}',
    '.sm-attachment-proxy-caption{display:block;margin-top:5px;color:#3d4856;font-size:12px;}',
    'p.sm-attachment-buffer{min-height:1em;}'
  ].join('');

  var normalizePlugins = function(plugins){
    var list = [];

    if(typeof plugins === 'string'){
      list = plugins.split(/\s+/);
    }else if(Array.isArray(plugins)){
      plugins.forEach(function(pluginGroup){
        if(typeof pluginGroup === 'string'){
          list = list.concat(pluginGroup.split(/\s+/));
        }
      });
    }

    var normalized = [];
    list.forEach(function(plugin){
      if(plugin && !unsupportedPlugins[plugin] && normalized.indexOf(plugin) < 0){
        normalized.push(plugin);
      }
    });

    if(normalized.indexOf('smartestattachments') < 0){
      normalized.push('smartestattachments');
    }

    if(normalized.indexOf('noneditable') < 0){
      normalized.push('noneditable');
    }

    return normalized.join(' ');
  };

  var normalizeToolbar = function(toolbar){
    if(typeof toolbar !== 'string'){
      return toolbar;
    }

    toolbar = toolbar.replace(/\bstyleselect\b/g, 'styles');
    toolbar = toolbar.replace(/\binsertfile\b\s*/g, '');
    toolbar = toolbar.replace(/\bsmartestattachment\b/g, 'smartestattachmentadd');

    if(!/\bsmartestattachment(label|add|edit|delete)\b/.test(toolbar)){
      toolbar += ' | smartestattachmentlabel smartestattachmentadd smartestattachmentedit smartestattachmentdelete';
    }

    return toolbar;
  };

  var escapeAttribute = function(value){
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };

  var escapeHtml = function(value){
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };

  var decodeEntities = function(value){
    var element = document.createElement('textarea');
    element.innerHTML = value;
    return element.value;
  };

  var normalizeAttachmentName = function(name){
    return String(name || '').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
  };

  var normalizeCssToken = function(name){
    return String(name || '').toLowerCase().replace(/[^a-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');
  };

  var getAttributeFromString = function(attributes, attributeName){
    var quotedPattern = new RegExp(attributeName + '\\s*=\\s*([\\\"\\\'])(.*?)\\1', 'i');
    var barePattern = new RegExp(attributeName + '\\s*=\\s*([^\\s\\\"\\\'>]+)', 'i');
    var matches = quotedPattern.exec(attributes) || barePattern.exec(attributes);

    if(!matches){
      return '';
    }

    return decodeEntities(matches[2] || matches[1] || '');
  };

  var buildAttachmentTag = function(name){
    return '<?sm:attachment name="' + escapeAttribute(name) + '":?>';
  };

  var buildAttachmentTagFromAttributes = function(attributes){
    return '<?sm:attachment ' + String(attributes || '').replace(/^\s+|\s+$/g, '') + ':?>';
  };

  var encodeParams = function(params){
    var pairs = [];

    Object.keys(params || {}).forEach(function(key){
      if(typeof params[key] !== 'undefined' && params[key] !== null){
        pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
      }
    });

    return pairs.join('&');
  };

  var buildAjaxUrl = function(action, params){
    var base = typeof window.sm_domain === 'string' && window.sm_domain.length ? window.sm_domain : '/';
    var query = encodeParams(params);

    if(base.charAt(base.length - 1) !== '/'){
      base += '/';
    }

    return base + 'ajax:assets/' + action + (query ? '?' + query : '');
  };

  var requestJson = function(action, params, callback, errorCallback, method){
    var xhr = new XMLHttpRequest();
    var body = null;

    method = method || 'GET';

    if(method === 'GET'){
      xhr.open('GET', buildAjaxUrl(action, params), true);
    }else{
      xhr.open(method, buildAjaxUrl(action), true);
      body = encodeParams(params);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    }

    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onreadystatechange = function(){
      var response;

      if(xhr.readyState !== 4){
        return;
      }

      if(xhr.status >= 200 && xhr.status < 300){
        try{
          response = JSON.parse(xhr.responseText || '{}');
        }catch(e){
          if(window.console && window.console.error){
            window.console.error('Smartest TinyMCE Ajax JSON parse failed for '+action+'. Raw response follows.');
            window.console.error(xhr.responseText);
          }
          if(errorCallback){
            errorCallback({
              success: false,
              message: 'The save request did not return valid JSON. The server may have emitted a PHP warning before the response.'
            });
          }
          return;
        }

        callback(response, xhr);
      }else if(errorCallback){
        if(window.console && window.console.error){
          window.console.error('Smartest TinyMCE Ajax request failed for '+action+' with HTTP status '+xhr.status+'. Raw response follows.');
          window.console.error(xhr.responseText);
        }
        errorCallback({
          success: false,
          message: 'The save request failed with HTTP status '+xhr.status+'.'
        });
      }
    };

    xhr.send(body);
  };

  var getEditorSetting = function(editor, name){
    if(editor.options && editor.options.get){
      return editor.options.get(name);
    }

    if(editor.getParam){
      return editor.getParam(name);
    }

    return editor.settings ? editor.settings[name] : null;
  };

  var getHostAssetId = function(editor){
    return getEditorSetting(editor, 'smartest_asset_id') || '';
  };

  var getTargetTextarea = function(editor){
    if(editor.getElement){
      return editor.getElement();
    }

    return document.getElementById(editor.id);
  };

  var renderAttachmentFigureContents = function(data){
    var name = data && data.attachment_name ? data.attachment_name : '';
    var html = '<span class="sm-attachment-proxy-main">Media attachment: <strong>' + escapeHtml(name) + '</strong></span>';

    if(!data){
      return html + '<span class="sm-attachment-proxy-meta">Loading...</span>';
    }

    if(data.has_asset){
      if(data.attached_asset_is_image && data.thumbnail_url){
        html += '<img src="' + escapeAttribute(data.thumbnail_url) + '" alt="" />';
      }else{
        html += '<span class="sm-attachment-proxy-graphic sm-attachment-proxy-graphic-' + escapeAttribute(normalizeCssToken(data.preview_kind || 'file')) + '">' + escapeHtml(data.preview_badge || 'FILE') + '</span>';
      }

      html += '<span class="sm-attachment-proxy-meta">' + escapeHtml(data.attached_asset_label || data.attached_asset_url || 'Attached file') + '</span>';
      if(data.preview_label){
        html += '<span class="sm-attachment-proxy-type">' + escapeHtml(data.preview_label) + '</span>';
      }
      if(data.image_is_resized && data.resize_label){
        html += '<span class="sm-attachment-proxy-type">Resized: ' + escapeHtml(data.resize_label) + '</span>';
      }
    }else{
      html += '<span class="sm-attachment-proxy-empty">No file selected</span>';
    }

    if(data.caption){
      html += '<span class="sm-attachment-proxy-caption">' + escapeHtml(data.caption) + '</span>';
    }

    return html;
  };

  var buildAttachmentFigure = function(name, originalTag){
    var safeName = escapeAttribute(name);
    var tag = originalTag || buildAttachmentTag(name);

    return '<figure id="sm-attachment-' + safeName + '" class="sm-attachment-proxy mceNonEditable" data-attachmentname="' + safeName + '" data-smartesttag="' + escapeAttribute(tag) + '" contenteditable="false">' + renderAttachmentFigureContents({attachment_name: name}) + '</figure>';
  };

  var getAttachmentFigure = function(editor, node){
    if(!node){
      node = editor.selection.getNode();
    }

    if(node && node.nodeType === 1 && editor.dom.hasClass(node, 'sm-attachment-proxy')){
      return node;
    }

    return editor.dom.getParent(node, 'figure.sm-attachment-proxy');
  };

  var replaceAttachmentFigureName = function(editor, figure, name){
    editor.dom.setAttribs(figure, {
      id: 'sm-attachment-' + name,
      'data-attachmentname': name,
      'data-smartesttag': buildAttachmentTag(name)
    });

    editor.dom.setHTML(figure, renderAttachmentFigureContents({attachment_name: name}));
  };

  var setAttachmentFigurePosition = function(editor, figure, data){
    var className = figure.className || '';
    var classes = className.split(/\s+/).filter(function(name){
      return name && !/^sm-attachment-(float|align)-/.test(name);
    });
    var alignment = data && data.alignment ? data.alignment : 'left';

    editor.dom.setAttrib(figure, 'style', '');

    if(data && data.float && (alignment === 'left' || alignment === 'right')){
      classes.push('sm-attachment-float-' + alignment);
    }else{
      classes.push('sm-attachment-align-' + alignment);
    }

    editor.dom.setAttrib(figure, 'class', classes.join(' '));
  };

  var applyAttachmentDataToFigure = function(editor, figure, data){
    var name;

    if(!figure || !data){
      return;
    }

    name = normalizeAttachmentName(data.attachment_name || editor.dom.getAttrib(figure, 'data-attachmentname'));

    editor.dom.setAttribs(figure, {
      id: 'sm-attachment-' + name,
      'data-attachmentname': name,
      'data-attachmentstatus': data.has_asset ? 'defined' : 'empty',
      'data-attachmentalign': data.alignment || 'left',
      'data-attachmentfloat': data.float ? 'true' : 'false'
    });

    setAttachmentFigurePosition(editor, figure, data);
    editor.dom.setHTML(figure, renderAttachmentFigureContents(data));
  };

  var applyAttachmentData = function(editor, data){
    var name = data ? normalizeAttachmentName(data.attachment_name) : '';

    if(!name){
      return;
    }

    editor.dom.select('figure.sm-attachment-proxy').forEach(function(figure){
      if(normalizeAttachmentName(editor.dom.getAttrib(figure, 'data-attachmentname')) === name){
        applyAttachmentDataToFigure(editor, figure, data);
      }
    });

    editor.nodeChanged();
  };

  var loadAttachmentDataForFigure = function(editor, figure){
    var assetId = getHostAssetId(editor);
    var name = normalizeAttachmentName(editor.dom.getAttrib(figure, 'data-attachmentname'));

    if(!assetId || !name){
      return;
    }

    editor.dom.setHTML(figure, renderAttachmentFigureContents({attachment_name: name}));

    requestJson('getTextFragmentAttachmentData', {
      asset_id: assetId,
      attachment: name
    }, function(response){
      if(response && response.success){
        applyAttachmentDataToFigure(editor, figure, response.data);
      }
    });
  };

  var refreshAttachmentFigures = function(editor){
    editor.dom.select('figure.sm-attachment-proxy').forEach(function(figure){
      loadAttachmentDataForFigure(editor, figure);
    });
  };

  var promptForAttachmentName = function(message, currentName){
    return normalizeAttachmentName(window.prompt(message, currentName || ''));
  };

  var renameAttachmentFigure = function(editor, figure){
    var currentName = normalizeAttachmentName(editor.dom.getAttrib(figure, 'data-attachmentname'));
    var name = promptForAttachmentName('Attachment name', currentName);

    if(name && name !== currentName){
      editor.undoManager.transact(function(){
        replaceAttachmentFigureName(editor, figure, name);
        loadAttachmentDataForFigure(editor, figure);
        editor.selection.select(figure);
        editor.nodeChanged();
      });
    }
  };

  var insertAttachmentFigure = function(editor){
    var name = promptForAttachmentName('Attachment name');

    if(name){
      editor.insertContent(buildAttachmentFigure(name) + '<p class="sm-attachment-buffer">&nbsp;</p>');
      refreshAttachmentFigures(editor);
    }
  };

  var editAttachmentFigure = function(editor){
    var figure = getAttachmentFigure(editor);
    var assetId = getHostAssetId(editor);
    var currentName;
    var modalUrl;

    if(!figure){
      return;
    }

    currentName = normalizeAttachmentName(editor.dom.getAttrib(figure, 'data-attachmentname'));

    if(assetId && window.MODALS && window.MODALS.load){
      modalUrl = 'assets/editTextFragmentAttachmentLite?asset_id=' + encodeURIComponent(assetId) + '&attachment=' + encodeURIComponent(currentName) + '&editor_id=' + encodeURIComponent(editor.id);
      window.MODALS.load(modalUrl, 'Edit attachment');
    }else{
      renameAttachmentFigure(editor, figure);
    }
  };

  var removeAttachmentFigure = function(editor){
    var figure = getAttachmentFigure(editor);

    if(!figure){
      return;
    }

    var name = normalizeAttachmentName(editor.dom.getAttrib(figure, 'data-attachmentname'));
    var message = name ? 'Delete attachment placeholder "' + name + '"?' : 'Delete this attachment placeholder?';

    if(!window.confirm(message)){
      return;
    }

    editor.undoManager.transact(function(){
      var next = figure.nextSibling;

      editor.dom.remove(figure);

      if(next && next.nodeType === 1 && editor.dom.hasClass(next, 'sm-attachment-buffer') && !/\S/.test(next.textContent || '')){
        editor.dom.remove(next);
      }

      editor.nodeChanged();
    });
  };

  var saveEditorContent = function(editor, callback, errorCallback){
    var assetId = getHostAssetId(editor);
    var textarea = getTargetTextarea(editor);
    var content;

    if(!assetId){
      if(callback){
        callback();
      }
      return;
    }

    editor.save();
    content = textarea ? textarea.value : editor.getContent({format: 'html'});

    requestJson('postBackTextEditorContentsFromModal', {
      asset_id: assetId,
      asset_content: content
    }, function(response){
      if(response && response.success){
        if(callback){
          callback(response);
        }
      }else if(errorCallback){
        errorCallback(response);
      }
    }, errorCallback, 'POST');
  };

  window.SmartestTinyMceAttachmentEditor = window.SmartestTinyMceAttachmentEditor || {};
  window.SmartestTinyMceAttachmentEditor.applyAttachmentData = function(editorId, data){
    var editor = window.tinymce.get(editorId);

    if(editor){
      applyAttachmentData(editor, data);
    }
  };

  window.SmartestTinyMceAttachmentEditor.openFullDefinition = function(editorId, attachmentName, url){
    var editor = window.tinymce.get(editorId);
    var proceed = function(){
      window.location = url;
    };

    if(editor){
      if(!window.confirm('Save this text before opening the full attachment editor?')){
        return;
      }
      saveEditorContent(editor, proceed, function(response){
        var message = response && response.message ? response.message : 'The text could not be saved.';
        if(window.confirm(message + ' Open the full attachment editor anyway?')){
          proceed();
        }
      });
    }else{
      proceed();
    }
  };

  var selectedAttachmentButtonSetup = function(editor){
    return function(api){
      var update = function(){
        api.setEnabled(!!getAttachmentFigure(editor));
      };

      editor.on('NodeChange click keyup SetContent', update);
      update();

      return function(){
        editor.off('NodeChange click keyup SetContent', update);
      };
    };
  };

  var convertAttachmentTagsToFigures = function(content){
    if(typeof content !== 'string' || content.indexOf('attachment') < 0){
      return content;
    }

    content = content.replace(attachmentTagPattern, function(tag, attributes){
      var name = normalizeAttachmentName(getAttributeFromString(attributes, 'name'));
      return name ? buildAttachmentFigure(name, tag) : tag;
    });

    return content.replace(protectedAttachmentTagPattern, function(tag, attributes){
      var name = normalizeAttachmentName(getAttributeFromString(attributes, 'name'));
      return name ? buildAttachmentFigure(name, buildAttachmentTagFromAttributes(attributes)) : tag;
    });
  };

  var convertAttachmentFiguresToTags = function(content){
    if(typeof content !== 'string' || content.indexOf('sm-attachment-proxy') < 0){
      return content;
    }

    return content.replace(attachmentFigurePattern, function(figure, beforeClass, quote, className, afterClass){
      var attributes = beforeClass + ' class=' + quote + className + quote + afterClass;
      var name = normalizeAttachmentName(getAttributeFromString(attributes, 'data-attachmentname'));
      var tag = getAttributeFromString(attributes, 'data-smartesttag');

      if(!name){
        return figure;
      }

      return tag || buildAttachmentTag(name);
    }).replace(/<p\b[^>]*\bclass=(["'])(?=[^"']*\bsm-attachment-buffer\b)[^"']*\1[^>]*>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/gi, '');
  };

  var installAttachmentRoundTrip = function(editor){
    editor.on('BeforeSetContent', function(event){
      event.content = convertAttachmentTagsToFigures(event.content);
    });

    editor.on('init SetContent', function(){
      refreshAttachmentFigures(editor);
    });

    editor.on('PostProcess', function(event){
      if(event.get){
        event.content = convertAttachmentFiguresToTags(event.content);
      }
    });

    editor.on('SaveContent', function(event){
      event.content = convertAttachmentFiguresToTags(event.content);
    });

    editor.on('click', function(event){
      var figure = getAttachmentFigure(editor, event.target);

      if(figure){
        editor.selection.select(figure);
      }
    });

    editor.on('dblclick', function(event){
      var figure = getAttachmentFigure(editor, event.target);

      if(figure){
        event.preventDefault();
        editor.selection.select(figure);
        editAttachmentFigure(editor);
      }
    });

    editor.on('keydown', function(event){
      var keyCode = event.keyCode || event.which;

      if((keyCode === 8 || keyCode === 46) && getAttachmentFigure(editor)){
        event.preventDefault();
        removeAttachmentFigure(editor);
      }
    });
  };

  window.tinymce.PluginManager.add('smartestattachments', function(editor){
    if(editor.options && editor.options.register){
      editor.options.register('smartest_asset_id', {
        processor: 'string',
        default: ''
      });
    }
    
    installAttachmentRoundTrip(editor);

    editor.ui.registry.addButton('smartestattachmentlabel', {
      text: 'Attachments',
      tooltip: 'Smartest attachments',
      onAction: function(){}
    });

    editor.ui.registry.addButton('smartestattachmentadd', {
      icon: 'plus',
      tooltip: 'Add Smartest attachment',
      onAction: function(){
        insertAttachmentFigure(editor);
      }
    });

    editor.ui.registry.addButton('smartestattachment', {
      icon: 'plus',
      tooltip: 'Add Smartest attachment',
      onAction: function(){
        insertAttachmentFigure(editor);
      }
    });

    editor.ui.registry.addButton('smartestattachmentedit', {
      icon: 'edit-block',
      tooltip: 'Edit selected Smartest attachment',
      onAction: function(){
        editAttachmentFigure(editor);
      },
      onSetup: selectedAttachmentButtonSetup(editor)
    });

    editor.ui.registry.addButton('smartestattachmentdelete', {
      icon: 'minus',
      tooltip: 'Delete selected Smartest attachment placeholder',
      onAction: function(){
        removeAttachmentFigure(editor);
      },
      onSetup: selectedAttachmentButtonSetup(editor)
    });

    editor.ui.registry.addMenuItem('smartestattachmentedit', {
      text: 'Edit Smartest attachment',
      onAction: function(){
        editAttachmentFigure(editor);
      },
      onSetup: selectedAttachmentButtonSetup(editor)
    });

    editor.ui.registry.addMenuItem('smartestattachmentdelete', {
      text: 'Delete Smartest attachment',
      onAction: function(){
        removeAttachmentFigure(editor);
      },
      onSetup: selectedAttachmentButtonSetup(editor)
    });
  });

  var originalInit = window.tinymce.init;

  window.tinymce.init = function(settings){
    settings = settings || {};

    if(!settings.license_key){
      settings.license_key = 'gpl';
    }

    settings.plugins = normalizePlugins(settings.plugins);
    settings.toolbar = normalizeToolbar(settings.toolbar);

    if(!settings.skin || settings.skin === 'smartest'){
      settings.skin = 'oxide';
    }

    if(!settings.content_css || settings.content_css === 'smartest'){
      settings.content_css = 'default';
    }

    if(typeof settings.promotion === 'undefined'){
      settings.promotion = false;
    }

    if(typeof settings.convert_urls === 'undefined'){
      settings.convert_urls = false;
    }

    settings.content_style = settings.content_style ? settings.content_style + '\n' + attachmentContentStyle : attachmentContentStyle;

    return originalInit.call(window.tinymce, settings);
  };
})();
