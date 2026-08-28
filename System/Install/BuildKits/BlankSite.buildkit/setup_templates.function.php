<?php

function buildkit_sm_blank_site_setup_templates($buildkit, $site, $user, $options){

    $master_template_filename = SmartestStringHelper::toVarName($site->getName()).'.tpl';
    $master_template = create_page_template('default.tpl', $site->getName().' master template', $master_template_filename);
    $main_css = get_registered_file_if_exists('main_css_file');
    $css_link = $main_css instanceof SmartestAsset ? '<?sm:stylesheet file="'.$main_css->getUrl().'":?>'."\n" : '';

    replace_file_tokens($master_template, array(
        '%CSSLINK%' => $css_link,
        '%DEFAULTTEMPLATENAME%.tpl' => $master_template->getUrl(),
        '%DEFAULTTEMPLATENAME%' => SmartestFileSystemHelper::removeDotSuffix($master_template->getUrl())
    ));

    register_file($master_template, 'master_template');
    set_standard_pages_template($master_template);

}
