<?php

function buildkit_sm_blog_setup_data_structures($buildkit, $site, $user, $options){
    
    $blog_post_model = create_model('Blog post', 'Blog posts');
    register_model($blog_post_model, 'blog_post_model');
    
    $sort_property = create_model_property('Date published', $blog_post_model, 'SM_DATATYPE_DATE', true);
    create_model_property('Synopsis', $blog_post_model, 'SM_DATATYPE_ML_TEXT', true);
    create_model_property('Main text', $blog_post_model, 'SM_DATATYPE_ASSET', true, 'SM_ASSETCLASS_RICH_TEXT');
    create_model_property('Thumbnail image', $blog_post_model, 'SM_DATATYPE_ASSET', true, 'SM_ASSETCLASS_STATIC_IMAGE');
    
    $blog_post_model->init();
    
    if(BUILDKIT_EXECUTE_CONTENT){
        $sample_blog_post = create_item('Your first blog post', $blog_post_model);
        // These files were created by the content function of the build kit
        $thumbnail_image = get_registered_file_if_exists('blog_post_thumbnail');
        $text = get_registered_file_if_exists('blog_post_text');
        $sample_blog_post->setThumbnailImage($thumbnail_image->getId());
        $sample_blog_post->setMainText($text);
        $sample_blog_post->setDatePublished(time());
        $sample_blog_post->setSynopsis("This is your first blog post. Hopefully you'll create many more.");
        $sample_blog_post->save();
    }
    
    $homepage_set = create_dynamic_set('Blog posts main', $blog_post_model);
    $rule = create_set_rule($homepage_set, SmartestCmsItem::NAME, 'XXX', SmartestQuery::NOT_EQUAL);
    $homepage_set->setSortField($sort_property->getId());
    $homepage_set->setSortDirection('DESC');
    $homepage_set->save();
    
}