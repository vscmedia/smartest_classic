<?php

function buildkit_sm_infosite_setup_page_structures($buildkit, $site, $user, $options){
    
    $page_specific_javascript = create_placeholder('page_specific_javascript', 'SM_ASSETCLASS_JAVASCRIPT');
    $page_specific_stylesheet = create_placeholder('page_specific_stylesheet', 'SM_ASSETCLASS_STYLESHEET');
    $page_layout = create_container('page_layout', 'Page layout');
    $master_template = null;
    $page_template = null;
    $main_nav = create_navigation_group('Main navigation', 'main_nav');

    $home_page = $site->getHomePage();
    add_page_to_group($home_page, $main_nav);

    if(BUILDKIT_EXECUTE_TEMPLATES){
        $master_template = get_registered_file_if_exists('master_template');
        $page_template = get_registered_file_if_exists('page_template');

        if($master_template instanceof SmartestAsset){
            $home_page->setDraftTemplate($master_template->getUrl());
            $home_page->save();
        }

        if($page_template instanceof SmartestAsset){
            define_container('page_layout', $page_template, $home_page);
        }
    }
    
    if(BUILDKIT_EXECUTE_DATA_STRUCTURES){
        $faq_model = get_registered_model_if_exists('faq_model');
        if($faq_model){
            $list_page = create_page('Frequently Asked Questions', '/faqs', 'home', $master_template);
            add_page_to_group($list_page, $main_nav);

            if($page_template instanceof SmartestAsset){
                define_container('page_layout', $page_template, $list_page);
            }

            $faq_page = create_meta_page('FAQ', '/faqs/:name', $faq_model, $list_page, $master_template);
            if($page_template instanceof SmartestAsset){
                define_container('page_layout', $page_template, $faq_page);
            }

            $faq_model->setDefaultMetaPageId($site->getId(), $faq_page->getId());
            $faq_model->save();
        }
    }
    
    $site_blurb = create_field('site_blurb', 'SM_DATATYPE_SL_TEXT', true);
    
    define_field('site_blurb', 'Test value');
    
}
