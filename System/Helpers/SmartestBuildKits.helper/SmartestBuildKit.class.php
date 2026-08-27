<?php

class SmartestBuildKit implements ArrayAccess{

    protected $_dir = '';
    protected $_info = array();
    private $_database;

    public function __construct($dir){

        $this->_database = SmartestDatabase::getInstance('SMARTEST');
        $dir = rtrim($dir, '/').'/';

        if(is_file($dir.'configure.yml')){

            $conf_data = SmartestYamlHelper::fastLoad($dir.'configure.yml');

            if(is_array($conf_data) && isset($conf_data['buildkit']) && is_array($conf_data['buildkit'])){
                $this->_dir = $dir;
                $this->_info = $conf_data['buildkit'];
            }
        }
    }

    public function getShortName(){
        return isset($this->_info['shortname']) ? SmartestStringHelper::toVarName($this->_info['shortname']) : '';
    }

    public function getLabel(){
        return isset($this->_info['label']) ? $this->_info['label'] : $this->getShortName();
    }

    public function getDirectory(){
        return $this->_dir;
    }

    public function isValid(){
        return strlen($this->getShortName()) && is_dir($this->_dir);
    }

    public function getRequiredWriteLocations(){

        $dirs = array();

        if(isset($this->_info['required_write_dirs'])){

            $raw_dirs = is_array($this->_info['required_write_dirs']) ? $this->_info['required_write_dirs'] : array($this->_info['required_write_dirs']);

            foreach($raw_dirs as $dir){
                $dir = trim((string) $dir);
                if(strlen($dir)){
                    $dirs[] = $dir;
                }
            }
        }

        return $dirs;
    }

    public function getUnwritableRequiredWriteLocations(){

        $unwritable_locations = array();

        foreach($this->getRequiredWriteLocations() as $dir){
            if(!is_dir(SM_ROOT_DIR.$dir) || !is_writable(SM_ROOT_DIR.$dir)){
                $unwritable_locations[] = $dir;
            }
        }

        return $unwritable_locations;
    }

    public function getTitleFormat(){
        return isset($this->_info['title_format']) ? $this->_info['title_format'] : null;
    }

    public function getEUCookieModeEnabled(){
        return $this->getSectionFlag('eu_cookie_mode', 'enabled', false);
    }

    public function getResponsiveModeEnabled(){
        return $this->getSectionFlag('responsive_mode', 'enabled', false);
    }

    public function getResponsiveModeOptions(){

        if(!$this->getResponsiveModeEnabled()){
            return array();
        }

        $options = $this->getSection('responsive_mode');
        $options['mobiles'] = isset($options['mobiles']) ? SmartestStringHelper::toRealBool($options['mobiles']) : false;
        $options['tablets'] = isset($options['tablets']) ? SmartestStringHelper::toRealBool($options['tablets']) : false;
        $options['oldpcs'] = isset($options['oldpcs']) ? SmartestStringHelper::toRealBool($options['oldpcs']) : false;

        return $options;
    }

    public function getMainConfigurationOptions(){
        return $this->getConfigurationOptionsFromInfo($this->_info, 'general');
    }

    public function getContentIsEnabled(){
        return $this->getSectionFlag('content', 'enabled', false);
    }

    public function getTemplatesAreEnabled(){
        return $this->getSectionFlag('templates', 'enabled', false);
    }

    public function getDataStructureIsEnabled(){
        return $this->getSectionFlag('data_structure', 'enabled', false);
    }

    public function getPageStructureIsEnabled(){
        return $this->getSectionFlag('frontend_structure', 'enabled', false);
    }

    public function getContentIsRequired(){
        return $this->getSectionFlag('content', 'required', true);
    }

    public function getTemplatesAreRequired(){
        return $this->getSectionFlag('templates', 'required', true);
    }

    public function getDataStructureIsRequired(){
        return $this->getSectionFlag('data_structure', 'required', true);
    }

    public function getPageStructureIsRequired(){
        return $this->getSectionFlag('frontend_structure', 'required', true);
    }

    public function getContentConfigurationOptions(){
        return $this->getContentIsEnabled() ? $this->getConfigurationOptionsFromInfo($this->getSection('content'), 'content') : array();
    }

    public function getTemplatesConfigurationOptions(){
        return $this->getTemplatesAreEnabled() ? $this->getConfigurationOptionsFromInfo($this->getSection('templates'), 'templates') : array();
    }

    public function getDataStructureConfigurationOptions(){
        return $this->getDataStructureIsEnabled() ? $this->getConfigurationOptionsFromInfo($this->getSection('data_structure'), 'data_structures') : array();
    }

    public function getPageStructureConfigurationOptions(){
        return $this->getPageStructureIsEnabled() ? $this->getConfigurationOptionsFromInfo($this->getSection('frontend_structure'), 'page_structures') : array();
    }

    public function execute(SmartestSite $site, SmartestUser $user, $options){

        if(!is_array($options)){
            throw new SmartestException('Buildkit parameters not supplied.');
        }

        $site->save();
        SmartestBuildKitsHelper::clearRegisteredObjects();

        $GLOBALS['_buildkit_executing'] = $this;
        $GLOBALS['_buildkit_executing_user'] = $user;
        $GLOBALS['_buildkit_executing_site'] = $site;

        include_once __DIR__.'/buildkit_api_functions.php';

        try{

            $this->executeSection('content', 'setup_content', 'content_options', 'BUILDKIT_EXECUTE_CONTENT', $options);
            $this->executeSection('templates', 'setup_templates', 'templates_options', 'BUILDKIT_EXECUTE_TEMPLATES', $options);
            $this->executeSection('data_structures', 'setup_data_structures', 'data_structures_options', 'BUILDKIT_EXECUTE_DATA_STRUCTURES', $options);
            $this->executeSection('page_structures', 'setup_page_structures', 'page_structures_options', 'BUILDKIT_EXECUTE_PAGE_STRUCTURES', $options);

        }finally{

            unset($GLOBALS['_buildkit_executing']);
            unset($GLOBALS['_buildkit_executing_user']);
            unset($GLOBALS['_buildkit_executing_site']);
            SmartestBuildKitsHelper::clearRegisteredObjects();
        }

        return $site;
    }

    protected function executeSection($option_key, $function_suffix, $options_key, $constant_name, $options){

        $execute = isset($options['execute_confirm'][$option_key]) && SmartestStringHelper::toRealBool($options['execute_confirm'][$option_key]);
        $this->defineExecutionConstant($constant_name, $execute);

        if(!$execute){
            return;
        }

        $function_name = 'buildkit_'.$this->getShortName().'_'.$function_suffix;
        $section_file = $this->getDirectory().$function_suffix.'.function.php';
        $combined_file = $this->getDirectory().'setup_functions.php';

        if(is_file($section_file)){
            include_once $section_file;
        }elseif(is_file($combined_file)){
            include_once $combined_file;
        }else{
            throw new SmartestException('Installing '.$option_key.' for buildkit \''.$this->getLabel().'\' failed because no file containing function \''.$function_name.'\' could be found.');
        }

        if(function_exists($function_name)){
            $function_options = isset($options[$options_key]) && is_array($options[$options_key]) ? $options[$options_key] : array();
            $function_name($this, $GLOBALS['_buildkit_executing_site'], $GLOBALS['_buildkit_executing_user'], $function_options);
        }else{
            throw new SmartestException('Installing '.$option_key.' for buildkit \''.$this->getLabel().'\' failed because function \''.$function_name.'\' is not defined.');
        }
    }

    protected function defineExecutionConstant($name, $value){
        if(!defined($name)){
            define($name, (bool) $value);
        }
    }

    protected function getSection($name){
        return isset($this->_info[$name]) && is_array($this->_info[$name]) ? $this->_info[$name] : array();
    }

    protected function getSectionFlag($section, $flag, $default){
        $data = $this->getSection($section);
        return isset($data[$flag]) ? SmartestStringHelper::toRealBool($data[$flag]) : (bool) $default;
    }

    protected function getConfigurationOptionsFromInfo($info, $outer_name){

        $options = array();

        if(isset($info['configure_options']) && is_array($info['configure_options'])){
            foreach($info['configure_options'] as $key=>$opt){
                if(is_array($opt)){
                    $option = new SmartestConfigurationParameter($key, $opt);
                    $option->setFormOuterName($outer_name);
                    $options[] = $option;
                }
            }
        }

        return $options;
    }

    public function offsetExists(mixed $offset): bool{
        return in_array($offset, array('shortname', 'id', 'label', 'dir', 'directory', 'required_write_locations', 'unwritable_required_write_locations'));
    }

    public function offsetSet(mixed $offset, mixed $value): void{}
    public function offsetUnset(mixed $offset): void{}

    public function offsetGet(mixed $offset): mixed{

        switch($offset){

            case 'shortname':
            case 'id':
            return $this->getShortName();

            case 'label':
            return $this->getLabel();

            case 'dir':
            case 'directory':
            return $this->getDirectory();

            case 'required_write_locations':
            return $this->getRequiredWriteLocations();

            case 'unwritable_required_write_locations':
            return $this->getUnwritableRequiredWriteLocations();
        }

        return null;
    }
}
