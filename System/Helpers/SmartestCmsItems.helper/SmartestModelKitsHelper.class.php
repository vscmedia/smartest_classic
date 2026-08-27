<?php

class SmartestModelKitsHelper{

    public static function execute($file_path, SmartestSite $site, ?SmartestUser $user=null, array $options=array()){

        $errors = self::validate($file_path);

        if(count($errors)){
            return false;
        }

        $data = SmartestYamlHelper::load($file_path);
        $kit = $data['modelkit'];
        $user = $user instanceof SmartestUser ? $user : SmartestSession::get('user');

        $model = self::createModelFromKit($kit, $site, $user, $options);
        $properties_by_varname = self::createPropertiesFromKit($model, $kit, $site);

        self::applyModelDefaultsFromKit($model, $kit, $properties_by_varname);

        $model->save();
        SmartestCache::clear('model_properties_'.$model->getId(), true);
        $model->refreshProperties();
        $model->init();

        return $model;
    }

    public static function validate($file_path){

        $errors = array();

        if(!is_file($file_path)){
            return array('The file '.$file_path.' could not be read.');
        }

        $data = SmartestYamlHelper::load($file_path);

        if(!is_array($data) || !isset($data['modelkit']) || !is_array($data['modelkit'])){
            return array('The file is not correctly formatted.');
        }

        $kit = $data['modelkit'];

        if(!isset($kit['name']) || !strlen((string) $kit['name'])){
            $errors[] = 'The model kit does not specify a singular name.';
        }

        if(!isset($kit['plural']) || !strlen((string) $kit['plural'])){
            $errors[] = 'The model kit does not specify a plural name.';
        }

        if(isset($kit['properties']) && is_array($kit['properties'])){
            foreach($kit['properties'] as $varname => $property){
                if(!is_array($property)){
                    $errors[] = "Property '".$varname."' is not correctly formatted.";
                    continue;
                }

                if(!isset($property['type']) || !SmartestDataUtility::isValidType($property['type'], 'itemproperty')){
                    $errors[] = "Property '".$varname."' has an invalid datatype.";
                }
            }
        }

        return $errors;
    }

    public static function isValid($file_path){
        return !(bool) count(self::validate($file_path));
    }

    public static function getAvailableModelKits($include_hidden=false){

        $kits = array();
        $dirs = array(
            SM_ROOT_DIR.'System/Install/ModelKits/',
            SM_ROOT_DIR.'Library/ModelKits/'
        );

        foreach($dirs as $dir){
            if(!is_dir($dir)){
                continue;
            }

            foreach(SmartestFileSystemHelper::getDirectoryContents($dir, false) as $file){
                if(strtolower(SmartestStringHelper::getDotSuffix($file)) != 'modelkit'){
                    continue;
                }

                $path = $dir.$file;
                $data = SmartestYamlHelper::load($path);

                if(isset($data['modelkit']) && is_array($data['modelkit'])){
                    if(!$include_hidden && isset($data['modelkit']['hidden']) && SmartestStringHelper::toRealBool($data['modelkit']['hidden'])){
                        continue;
                    }

                    $info = $data['modelkit'];
                    $id = SmartestStringHelper::toVarName(isset($info['plural']) ? $info['plural'] : $file);
                    $info['id'] = $id;
                    $info['path'] = $path;
                    $info['filename'] = $file;
                    $info['label'] = isset($info['label']) ? $info['label'] : (isset($info['plural']) ? $info['plural'] : $file);
                    $info['description'] = isset($info['description']) ? $info['description'] : '';
                    $kits[$id] = $info;
                }
            }
        }

        ksort($kits);

        return $kits;
    }

    public static function getAvailableModelKit($id, $include_hidden=false){

        $kits = self::getAvailableModelKits($include_hidden);
        $id = SmartestStringHelper::toVarName($id);

        return isset($kits[$id]) ? $kits[$id] : null;
    }

    public static function dropdownExists($dropdown_identifier, $site_id=null){
        return self::getDropdownByIdentifier($dropdown_identifier, $site_id) instanceof SmartestDropdown;
    }

    public static function createDropdown($data){

        $label = isset($data['label']) ? $data['label'] : 'Dropdown';
        $values = isset($data['values']) && is_array($data['values']) ? $data['values'] : array();
        $dropdown = new SmartestDropdown;
        $dropdown->setName(SmartestStringHelper::toVarName($label));
        $dropdown->setLabel($label);
        $dropdown->setLanguage('eng');
        $dropdown->setDatatype('SM_DATATYPE_SL_TEXT');
        $dropdown->save();

        foreach($values as $value => $option_label){
            self::createDropdownOption($dropdown, $option_label, $value);
        }

        return $dropdown;
    }

    protected static function createModelFromKit($kit, SmartestSite $site, $user, $options){

        $du = new SmartestDataUtility;
        $shared = isset($options['shared']) ? SmartestStringHelper::toRealBool($options['shared']) : false;
        $model = new SmartestModel;

        if(!$du->isValidModelName($kit['name'])){
            throw new SmartestException("The model kit name '".$kit['name']."' is not valid.");
        }

        if(!$du->modelNameIsAvailable($kit['name'], $site->getId(), $shared)){
            throw new SmartestException("The model kit name '".$kit['name']."' is already in use.");
        }

        $model->setType('SM_ITEMCLASS_MODEL');
        $model->setName($kit['name']);
        $model->setPluralName($kit['plural']);
        $model->setVarname(SmartestStringHelper::toVarName($kit['plural']));
        $model->setWebid(SmartestStringHelper::random(16, SM_RANDOM_ALPHANUMERIC));
        $model->setSiteId($site->getId());
        $model->setShared($shared ? 1 : 0);
        $model->setItemNameFieldVisible(1);

        if(isset($kit['name_field_label']) && strlen((string) $kit['name_field_label'])){
            $model->setItemNameFieldName($kit['name_field_label']);
        }

        if(isset($kit['long_id_format']) && strlen((string) $kit['long_id_format'])){
            $model->setLongIdFormat($kit['long_id_format']);
        }else{
            $model->setLongIdFormat('_STD');
        }

        if(isset($kit['hidden'])){
            $model->setIsHidden((int) SmartestStringHelper::toRealBool($kit['hidden']));
        }

        if($user instanceof SmartestUser){
            $model->setUserid($user->getId());
        }

        $model->save();

        return $model;
    }

    protected static function createPropertiesFromKit(SmartestModel $model, $kit, SmartestSite $site){

        $properties_by_varname = array();

        if(!isset($kit['properties']) || !is_array($kit['properties'])){
            return $properties_by_varname;
        }

        foreach($kit['properties'] as $varname => $property_data){
            $property = new SmartestItemProperty;
            $label = isset($property_data['label']) ? $property_data['label'] : SmartestStringHelper::toTitleCase(str_replace('_', ' ', $varname));
            $type = $property_data['type'];

            $property->setName($label);
            $property->setVarname(SmartestStringHelper::toVarName($varname));
            $property->setDatatype($type);
            $property->setRequired(isset($property_data['required']) && SmartestStringHelper::toRealBool($property_data['required']) ? 'TRUE' : 'FALSE');
            $property->setItemclassId($model->getId());
            $property->setWebid(SmartestStringHelper::random(16, SM_RANDOM_ALPHANUMERIC));
            $property->setOrderIndex($model->getNextPropertyOrderIndex());

            if(isset($property_data['default_value'])){
                $property->setDefaultvalue($property_data['default_value']);
            }

            if(isset($property_data['default_format'])){
                $property->setDefaultformat($property_data['default_format']);
            }

            if($type == 'SM_DATATYPE_DROPDOWN_MENU'){
                $dropdown = self::getOrCreateDropdownForProperty($property_data, $label, $site);
                if($dropdown instanceof SmartestDropdown){
                    $property->setForeignKeyFilter($dropdown->getId());
                }
            }else if(isset($property_data['filetype'])){
                $property->setForeignKeyFilter($property_data['filetype']);
            }else if(isset($property_data['foreign_key_filter'])){
                $property->setForeignKeyFilter($property_data['foreign_key_filter']);
            }

            $property->save();
            $properties_by_varname[$property->getVarname()] = $property;
        }

        return $properties_by_varname;
    }

    protected static function applyModelDefaultsFromKit(SmartestModel $model, $kit, $properties_by_varname){

        if(isset($kit['default_thumbnail_property'])){
            $property = self::getPropertyByVarname($properties_by_varname, $kit['default_thumbnail_property']);
            if($property instanceof SmartestItemProperty){
                $model->setDefaultThumbnailPropertyId($property->getId());
            }
        }

        if(isset($kit['default_sort_property'])){
            $property = self::getPropertyByVarname($properties_by_varname, $kit['default_sort_property']);
            if($property instanceof SmartestItemProperty){
                $model->setDefaultSortPropertyId($property->getId());
                if(isset($kit['default_sort_dir']) && in_array(strtoupper($kit['default_sort_dir']), array('ASC', 'DESC'))){
                    $model->setDefaultSortPropertyDirection(strtoupper($kit['default_sort_dir']));
                }
            }
        }

        if(isset($kit['default_date_property'])){
            $property = self::getPropertyByVarname($properties_by_varname, $kit['default_date_property']);
            if($property instanceof SmartestItemProperty){
                $model->setDefaultDatePropertyId($property->getId());
            }
        }

        if(isset($kit['primary_property'])){
            $property = self::getPropertyByVarname($properties_by_varname, $kit['primary_property']);
            if($property instanceof SmartestItemProperty){
                $model->setPrimaryPropertyId($property->getId());
            }
        }
    }

    protected static function getOrCreateDropdownForProperty($property_data, $label, SmartestSite $site){

        if(isset($property_data['dropdown_identifier'])){
            $dropdown = self::getDropdownByIdentifier($property_data['dropdown_identifier'], $site->getId());
            if($dropdown instanceof SmartestDropdown){
                return $dropdown;
            }
        }

        if(isset($property_data['values']) && is_array($property_data['values'])){
            return self::createDropdown(array(
                'label' => $label,
                'values' => $property_data['values']
            ));
        }

        return null;
    }

    protected static function getDropdownByIdentifier($dropdown_identifier, $site_id=null){

        if(!strlen((string) $dropdown_identifier)){
            return null;
        }

        $du = new SmartestDataUtility;
        $dropdowns = $du->getDropdowns($site_id);

        foreach($dropdowns as $dropdown){
            if($dropdown->getIdentifier() == $dropdown_identifier){
                return $dropdown;
            }
        }

        return null;
    }

    protected static function createDropdownOption(SmartestDropdown $dropdown, $label, $value){
        $option = new SmartestDropdownOption;
        $option->setLabel($label);
        $option->setValue($value);
        $option->setDropdownId($dropdown->getId());
        $option->setOrderIndex($dropdown->getNextOptionOrderIndex());
        $option->save();
        return $option;
    }

    protected static function getPropertyByVarname($properties_by_varname, $varname){
        $varname = SmartestStringHelper::toVarName($varname);
        return isset($properties_by_varname[$varname]) ? $properties_by_varname[$varname] : null;
    }

}

class SmartestModelKitHelper extends SmartestModelKitsHelper{}
