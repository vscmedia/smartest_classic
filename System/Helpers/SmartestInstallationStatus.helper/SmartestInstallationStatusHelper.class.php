<?php

require SM_ROOT_DIR.'System/Base/Exceptions/SmartestNotInstalledException.class.php';

class SmartestInstallationStatusHelper{

    const INSTALLATION_RECEIPT_FILE = 'System/Core/Info/.installation.log';
    const AUTOMATED_DATABASE_CONFIG_FILE = 'System/Temporary/installer-database.yml';
    
    public static function checkStatus($purge=false){
        
        if(!$purge){
            self::importAutomatedDatabaseConfig();
        }

        if(!$purge && self::installationLooksComplete()){
            self::markInstallationComplete();
            return;
        }

        if(!$purge && (is_file(SM_ROOT_DIR.'Public/.htaccess') && (is_file(SM_ROOT_DIR.'Configuration/database.ini') || is_file(SM_ROOT_DIR.'Configuration/database.yml')))){
            $cached_status = SmartestCache::load('installation_status', true);
        }
        // if(SmartestCache::load('installation_status', true) !== SM_INSTALLSTATUS_COMPLETE || $purge || (!is_file(SM_ROOT_DIR.'Public/.htaccess') || !is_file(SM_ROOT_DIR.'Configuration/controller.xml') || !is_file(SM_ROOT_DIR.'Configuration/database.ini'))){
            
    	    if(is_file(SM_ROOT_DIR."System/Core/Info/system.yml")){
    	        $SYSTEM_INFO_FILE = SM_ROOT_DIR."System/Core/Info/system.yml";
    	    }else if(is_file(SM_ROOT_DIR."System/Core/Info/.system.yml")){
    	        $SYSTEM_INFO_FILE = SM_ROOT_DIR."System/Core/Info/.system.yml";
    	    }
            
            // session_start();
            $system_data = SmartestYamlHelper::toParameterHolder($SYSTEM_INFO_FILE, false);
            $writable_files = self::getInstallationWritableLocations($system_data);
            
            $errors = array();
            
            foreach($writable_files as $file){
    			if(!is_writable(SM_ROOT_DIR.$file)){
    				$errors[] = SM_ROOT_DIR.$file;
    			}
    		}
    		
    		if(count($errors)){
    		    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_FILE_PERMS);
    		}
    		
    		// Now, if a form has been submitted, there might be an installer action that needs to be carried out before we check the installation status again
    		if(isset($_POST['execute']) && $_POST['execute'] == '1' && isset($_POST['action'])){
    		    
    		    if(!class_exists('SmartestInstaller')){
    	            require SM_ROOT_DIR.'System/Install/SmartestInstaller.class.php';
                }

                $action = $_POST['action'];
                
                SmartestLog::getInstance('installer')->log('The installer submitted action \''.$action.'\'.', SM_LOG_DEBUG);

                // Yes, yes, switch/case is ugly, but the whole point of this is not to rely on any of the actual Smartest code - just small and simple.
                switch($action){

                    case 'createConfigs':
                    
                    $fve = new SmartestParameterHolder("User creation form validator errors");
                    $ph = new SmartestParameterHolder("New database connection parameters");
                    
                    if(strlen($_POST['db_username'])){
                        $ph->setParameter('username', $_POST['db_username']);
                    }else{
                        $fve->setParameter('username', "You did not provide a username.");
                    }
                    
                    if(strlen($_POST['db_password'])){
                        $ph->setParameter('password', $_POST['db_password']);
                    }else{
                        $fve->setParameter('password', "You did not provide a password.");
                    }
                    
                    if(strlen($_POST['db_database'])){
                        $ph->setParameter('database', $_POST['db_database']);
                    }else{
                        $fve->setParameter('database', "You did not provide the name of a database where Smartest can store your stuff.");
                    }
                    
                    if(strlen($_POST['db_host'])){
                        $ph->setParameter('host', $_POST['db_host']);
                    }else{
                        $fve->setParameter('host', "You did not provide the name of a database host. Try 'localhost'.");
                    }
                    
                    if($fve->hasData()){
                        $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_DB_DATA_INVALID);
                        $nie->setValidationErrors($fve);
                        throw $nie;
                    }else{
                        
                        $installer = new SmartestInstaller;
                        $installer->createNewDatabaseConfig($ph);

                        if(isset($_POST['controller_domain'])){
                            $controller_domain = $_POST['controller_domain'];
                            if(substr($controller_domain, -1, 1) != '/'){
                                $controller_domain .= '/';
                            }
                        }else{
                            $controller_domain = '';
                        }

                        SmartestCache::save('controller_domain_temp', $controller_domain, -1, true);

                        // $installer->createQuinceControllerFile($controller_domain);
                        // $installer->createHtAccessFile('/'.$controller_domain);
                        
                    }
                    
                    break;
                    
                    case 'createUser':
                    
                    $fve = new SmartestParameterHolder("User creation form validator errors");
                    
                    if(strlen($_POST['smartest_username']) < 3){
                        // problem with username
                        $fve->setParameter('username', "The username you entered is too short. It must have a minimum of three characters.");
                        SmartestLog::getInstance('installer')->log('The username given to the installer at stage 3 was shorter than the required 3 character ('.strlen($_POST['smartest_password']).' chars).', SM_LOG_WARNING);
                    }
                    
                    if(strlen($_POST['smartest_password']) < 6){
                        // problem with password 1
                        $fve->setParameter('password', "The password you entered is too short. It must have a minimum of six characters.");
                        SmartestLog::getInstance('installer')->log('The password given to the installer at stage 3 was shorter than the required 6 character ('.strlen($_POST['smartest_password']).' chars).', SM_LOG_WARNING);
                    }else if($_POST['smartest_password'] != $_POST['smartest_password_2']){
                        // problem with password 2
                        $fve->setParameter('password', "The passwords you entered did not match.");
                        SmartestLog::getInstance('installer')->log('The passwords given to the installer at stage 3 did not match.', SM_LOG_WARNING);
                    }
                    
                    if(strlen($_POST['smartest_firstname']) < 2){
                        // problem with firstname
                        $fve->setParameter('firstname', "The first name you entered is too short. It must have a minimum of two characters.");
                        SmartestLog::getInstance('installer')->log('The first name given to the installer at stage 3 was shorter than the required 2 characters ('.strlen($_POST['smartest_firstname']).' chars).', SM_LOG_WARNING);
                    }
                    
                    if(!SmartestStringHelper::isEmailAddress($_POST['smartest_email'])){
                        // problem with email format
                        $fve->setParameter('email', "Please enter a valid e-mail address.");
                        SmartestLog::getInstance('installer')->log('The e-mail address given to the installer at stage 3 was invalid.', SM_LOG_WARNING);
                    }
                    
                    if($fve->hasData()){
                        $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_USER_DATA_INVALID);
                        $nie->setValidationErrors($fve);
                        throw $nie;
                    }else{
                        
                        $username = SmartestStringHelper::toVarName($_POST['smartest_username']);
                        $password = md5($_POST['smartest_password']);
                        $firstname = SmartestStringHelper::sanitize($_POST['smartest_firstname']);
                        $firstname = str_replace("'", '', $firstname);
                        $lastname = SmartestStringHelper::sanitize($_POST['smartest_lastname']);
                        $lastname = str_replace("'", '', $lastname);
                        $email = SmartestStringHelper::isEmailAddress($_POST['smartest_email']) ? $_POST['smartest_email'] : '';
                        
                        $sql = file_get_contents(SM_ROOT_DIR.'System/Install/SqlScripts/create_user.sql.txt');
                        $sql = str_replace('%USERNAME%', $username, $sql);
                        $sql = str_replace('%PASSWORD%', $password, $sql);
                        $sql = str_replace('%FIRSTNAME%', $firstname, $sql);
                        $sql = str_replace('%LASTNAME%', $lastname, $sql);
                        $sql = str_replace('%EMAIL%', $email, $sql);
                        
                        $queries = explode(';', $sql);
                        $db = SmartestDatabase::getInstance('SMARTEST');

                        foreach($queries as $query){
                            if(strlen(trim($query))){
                                try{
                                    $db->rawQuery(trim($query).';');
                                }catch (SmartestDatabaseException $user_error) {
                                    SmartestLog::getInstance('installer')->log('There was an error creating user account data on query: '.$query.'.', SM_LOG_ERROR);
                                    continue;
                                }
                            }
                        }
                        
                        SmartestLog::getInstance('installer')->log('Created system user \'Smartest\' with a uid of 0', SM_LOG_DEBUG);
                        SmartestLog::getInstance('installer')->log('Created user '.$username.' with a uid of 1', SM_LOG_DEBUG);
                        
                    }
                    
                    break;
                    
                    case 'createSite':
                    
                    $fve = new SmartestParameterHolder("Site creation form validator errors");
                    
                    if(strlen($_POST['site_name']) < 3){
                        // problem with username
                        $fve->setParameter('name', "The site name you entered is too short. It must have a minimum of three characters.");
                        SmartestLog::getInstance('installer')->log('The site name given to the installer at stage 4 was shorter than the required 3 characters ('.strlen($_POST['site_name']).' chars).', SM_LOG_WARNING);
                    }
                    
	                    if(strlen($_POST['site_host']) < 5){
	                        // problem with username
	                        $fve->setParameter('host', "The hostname you entered is too short. It must have a minimum of five characters.");
	                        SmartestLog::getInstance('installer')->log('The hostname given to the installer at stage 4 was shorter than the possible 5 characters ('.strlen($_POST['site_host']).' chars).', SM_LOG_WARNING);
	                    }

                        if(!class_exists('SmartestBuildKitUtilities') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php')){
                            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php';
                        }

                        if(!class_exists('SmartestBuildKit') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php')){
                            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php';
                        }

                        if(!class_exists('SmartestBuildKitsHelper') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitsHelper.class.php')){
                            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitsHelper.class.php';
                        }

                        if(!class_exists('SmartestSiteCreationHelper') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestSiteCreation.helper/SmartestSiteCreationHelper.class.php')){
                            require_once SM_ROOT_DIR.'System/Helpers/SmartestSiteCreation.helper/SmartestSiteCreationHelper.class.php';
                        }

                        $build_kit_name = isset($_POST['use_buildkit']) ? $_POST['use_buildkit'] : SmartestBuildKitUtilities::getDefaultInstallerBuildKitShortName();
                        if($build_kit_name == '_NONE'){
                            $build_kit_name = SmartestBuildKitUtilities::getDefaultInstallerBuildKitShortName();
                        }

                        $buildkit = null;
                        $prepared_buildkit_params = array();

                        if(strlen((string) $build_kit_name)){
                            $buildkit = SmartestBuildKitUtilities::getBuildKitIfInstalled($build_kit_name);

                            if($buildkit instanceof SmartestBuildKit){
                                $unwritable_locations = $buildkit->getUnwritableRequiredWriteLocations();

                                if(count($unwritable_locations)){
                                    $fve->setParameter('buildkit_permissions', "The selected Build Kit needs these locations to be writable before it can run: ".implode(', ', $unwritable_locations).'.');
                                }else{
                                    $prepared_buildkit_params = SmartestBuildKitsHelper::prepareRequestParamsForBuildKit($_POST, $buildkit);
                                }
                            }else{
                                $fve->setParameter('buildkit', "The Build Kit you selected could not be found.");
                            }
                        }

	                    if($fve->hasData()){
	                        $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
	                        $nie->setValidationErrors($fve);
                        throw $nie;
                    }else{

	                        $controller_domain_cache = SmartestCache::load('controller_domain_temp', -1, true);
                        
                        if($controller_domain_cache && strlen($controller_domain_cache)){
                            $controller_domain = $controller_domain_cache;
                            if(substr($controller_domain, -1, 1) != '/'){
                                $controller_domain .= '/';
                            }
                        }else{
                            $controller_domain = '';
                        }
                        
	                        $installer = new SmartestInstaller;
	                        $installer->createHtAccessFile('/'.$controller_domain);
	                        $installer->moveEssentialFilesIntoPlace();

                            $db = SmartestDatabase::getInstance('SMARTEST');
                            $uq = $db->preparedQuery('SELECT user_email FROM Users WHERE user_id=:user_id LIMIT 1', array('user_id' => 1));
                            $email = isset($uq[0]['user_email']) ? $uq[0]['user_email'] : '';
                            $sitename = SmartestStringHelper::sanitize($_POST['site_name']);
                            $hostname = SmartestStringHelper::sanitize($_POST['site_host']);
                            $user = new SmartestSystemUser;

                            if(!$user->find(1)){
                                $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
                                $errors = new SmartestParameterHolder("Site creation form validator errors");
                                $errors->setParameter('user', "The first user account could not be loaded, so the first site could not be created.");
                                $nie->setValidationErrors($errors);
                                throw $nie;
                            }

                            $site_params = new SmartestParameterHolder('First site creation parameters');
                            $site_params->setParameter('site_name', $sitename);
                            $site_params->setParameter('site_internal_label', $sitename);
                            $site_params->setParameter('site_domain', $hostname);
                            $site_params->setParameter('site_admin', $email);
                            $site_params->setParameter('site_master_template', '_DEFAULT');

                            $sch = new SmartestSiteCreationHelper;

                            try{
                                $site = $sch->createNewSiteFromBuildKit($site_params, $user, $buildkit, $prepared_buildkit_params);
                                SmartestLog::getInstance('installer')->log("Created first site '".$site->getName()."' with Build Kit '".$buildkit->getLabel()."'.", SM_LOG_DEBUG);
                            }catch(Exception $buildkit_error){
                                SmartestLog::getInstance('installer')->log("First site creation with Build Kit '".$buildkit->getLabel()."' failed: ".$buildkit_error->getMessage(), SM_LOG_ERROR);
                                $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
                                $errors = new SmartestParameterHolder("Site creation form validator errors");
                                $errors->setParameter('buildkit', "The first site could not be created: ".$buildkit_error->getMessage());
                                $nie->setValidationErrors($errors);
                                throw $nie;
                            }

                            self::markInstallationComplete();

	                        // $cd = SmartestSystemSettingHelper::load('htaccess_rewrite_base');
	                        $cd = '/'.$controller_domain;
                        
                        if(strlen($cd) && $cd != '/'){
                            $location = $cd.'smartest/login#welcome';
                        }else{
                            $location = '/smartest/login#welcome';
                        }
                        
                        header("Location: ".$location);
                        
                    }
                    
                    break;

                }

            }
            
            // ok, now the status can be checked again
            
            if(file_exists(SM_ROOT_DIR.'Configuration/database.yml')){
                // Config files are in place, so try connecting to the database
                try{
                    $db = SmartestDatabase::getInstance('SMARTEST', true);
                }catch(SmartestDatabaseException $e){
                    
                    switch($e->getDbErrorType()){
                        
                        case SmartestDatabaseException::SPEC_DB_ACCESS_DENIED:
                        SmartestLog::getInstance('installer')->log('Database error: access denied for user specified in connection [SMARTEST].', SM_LOG_WARNING);
                        $ph = new SmartestParameterHolder('Database connection parameters');
                        $ph->setParameter('username', $e->getUsername());
                        $ph->setParameter('database', $e->getDatabase());
                        $ph->setParameter('host', $e->getHost());
                        $s = SM_INSTALLSTATUS_DB_NOT_ALLOWED;
                        break;
                        
                        case SmartestDatabaseException::INVALID_CONNECTION_NAME:
                        SmartestLog::getInstance('installer')->log('Database error: connection [SMARTEST] does not exist.', SM_LOG_WARNING);
                        $ph = new SmartestParameterHolder('Database connection parameters');
                        $ph->setParameter('username', $e->getUsername());
                        $ph->setParameter('database', $e->getDatabase());
                        $ph->setParameter('host', $e->getHost());
                        $s = SM_INSTALLSTATUS_NO_DB_CONFIG;
                        break;
                        
                        case SmartestDatabaseException::CONNECTION_IMPOSSIBLE:
                        default:
                        SmartestLog::getInstance('installer')->log('Database error: Smartest could not connect to the database with the details provided in ./Configuration/database.yml', SM_LOG_WARNING);
                        $ph = new SmartestParameterHolder('Database connection parameters');
                        $ph->setParameter('username', $e->getUsername());
                        $ph->setParameter('database', $e->getDatabase());
                        $ph->setParameter('host', $e->getHost());
                        $s = SM_INSTALLSTATUS_DB_NO_CONN;
                        break;
                        
                    }
                    
                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
                        SmartestCache::save('installation_status', $s, -1, true);
                    }
                    
                    $nie = new SmartestNotInstalledException($s);
                    $nie->setDatabaseConnectionParameters($ph);
                    throw $nie;
                    
                }
                
                SmartestLog::getInstance('installer')->log('SmartestInstaller has a working database connection.', SM_LOG_DEBUG);
                
                $tables = $db->getTables();

                if(count($tables) < 1 || !in_array('Users', $tables) || !in_array('Sites', $tables)){
                    SmartestLog::getInstance('installer')->log('Trying to build database tables structure.', SM_LOG_DEBUG);
                    try{
                        $db->executeSqlFile(SM_ROOT_DIR."System/Install/SqlScripts/table_setup.sql");
                    }catch(SmartestDatabaseException $e){
                        // The tables could not be set up. Write to install log
                        SmartestLog::getInstance('installer')->log('Database schema setup failed: '.$e->getMessage(), SM_LOG_ERROR);
                    }
                }
                
                // If we have got this far then that means we have a working connection to the database
                if(count($db->getTables(true)) < 1){
                    
                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_DB_NO_CREATE_PERMS, -1, true);
                    }
                    
                    SmartestLog::getInstance('installer')->log('After trying to create, database tables still don\'t exist which probably means Smartest doesn\'t have permission to create them', SM_LOG_WARNING);
                    $ph = SmartestDatabase::readConfiguration('SMARTEST');
                    $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_DB_NO_CREATE_PERMS);
                    $nie->setDatabaseConnectionParameters($ph);
                    throw $nie;
                }
                
                if(count($db->queryToArray("SELECT user_id FROM Users")) < 2){
                    
                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_NO_USERS, -1, true);
                    }
                    
                    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_USERS);
                    
                }
                
                /* if(count($db->queryToArray("SELECT token_id FROM UserTokens")) < 1){
                    
                    try{
                        $db->executeSqlFile(SM_ROOT_DIR."System/Install/SqlScripts/user_tokens.sql");
                    }catch (SmartestDatabaseException $tokens_error) {
                        SmartestLog::getInstance('installer')->log('There was an error creating user tokens from file System/Install/SqlScripts/user_tokens.sql: '.$tokens_error->getMessage(), SM_LOG_ERROR);
                    }
                    
                } */
                
	                if(count($db->queryToArray("SELECT site_id FROM Sites")) < 1){

	                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
	                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_NO_SITES, -1, true);
	                    }

	                    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_SITES);

	                }

	                if(count($db->queryToArray("SELECT page_id FROM Pages")) < 1 || count($db->queryToArray("SELECT setting_id FROM Settings")) < 1 || count($db->queryToArray("SELECT asset_id FROM Assets")) < 1){

	                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
	                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_SITE_DATA_INVALID, -1, true);
	                    }

	                    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);

	                }

	                if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
		                    SmartestCache::save('installation_status', SM_INSTALLSTATUS_COMPLETE, -1, true);
                    if(!SmartestSystemSettingHelper::hasData('_system_installed_timestamp')){
                        SmartestSystemSettingHelper::save('_system_installed_timestamp', time());
                    }
                }
                self::markInstallationComplete();

	            }else{
                
                // Config files haven't been created yet
                if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
                    
                    SmartestCache::save('installation_status', SM_INSTALLSTATUS_NO_CONFIG, -1, true);
                    
                }
                
                throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_CONFIG);
            }
	    }

    public static function markInstallationComplete(){

        if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
            SmartestCache::save('installation_status', SM_INSTALLSTATUS_COMPLETE, -1, true);
        }

        $file = SM_ROOT_DIR.self::INSTALLATION_RECEIPT_FILE;
        $dir = dirname($file);

        if(!is_dir($dir) || (!is_writable($dir) && !is_file($file))){
            return false;
        }

        if(is_file($file)){
            return true;
        }

        $contents = array(
            'Smartest installation completed',
            'Completed: '.date('c'),
            'Root: '.SM_ROOT_DIR,
            'Host: '.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''),
        );

        return file_put_contents($file, implode("\n", $contents)."\n") !== false;
    }

    protected static function installationLooksComplete(){

        if(!is_file(SM_ROOT_DIR.'Public/.htaccess') || !(is_file(SM_ROOT_DIR.'Configuration/database.ini') || is_file(SM_ROOT_DIR.'Configuration/database.yml'))){
            return false;
        }

        try{
            $db = SmartestDatabase::getInstance('SMARTEST', true);
            $tables = $db->getTables(true);
        }catch(Exception $e){
            return false;
        }

        if(!in_array('Users', $tables) || !in_array('Sites', $tables)){
            return false;
        }

        try{
            return count($db->queryToArray("SELECT user_id FROM Users LIMIT 2")) > 1
                && count($db->queryToArray("SELECT site_id FROM Sites LIMIT 1")) > 0;
        }catch(Exception $e){
            return false;
        }
    }

    protected static function databaseConfigurationConnects(){

        if(!is_file(SM_ROOT_DIR.'Configuration/database.yml') && !is_file(SM_ROOT_DIR.'Configuration/database.ini')){
            return false;
        }

        try{
            SmartestDatabase::testConnection('SMARTEST');
            return true;
        }catch(Exception $e){
            return false;
        }
    }

    protected static function importAutomatedDatabaseConfig(){

        if(is_file(SM_ROOT_DIR.'Configuration/database.yml') || is_file(SM_ROOT_DIR.'Configuration/database.ini')){
            return false;
        }

        $source_file = SM_ROOT_DIR.self::AUTOMATED_DATABASE_CONFIG_FILE;

        if(!is_file($source_file)){
            return false;
        }

        if(!is_readable($source_file)){
            SmartestLog::getInstance('installer')->log('Automated installer database configuration file exists but is not readable: '.self::AUTOMATED_DATABASE_CONFIG_FILE, SM_LOG_WARNING);
            return false;
        }

        $data = SmartestYamlHelper::load($source_file);
        $params = self::getAutomatedDatabaseConfigParameters($data);

        if(!$params instanceof SmartestParameterHolder){
            SmartestLog::getInstance('installer')->log('Automated installer database configuration file could not be used because it did not contain username, database, and host values.', SM_LOG_WARNING);
            self::removeAutomatedDatabaseConfigFile($source_file);
            return false;
        }

        if(!is_writable(SM_ROOT_DIR.'Configuration/')){
            SmartestLog::getInstance('installer')->log('Automated installer database configuration file was found but ./Configuration/ is not writable, so Configuration/database.yml could not be created.', SM_LOG_WARNING);
            return false;
        }

        if(!class_exists('SmartestInstaller')){
            require SM_ROOT_DIR.'System/Install/SmartestInstaller.class.php';
        }

        $installer = new SmartestInstaller;

        if(!$installer->createNewDatabaseConfig($params) || !is_file(SM_ROOT_DIR.'Configuration/database.yml')){
            SmartestLog::getInstance('installer')->log('Automated installer database configuration file was found but Configuration/database.yml could not be created.', SM_LOG_WARNING);
            return false;
        }

        SmartestCache::clear('dbc_SMARTEST', true);
        SmartestCache::clear('db_config_yaml_file_md5', true);

        try{
            SmartestDatabase::testConnection('SMARTEST');
        }catch(Exception $e){
            if(is_file(SM_ROOT_DIR.'Configuration/database.yml') && is_writable(SM_ROOT_DIR.'Configuration/database.yml')){
                unlink(SM_ROOT_DIR.'Configuration/database.yml');
            }
            SmartestCache::clear('dbc_SMARTEST', true);
            SmartestCache::clear('db_config_yaml_file_md5', true);
            SmartestLog::getInstance('installer')->log('Automated installer database configuration was rejected because Smartest could not connect to the database: '.$e->getMessage(), SM_LOG_WARNING);
            self::removeAutomatedDatabaseConfigFile($source_file);
            return false;
        }

        self::removeAutomatedDatabaseConfigFile($source_file);
        SmartestLog::getInstance('installer')->log('Imported and tested automated installer database configuration from '.self::AUTOMATED_DATABASE_CONFIG_FILE.'. The browser installer can continue from user account creation.', SM_LOG_DEBUG);
        return true;
    }

    protected static function getAutomatedDatabaseConfigParameters($data){

        if(!is_array($data)){
            return false;
        }

        if(isset($data['SMARTEST']) && is_array($data['SMARTEST'])){
            $data = $data['SMARTEST'];
        }

        $required = array('username', 'database', 'host');

        foreach($required as $name){
            if(!isset($data[$name]) || !is_scalar($data[$name]) || !strlen(trim((string) $data[$name])) || preg_match('/[\x00-\x1F\x7F]/', (string) $data[$name])){
                return false;
            }
        }

        if(isset($data['password']) && !is_scalar($data['password'])){
            return false;
        }

        $ph = new SmartestParameterHolder("Automated database connection parameters");
        $ph->setParameter('username', trim((string) $data['username']));
        $ph->setParameter('password', isset($data['password']) ? (string) $data['password'] : '');
        $ph->setParameter('database', trim((string) $data['database']));
        $ph->setParameter('host', trim((string) $data['host']));

        return $ph;
    }

    protected static function removeAutomatedDatabaseConfigFile($source_file){

        if(is_file($source_file)){
            if(is_writable($source_file) && is_writable(dirname($source_file))){
                unlink($source_file);
                SmartestLog::getInstance('installer')->log('Deleted automated installer database configuration file '.$source_file.'.', SM_LOG_DEBUG);
                return true;
            }else{
                SmartestLog::getInstance('installer')->log('Automated installer database configuration file could not be deleted; please remove it manually: '.$source_file, SM_LOG_WARNING);
            }
        }

        return false;
    }

    protected static function getInstallationWritableLocations(SmartestParameterHolder $system_data){

        $writable_files = array_merge($system_data->g('system')->g('writable_locations')->g('always')->toArray(), $system_data->g('system')->g('writable_locations')->g('installation')->toArray());

        if(self::databaseConfigurationConnects()){
            $writable_files = array_diff($writable_files, array('Configuration/'));
        }

        if(!class_exists('SmartestBuildKitUtilities') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php';
        }

        if(!class_exists('SmartestBuildKit') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php';
        }

        if(class_exists('SmartestBuildKitUtilities')){
            foreach(SmartestBuildKitUtilities::getAvailableBuildKits() as $buildkit){
                foreach($buildkit->getRequiredWriteLocations() as $location){
                    if(!in_array($location, $writable_files, true)){
                        $writable_files[] = $location;
                    }
                }
            }
        }

        return $writable_files;
    }
}
