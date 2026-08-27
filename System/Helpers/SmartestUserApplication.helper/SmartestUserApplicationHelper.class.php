<?php

class SmartestUserApplicationHelper{
    
    public static function createApplication($shortname, $classname='', $class_file_contents='', $config_file_contents='', $dir_name='', $create_presentation=true){
        
        if(!is_dir(SM_ROOT_DIR.'Applications/')){
            if(!@mkdir(SM_ROOT_DIR.'Applications/', 0777, true)){
                throw new SmartestException(SM_ROOT_DIR.'Applications/ could not be created');
            }
        }

        if(is_writable(SM_ROOT_DIR.'Applications/')){
        
            $shortname = self::createShortName($shortname);
        
            if(strlen($shortname) < 3){
                throw new SmartestException('$shortname must be three characters or longer in SmartestUserApplicationHelper::createApplication()');
            }
        
            if(self::applicationExistsWithShortName($shortname)){
                return false;
            }else{
                if(strlen($classname)){
                    $classname = SmartestStringHelper::toCamelCase($classname);
                }else{
                    $classname = SmartestStringHelper::toCamelCase($shortname);
                }
                
                if($dir_name){
                    $dir_name = str_replace(' ', '', $dir_name);
                }else{
                    $dir_name = $classname;
                }
                
                $dir = SmartestFileSystemHelper::getUniqueFileName(SM_ROOT_DIR.'Applications/'.$dir_name.'/');

                if(!strlen((string) $dir)){
                    throw new SmartestException('Could not determine a unique directory name for application '.$classname);
                }

                if(!SmartestStringHelper::endsWith($dir, '/')){
                    $dir .= '/';
                }
	            
                if(strlen($class_file_contents)){
                    if(is_file($class_file_contents)){
                        // We have been given a file path, so copy from that file
                        $raw_class_file_content = SmartestFileSystemHelper::load($class_file_contents);
                    }else{
                        // We have been given the file contents as a string
                        $raw_class_file_content = $class_file_contents;
                    }
                }else{
                    // No class file provided, so copy from Smartest samples and replace class name
                    $raw_class_file_content = SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Install/Samples/Application/App.class.php.txt');
                }
                
                $class_file_content = str_replace('%CLASSNAME%', $classname, $raw_class_file_content);
                $class_file_content = str_replace('%SHORTNAME%', $shortname, $class_file_content);
                $class_file_content = str_replace('%APPDIR%', $dir, $class_file_content);
                
                if(strlen($config_file_contents)){
                    if(is_file($config_file_contents)){
                        // We have been given a file path, so copy from that file
                        $raw_config_file_content = SmartestFileSystemHelper::load($config_file_contents);
                    }else{
                        // We have been given the file contents as a string
                        $raw_config_file_content = $config_file_contents;
                    }
                }else{
                    // No class file provided, so copy from Smartest samples and replace class name
                    $raw_config_file_content = SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Install/Samples/Application/quince.yml.txt');
                }
                
                $identifier = 'org.yourname.'.$classname.SmartestStringHelper::random(8);
                
                $config_file_content = str_replace('%CLASSNAME%', $classname, $raw_config_file_content);
                $config_file_content = str_replace('%SHORTNAME%', $shortname, $config_file_content);
                $config_file_content = str_replace('%APPIDENTIFIER%', $identifier, $config_file_content);
                $config_file_content = str_replace('%RANDOMURL%', SmartestStringHelper::random(6), $config_file_content);
                
                if(!is_dir($dir) && @mkdir($dir, 0777, true)){
	                    
                    if(!is_dir($dir.'Presentation/')){
                        mkdir($dir.'Presentation/', 0777, true);
                    }

                    if(!is_dir($dir.'Configuration/')){
                        mkdir($dir.'Configuration/', 0777, true);
                    }
	                    
                    SmartestFileSystemHelper::save($dir.$classname.'.class.php', $class_file_content);
                    SmartestFileSystemHelper::save($dir.'Configuration/quince.yml', $config_file_content);
                    
                    if($create_presentation){
                        SmartestFileSystemHelper::save($dir.'Presentation/_default.tpl', SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Install/Samples/Application/_default.tpl'));
                        SmartestFileSystemHelper::save($dir.'Presentation/startAction.tpl', SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Install/Samples/Application/startAction.tpl'));
                    }
                    
                    self::clearModuleCache();
	                    
                    $creation_data = new SmartestParameterHolder('Application creation data');
                    $creation_data->setParameter('directory', $dir);
                    $creation_data->setParameter('auto_identifier', $identifier);
                    $creation_data->setParameter('classname', $classname);
                    $creation_data->setParameter('shortname', $shortname);
                    return $creation_data;
                    
                }else{
                    return false;
                }
                
            }
        
        }else{
            throw new SmartestException(SM_ROOT_DIR.'Applications/ must be writable to create applications automatically');
        }
        
    }
    
    public static function applicationExistsWithShortName($shortname){
        
        $shortname = self::createShortName($shortname);
        return in_array($shortname, self::getAllModuleShortNames(), true);
	        
    }

    public static function getAllModuleShortNames(){

        $shortnames = array();
        $config = self::getQuinceConfiguration();
        $module_config_file = isset($config['modules']['config']) ? $config['modules']['config'] : 'Configuration/quince.yml';
        $module_dirs = isset($config['modules']['storage']) && is_array($config['modules']['storage']) ? $config['modules']['storage'] : array('System/Applications/', 'Applications/');

        foreach($module_dirs as $module_dir){
            $full_module_dir = SM_ROOT_DIR.$module_dir;

            if(!is_dir($full_module_dir)){
                continue;
            }

            foreach(SmartestFileSystemHelper::getDirectoryContents($full_module_dir, false, SM_DIR_SCAN_DIRECTORIES) as $dir_name){
                $module_config_path = $full_module_dir.$dir_name.'/'.$module_config_file;

                if(!is_file($module_config_path)){
                    continue;
                }

                $module_config = self::loadYaml($module_config_path);

                if(isset($module_config['module']['shortname'])){
                    $shortnames[] = self::createShortName($module_config['module']['shortname']);
                }
            }
        }

        return array_unique($shortnames);

    }

    protected static function getQuinceConfiguration(){

        $config_file = SM_ROOT_DIR.'Configuration/quince.yml';

        if(is_file($config_file)){
            $config = self::loadYaml($config_file);
            if(isset($config['quince']) && is_array($config['quince'])){
                return $config['quince'];
            }
        }

        return array();

    }

    protected static function loadYaml($file){

        if(class_exists('SmartestYamlHelper')){
            return SmartestYamlHelper::fastLoad($file);
        }

        if(class_exists('QuinceUtilities')){
            return QuinceUtilities::yamlFastLoad($file);
        }

        throw new SmartestException('Could not load YAML file '.$file.' because no YAML helper is available.');

    }

    protected static function clearModuleCache(){

        if(!class_exists('QuinceUtilities') || !strlen((string) Quince::$cache_dir)){
            return;
        }

        foreach(array(
            'all_modules_hash',
            'all_modules_config_hash',
            'all_aliases',
            'alias_url_shortcuts',
            'routes',
            'all_modules',
            'module_shortnames'
        ) as $key){
            QuinceUtilities::cacheClear($key);
        }

    }
	    
    public static function createShortName($shortname){
        return str_replace('_', '', SmartestStringHelper::toVarName($shortname, true));
    }
    
}
