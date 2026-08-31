<?php

function buildkit_sm_blog_setup_page_structures($buildkit, $site, $user, $options){

    $master_template = get_registered_file_if_exists('master_template');
    $home_page = $site->getHomePage();
    $main_nav = create_navigation_group('Main navigation', 'main_nav');

    add_page_to_group($home_page, $main_nav);
	    
    if(BUILDKIT_EXECUTE_TEMPLATES){
        
        $page_specific_javascript = create_placeholder('page_specific_javascript', 'SM_ASSETCLASS_JAVASCRIPT');
        $page_specific_stylesheet = create_placeholder('page_specific_stylesheet', 'SM_ASSETCLASS_STYLESHEET');
        $banner_image = create_placeholder('banner_image', 'SM_ASSETCLASS_STATIC_IMAGE');
        $page_layout = create_container('page_layout', 'Page layout');

        if($page_layout instanceof SmartestContainer && (int) $site->getPrimaryContainerId() != (int) $page_layout->getId()){
            $site->setPrimaryContainerId($page_layout->getId());
            $site->save();
        }
        
        $is_javascript = get_registered_file_if_exists('is_javascript');
        $blog_home_template = get_registered_file_if_exists('blog_home_template');
        $home_page = $site->getHomePage();
        define_placeholder('page_specific_javascript', $is_javascript, $home_page);
        define_container('page_layout', $blog_home_template, $home_page);

        if(BUILDKIT_EXECUTE_CONTENT){
            $thumbnail_image = get_registered_file_if_exists('blog_post_thumbnail');
            if($thumbnail_image instanceof SmartestAsset){
                define_placeholder('banner_image', $thumbnail_image, $home_page);
            }
        }
        
        $home_page->setDraftTemplate($master_template->getUrl());
        $home_page->save();
        
    }
    
    if(BUILDKIT_EXECUTE_DATA_STRUCTURES){
        $blog_post_model = get_registered_model_if_exists('blog_post_model');
        $blog_home_template = get_registered_file_if_exists('blog_home_template');
        $blog_post_template = get_registered_file_if_exists('blog_post_template');
        $list_page = create_page('Blog list page', '/posts', $home_page, $master_template);
        $list_page->setDraftTemplate($master_template->getUrl());
        $list_page->save();
        add_page_to_group($list_page, $main_nav);
        define_container('page_layout', $blog_home_template, $list_page);

        $blog_page = create_meta_page('Blog post', '/posts/:name', $blog_post_model, $list_page, $master_template);
        $blog_post_model->setDefaultMetaPageId($site->getId(), $blog_page->getId());
        $blog_page->setDraftTemplate($master_template->getUrl());
        $blog_page->save();
        define_container('page_layout', $blog_post_template, $blog_page);
    }
    
    $site_blurb = create_field('site_blurb', 'SM_DATATYPE_SL_TEXT', true);
    $copyright_owner = create_field('copyright_owner', 'SM_DATATYPE_SL_TEXT', true);
    $highllight_color = create_field('highlight_color', 'SM_DATATYPE_RGB_COLOR', true);
    
    define_field('site_blurb', 'Test value');
    define_field('copyright_owner', 'Copyright owner');
    
}
