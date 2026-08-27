<?php

function buildkit_sm_infosite_setup_content($buildkit, $site, $user, $options){
    $css = create_file('info-main.css', 'Main CSS file');
    register_file($css, 'main_css_file');
    
}
