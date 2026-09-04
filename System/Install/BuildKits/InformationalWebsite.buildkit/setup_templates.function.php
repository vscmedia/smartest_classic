<?php

function buildkit_sm_infosite_setup_templates($buildkit, $site, $user, $options){
    $master_template = create_page_template('main.tpl', $site->getName().' page template');
    register_file($master_template, 'master_template');

    $page_template = create_container_template('page.tpl', 'Standard page layout');
    register_file($page_template, 'page_template');
    
}
