<?php

class SmartestBuildKitsHelper{

    public static function logBuildKit($message, $level=SM_LOG_DEBUG){
        $log_name = isset($GLOBALS['_buildkit_execution_log']) && strlen((string) $GLOBALS['_buildkit_execution_log']) ? (string) $GLOBALS['_buildkit_execution_log'] : 'system';
        SmartestLog::getInstance($log_name)->log('Build Kit API: '.$message, $level);
    }

    public static function prepareRequestParamsForBuildKit($request_params, SmartestBuildKit $buildkit){

        if($request_params instanceof SmartestParameterHolder){
            $request_params = $request_params->getArray();
        }

        if(!is_array($request_params)){
            $request_params = array();
        }

        $sections = array(
            'general' => array(
                'option_key' => 'general_options',
                'method' => 'getMainConfigurationOptions',
                'enabled' => true,
                'required' => true
            ),
            'data_structures' => array(
                'option_key' => 'data_structures_options',
                'method' => 'getDataStructureConfigurationOptions',
                'enabled' => $buildkit->getDataStructureIsEnabled(),
                'required' => $buildkit->getDataStructureIsRequired()
            ),
            'page_structures' => array(
                'option_key' => 'page_structures_options',
                'method' => 'getPageStructureConfigurationOptions',
                'enabled' => $buildkit->getPageStructureIsEnabled(),
                'required' => $buildkit->getPageStructureIsRequired()
            ),
            'templates' => array(
                'option_key' => 'templates_options',
                'method' => 'getTemplatesConfigurationOptions',
                'enabled' => $buildkit->getTemplatesAreEnabled(),
                'required' => $buildkit->getTemplatesAreRequired()
            ),
            'content' => array(
                'option_key' => 'content_options',
                'method' => 'getContentConfigurationOptions',
                'enabled' => $buildkit->getContentIsEnabled(),
                'required' => $buildkit->getContentIsRequired()
            )
        );

        $final_options = array('execute_confirm' => array());

        foreach($sections as $section_name => $settings){
            $final_options[$settings['option_key']] = array();
            $section_values = isset($request_params[$section_name]) && is_array($request_params[$section_name]) ? $request_params[$section_name] : array();

            foreach($buildkit->{$settings['method']}() as $option){
                $key = $option->getName();
                $final_options[$settings['option_key']][$key] = self::resolveConfigurationOptionValue($option, $section_values);
            }

            if($section_name != 'general'){
                $optional_value = isset($request_params['execute_optional'][$section_name]) ? $request_params['execute_optional'][$section_name] : 'TRUE';
                $final_options['execute_confirm'][$section_name] = $settings['required'] || ($settings['enabled'] && !SmartestStringHelper::isFalse($optional_value));
            }
        }

        return $final_options;
    }

    public static function registerObject($name, $object){
        self::getRegistry()->setParameter(SmartestStringHelper::toVarName($name), $object);
    }

    public static function unregisterObject($name){
        self::getRegistry()->clearParameter(SmartestStringHelper::toVarName($name));
    }

    public static function registerValue($name, $value){
        self::registerObject($name, $value);
    }

    public static function registeredObjectExists($name){
        return self::getRegistry()->hasParameter(SmartestStringHelper::toVarName($name));
    }

    public static function getRegisteredObject($name){
        return self::getRegistry()->getParameter(SmartestStringHelper::toVarName($name));
    }

    public static function clearRegisteredObjects(){
        $GLOBALS['_buildkit_registered_objects'] = new SmartestParameterHolder('Objects used for executing buildkits');
    }

    public static function createFile($filename, $label, $file_type_code='', $desired_filename=''){

        $buildkit = self::getExecutingBuildKit();
        $alh = new SmartestAssetsLibraryHelper;
        $full_path = $buildkit->getDirectory().'Resources/'.$filename;
        self::logBuildKit("Creating resource file '".$filename."' as '".$label."'".(strlen((string) $file_type_code) ? " with type ".$file_type_code : ' with type inferred from suffix').'.');

        if(!is_file($full_path)){
            throw new SmartestBuildKitException("Build Kit resource file '".$full_path."' does not exist.");
        }

        if(strlen((string) $file_type_code)){
            $file_type = $alh->getTypeInfoFromTypeCode($file_type_code);
            if(!is_array($file_type)){
                throw new SmartestBuildKitException("Build Kit resource file '".$filename."' uses unsupported file type '".$file_type_code."'.");
            }
        }else{
            $suffix = strtolower(SmartestStringHelper::getDotSuffix($filename));
            $file_type = $alh->getTypeInfoBySuffix($suffix);
            if(!is_array($file_type)){
                throw new SmartestBuildKitException("Build Kit resource file '".$filename."' has an unknown suffix '".$suffix."'.");
            }
            $file_type_code = $file_type['id'];
        }

        if(isset($file_type['storage']['type']) && $file_type['storage']['type'] == 'database'){
            self::logBuildKit("Resource file '".$filename."' will be saved as database-backed text asset.");
            return self::createTextFragmentFile($filename, SmartestFileSystemHelper::load($full_path, true), $label, $file_type_code);
        }

        return self::createDiskFile($full_path, $label, $file_type_code, $desired_filename);
    }

    public static function createDiskFile($full_file_path, $label, $file_type_code, $desired_filename=''){

        $user = self::getExecutingUser();
        $site = self::getExecutingSite();
        $alh = new SmartestAssetsLibraryHelper;
        $file_type_info = $alh->getTypeInfoFromTypeCode($file_type_code);
        self::logBuildKit("Preparing disk-backed asset '".$label."' for site #".$site->getId()." from '".$full_file_path."'.");

        if(!is_array($file_type_info) || !isset($file_type_info['storage']['location'])){
            throw new SmartestBuildKitException("Build Kit could not create file because asset type '".$file_type_code."' does not define a storage location.");
        }

        $dir = SM_ROOT_DIR.$file_type_info['storage']['location'];
        self::logBuildKit("Resolved storage directory for '".$label."' to '".$dir."'.");

        if(!is_dir($dir)){
            self::logBuildKit("Storage directory '".$dir."' does not exist; attempting to create it.", SM_LOG_WARNING);
            if(!@mkdir($dir, 0777, true)){
                throw new SmartestBuildKitException("Build Kit could not create file storage directory '".$dir."'.");
            }
        }

        if(!is_writable($dir)){
            throw new SmartestBuildKitException("Build Kit cannot write to file storage directory '".$dir."'.");
        }

        if(!strlen((string) $desired_filename)){
            $desired_filename = SmartestFileSystemHelper::getFileName($full_file_path);
        }

        $desired_filename = str_replace(' ', '_', SmartestFileSystemHelper::getFileName($desired_filename));
        $new_file_path = SmartestFileSystemHelper::getUniqueFileName($dir.$desired_filename);

        if(!strlen((string) $new_file_path)){
            throw new SmartestBuildKitException("Build Kit could not determine a unique destination path for '".$desired_filename."'.");
        }
        self::logBuildKit("Destination for '".$label."' resolved to '".$new_file_path."'.");

        $url = SmartestFileSystemHelper::getFileName($new_file_path);

        if(self::fileCanReceiveTokenReplacements($full_file_path)){
            $contents = self::prepareResourceContents(SmartestFileSystemHelper::load($full_file_path));
            $operation = 'save prepared contents from';
            $copied = SmartestFileSystemHelper::save($new_file_path, $contents, true);
        }else{
            $operation = 'copy';
            $copied = SmartestFileSystemHelper::copy($full_file_path, $new_file_path);
        }

        if(!$copied){
            throw new SmartestBuildKitException("Build Kit could not ".$operation." '".$full_file_path."' to '".$new_file_path."'.");
        }

        $asset = new SmartestAsset;
        $asset->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $asset->setCreated(time());
        $asset->setModified(time());
        $asset->setStringId(SmartestStringHelper::toVarName($label));
        $asset->setLabel($label);
        $asset->setUserId($user->getId());
        $asset->setSiteId($site->getId());
        $asset->setType($file_type_code);
        $asset->setUrl($url);
        $asset->save();
        self::logBuildKit("Created disk-backed asset #".$asset->getId()." '".$asset->getLabel()."' at '".$asset->getUrl()."'.");

        return $asset;
    }

    public static function createTextFragmentFile($filename, $file_contents, $label, $file_type_code){

        $user = self::getExecutingUser();
        $site = self::getExecutingSite();
        self::logBuildKit("Creating database-backed text asset '".$label."' from '".$filename."' for site #".$site->getId().'.');

        $asset = new SmartestAsset;
        $asset->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $asset->setCreated(time());
        $asset->setModified(time());
        $asset->setStringId(SmartestStringHelper::toVarName($label));
        $asset->setLabel($label);
        $asset->setUserId($user->getId());
        $asset->setSiteId($site->getId());
        $asset->setType($file_type_code);
        $asset->setUrl($filename);
        $asset->getTextFragment()->setContent((string) $file_contents);
        $asset->connectTextFragmentOnSave();
        $asset->save();
        self::logBuildKit("Created database-backed text asset #".$asset->getId()." '".$asset->getLabel()."'.");

        return $asset;
    }

    public static function createFileGroup($type, $label, $gallery=false){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($label);
        $group = new SmartestAssetGroup;

        if($group->findBy('name', $name, $site->getId())){
            self::logBuildKit("Reusing existing file group #".$group->getId()." '".$label."' for site #".$site->getId().'.');
            return $group;
        }

        $group->setLabel($label);
        $group->setName($name);
        $group->setVarname($name);
        $group->setSiteId($site->getId());
        $group->setShared(0);
        $group->setWebId(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $group->setFilterValue($type);
        $group->setFilterType(self::getSetFilterTypeForCode($type));

        if($gallery){
            $group->setIsGallery(true);
        }

        $group->save();
        self::logBuildKit("Created file group #".$group->getId()." '".$label."' for site #".$site->getId().'.');

        return $group;
    }

    public static function createFileGroupWithFiles($files, $type, $label, $gallery=false){
        $group = self::createFileGroup($type, $label, $gallery);

        if(is_array($files)){
            foreach($files as $file){
                self::addFileToGroup($file, $group);
            }
        }

        return $group;
    }

    public static function addFileToGroup($file, $group){
        $file_id = self::resolveAssetId($file);
        $group = self::resolveAssetGroup($group);

        if($file_id && $group instanceof SmartestAssetGroup){
            $group->addAssetById($file_id, false);
            self::logBuildKit("Added asset #".$file_id." to file group #".$group->getId().'.');
            return true;
        }

        throw new SmartestBuildKitException('Build Kit could not add a file to a file group because the file or group could not be resolved.');
    }

    public static function replaceTokensInFile($file, $replacements=array()){

        $asset = self::resolveAsset($file);

        if(!$asset instanceof SmartestAsset){
            throw new SmartestBuildKitException('Build Kit could not replace tokens because the target file could not be resolved.');
        }

        $replacements = is_array($replacements) ? $replacements : array();
        $replacements = array_merge(self::getRegisteredObjectTokenReplacements(), $replacements);

        if(!count($replacements)){
            self::logBuildKit("No token replacements were needed for asset #".$asset->getId().'.');
            return true;
        }

        self::logBuildKit("Replacing ".count($replacements)." token(s) in asset #".$asset->getId()." '".$asset->getLabel()."'.");

        if($asset->usesTextFragment()){
            $content = $asset->getTextFragment()->getContent();
            $asset->getTextFragment()->setContent(self::applyTokenReplacements($content, $replacements));
            $asset->connectTextFragmentOnSave();
            $asset->save();
            self::logBuildKit("Replaced tokens in database-backed asset #".$asset->getId().'.');
            return true;
        }

        if($asset->usesLocalFile() && is_file($asset->getFullPathOnDisk()) && is_writable($asset->getFullPathOnDisk())){
            $content = SmartestFileSystemHelper::load($asset->getFullPathOnDisk(), true);
            $saved = SmartestFileSystemHelper::save($asset->getFullPathOnDisk(), self::applyTokenReplacements($content, $replacements), true);

            if($saved){
                self::logBuildKit("Replaced tokens in disk-backed asset #".$asset->getId()." at '".$asset->getFullPathOnDisk()."'.");
                return true;
            }
        }

        throw new SmartestBuildKitException("Build Kit could not replace tokens in asset #".$asset->getId()." because its file is missing or not writable.");
    }

    public static function defineTextAttachment($text_asset, $attachment_name, $attached_asset, $metadata=array()){

        $text_asset = self::resolveAsset($text_asset);
        $attached_asset = self::resolveAsset($attached_asset);
        $attachment_name = SmartestStringHelper::toVarName($attachment_name);
        $metadata = is_array($metadata) ? $metadata : array();

        if(!$text_asset instanceof SmartestAsset || !$text_asset->usesTextFragment()){
            throw new SmartestBuildKitException("Build Kit could not define attachment '".$attachment_name."' because no valid text asset was supplied.");
        }

        if(!$attached_asset instanceof SmartestAsset){
            throw new SmartestBuildKitException("Build Kit could not define attachment '".$attachment_name."' because no valid attached asset was supplied.");
        }

        if(!strlen($attachment_name)){
            throw new SmartestBuildKitException("Build Kit could not define an attachment with no name.");
        }

        $textfragment = $text_asset->getTextFragment();
        $attachment = $textfragment->getAttachmentCurrentDefinition($attachment_name);

        if(!$attachment->getTextFragmentId()){
            $attachment->setTextFragmentId($textfragment->getId());
        }

        $attachment->setAttachmentName($attachment_name);
        $attachment->setAttachedAssetId($attached_asset->getId());

        if(isset($metadata['alignment'])){
            $attachment->setAlignment(SmartestStringHelper::toVarName($metadata['alignment']));
        }

        if(isset($metadata['caption'])){
            $attachment->setCaption(htmlentities($metadata['caption'], ENT_COMPAT, 'UTF-8'));
        }

        if(isset($metadata['caption_alignment'])){
            $attachment->setCaptionAlignment(SmartestStringHelper::toVarName($metadata['caption_alignment']));
        }

        if(isset($metadata['float'])){
            $attachment->setFloat(SmartestStringHelper::toRealBool($metadata['float']));
        }

        if(isset($metadata['border'])){
            $attachment->setBorder(SmartestStringHelper::toRealBool($metadata['border']));
        }

        if(isset($metadata['resize'])){
            $attachment->setResizeImageResizeFlag(SmartestStringHelper::toRealBool($metadata['resize']));
        }else if(isset($metadata['allow_resize'])){
            $attachment->setResizeImageResizeFlag(SmartestStringHelper::toRealBool($metadata['allow_resize']));
        }

        if(isset($metadata['zoom'])){
            $attachment->setZoomFromThumbnail(SmartestStringHelper::toRealBool($metadata['zoom']));
        }

        if(isset($metadata['thumbnail_relative_size'])){
            $attachment->setThumbnailRelativeSize((int) $metadata['thumbnail_relative_size']);
        }

        if(isset($metadata['manual_width'])){
            $attachment->setManualWidth($metadata['manual_width']);
        }

        $attachment->save();
        self::logBuildKit("Defined attachment '".$attachment_name."' on text asset #".$text_asset->getId()." using attached asset #".$attached_asset->getId().'.');
        return $attachment;
    }

    public static function getBuildKitOption($options, $name, $default=null){

        if(!is_array($options)){
            return $default;
        }

        if(isset($options[$name])){
            return $options[$name];
        }

        $name = SmartestStringHelper::toVarName($name);
        return isset($options[$name]) ? $options[$name] : $default;
    }

    public static function createDropdownMenu($label, $options=''){

        $name = SmartestStringHelper::toVarName($label);
        $menu = new SmartestDropdown;

        if($menu->findBy('name', $name)){
            self::logBuildKit("Reusing dropdown menu #".$menu->getId()." '".$label."'.");
            return $menu;
        }

        $menu->setLabel($label);
        $menu->setName($name);
        $menu->setLanguage('eng');
        $menu->setDatatype('SM_DATATYPE_SL_TEXT');
        $menu->save();
        self::logBuildKit("Created dropdown menu #".$menu->getId()." '".$label."'.");

        if(is_array($options)){
            foreach($options as $option){
                if(is_array($option)){
                    $option_label = isset($option['label']) ? $option['label'] : (isset($option['value']) ? $option['value'] : '');
                    $option_value = isset($option['value']) ? $option['value'] : null;
                    self::createDropdownMenuOption($menu, $option_label, $option_value);
                }else{
                    self::createDropdownMenuOption($menu, (string) $option);
                }
            }
        }

        return $menu;
    }

    public static function createDropdownMenuOption($menu, $label, $value=null){

        $menu = self::resolveDropdown($menu);

        if(!$menu instanceof SmartestDropdown){
            throw new SmartestBuildKitException("Build Kit could not create dropdown option because no valid dropdown menu was supplied.");
        }

        $value = strlen((string) $value) ? $value : $label;
        $option = new SmartestDropdownOption;
        $option->setLabel($label);
        $option->setValue(SmartestStringHelper::toSlug($value));
        $option->setDropdownId($menu->getId());
        $option->setOrderIndex($menu->getNextOptionOrderIndex());
        $option->save();
        self::logBuildKit("Created dropdown option #".$option->getId()." '".$label."' for menu #".$menu->getId().'.');

        return $option;
    }

    public static function createInitialModel($singular_name, $plural_name){

        $buildkit = self::getExecutingBuildKit();
        $site = self::getExecutingSite();
        $du = new SmartestDataUtility;

        if(!strlen((string) $singular_name) || !strlen((string) $plural_name)){
            throw new SmartestBuildKitException("Build Kit '".$buildkit->getLabel()."' tried to create a model without both singular and plural names.");
        }

        if($du->sharedModelExistsWithName($singular_name) || $du->sharedModelExistsWithPluralName($plural_name)){
            throw new SmartestBuildKitException("Build Kit '".$buildkit->getLabel()."' tried to create a model that conflicts with an existing shared model.");
        }

        if($model = self::resolveModelByIdentifierForSite($singular_name, $site)){
            self::logBuildKit("Reusing model #".$model->getId()." '".$model->getName()."' for site #".$site->getId().'.');
            return $model;
        }

        if($model = self::resolveModelByIdentifierForSite($plural_name, $site)){
            self::logBuildKit("Reusing model #".$model->getId()." '".$model->getName()."' for site #".$site->getId().'.');
            return $model;
        }

        self::logBuildKit("Creating model '".$singular_name."'/'".$plural_name."' for site #".$site->getId().'.');
        $model = new SmartestModel;
        $model->setType('SM_ITEMCLASS_MODEL');
        $model->setWebId(SmartestStringHelper::random(16, SM_RANDOM_ALPHANUMERIC));
        $model->setName($singular_name);
        $model->setPluralName($plural_name);
        $model->setSiteId($site->getId());
        $model->setShared(0);
        $model->setVarName(SmartestStringHelper::toVarName($plural_name));
        $model->setItemNameFieldVisible(1);
        $model->setLongIdFormat('_STD');
        $model->setCreatedFromBuildkit($buildkit->getShortName());
        $model->save();
        $du->flushModelsCache();
        self::logBuildKit("Created model #".$model->getId()." '".$model->getName()."' for site #".$site->getId().'.');

        return $model;
    }

    public static function createModelProperty($name, SmartestModel $model, $type, $required=false, $foreign_key_filter=null){

        $buildkit = self::getExecutingBuildKit();
        $property_varname = SmartestStringHelper::toVarName($name);

        if(!strlen($property_varname)){
            throw new SmartestBuildKitException("Build Kit '".$buildkit->getLabel()."' tried to create a model property with no name.");
        }

        if(isset($GLOBALS['reserved_keywords']) && in_array($property_varname, $GLOBALS['reserved_keywords'])){
            throw new SmartestBuildKitException("Build Kit '".$buildkit->getLabel()."' tried to create model property '".$name."', which is a reserved PHP keyword.");
        }

        if(!SmartestDataUtility::isValidType($type, 'itemproperty')){
            throw new SmartestBuildKitException("Build Kit '".$buildkit->getLabel()."' tried to create a model property with invalid datatype '".$type."'.");
        }

        foreach($model->getProperties() as $existing_property){
            if($existing_property->getVarName() == $property_varname){
                self::logBuildKit("Reusing model property #".$existing_property->getId()." '".$property_varname."' on model #".$model->getId().'.');
                return $existing_property;
            }
        }

        self::logBuildKit("Creating model property '".$property_varname."' of type '".$type."' on model #".$model->getId().'.');
        $property = new SmartestItemProperty;
        $property->setWebId(SmartestStringHelper::random(16, SM_RANDOM_ALPHANUMERIC));
        $property->setItemclassId($model->getId());
        $property->setName($name);
        $property->setVarName($property_varname);
        $property->setRequired(SmartestStringHelper::toRealBool($required) ? 'TRUE' : 'FALSE');
        $property->setDatatype($type);
        $property->setOrderIndex($model->getNextPropertyOrderIndex());

        if($foreign_key_filter !== null && strlen((string) $foreign_key_filter)){
            $property->setForeignKeyFilter($foreign_key_filter);
        }

        $property->save();
        SmartestCache::clear('model_properties_'.$model->getId(), true);
        $model->refreshProperties();
        self::logBuildKit("Created model property #".$property->getId()." '".$property_varname."' on model #".$model->getId().'.');

        return $property;
    }

    public static function createItem($name, SmartestModel $model){

        $user = self::getExecutingUser();
        $site = self::getExecutingSite();
        $class_name = $model->getClassName();

        if(!class_exists($class_name)){
            $model->init();
        }

        $item = class_exists($class_name) ? new $class_name : new SmartestCmsItem;

        if($item instanceof SmartestCmsItem && get_class($item) == 'SmartestCmsItem'){
            $item->setModelId($model->getId());
        }

        $item->setName($name);
        $item->setSiteId($site->getId());
        $item->getItem()->setSlug(SmartestStringHelper::toSlug($name), $site->getId());
        $item->getItem()->setPublic('FALSE');
        $item->getItem()->setCreated(time());
        $item->getItem()->setCreatedbyUserid($user->getId());
        $item->save();
        self::logBuildKit("Created item #".$item->getId()." '".$name."' for model #".$model->getId().'.');

        return $item;
    }

    public static function createSet($label, $model, $dynamic=false){

        $site = self::getExecutingSite();
        $model = self::resolveModel($model);

        if(!$model instanceof SmartestModel){
            throw new SmartestBuildKitException("Build Kit could not create set '".$label."' because the model was not recognized.");
        }

        $name = SmartestStringHelper::toVarName($label);
        $set = new SmartestCmsItemSet;

        if($set->findBy('name', $name, $site->getId()) && (int) $set->getSiteId() == (int) $site->getId()){
            if((int) $set->getItemclassId() != (int) $model->getId()){
                $set->setItemclassId($model->getId());
                $set->save();
            }
            return $set;
        }

        $set->setName($name);
        $set->setVarname($name);
        $set->setLabel($label);
        $set->setType($dynamic ? 'DYNAMIC' : 'STATIC');
        $set->setItemclassId($model->getId());
        $set->setSiteId($site->getId());
        $set->setShared(0);
        $set->save();

        return $set;
    }

    public static function createTag($label, $name=null){

        if(!strlen((string) $label)){
            throw new SmartestBuildKitException('Build Kit tried to create a tag with no label.');
        }

        $name = strlen((string) $name) ? SmartestStringHelper::toSlug($name) : SmartestStringHelper::toSlug($label);
        $tag = new SmartestTag;

        if($tag->findBy('name', $name)){
            return $tag;
        }

        $tag->setName($name);
        $tag->setLabel($label);
        $tag->setType('SM_TAGTYPE_TAG');
        $tag->save();

        return $tag;
    }

    public static function addItemToSet($item, SmartestCmsItemSet $set){

        if($set->getType() != 'STATIC'){
            return false;
        }

        $item_id = self::resolveItemId($item);

        if($item_id){
            $set->addItem($item_id, true);
            return true;
        }

        return false;
    }

    public static function createSetRule($set, $property, $value, $operator=null){

        $set = self::resolveCmsItemSet($set);

        if(!$set instanceof SmartestCmsItemSet || $set->getType() != 'DYNAMIC'){
            throw new SmartestBuildKitException('Build Kit could not create a set rule because the set could not be resolved or is not dynamic.');
        }

        if($operator === null || $operator === ''){
            $operator = SmartestQuery::EQUALS;
        }

        $property_id = self::resolveSetRulePropertyId($set, $property);

        if(is_object($value) && $value instanceof SmartestStorableValue){
            $value = $value->getStorableFormat();
        }

        $db = SmartestPersistentObject::get('db:main');
        $existing = $db->preparedQuery(
            "SELECT * FROM SetRules WHERE setrule_set_id=:set_id AND setrule_itemproperty_id=:property_id AND setrule_operator=:operator AND setrule_value=:value LIMIT 1",
            array('set_id'=>$set->getId(), 'property_id'=>$property_id, 'operator'=>$operator, 'value'=>$value)
        );

        $condition = new SmartestDynamicDataSetCondition;

        if(isset($existing[0]) && is_array($existing[0])){
            $condition->hydrate($existing[0]);
            self::logBuildKit("Reusing set rule #".$condition->getId()." on set #".$set->getId().'.');
            return $condition;
        }

        self::logBuildKit("Creating set rule on set #".$set->getId()." for property #".$property_id.'.');
        $condition->setSetId($set->getId());
        $condition->setItempropertyId($property_id);
        $condition->setOperator($operator);
        $condition->setValue($value);
        $condition->save();
        self::logBuildKit("Created set rule #".$condition->getId()." on set #".$set->getId().'.');

        return $condition;
    }

    public static function createPage($title, $url, $section=false, $parent_page='home', $template=null){

        $site = self::getExecutingSite();
        $parent = self::resolvePage($parent_page, $site);

        if(!$parent instanceof SmartestPage){
            $parent = $site->getHomePage(true);
        }

        if(!$parent instanceof SmartestPage || !$parent->getId()){
            throw new SmartestBuildKitException("Build Kit could not create page '".$title."' because no parent page could be found.");
        }

        $template_name = self::resolveTemplateName($template);
        $url = self::normalizeRelativeUrl($url);
        $title = strlen((string) $title) ? $title : 'Untitled Smartest Web Page';
        $page_name = SmartestStringHelper::toSlug($title);

        $existing = strlen($url) ? self::resolvePageByUrl($url, $site) : null;
        if(!$existing instanceof SmartestPage){
            $existing = self::resolvePageByName($page_name, $site, 'NORMAL');
        }

        if($existing instanceof SmartestPage){
            $modified = false;
            self::logBuildKit("Reusing page #".$existing->getId()." '".$existing->getTitle()."' for URL '".$url."'.");

            if((int) $existing->getId() != (int) $parent->getId() && (int) $existing->getParent() != (int) $parent->getId()){
                $existing->setParent($parent->getId());
                $modified = true;
            }

            if(strlen($template_name) && $existing->getDraftTemplate() != $template_name){
                $existing->setDraftTemplate($template_name);
                $modified = true;
            }

            if(strlen($url) && !self::pageHasUrl($existing, $url, $site)){
                $existing->addUrl($url);
                $modified = true;
            }

            if($modified){
                $existing->save();
            }

            return $existing;
        }

        self::logBuildKit("Creating page '".$title."' under parent page #".$parent->getId()." with URL '".$url."'.");
        $page = new SmartestPage;
        $page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $page->setTitle($title);
        $page->setType('NORMAL');
        $page->setName($page_name);
        $page->setCacheAsHtml('TRUE');
        $page->setCacheInterval('PERMANENT');
        $page->setIsPublished('FALSE');
        $page->setChangesApproved(0);
        $page->setSearchField('');
        $page->setParent($parent->getId());
        $page->setIsSection(SmartestStringHelper::toRealBool($section) ? 1 : 0);
        $page->setSiteId($site->getId());
        $page->setOrderIndex($parent->getNextChildOrderIndex());

        if(strlen($template_name)){
            $page->setDraftTemplate($template_name);
        }

        if(strlen($url)){
            $page->addUrl($url);
        }

        $page->save();
        self::logBuildKit("Created page #".$page->getId()." '".$title."' for site #".$site->getId().'.');

        return $page;
    }

    public static function createMetaPage($title, $url, $model, $parent_page='home', $template=null){

        $site = self::getExecutingSite();
        $model = self::resolveModel($model);
        $parent = self::resolvePage($parent_page, $site);

        if(!$model instanceof SmartestModel){
            throw new SmartestBuildKitException("Build Kit could not create meta page '".$title."' because the model was not recognized.");
        }

        if(!$parent instanceof SmartestPage){
            $parent = $site->getHomePage(true);
        }

        if(!$parent instanceof SmartestPage || !$parent->getId()){
            throw new SmartestBuildKitException("Build Kit could not create meta page '".$title."' because no parent page could be found.");
        }

        $template_name = self::resolveTemplateName($template);
        $url = self::normalizeRelativeUrl($url);
        $title = strlen((string) $title) ? $title : $model->getName();
        $page_name = SmartestStringHelper::toSlug($title);

        $existing = strlen($url) ? self::resolvePageByUrl($url, $site) : null;
        if(!$existing instanceof SmartestPage){
            $existing = self::resolvePageByName($page_name, $site, 'ITEMCLASS', $model->getId());
        }

        if($existing instanceof SmartestPage){
            $modified = false;
            self::logBuildKit("Reusing meta page #".$existing->getId()." '".$existing->getTitle()."' for URL '".$url."'.");

            if($existing->getType() != 'ITEMCLASS'){
                throw new SmartestBuildKitException("Build Kit could not create meta page '".$title."' because the URL '".$url."' is already used by a normal page.");
            }

            if((int) $existing->getId() != (int) $parent->getId() && (int) $existing->getParent() != (int) $parent->getId()){
                $existing->setParent($parent->getId());
                $modified = true;
            }

            if((int) $existing->getDatasetId() != (int) $model->getId()){
                $existing->setDatasetId($model->getId());
                $modified = true;
            }

            if(strlen($template_name) && $existing->getDraftTemplate() != $template_name){
                $existing->setDraftTemplate($template_name);
                $modified = true;
            }

            if(strlen($url) && !self::pageHasUrl($existing, $url, $site)){
                $existing->addUrl($url);
                $modified = true;
            }

            if($modified){
                $existing->save();
            }

            return $existing;
        }

        self::logBuildKit("Creating meta page '".$title."' for model #".$model->getId()." under parent page #".$parent->getId()." with URL '".$url."'.");
        $page = new SmartestPage;
        $page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
        $page->setTitle($title);
        $page->setType('ITEMCLASS');
        $page->setName($page_name);
        $page->setDatasetId($model->getId());
        $page->setCacheAsHtml('TRUE');
        $page->setCacheInterval('PERMANENT');
        $page->setIsPublished('FALSE');
        $page->setChangesApproved(0);
        $page->setSearchField('');
        $page->setParent($parent->getId());
        $page->setIsSection(0);
        $page->setSiteId($site->getId());
        $page->setOrderIndex($parent->getNextChildOrderIndex());

        if(strlen($template_name)){
            $page->setDraftTemplate($template_name);
        }

        if(strlen($url)){
            $page->addUrl($url);
        }

        $page->save();
        self::logBuildKit("Created meta page #".$page->getId()." '".$title."' for model #".$model->getId().'.');

        return $page;
    }

    public static function createPageGroup($label, $name='', $settings=array()){

        $site = self::getExecutingSite();
        $name = strlen((string) $name) ? SmartestStringHelper::toVarName($name) : SmartestStringHelper::toVarName($label);
        $group = new SmartestPageGroup;

        if(!$group->findBy('name', $name, $site->getId())){
            self::logBuildKit("Creating page group '".$name."' for site #".$site->getId().'.');
            $group->setName($name);
            $group->setVarname($name);
            $group->setLabel($label);
            $group->setSiteId($site->getId());
        }else{
            self::logBuildKit("Reusing page group #".$group->getId()." '".$name."' for site #".$site->getId().'.');
        }

        if(is_array($settings)){
            foreach($settings as $setting_name => $setting_value){
                $group->setSettingValue($setting_name, $setting_value);
            }
        }

        if($group->getModifiedProperties()){
            $group->save();
        }else if(!$group->getId()){
            $group->save();
        }

        self::logBuildKit("Page group '".$name."' is available as #".$group->getId().'.');

        return $group;
    }

    public static function createNavigationGroup($label, $name=''){

        $settings = array(
            'purpose' => 'navigation',
            'navigation_role' => strlen((string) $name) ? SmartestStringHelper::toVarName($name) : SmartestStringHelper::toVarName($label)
        );

        return self::createPageGroup($label, $name, $settings);
    }

    public static function addPageToGroup($page, SmartestPageGroup $group){
        $site = self::getExecutingSite();
        $page = self::resolvePage($page, $site);

        if($page instanceof SmartestPage && $page->getId()){
            $group->addPageById($page->getId());
            self::logBuildKit("Added page #".$page->getId()." to page group #".$group->getId().'.');
            return true;
        }

        throw new SmartestBuildKitException('Build Kit could not add a page to a page group because the page could not be resolved.');
    }

    public static function createPlaceholder($name, $type, $label='', $file_group=null){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($name);

        if(!strlen($name)){
            throw new SmartestBuildKitException('Build Kit tried to create a placeholder with no name.');
        }

        $placeholder = new SmartestPlaceholder;

        if($placeholder->exists($name, $site->getId())){
            self::logBuildKit("Reusing placeholder #".$placeholder->getId()." '".$name."' for site #".$site->getId().'.');
            return $placeholder;
        }

        self::logBuildKit("Creating placeholder '".$name."' of type '".$type."' for site #".$site->getId().'.');
        $placeholder->setLabel(strlen((string) $label) ? $label : $name);
        $placeholder->setName($name);
        $placeholder->setSiteId($site->getId());
        $placeholder->setShared(0);
        $placeholder->setType($type);
        self::applyAssetClassGroupFilter($placeholder, $file_group, 'SM_ASSETCLASS_FILTERTYPE_ASSETGROUP');
        $placeholder->save();
        self::logBuildKit("Created placeholder #".$placeholder->getId()." '".$name."'.");

        return $placeholder;
    }

    public static function createContainer($name, $label='', $template_group=null){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($name);

        if(!strlen($name)){
            throw new SmartestBuildKitException('Build Kit tried to create a container with no name.');
        }

        $container = new SmartestContainer;

        if($container->exists($name, $site->getId())){
            self::logBuildKit("Reusing container #".$container->getId()." '".$name."' for site #".$site->getId().'.');
            return $container;
        }

        self::logBuildKit("Creating container '".$name."' for site #".$site->getId().'.');
        $container->setLabel(strlen((string) $label) ? $label : $name);
        $container->setName($name);
        $container->setSiteId($site->getId());
        $container->setShared(0);
        $container->setType('SM_ASSETCLASS_CONTAINER');
        self::applyAssetClassGroupFilter($container, $template_group, 'SM_ASSETCLASS_FILTERTYPE_TEMPLATEGROUP');
        $container->save();
        self::logBuildKit("Created container #".$container->getId()." '".$name."'.");

        return $container;
    }

    public static function createField($name, $type, $site_wide=false, $foreign_key_filter=null){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($name);

        if(!SmartestDataUtility::isValidType($type)){
            $type = 'SM_DATATYPE_SL_TEXT';
        }

        $field = new SmartestPageField;

        if($field->findBy('name', $name, $site->getId())){
            self::logBuildKit("Reusing page field #".$field->getId()." '".$name."' for site #".$site->getId().'.');
            return $field;
        }

        self::logBuildKit("Creating page field '".$name."' of type '".$type."' for site #".$site->getId().'.');
        $field->setLabel($name);
        $field->setSiteId($site->getId());
        $field->setType($type);
        $field->setName($name);
        $field->setIsSitewide(SmartestStringHelper::toRealBool($site_wide) ? 1 : 0);

        if($foreign_key_filter !== null && strlen((string) $foreign_key_filter)){
            $field->setForeignKeyFilter($foreign_key_filter);
        }

        $field->save();
        self::logBuildKit("Created page field #".$field->getId()." '".$name."'.");

        return $field;
    }

    public static function createItemSpace($name, $dataset_id, $template_id=null){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($name);
        $item_space = new SmartestItemSpace;

        if(!strlen($name)){
            throw new SmartestBuildKitException('Build Kit tried to create an itemspace with no name.');
        }

        if($item_space->exists($name, $site->getId())){
            return $item_space;
        }

        $item_space->setName($name);
        $item_space->setLabel($name);
        $item_space->setSiteId($site->getId());
        $item_space->setShared(0);
        $item_space->setDataSetId($dataset_id instanceof SmartestCmsItemSet ? $dataset_id->getId() : (int) $dataset_id);

        if($template_id){
            $item_space->setUsesTemplate(true);
            $item_space->setTemplateAssetId(self::resolveAssetId($template_id));
        }else{
            $item_space->setUsesTemplate(false);
        }

        $item_space->save();

        return $item_space;
    }

    public static function setStandardPagesTemplate($template){

        $site = self::getExecutingSite();
        $template_name = self::resolveTemplateName($template);

        if(!strlen((string) $template_name)){
            return false;
        }

        $pages = array(
            $site->getHomePage(true),
            $site->getErrorPage(),
            $site->getSearchPage(),
            $site->getTagPage(),
            $site->getUserPage(),
            $site->getHoldingPage()
        );

        foreach($pages as $page){
            if($page instanceof SmartestPage && $page->getId()){
                $page->setDraftTemplate($template_name);
                $page->setLiveTemplate($template_name);
                $page->save();
            }
        }

        return true;
    }

    public static function definePlaceholder($placeholder, SmartestAsset $file, $page, $item_id=null, $instance_name='default'){

        $site = self::getExecutingSite();
        $placeholder = self::resolvePlaceholder($placeholder, $site);
        $page = self::resolvePage($page, $site);

        if(!$placeholder instanceof SmartestPlaceholder || !$page instanceof SmartestPage){
            return false;
        }

        $definition = new SmartestPlaceholderDefinition;

        if($definition->loadForUpdate($placeholder->getName(), $page, $item_id, $instance_name)){
            $definition->setDraftAssetId($file->getId());
        }else{
            $definition->setDraftAssetId($file->getId());
            $definition->setAssetclassId($placeholder->getId());
            $definition->setInstanceName(SmartestStringHelper::toVarName($instance_name));
            $definition->setPageId($page->getId());
            $definition->setSiteId($site->getId());
            if(is_numeric($item_id)){
                $definition->setItemId($item_id);
            }
        }

        $definition->save();

        return $definition;
    }

    public static function defineContainer($container, SmartestAsset $template, $page, $item_id=null, $instance_name='default'){

        $site = self::getExecutingSite();
        $container = self::resolveContainer($container, $site);
        $page = self::resolvePage($page, $site);

        if(!$container instanceof SmartestContainer || !$page instanceof SmartestPage){
            return false;
        }

        $definition = new SmartestContainerDefinition;

        if($definition->loadForUpdate($container->getName(), $page, true, $item_id, $instance_name)){
            $definition->setDraftAssetId($template->getId());
        }else{
            $definition->setDraftAssetId($template->getId());
            $definition->setAssetclassId($container->getId());
            $definition->setInstanceName(SmartestStringHelper::toVarName($instance_name));
            $definition->setPageId($page->getId());
            $definition->setSiteId($site->getId());
            if(is_numeric($item_id)){
                $definition->setItemId($item_id);
            }
        }

        $definition->save();

        return $definition;
    }

    public static function defineField($name, $value, $page=''){

        $site = self::getExecutingSite();
        $name = SmartestStringHelper::toVarName($name);

        if(!strlen($name)){
            return false;
        }

        $field = new SmartestPageField;

        if(!$field->findBy('name', $name, $site->getId())){
            return false;
        }

        if(is_object($value) && $value instanceof SmartestStorableValue){
            $value = $value->getStorableFormat();
        }

        $page = $page ? self::resolvePage($page, $site) : $site->getHomePage(true);

        if(!$page instanceof SmartestPage || !$page->getId()){
            return false;
        }

        $definition = new SmartestPageFieldDefinition;
        $definition->loadForUpdate($field, $page, true);
        $definition->setDraftValue($value);
        $definition->setPagepropertyId($field->getId());

        if($field->getIsSitewide()){
            $definition->setSiteId($site->getId());
            $definition->setPageId(0);
        }else{
            $definition->setPageId($page->getId());
        }

        $definition->save();

        return $definition;
    }

    public static function defineList($name, $set, $template, $limit=0, $title='', $page='home'){

        $site = self::getExecutingSite();
        $set = self::resolveCmsItemSet($set);
        $page = self::resolvePage($page, $site);

        if(!$set instanceof SmartestCmsItemSet || !$page instanceof SmartestPage){
            return false;
        }

        $template_name = self::resolveTemplateName($template);
        $name = SmartestStringHelper::toVarName($name);
        $list = new SmartestCmsItemList;

        if(!$list->load($name, $page, true)){
            $list->setName($name);
            $list->setPageId($page->getId());
        }

        $list->setType('SM_LIST_SIMPLE');
        $list->setMaximumLength((int) $limit);
        $list->setTitle(strlen((string) $title) ? $title : $name);
        $list->setDraftSetId($set->getId());
        $list->setDraftTemplateFile($template_name);
        $list->save();

        return $list;
    }

    public static function defineItemspace($itemspace, $item, $page){
        return false;
    }

    public static function installApp($folder_name){

        $buildkit = self::getExecutingBuildKit();
        $site = self::getExecutingSite();
        $source_dir = $buildkit->getDirectory().'Applications/'.$folder_name.'/';

        if(!is_dir($source_dir)){
            return false;
        }

        $install_info = array();

        if(is_file($source_dir.'install.yml')){
            $raw_install_info = SmartestYamlHelper::fastLoad($source_dir.'install.yml');
            if(isset($raw_install_info['install']) && is_array($raw_install_info['install'])){
                $install_info = $raw_install_info['install'];
            }
        }

        $base_class_name = isset($install_info['class_name']) ? preg_replace('/[^A-Za-z0-9_]/', '', $install_info['class_name']) : SmartestStringHelper::toCamelCase($site->getTitle().$folder_name);
        $base_short_name = isset($install_info['short_name']) ? SmartestUserApplicationHelper::createShortName($install_info['short_name']) : SmartestUserApplicationHelper::createShortName($site->getTitle().$folder_name);
        $site_suffix = self::getSiteModuleSuffix($site);
        $class_name = $base_class_name;
        $short_name = $base_short_name;

        if(SmartestUserApplicationHelper::applicationExistsWithShortName($short_name) || class_exists($class_name, false)){
            $short_name = self::getUniqueModuleShortName($base_short_name, $site_suffix);
            $class_name = self::getUniqueModuleClassName($base_class_name, SmartestStringHelper::toCamelCase($site_suffix));
        }

        $class_file = is_file($source_dir.$base_class_name.'.class.php') ? $source_dir.$base_class_name.'.class.php' : (is_file($source_dir.'class.php') ? $source_dir.'class.php' : '');
        $config_file = is_file($source_dir.'Configuration/quince.yml') ? $source_dir.'Configuration/quince.yml' : (is_file($source_dir.'quince.yml') ? $source_dir.'quince.yml' : '');
        $create_presentation = !is_dir($source_dir.'Presentation/');
        $identifier = isset($install_info['identifier']) ? $install_info['identifier'] : 'com.smartest.buildkit.'.$base_class_name;

        if($short_name != $base_short_name){
            $identifier .= '.'.$site_suffix;
        }

        $info = SmartestUserApplicationHelper::createApplication($short_name, $class_name, $class_file, $config_file, $folder_name, $create_presentation, $identifier);

        if(!$info instanceof SmartestParameterHolder){
            return false;
        }

        $target_dir = $info->getParameter('directory');

        $controller_domain = defined('SM_CONTROLLER_DOMAIN') ? SM_CONTROLLER_DOMAIN : '/';

        $replacements = array(
            '%%CLASSNAME%%' => $class_name,
            '%%SHORTNAME%%' => $short_name,
            '%%APPIDENTIFIER%%' => $info->getParameter('auto_identifier'),
            '%%RANDOMURL%%' => SmartestStringHelper::random(6),
            '%%QUINCE_MODULE_SHORTNAME%%' => $short_name,
            '%%QUINCE_BASE_DIR%%' => $controller_domain
        );

        foreach(array('Configuration', 'Presentation', 'Library') as $subdir){
            if(is_dir($source_dir.$subdir.'/')){
                self::copyDirectoryContents($source_dir.$subdir.'/', $target_dir.$subdir.'/', $replacements);
            }
        }

        self::registerObject('quince_module_shortname', $short_name);
        self::registerObject('quince_base_dir', $controller_domain);
        self::registerObject('quince_module_url', $controller_domain.'ajax:'.$short_name.'/');

        return $info;
    }

    protected static function resolveConfigurationOptionValue(SmartestConfigurationParameter $option, $section_values){

        $key = $option->getName();

        if(isset($section_values[$key]) && (strlen((string) $section_values[$key]) || $section_values[$key] === 0 || $section_values[$key] === '0')){
            $value = $section_values[$key];
        }else{
            $value = $option->getDefault();
        }

        if(is_object($value) && $value instanceof SmartestStorableValue){
            $value = $value->getStorableFormat();
        }else if(is_object($value) && method_exists($value, 'getValue')){
            $value = $value->getValue();
        }else if(is_object($value) && method_exists($value, '__toString')){
            $value = (string) $value;
        }

        if($option->getDatatype() == 'SM_DATATYPE_BOOLEAN'){
            return SmartestStringHelper::toRealBool($value);
        }

        if($value === null && $option->isRequired()){
            throw new SmartestBuildKitException("Required Build Kit option '".$key."' was not provided.");
        }

        return $value;
    }

    protected static function getExecutingBuildKit(){
        if(isset($GLOBALS['_buildkit_executing']) && $GLOBALS['_buildkit_executing'] instanceof SmartestBuildKit){
            return $GLOBALS['_buildkit_executing'];
        }
        throw new SmartestBuildKitException('Build Kit helper was called outside a Build Kit execution context.');
    }

    protected static function getExecutingSite(){
        if(isset($GLOBALS['_buildkit_executing_site']) && $GLOBALS['_buildkit_executing_site'] instanceof SmartestSite){
            return $GLOBALS['_buildkit_executing_site'];
        }
        throw new SmartestBuildKitException('Build Kit helper was called without a valid site context.');
    }

    protected static function getExecutingUser(){
        if(isset($GLOBALS['_buildkit_executing_user']) && $GLOBALS['_buildkit_executing_user'] instanceof SmartestUser){
            return $GLOBALS['_buildkit_executing_user'];
        }
        throw new SmartestBuildKitException('Build Kit helper was called without a valid user context.');
    }

    protected static function getRegistry(){
        if(!isset($GLOBALS['_buildkit_registered_objects']) || !$GLOBALS['_buildkit_registered_objects'] instanceof SmartestParameterHolder){
            self::clearRegisteredObjects();
        }
        return $GLOBALS['_buildkit_registered_objects'];
    }

    protected static function getSetFilterTypeForCode($code){
        return substr((string) $code, 8, 1) == 'C' ? 'SM_SET_FILTERTYPE_ASSETCLASS' : 'SM_SET_FILTERTYPE_ASSETTYPE';
    }

    protected static function applyAssetClassGroupFilter(SmartestAssetClass $asset_class, $group, $filter_type){
        if($group instanceof SmartestSet){
            $asset_class->setFilterType($filter_type);
            $asset_class->setFilterValue($group->getId());
        }else if(is_numeric($group)){
            $asset_class->setFilterType($filter_type);
            $asset_class->setFilterValue($group);
        }else{
            $asset_class->setFilterType('SM_ASSETCLASS_FILTERTYPE_NONE');
            $asset_class->setFilterValue('');
        }
    }

    protected static function resolveAssetId($file){
        if($file instanceof SmartestAsset){
            return $file->getId();
        }else if(is_numeric($file)){
            return (int) $file;
        }
        return 0;
    }

    protected static function resolveAsset($file){
        if($file instanceof SmartestAsset){
            return $file;
        }else if(is_numeric($file)){
            $asset = new SmartestAsset;
            if($asset->find((int) $file)){
                return $asset;
            }
        }
        return null;
    }

    protected static function resolveItemId($item){
        if($item instanceof SmartestCmsItem || $item instanceof SmartestItem){
            return $item->getId();
        }else if(is_numeric($item)){
            return (int) $item;
        }
        return 0;
    }

    protected static function resolveAssetGroup($group){
        if($group instanceof SmartestAssetGroup){
            return $group;
        }else if(is_numeric($group)){
            $g = new SmartestAssetGroup;
            if($g->find((int) $group)){
                return $g;
            }
        }
        return null;
    }

    protected static function resolveDropdown($menu){
        if($menu instanceof SmartestDropdown){
            return $menu;
        }else if(is_numeric($menu)){
            $m = new SmartestDropdown;
            if($m->find((int) $menu)){
                return $m;
            }
        }else if(strlen((string) $menu)){
            $m = new SmartestDropdown;
            if($m->findBy('name', SmartestStringHelper::toVarName($menu))){
                return $m;
            }
        }
        return null;
    }

    protected static function resolveModel($model){
        $site = self::getExecutingSite();

        if($model instanceof SmartestModel){
            return self::modelIsAvailableToSite($model, $site) ? $model : null;
        }else if(is_numeric($model)){
            $m = new SmartestModel;
            if($m->find((int) $model) && self::modelIsAvailableToSite($m, $site)){
                return $m;
            }
        }else if(strlen((string) $model)){
            if($m = self::resolveModelByIdentifierForSite($model, $site)){
                return $m;
            }
        }

        return null;
    }

    protected static function resolveModelByIdentifierForSite($identifier, SmartestSite $site){

        $identifier = trim((string) $identifier);

        if(!strlen($identifier)){
            return null;
        }

        $identifier_varname = SmartestStringHelper::toVarName($identifier);
        $du = new SmartestDataUtility;
        $models = $du->getModels(false, $site->getId(), true);

        foreach($models as $model){
            if($model instanceof SmartestModel && self::modelIsAvailableToSite($model, $site)){
                if(strtolower($model->getName()) == strtolower($identifier) || strtolower($model->getPluralName()) == strtolower($identifier) || SmartestStringHelper::toVarName($model->getVarName()) == $identifier_varname || SmartestStringHelper::toVarName($model->getName()) == $identifier_varname || SmartestStringHelper::toVarName($model->getPluralName()) == $identifier_varname){
                    return $model;
                }
            }
        }

        return null;
    }

    protected static function modelIsAvailableToSite(SmartestModel $model, SmartestSite $site){
        return (int) $model->getSiteId() == (int) $site->getId() || SmartestStringHelper::toRealBool($model->getShared());
    }

    protected static function resolveCmsItemSet($set){
        $site = self::getExecutingSite();

        if($set instanceof SmartestCmsItemSet){
            return $set;
        }else if(is_numeric($set)){
            $s = new SmartestCmsItemSet;
            if($s->find((int) $set)){
                return $s;
            }
        }else if(strlen((string) $set)){
            $s = new SmartestCmsItemSet;
            if($s->findBy('name', SmartestStringHelper::toVarName($set), $site->getId())){
                return $s;
            }
        }

        return null;
    }

    protected static function resolveSetRulePropertyId(SmartestCmsItemSet $set, $property){

        if(in_array($property, array(SmartestCmsItem::NAME, SmartestCmsItem::ID, SmartestCmsItem::WEBID, SmartestCmsItem::WEB_ID), true)){
            return $property;
        }

        if($property instanceof SmartestItemProperty){
            return $property->getId();
        }

        if(is_numeric($property)){
            return (int) $property;
        }

        $model = new SmartestModel;
        if($model->find($set->getItemclassId())){
            $varname = SmartestStringHelper::toVarName($property);
            foreach($model->getProperties() as $p){
                if($p->getVarName() == $varname || $p->getName() == $property){
                    return $p->getId();
                }
            }
        }

        return SmartestCmsItem::NAME;
    }

    protected static function resolvePage($page, SmartestSite $site){

        if($page instanceof SmartestPage){
            return (int) $page->getSiteId() == (int) $site->getId() ? $page : null;
        }

        $page_ref = trim((string) $page);

        if(!strlen($page_ref) || in_array(SmartestStringHelper::toVarName($page_ref), array('home', 'homepage', 'top', 'top_page'), true)){
            return $site->getHomePage(true);
        }

        if(is_numeric($page)){
            $p = new SmartestPage;
            if($p->find((int) $page) && (int) $p->getSiteId() == (int) $site->getId()){
                return $p;
            }
        }

        if(strlen($page_ref)){
            if($p = self::resolvePageByUrl($page_ref, $site)){
                return $p;
            }

            $p = new SmartestPage;
            if(preg_match('/^[\w\$-]{32}$/', $page_ref) && $p->findBy('webid', $page_ref, $site->getId()) && (int) $p->getSiteId() == (int) $site->getId()){
                $p->setParentSite($site);
                return $p;
            }

            if($p = self::resolvePageByName($page_ref, $site)){
                return $p;
            }
        }

        return null;
    }

    protected static function resolvePageByUrl($url, SmartestSite $site){

        $url = self::normalizeRelativeUrl($url);

        if(!strlen($url)){
            return null;
        }

        $db = SmartestPersistentObject::get('db:main');
        $result = $db->preparedQuery(
            "SELECT Pages.* FROM Pages, PageUrls WHERE Pages.page_id=PageUrls.pageurl_page_id AND Pages.page_site_id=:site_id AND Pages.page_deleted!='TRUE' AND PageUrls.pageurl_url=:url LIMIT 1",
            array('site_id'=>$site->getId(), 'url'=>$url)
        );

        if(isset($result[0]) && is_array($result[0])){
            $page = new SmartestPage;
            $page->hydrate($result[0]);
            $page->setParentSite($site);
            return $page;
        }

        return null;

    }

    protected static function resolvePageByName($name, SmartestSite $site, $type='', $dataset_id=0){

        $name = SmartestStringHelper::toSlug($name);

        if(!strlen($name)){
            return null;
        }

        $page = new SmartestPage;
        if($page->findBy('name', $name, $site->getId()) && (int) $page->getSiteId() == (int) $site->getId()){
            if(strlen((string) $type) && $page->getType() != $type){
                return null;
            }

            if((int) $dataset_id && (int) $page->getDatasetId() != (int) $dataset_id){
                return null;
            }

            $page->setParentSite($site);
            return $page;
        }

        return null;

    }

    protected static function pageHasUrl(SmartestPage $page, $url, SmartestSite $site){

        $url = self::normalizeRelativeUrl($url);

        if(!strlen($url) || !$page->getId()){
            return false;
        }

        $db = SmartestPersistentObject::get('db:main');
        $result = $db->preparedQuery(
            "SELECT PageUrls.pageurl_id FROM PageUrls, Pages WHERE Pages.page_id=PageUrls.pageurl_page_id AND Pages.page_site_id=:site_id AND PageUrls.pageurl_page_id=:page_id AND PageUrls.pageurl_url=:url LIMIT 1",
            array('site_id'=>$site->getId(), 'page_id'=>$page->getId(), 'url'=>$url)
        );

        return isset($result[0]);

    }

    protected static function resolvePlaceholder($placeholder, SmartestSite $site){

        if($placeholder instanceof SmartestPlaceholder){
            return $placeholder;
        }else if(is_numeric($placeholder)){
            $p = new SmartestPlaceholder;
            if($p->find((int) $placeholder)){
                return $p;
            }
        }else if(strlen((string) $placeholder)){
            $p = new SmartestPlaceholder;
            if($p->exists(SmartestStringHelper::toVarName($placeholder), $site->getId())){
                return $p;
            }
        }

        return null;
    }

    protected static function resolveContainer($container, SmartestSite $site){

        if($container instanceof SmartestContainer){
            return $container;
        }else if(is_numeric($container)){
            $c = new SmartestContainer;
            if($c->find((int) $container)){
                return $c;
            }
        }else if(strlen((string) $container)){
            $c = new SmartestContainer;
            if($c->exists(SmartestStringHelper::toVarName($container), $site->getId())){
                return $c;
            }
        }

        return null;
    }

    protected static function resolveTemplateName($template){

        $buildkit = self::getExecutingBuildKit();

        if($template instanceof SmartestAsset){
            return $template->getUrl();
        }

        if(strlen((string) $template)){
            if(is_file(SM_ROOT_DIR.'Presentation/Masters/'.$template) || is_file(SM_ROOT_DIR.'Presentation/Layouts/'.$template)){
                return $template;
            }

            if(is_file($buildkit->getDirectory().'Resources/'.$template)){
                $asset = self::createDiskFile($buildkit->getDirectory().'Resources/'.$template, $template, 'SM_ASSETTYPE_COMPOUND_LIST_TEMPLATE');
                return $asset->getUrl();
            }

            return (string) $template;
        }

        return '';
    }

    protected static function normalizeRelativeUrl($url){
        $url = (string) $url;
        if(strlen($url) && $url[0] == '/'){
            return substr($url, 1);
        }
        return $url;
    }

    protected static function copyDirectoryContents($source_dir, $target_dir, $replacements=array()){

        if(!is_dir($target_dir)){
            mkdir($target_dir, 0777, true);
        }

        foreach(scandir($source_dir) as $name){
            if($name == '.' || $name == '..'){
                continue;
            }

            $source = $source_dir.$name;
            $target = $target_dir.$name;

            if(is_dir($source)){
                self::copyDirectoryContents($source.'/', $target.'/', $replacements);
            }else if(count($replacements) && self::fileCanReceiveTokenReplacements($source)){
                SmartestFileSystemHelper::save($target, self::applyTokenReplacements(SmartestFileSystemHelper::load($source), $replacements));
            }else{
                SmartestFileSystemHelper::copy($source, $target);
            }
        }
    }

    protected static function getSiteModuleSuffix(SmartestSite $site){

        $source = $site->getDomain() ? $site->getDomain() : $site->getName();
        $suffix = SmartestStringHelper::toVarName(str_replace('.', '_', $source), true);

        if(!strlen($suffix)){
            $suffix = 'site'.$site->getId();
        }

        return substr($suffix, 0, 32);
    }

    protected static function getUniqueModuleShortName($base_short_name, $site_suffix){

        $base = SmartestUserApplicationHelper::createShortName($base_short_name.'_'.$site_suffix);
        $candidate = $base;
        $index = 2;

        while(SmartestUserApplicationHelper::applicationExistsWithShortName($candidate)){
            $candidate = SmartestUserApplicationHelper::createShortName($base.'_'.$index);
            $index++;
        }

        return $candidate;
    }

    protected static function getUniqueModuleClassName($base_class_name, $site_suffix){

        $base = preg_replace('/[^A-Za-z0-9_]/', '', $base_class_name.$site_suffix);
        $candidate = $base;
        $index = 2;

        while(class_exists($candidate, false)){
            $candidate = $base.$index;
            $index++;
        }

        return $candidate;
    }

    protected static function fileCanReceiveTokenReplacements($path){

        $suffix = strtolower(SmartestStringHelper::getDotSuffix($path));

        return in_array($suffix, array(
            'php',
            'tpl',
            'yml',
            'yaml',
            'txt',
            'conf',
            'json',
            'xml',
            'css',
            'js',
            'html',
            'md'
        ), true);
    }

    protected static function prepareResourceContents($contents){

        $replacements = self::getRegisteredObjectTokenReplacements();

        if(count($replacements)){
            return self::applyTokenReplacements($contents, $replacements);
        }

        return $contents;
    }

    protected static function applyTokenReplacements($contents, $replacements){

        if(!is_array($replacements) || !count($replacements)){
            return $contents;
        }

        uksort($replacements, function($a, $b){
            return strlen($b) - strlen($a);
        });

        return str_replace(array_keys($replacements), array_values($replacements), $contents);
    }

    protected static function getRegisteredObjectTokenReplacements(){

        $replacements = array();

        foreach(self::getRegistry()->toArray() as $name=>$object){
            $value = self::getRegisteredObjectReplacementValue($object);

            if($value === null){
                continue;
            }

            $tokens = array(
                '%%'.strtoupper($name).'%%',
                '%%'.strtoupper(str_replace('_', '', $name)).'%%'
            );

            foreach($tokens as $token){
                $replacements[$token] = $value;
            }
        }

        return $replacements;
    }

    protected static function getRegisteredObjectReplacementValue($object){

        if($object instanceof SmartestAsset){
            return $object->getUrl();
        }

        if($object instanceof SmartestPage){
            return $object->getName();
        }

        if($object instanceof SmartestModel){
            return $object->getVarName();
        }

        if(is_scalar($object)){
            return (string) $object;
        }

        if(is_object($object) && method_exists($object, '__toString')){
            return (string) $object;
        }

        return null;
    }

}
