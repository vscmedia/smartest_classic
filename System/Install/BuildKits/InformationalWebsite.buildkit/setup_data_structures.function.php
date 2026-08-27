<?php

function buildkit_sm_infosite_setup_data_structures($buildkit, $site, $user, $options){
    
    $faq_model = create_model('Frequently asked question', 'Frequently asked questions');
    register_model($faq_model, 'faq_model');
    
    $sort_property = create_model_property('Date published', $faq_model, 'SM_DATATYPE_DATE', true);
    create_model_property('Question', $faq_model, 'SM_DATATYPE_ML_TEXT', true);
    create_model_property('Answer text', $faq_model, 'SM_DATATYPE_ASSET', true, 'SM_ASSETCLASS_RICH_TEXT');
    
    $faq_model->init();
    
    if(BUILDKIT_EXECUTE_CONTENT){
        $sample_faq = create_item('First FAQ', $faq_model);
        // These files were created by the content function of the build kit
        $text = get_registered_file_if_exists('sample_faq_text');
        if($text){
            $sample_faq->setAnswerText($text);
        }
        $sample_faq->setDatePublished(time());
        $sample_faq->setQuestion("This is a sample FAQ");
        $sample_faq->save();
    }
    
    $sample_set = create_dynamic_set('FAQ main', $faq_model);
    $rule = create_set_rule($sample_set, SmartestCmsItem::NAME, 'XXX', SmartestQuery::NOT_EQUAL);
    $sample_set->setSortField($sort_property->getId());
    $sample_set->setSortDirection('DESC');
    $sample_set->save();
    
}
