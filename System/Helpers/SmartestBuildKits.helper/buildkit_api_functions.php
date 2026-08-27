<?php

/** Files API Functions **/

function create_file($resource_filename_within_buildkit, $label, $asset_type_code=null, $desired_filename=''){
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, $asset_type_code, $desired_filename);    
}

function register_file($file, $registration_name){
    SmartestBuildKitsHelper::registerObject(SmartestStringHelper::toVarName($registration_name), $file);    
}

function get_registered_file_if_exists($registration_name){
    if(SmartestBuildKitsHelper::registeredObjectExists(SmartestStringHelper::toVarName($registration_name))){
        return SmartestBuildKitsHelper::getRegisteredObject($registration_name);
    }else{
        return null;
    }
}

function create_file_group($type, $label){
    return SmartestBuildKitsHelper::createFileGroup($type, $label);
}

function create_file_group_with_files($files, $type, $label){
    return SmartestBuildKitsHelper::createFileGroupWithFiles($files, $type, $label);
}

function create_file_gallery($files, $type, $label){
    return SmartestBuildKitsHelper::createFileGroupWithFiles($files, $type, $label, true);
}

function register_file_group($file_group, $registration_name){
    SmartestBuildKitsHelper::registerObject(SmartestStringHelper::toVarName($registration_name), $file_group);    
}

function get_registered_file_group_if_exists($registration_name){
    if(SmartestBuildKitsHelper::registeredObjectExists(SmartestStringHelper::toVarName($registration_name))){
        return SmartestBuildKitsHelper::getRegisteredObject($registration_name);
    }else{
        return null;
    }
}

function add_file_to_group($file, $group){
    return SmartestBuildKitsHelper::addFileToGroup($file, $group);
}

/*** Templates stuff ***/

function create_page_template($resource_filename_within_buildkit, $label, $desired_filename=''){
    $site = $GLOBALS['_buildkit_executing_site'];
    if(!strlen($desired_filename)){
        $desired_filename = SmartestStringHelper::toVarName($site->getName()).'_'.$resource_filename_within_buildkit;
    }
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_MASTER_TEMPLATE', $desired_filename);    
}

function create_container_template($resource_filename_within_buildkit, $label){
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_CONTAINER_TEMPLATE');    
}

function create_itemspace_template($resource_filename_within_buildkit, $label){
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_ITEMSPACE_TEMPLATE');    
}

function create_list_template($resource_filename_within_buildkit, $label){
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_COMPOUND_LIST_TEMPLATE');    
}

function create_item_template($resource_filename_within_buildkit, $label){
    return SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_SINGLE_ITEM_TEMPLATE');    
}

function create_blocklist_template($resource_filename_within_buildkit, $label, $blocklist_style='default'){
    $site = $GLOBALS['_buildkit_executing_site'];
    $file = SmartestBuildKitsHelper::createFile($resource_filename_within_buildkit, $label, 'SM_ASSETTYPE_BLOCKLIST_TEMPLATE');
    $styles = $site->getBlockListStyles();
    if(!isset($styles[$blocklist_style]) || $blocklist_style == 'default'){
        $style = $site->getDefaultBlockListStyle();
    }elseif(isset($styles[$blocklist_style])){
        $style = $styles[$blocklist_style];
    }
    $style->addBlockTemplateById($file->getId());
    return $file;
}

function create_template_group($files, $type, $label){
    return SmartestBuildKitsHelper::createFileGroupWithFiles($files, $type, $label);
}

/** Data structures and models **/

function create_dropdown_menu($label, $options=''){
    return SmartestBuildKitsHelper::createDropdownMenu($label, $options);
}

function create_dropdown_menu_option($menu, $label, $value=null){
    return SmartestBuildKitsHelper::createDropdownMenuOption($menu, $label, $value);
}

function create_model($single_name, $plural_name){
    return SmartestBuildKitsHelper::createInitialModel($single_name, $plural_name);
}

function register_model($model, $registration_name){
    SmartestBuildKitsHelper::registerObject(SmartestStringHelper::toVarName($registration_name), $model);    
}

function get_registered_model_if_exists($registration_name){
    if(SmartestBuildKitsHelper::registeredObjectExists(SmartestStringHelper::toVarName($registration_name))){
        return SmartestBuildKitsHelper::getRegisteredObject($registration_name);
    }else{
        return null;
    }
}

function create_model_property($name, $model, $type, $required=false, $foreign_key_filter=null){
    return SmartestBuildKitsHelper::createModelProperty($name, $model, $type, $required, $foreign_key_filter);
}

function create_item($name, $model){
    return SmartestBuildKitsHelper::createItem($name, $model);
}

function create_set($label, $model){
    return SmartestBuildKitsHelper::createSet($label, $model);
}

function create_dynamic_set($label, $model){
    return SmartestBuildKitsHelper::createSet($label, $model, true);
}

function create_tag($label, $name=null){
    return SmartestBuildKitsHelper::createTag($label, $name);
}

function add_item_to_set($item, $set){
    return SmartestBuildKitsHelper::addItemToSet($item, $set);
}

function create_set_rule($set, $property, $value, $operator=''){
    return SmartestBuildKitsHelper::createSetRule($set, $property, $value, $operator);
}

/** Page structures **/

function create_page($title, $url, $parent_page='home', $template_name=null){
    return SmartestBuildKitsHelper::createPage($title, $url, false, $parent_page, $template_name);
}

function create_section_page($title, $url, $parent_page='home', $template_name=null){
    return SmartestBuildKitsHelper::createPage($title, $url, true, $parent_page, $template_name);
}

function create_meta_page($title, $url, $model, $parent_page='home', $template_name=null){
    return SmartestBuildKitsHelper::createMetaPage($title, $url, $model, $parent_page, $template_name);
}

function create_page_group($label){
    return SmartestBuildKitsHelper::createPageGroup($label);
}

function add_page_to_group($page, $group){
    return SmartestBuildKitsHelper::addPageToGroup($page, $group);
}

function create_placeholder($name, $type, $label='', $file_group=null){
    return SmartestBuildKitsHelper::createPlaceholder($name, $type, $label, $file_group);
}

function create_container($name, $label='', $template_group=null){
    return SmartestBuildKitsHelper::createContainer($name, $label, $template_group);
}

function create_field($name, $type, $site_wide=false, $foreign_key_filter=null){
    return SmartestBuildKitsHelper::createField($name, $type, $site_wide, $foreign_key_filter);
}

function create_itemspace($name, $dataset_id, $template_id=null){
    return SmartestBuildKitsHelper::createItemSpace($name, $dataset_id, $template_id);
}

function define_placeholder($placeholder, $file, $page, $item_id=null){
    return SmartestBuildKitsHelper::definePlaceholder($placeholder, $file, $page, $item_id);
}

function define_container($container, $template, $page, $item_id=null){
    return SmartestBuildKitsHelper::defineContainer($container, $template, $page, $item_id);
}

function define_field($name, $value, $page=''){
    return SmartestBuildKitsHelper::defineField($name, $value, $page);
}

function define_list($name, $set, $template, $limit=0, $title='', $page='home'){
    return SmartestBuildKitsHelper::defineList($name, $set, $template, $limit, $title, $page);
}

function define_itemspace($itemspace, $item, $page){
    return SmartestBuildKitsHelper::defineItemspace($itemspace, $item, $page);
}

/** Other functions **/

function install_app($folder_name){
    return SmartestBuildKitsHelper::installApp($folder_name);
}
