<script language="javascript">

// var acceptable_suffixes = {$suffixes};
// var input_mode = '{$starting_mode}';
// var show_params_holder = false;
var SiteNameFieldDefaultValue = 'My Smartest Web Site';
var SiteDomainFieldDefaultValue = 'example.com'
var preventDefaultValue = true;

{literal}

document.observe('dom:loaded', function(){

    $('new-site-name').observe('focus', function(){
        if(($('new-site-name').getValue() == SiteNameFieldDefaultValue)|| $('new-site-name').getValue() == ''){
            $('new-site-name').removeClassName('unfilled');
            $('new-site-name').setValue('');
        }
    });

    $('new-site-name').observe('blur', function(){
        if(($('new-site-name').getValue() == SiteNameFieldDefaultValue) || $('new-site-name').getValue() == ''){
            $('new-site-name').addClassName('unfilled');
            $('new-site-name').setValue(SiteNameFieldDefaultValue);
        }else{
            $('new-site-name').removeClassName('error');
        }
    });

    $('new-site-domain').observe('focus', function(){
        if(($('new-site-domain').getValue() == SiteDomainFieldDefaultValue)|| $('new-site-domain').getValue() == ''){
            $('new-site-domain').removeClassName('unfilled');
            $('new-site-domain').setValue('');
        }
    });

    $('new-site-domain').observe('blur', function(){
        if(($('new-site-domain').getValue() == SiteDomainFieldDefaultValue) || $('new-site-domain').getValue() == ''){
            $('new-site-domain').addClassName('unfilled');
            $('new-site-domain').setValue(SiteDomainFieldDefaultValue);
        }else{
            $('new-site-domain').removeClassName('error');
        }
    });

    $('new-site-form').observe('submit', function(e){

        if(($('new-site-name').getValue() == SiteNameFieldDefaultValue) || $('new-site-name').getValue() == ''){
            $('new-site-name').addClassName('error');
            e.stop();
        }

        if(($('new-site-domain').getValue() == SiteDomainFieldDefaultValue) || $('new-site-domain').getValue() == ''){
            $('new-site-domain').addClassName('error');
            e.stop();
        }

        if($('site-admin-email').getValue() == ''){
            $('site-admin-email').addClassName('error');
            e.stop();
        }

    });

    if($('buildkit-selector')){
        $('buildkit-selector').observe('change', function(){
            if($F('buildkit-selector') == '_NONE'){
                $('buildkit-options').hide();
                $('buildkit-options').update('');
                new Effect.BlindDown('no-buildkit-options', {duration: 0.4});
            }else{
                new Effect.BlindUp('no-buildkit-options', {duration: 0.3});
                new Ajax.Updater('buildkit-options', sm_domain+'ajax:desktop/buildKitOptions', {
                    parameters: {buildkit_id: $F('buildkit-selector')},
                    onSuccess: function(){
                        new Effect.BlindDown('buildkit-options', {duration: 0.4});
                    },
                    evalScripts: true
                });
            }
        });
    }

});

function toggleDataStructureConfiguration(state){
    if(state){
        new Effect.BlindDown('datastructure-options', {duration: 0.4});
    }else{
        new Effect.BlindUp('datastructure-options', {duration: 0.4});
    }
}

function togglePageStructureConfiguration(state){
    if(state){
        new Effect.BlindDown('pagestructure-options', {duration: 0.4});
    }else{
        new Effect.BlindUp('pagestructure-options', {duration: 0.4});
    }
}

function toggleTemplatesConfiguration(state){
    if(state){
        new Effect.BlindDown('templates-options', {duration: 0.4});
    }else{
        new Effect.BlindUp('templates-options', {duration: 0.4});
    }
}

function toggleContentConfiguration(state){
    if(state){
        new Effect.BlindDown('content-options', {duration: 0.4});
    }else{
        new Effect.BlindUp('content-options', {duration: 0.4});
    }
}

{/literal}
</script>

<div id="work-area">

<h3 id="siteName">Create a New Site</h3>

{foreach from=$errors item="error"}
<div class="error">{$error}</div>
{/foreach}

<form id="new-site-form" name="buildSite" action="{$domain}{$section}/buildSite" method="post" style="margin:0px" enctype="multipart/form-data">

<div id="edit-form-layout">

<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />

<div class="edit-form-row">
  <div class="form-section-label">Site Title *</div>
  <input type="text" name="site_name" class="unfilled" id="new-site-name" value="My Smartest Web Site"/>
</div>

<div class="edit-form-row">
  <div class="form-section-label">Host name *</div>
  <input type="text" name="site_domain" class="unfilled" id="new-site-domain" value="example.com" />
</div>

<div class="edit-form-row">
  <div class="form-section-label">Your name</div>
  <input type="text" name="site_organization_name" id="new-site-org" value="{$user.full_name}" />
  <div class="form-hint">Optional. The name of the person or organisation behind this website, rather than the name of the site.</div>
</div>

<div class="edit-form-row">
  <div class="form-section-label">Logo</div>
  <input type="file" name="site_logo" /><div class="form-hint">Optional: Pick an image to represent this site when you first log in.</div>
</div>

<div class="edit-form-row">
  <div class="form-section-label">Admin email</div>
  <input type="text" name="site_admin_email" value="{$user.email}" id="site-admin-email" />
</div>

<div class="edit-form-row">
  <div class="form-section-label">Build Kit</div>
  <select name="use_buildkit" id="buildkit-selector">
    <option value="_NONE">None</option>
    {foreach from=$buildkits item="buildkit"}
    <option value="{$buildkit.shortname}">{$buildkit.label}</option>
    {/foreach}
  </select>
  <div class="form-hint">Optional. Build Kits can create starter files, models, templates, pages and sample content for this site.</div>
</div>

<div id="buildkit-options" style="display:none"></div>

<div id="no-buildkit-options">
<div class="edit-form-row">
  <div class="form-section-label">Master template</div>
  <select name="site_master_template">
    <option value="_BLANK"{if !$allow_create_master_tpl} disabled="disabled"{/if}>Create a new, blank template{if !$allow_create_master_tpl} (directory is not writable){/if}</option>
    <option value="_DEFAULT">None for now, I will create one later</option>
    {foreach from=$templates item="template"}
    <option value="{$template.url}">Use {$template.url}</option>
    {/foreach}
  </select>
</div>
</div>

<div class="buttons-bar">
  <input type="button" value="Cancel" onclick="window.location='{$domain}smartest'" />
  <input type="submit" name="action" value="Create new site" />
</div>

</div>

</div>

<div id="actions-area">

</div>

</form>
