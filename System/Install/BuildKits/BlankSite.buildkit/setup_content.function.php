<?php

function buildkit_sm_blank_site_setup_content($buildkit, $site, $user, $options){

    register_value(date('r'), 'time');
    register_value('background:#333;', 'colour');

    $css_filename = SmartestStringHelper::toVarName($site->getName()).'.css';
    $css = create_file('default.css', 'Main CSS file for '.$site->getName(), 'SM_ASSETTYPE_STYLESHEET', $css_filename);
    register_file($css, 'main_css_file');

}
