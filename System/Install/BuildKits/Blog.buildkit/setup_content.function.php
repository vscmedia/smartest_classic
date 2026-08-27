<?php

function buildkit_sm_blog_setup_content($buildkit, $site, $user, $options){
    
    $css = create_file('blog-main.css', "Main CSS file");
    register_file($css, 'main_css_file');
    
    $blog_post_text = create_file('first blog post.html', "First Blog Post Text", 'SM_ASSETTYPE_RICH_TEXT');
    register_file($blog_post_text, 'blog_post_text');
    
    $elephant = create_file('African_elephant_warning_raised_trunk.jpg', "Elephant with raised trunk", 'SM_ASSETTYPE_JPEG_IMAGE');
    register_file($elephant, 'blog_post_thumbnail');
    
    $group = create_file_group_with_files(array($blog_post_text), 'SM_ASSETTYPE_RICH_TEXT', 'Blog post texts');
    register_file_group($group, 'blog_post_texts_group');
    
}