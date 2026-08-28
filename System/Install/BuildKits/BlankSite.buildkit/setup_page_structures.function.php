<?php

function buildkit_sm_blank_site_setup_page_structures($buildkit, $site, $user, $options){

    $page_specific_javascript = create_placeholder('page_specific_javascript', 'SM_ASSETCLASS_JAVASCRIPT');
    $page_specific_stylesheet = create_placeholder('page_specific_stylesheet', 'SM_ASSETCLASS_STYLESHEET');
    $page_layout = create_container('page_layout', 'Page layout');
    $main_nav = create_page_group('Main nav');
    $home_page = $site->getHomePage(true);

    if($page_layout instanceof SmartestContainer){
        $site->setPrimaryContainerId($page_layout->getId());
        $site->save();
    }

    add_page_to_group($home_page, $main_nav);

}
