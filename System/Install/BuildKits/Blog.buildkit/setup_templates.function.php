<?php

function buildkit_sm_blog_setup_templates($buildkit, $site, $user, $options){
    
    $is_javascript = create_file('infinitescroller.js', "Infinite scroll javascript", 'SM_ASSETTYPE_JAVASCRIPT');
    register_file($is_javascript, 'is_javascript');
    
    $master_template = create_page_template('main.tpl', $site->getName().' page template');
    register_file($master_template, 'master_template');

    $blog_home_template = create_container_template('blog_post_front.tpl', 'Blog index layout');
    register_file($blog_home_template, 'blog_home_template');

    $blog_post_template = create_container_template('blog_post.tpl', 'Blog post layout');
    register_file($blog_post_template, 'blog_post_template');
    
    install_app('BlogInfiniteScroll');
    replace_file_tokens($is_javascript);
    
}
