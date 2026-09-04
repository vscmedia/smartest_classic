<?php

require SM_ROOT_DIR.'System/Base/Exceptions/SmartestNotInstalledException.class.php';

class SmartestInstallationStatusHelper{

    const INSTALLATION_RECEIPT_FILE = 'System/Core/Info/.installation.log';
    const AUTOMATED_DATABASE_CONFIG_FILE = 'System/Temporary/installer-database.yml';
    const PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY = 'installer_pending_first_site_buildkit';
    const FIRST_SITE_INSTALLER_TOKEN_CACHE_KEY = 'installer_first_site_buildkit_token';
    const INSTALLATION_DATABASE_MARKER = '_system_installed_timestamp';
    
    public static function checkStatus($purge=false){
        
        if(!$purge){
            self::importAutomatedDatabaseConfig();
        }

        if(!$purge && self::installationLooksComplete()){
            self::markInstallationComplete();
            return;
        }

        self::logInstall('Installation status check started. purge='.($purge ? 'true' : 'false').self::getRequestContextForLog().'.', SM_LOG_DEBUG);

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
            self::logInstall('Installer is checking '.count($writable_files).' required writable location(s).', SM_LOG_DEBUG);
            
            $errors = array();
            
            foreach($writable_files as $file){
    			if(!is_writable(SM_ROOT_DIR.$file)){
    				$errors[] = SM_ROOT_DIR.$file;
    			}
    		}
    		
    		if(count($errors)){
                self::logInstall('Installer cannot continue because these paths are not writable: '.implode(', ', $errors).'.', SM_LOG_WARNING);
    		    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_FILE_PERMS);
    		}
    		
    		// Now, if a form has been submitted, there might be an installer action that needs to be carried out before we check the installation status again
    		if(isset($_POST['execute']) && $_POST['execute'] == '1' && isset($_POST['action'])){
    		    
    		    if(!class_exists('SmartestInstaller')){
    	            require SM_ROOT_DIR.'System/Install/SmartestInstaller.class.php';
                }

                $action = $_POST['action'];
                
                self::logInstall('The installer submitted action \''.$action.'\'.', SM_LOG_DEBUG);

                // Yes, yes, switch/case is ugly, but the whole point of this is not to rely on any of the actual Smartest code - just small and simple.
                try{
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

                        $controller_domain = isset($_POST['controller_domain']) ? $_POST['controller_domain'] : '';
                        $controller_domain = self::normalizeControllerDomain($controller_domain);

                        SmartestCache::save('controller_domain_temp', $controller_domain, -1, true);

                        // $installer->createQuinceControllerFile($controller_domain);
                        if(!$installer->createHtAccessFile($controller_domain, true) || !is_file(SM_ROOT_DIR.'Public/.htaccess')){
                            self::logInstall('Installer could not create Public/.htaccess during basic configuration.', SM_LOG_WARNING);
                            throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_FILE_PERMS);
                        }
                        
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

                        require_once SM_ROOT_DIR.'System/Install/SmartestInstallerUser.class.php';

                        $installer_user = new SmartestInstallerUser(SmartestDatabase::getInstance('SMARTEST'));
                        $installer_user->createInitialAccounts(
                            $_POST['smartest_username'],
                            $_POST['smartest_password'],
                            $_POST['smartest_firstname'],
                            $_POST['smartest_lastname'],
                            $_POST['smartest_email']
                        );
                        
                    }
                    
                    break;
                    
                    case 'createSite':
                    
                    $fve = new SmartestParameterHolder("Site creation form validator errors");
                    $direct_postinstaller_request = self::isPendingFirstSiteBuildKitExecutionRequest();
                    self::logInstall("Installer createSite request detected. direct_postinstaller_request=".($direct_postinstaller_request ? 'true' : 'false').self::getRequestContextForLog().'.', SM_LOG_DEBUG);
                    
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

                        if($direct_postinstaller_request && !self::firstSiteInstallerTokenMatches(isset($_POST['token']) ? $_POST['token'] : '')){
                            $fve->setParameter('token', "This installer form has expired. Please check the details below and click Finish & Log In again.");
                            SmartestLog::getInstance('installer')->log('The final installer form submitted to the post-installer action with a missing or invalid token.', SM_LOG_WARNING);
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

	                        $controller_domain_cache = SmartestCache::load('controller_domain_temp', true);
                        
                        if($controller_domain_cache && strlen($controller_domain_cache)){
                            $controller_domain = self::normalizeControllerDomain($controller_domain_cache);
                        }else{
                            $controller_domain = '';
                        }
                        
	                        $installer = new SmartestInstaller;
	                        if(!$installer->createHtAccessFile($controller_domain, true) || !is_file(SM_ROOT_DIR.'Public/.htaccess')){
                                $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
                                $errors = new SmartestParameterHolder("Site creation form validator errors");
                                $errors->setParameter('htaccess', "Smartest could not create Public/.htaccess. Please check write permissions for Public/.");
                                $nie->setValidationErrors($errors);
                                throw $nie;
	                        }
	                        $installer->moveEssentialFilesIntoPlace();

	                            if($direct_postinstaller_request){
	                                SmartestPersistentObject::set('installer:first_site_creation_request', array(
	                                    'site_name' => SmartestStringHelper::sanitize($_POST['site_name']),
	                                    'site_host' => SmartestStringHelper::sanitize($_POST['site_host']),
	                                    'buildkit' => $buildkit instanceof SmartestBuildKit ? $buildkit->getShortName() : (string) $build_kit_name,
                                    'buildkit_params' => $prepared_buildkit_params,
                                    'controller_domain' => $controller_domain,
                                ));
                                self::logInstall("Final installer form submitted directly to normal-runtime first-site creation action. Build Kit execution request has been handed to SmartestPersistentObject.", SM_LOG_DEBUG);
                                return;
                            }

                            $post_install_token = self::savePendingFirstSiteBuildKit(
                                $_POST['site_name'],
                                $_POST['site_host'],
                                $buildkit,
                                $prepared_buildkit_params,
                                $controller_domain
                            );

                            if(!$post_install_token){
                                $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
                                $errors = new SmartestParameterHolder("Site creation form validator errors");
                                $errors->setParameter('buildkit', "The first site could not be queued for creation. Please check write permissions for System/Cache/Data/.");
                                $nie->setValidationErrors($errors);
                                throw $nie;
                            }

                            self::redirectToPendingFirstSiteBuildKit($controller_domain, $post_install_token);

	                    }
                    
                    break;

                    }
                    self::logInstall('Installer action \''.$action.'\' completed.', SM_LOG_DEBUG);
                }catch(SmartestNotInstalledException $e){
                    self::logInstall('Installer action \''.$action.'\' returned installation status '.$e->getInstallationStatus().'.', SM_LOG_DEBUG);
                    throw $e;
                }catch(Throwable $e){
                    self::logInstall('Installer action \''.$action.'\' failed unexpectedly: '.self::describeThrowable($e), SM_LOG_ERROR);
                    throw $e;
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
                self::logInstall('Database contains '.count($tables).' table(s) before schema check.', SM_LOG_DEBUG);

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
                    self::logInstall('Installer status: database exists but no initial Smartest user account has been created yet.', SM_LOG_DEBUG);
                    
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
                
                if(self::hasPendingFirstSiteBuildKit()){
                    if(self::pendingFirstSiteBuildKitIsLocked()){
                        self::clearPendingFirstSiteBuildKit("Rejected pending first-site Build Kit work because Smartest has already recorded a completed installation.");
                    }else if(self::isPendingFirstSiteBuildKitExecutionRequest() && self::currentRequestHasValidPendingFirstSiteBuildKitToken()){
                        SmartestLog::getInstance('installer')->log('First site creation is pending and this request has a valid post-installer token. Allowing normal runtime to continue.', SM_LOG_DEBUG);
                        return;
                    }else{
                        $nie = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
                        $errors = new SmartestParameterHolder("Site creation form validator errors");
                        if(self::isPendingFirstSiteBuildKitExecutionRequest()){
                            $errors->setParameter('buildkit', "The one-time first-site setup link is missing or invalid. Please return to this installer screen and click Finish & Log In again.");
                        }else{
                            $errors->setParameter('buildkit', "Smartest has not finished creating your first site. Please check the details below and click Finish & Log In again.");
                        }
                        $nie->setValidationErrors($errors);
                        SmartestLog::getInstance('installer')->log('First site creation is pending but this request is not an authorized post-installer execution request.', SM_LOG_DEBUG);
                        throw $nie;
                    }
                }

		                if(count($db->queryToArray("SELECT site_id FROM Sites")) < 1){
		                    self::logInstall('Installer status: database exists but no site has been created yet.', SM_LOG_DEBUG);

		                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
		                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_NO_SITES, -1, true);
	                    }

	                    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_SITES);

	                }

	                if(count($db->queryToArray("SELECT page_id FROM Pages")) < 1 || count($db->queryToArray("SELECT setting_id FROM Settings")) < 1 || count($db->queryToArray("SELECT asset_id FROM Assets")) < 1){
	                    self::logInstall('Installer status: site exists but required starter Pages, Settings or Assets rows are missing.', SM_LOG_WARNING);

	                    if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
	                        SmartestCache::save('installation_status', SM_INSTALLSTATUS_SITE_DATA_INVALID, -1, true);
	                    }

	                    throw new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);

	                }

                if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
	                    SmartestCache::save('installation_status', SM_INSTALLSTATUS_COMPLETE, -1, true);
                }
                self::markInstallationComplete();
                self::logInstall('Installation marked complete.', SM_LOG_DEBUG);

	            }else{
                
                // Config files haven't been created yet
                if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
                    
                    SmartestCache::save('installation_status', SM_INSTALLSTATUS_NO_CONFIG, -1, true);
                    
                }
                
                self::logInstall('Installer status: database configuration has not been created yet.', SM_LOG_DEBUG);
                throw new SmartestNotInstalledException(SM_INSTALLSTATUS_NO_CONFIG);
            }
	    }

    public static function markInstallationComplete(){

        if(is_writable(SM_ROOT_DIR."System/Cache/Data/")){
            SmartestCache::save('installation_status', SM_INSTALLSTATUS_COMPLETE, -1, true);
        }

        self::markInstallationDatabaseComplete();

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

    protected static function hasInstallationReceipt(){
        return is_file(SM_ROOT_DIR.self::INSTALLATION_RECEIPT_FILE);
    }

    protected static function pendingFirstSiteBuildKitIsLocked(){
        return self::hasInstallationReceipt() || self::hasInstallationDatabaseReceipt();
    }

    protected static function hasInstallationDatabaseReceipt(){

        try{
            $db = SmartestDatabase::getInstance('SMARTEST', true);

            if(!in_array('Settings', $db->getTables(true))){
                return false;
            }

            $result = $db->preparedQuery(
                "SELECT setting_id FROM Settings WHERE setting_name=:setting_name AND setting_application_id=:application_id AND setting_type=:setting_type LIMIT 1",
                array(
                    'setting_name' => self::INSTALLATION_DATABASE_MARKER,
                    'application_id' => '_GLOBAL',
                    'setting_type' => 'SM_SETTINGTYPE_GLOBAL_PREFERENCE',
                )
            );

            return is_array($result) && count($result);
        }catch(Throwable $e){
            return false;
        }

    }

    protected static function markInstallationDatabaseComplete(){

        try{
            $db = SmartestDatabase::getInstance('SMARTEST', true);

            if(!in_array('Settings', $db->getTables(true))){
                return false;
            }

            $value = (string) time();
            $params = array(
                'setting_name' => self::INSTALLATION_DATABASE_MARKER,
                'application_id' => '_GLOBAL',
                'setting_type' => 'SM_SETTINGTYPE_GLOBAL_PREFERENCE',
            );

            $result = $db->preparedQuery(
                "SELECT setting_id FROM Settings WHERE setting_name=:setting_name AND setting_application_id=:application_id AND setting_type=:setting_type LIMIT 1",
                $params
            );

            if(is_array($result) && isset($result[0]['setting_id'])){
                $params['setting_id'] = (int) $result[0]['setting_id'];
                $params['setting_value'] = $value;
                $db->preparedQuery(
                    "UPDATE Settings SET setting_value=:setting_value WHERE setting_id=:setting_id LIMIT 1",
                    array(
                        'setting_value' => $params['setting_value'],
                        'setting_id' => $params['setting_id'],
                    )
                );
            }else{
                $params['setting_value'] = $value;
                $db->preparedQuery(
                    "INSERT INTO Settings (setting_site_id, setting_user_id, setting_application_id, setting_type, setting_name, setting_value) VALUES (0, 0, :application_id, :setting_type, :setting_name, :setting_value)",
                    $params
                );
            }

            return true;
        }catch(Throwable $e){
            self::logInstall('Could not write database installation marker: '.self::describeThrowable($e), SM_LOG_WARNING);
            return false;
        }

    }

    protected static function clearPendingFirstSiteBuildKit($message=''){

        $cleared = SmartestCache::clear(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(strlen((string) $message)){
            self::logInstall((string) $message, $cleared ? SM_LOG_WARNING : SM_LOG_DEBUG);
        }

        return $cleared;
    }

    protected static function savePendingFirstSiteBuildKit($site_name, $site_host, $buildkit, $prepared_params, $controller_domain=''){

        $buildkit_name = $buildkit instanceof SmartestBuildKit ? $buildkit->getShortName() : (string) $buildkit;
        $token = self::generatePendingFirstSiteBuildKitToken();

        $pending = array(
            'site_name' => SmartestStringHelper::sanitize($site_name),
            'site_host' => SmartestStringHelper::sanitize($site_host),
            'buildkit' => $buildkit_name,
            'buildkit_params' => is_array($prepared_params) ? $prepared_params : array(),
            'controller_domain' => trim((string) $controller_domain, '/'),
            'token_hash' => hash('sha256', $token),
            'created' => time(),
        );

        if(SmartestCache::save(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, $pending, -1, true)){
            self::logInstall("Queued first site creation with Build Kit '".$buildkit_name."' for post-installer normal-runtime execution.", SM_LOG_DEBUG);
            return $token;
        }

        self::logInstall('Could not queue first site creation Build Kit payload. Check write permissions for System/Cache/Data/.', SM_LOG_ERROR);
        return false;

    }

	    protected static function hasPendingFirstSiteBuildKit(){

	        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);
	        return is_array($pending) && isset($pending['buildkit']) && strlen((string) $pending['buildkit']);

    }

    protected static function firstSiteRequestMatchesPendingBuildKit($request){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(!is_array($pending) || !is_array($request)){
            return false;
        }

        foreach(array('site_name', 'site_host', 'buildkit') as $field){
            $pending_value = isset($pending[$field]) ? (string) $pending[$field] : '';
            $request_value = isset($request[$field]) ? (string) $request[$field] : '';

            if($pending_value !== $request_value){
                return false;
            }
        }

        self::logInstall("Direct first-site POST matches the saved pending Build Kit request, so partial site creation may be resumed.", SM_LOG_DEBUG);
        return true;

    }

    public static function executeFirstSiteBuildKitFromInstallerPost($redirect=true){

        $request = SmartestPersistentObject::get('installer:first_site_creation_request');

        if(!is_array($request) || !isset($request['buildkit']) || !strlen((string) $request['buildkit'])){
            throw new SmartestException('Smartest could not verify this first-site setup request. Please return to the installer and click Finish & Log In again.');
        }

        if(self::pendingFirstSiteBuildKitIsLocked()){
            throw new SmartestException('Smartest has already recorded this installation as complete, so first-site setup cannot run again.');
        }

        self::logInstall("Executing first-site Build Kit '".$request['buildkit']."' from direct installer POST.", SM_LOG_DEBUG);

        $site = self::executeFirstSiteBuildKitRequest($request, self::firstSiteRequestMatchesPendingBuildKit($request));

        SmartestCache::clear(self::FIRST_SITE_INSTALLER_TOKEN_CACHE_KEY, true);
        SmartestCache::clear(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);
        self::markInstallationComplete();

        if($redirect){
            self::redirectToInstallerLogin(isset($request['controller_domain']) ? $request['controller_domain'] : '');
        }

        return $site;

    }

    public static function executePendingFirstSiteBuildKit($token='', $redirect=true){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(!is_array($pending) || !isset($pending['buildkit']) || !strlen((string) $pending['buildkit'])){
            return false;
        }

        if(self::pendingFirstSiteBuildKitIsLocked()){
            self::clearPendingFirstSiteBuildKit("Rejected pending first-site Build Kit work because Smartest has already recorded a completed installation.");
            return false;
        }

        if(!self::pendingFirstSiteBuildKitTokenMatches($pending, $token)){
            self::logInstall("A pending first-site Build Kit execution request was rejected because the token was missing or invalid.", SM_LOG_WARNING);
            throw new SmartestException('The one-time first-site Build Kit token was missing or invalid.');
        }

        self::logInstall("Pending first-site Build Kit '".$pending['buildkit']."' found; executing in the normal runtime.", SM_LOG_DEBUG);

        try{
            $site = self::executeFirstSiteBuildKitRequest($pending, true);

            SmartestCache::clear(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);
            SmartestCache::clear(self::FIRST_SITE_INSTALLER_TOKEN_CACHE_KEY, true);
            self::markInstallationComplete();
            self::logInstall("Created first site '".$site->getName()."' with pending Build Kit request.", SM_LOG_DEBUG);
        }catch(SmartestBuildKitException $buildkit_error){
            self::recordPendingFirstSiteBuildKitFailure($buildkit_error);
            self::logInstall("Pending first-site Build Kit failed: ".$buildkit_error->getMessage(), SM_LOG_ERROR);
            throw $buildkit_error;
        }catch(Throwable $buildkit_error){
            $detail = self::describeThrowable($buildkit_error);
            self::recordPendingFirstSiteBuildKitFailure($buildkit_error);
            self::logInstall("Pending first-site Build Kit failed: ".$detail, SM_LOG_ERROR);
            throw new SmartestException("Pending first-site Build Kit failed: ".$detail);
        }

        if($redirect){
            self::redirectToInstallerLogin(isset($pending['controller_domain']) ? $pending['controller_domain'] : '');
        }

        return $site;

    }

    protected static function executeFirstSiteBuildKitRequest($request, $allow_resume=false){

        $db = SmartestDatabase::getInstance('SMARTEST');

        self::includeFirstSiteBuildKitRuntimeClasses();

        $buildkit = SmartestBuildKitUtilities::getBuildKitIfInstalled($request['buildkit']);

        if(!$buildkit instanceof SmartestBuildKit){
            throw new SmartestException("First-site Build Kit '".$request['buildkit']."' could not be found.");
        }

        $user = new SmartestSystemUser;

        if(!$user->find(1)){
            throw new SmartestException('The first user account could not be loaded, so the pending first-site Build Kit could not be executed.');
        }

        $uq = $db->preparedQuery('SELECT user_email FROM Users WHERE user_id=:user_id LIMIT 1', array('user_id' => 1));
        $email = isset($uq[0]['user_email']) ? $uq[0]['user_email'] : '';
        $sitename = isset($request['site_name']) ? $request['site_name'] : '';
        $hostname = isset($request['site_host']) ? $request['site_host'] : '';

        $site_params = new SmartestParameterHolder('Pending first site creation parameters');
        $site_params->setParameter('site_name', $sitename);
        $site_params->setParameter('site_internal_label', $sitename);
        $site_params->setParameter('site_domain', $hostname);
        $site_params->setParameter('site_admin', $email);
        $site_params->setParameter('site_master_template', '_DEFAULT');

        $previous_buildkit_log = isset($GLOBALS['_buildkit_execution_log']) ? $GLOBALS['_buildkit_execution_log'] : null;
        $previous_site_creation_log = isset($GLOBALS['_site_creation_log']) ? $GLOBALS['_site_creation_log'] : null;
        $GLOBALS['_buildkit_execution_log'] = 'installer';
        $GLOBALS['_site_creation_log'] = 'installer';

        try{
            $sch = new SmartestSiteCreationHelper;
            $existing_site = $allow_resume ? self::getPendingFirstSiteIfAlreadyCreated($db, $request) : null;

            if(!$allow_resume && count($db->preparedQuery('SELECT site_id FROM Sites LIMIT 1'))){
                throw new SmartestException('A site already exists, so the installer cannot create a first site.');
            }

            if($existing_site instanceof SmartestSite){
                self::logInstall("Resuming pending first-site Build Kit '".$buildkit->getLabel()."' on existing site '".$existing_site->getName()."'.", SM_LOG_WARNING);
                $site = $sch->completeExistingSiteFromBuildKit($existing_site, $user, $buildkit, isset($request['buildkit_params']) ? $request['buildkit_params'] : array());
            }else{
                $site = $sch->createNewSiteFromBuildKit($site_params, $user, $buildkit, isset($request['buildkit_params']) ? $request['buildkit_params'] : array());
            }
        }catch(SmartestBuildKitException $buildkit_error){
            throw $buildkit_error;
        }catch(Throwable $buildkit_error){
            throw new SmartestException("First-site Build Kit '".$buildkit->getLabel()."' failed: ".self::describeThrowable($buildkit_error));
        }finally{
            if($previous_buildkit_log === null){
                unset($GLOBALS['_buildkit_execution_log']);
            }else{
                $GLOBALS['_buildkit_execution_log'] = $previous_buildkit_log;
            }

            if($previous_site_creation_log === null){
                unset($GLOBALS['_site_creation_log']);
            }else{
                $GLOBALS['_site_creation_log'] = $previous_site_creation_log;
            }
        }

        return $site;

    }

    public static function recordPendingFirstSiteBuildKitFailure(Throwable $e){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(is_array($pending)){
            $pending['last_error'] = self::describeThrowable($e);
            $pending['last_error_time'] = time();
            SmartestCache::save(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, $pending, -1, true);
        }

    }

    public static function getPendingFirstSiteBuildKitFormDefaults(){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(!is_array($pending)){
            return array();
        }

        return array(
            'site_name' => isset($pending['site_name']) ? (string) $pending['site_name'] : '',
            'site_host' => isset($pending['site_host']) ? (string) $pending['site_host'] : '',
            'use_buildkit' => isset($pending['buildkit']) ? (string) $pending['buildkit'] : '',
        );

    }

    public static function showPendingFirstSiteBuildKitFailure(Throwable $buildkit_error){

        self::recordPendingFirstSiteBuildKitFailure($buildkit_error);

        $errors = new SmartestParameterHolder("Site creation form validator errors");
        $errors->setParameter('buildkit', "The selected Build Kit could not finish creating your first site. Please fix the problem and click Finish & Log In again. Technical detail: ".get_class($buildkit_error).': '.$buildkit_error->getMessage());

        $e = new SmartestNotInstalledException(SM_INSTALLSTATUS_SITE_DATA_INVALID);
        $e->setValidationErrors($errors);

        if(!class_exists('SmartestInstaller')){
            require SM_ROOT_DIR.'System/Install/SmartestInstaller.class.php';
        }

        require SM_ROOT_DIR.'System/Install/Screens/index.php';
        exit;

    }

    public static function getPendingFirstSiteBuildKitExecutionUrl($controller_domain, $token){

        return self::getFirstSiteBuildKitExecutionPath($controller_domain).'?token='.rawurlencode($token);

    }

    public static function getFirstSiteBuildKitExecutionPath($controller_domain=''){

        $controller_domain = self::normalizeControllerDomain($controller_domain);
        $prefix = strlen($controller_domain) ? '/'.$controller_domain : '';

        return $prefix.'/smartest/settings/buildFirstSitePostInstaller';

    }

    public static function getCachedControllerDomain(){

        return self::normalizeControllerDomain(SmartestCache::load('controller_domain_temp', true));

    }

    public static function getFirstSiteInstallerToken(){

        $token = self::generatePendingFirstSiteBuildKitToken();

        SmartestCache::save(self::FIRST_SITE_INSTALLER_TOKEN_CACHE_KEY, array(
            'token_hash' => hash('sha256', $token),
            'created' => time(),
        ), -1, true);

        return $token;

    }

    protected static function firstSiteInstallerTokenMatches($token){

        $stored = SmartestCache::load(self::FIRST_SITE_INSTALLER_TOKEN_CACHE_KEY, true);

        if(!is_array($stored) || !isset($stored['token_hash']) || !strlen((string) $stored['token_hash'])){
            return false;
        }

        if(!is_scalar($token) || !strlen((string) $token)){
            return false;
        }

        $candidate = hash('sha256', (string) $token);

        if(function_exists('hash_equals')){
            return hash_equals((string) $stored['token_hash'], $candidate);
        }

        return (string) $stored['token_hash'] === $candidate;

    }

    protected static function includeFirstSiteBuildKitRuntimeClasses(){

        if(!class_exists('SmartestBuildKitUtilities') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKitUtilities.class.php';
        }

        if(!class_exists('SmartestBuildKit') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestBuildKits.helper/SmartestBuildKit.class.php';
        }

        if(!class_exists('SmartestSiteCreationHelper') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestSiteCreation.helper/SmartestSiteCreationHelper.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestSiteCreation.helper/SmartestSiteCreationHelper.class.php';
        }

    }

    protected static function redirectToPendingFirstSiteBuildKit($controller_domain, $token){

        $location = self::getAbsoluteInstallerUrl(self::getPendingFirstSiteBuildKitExecutionUrl($controller_domain, $token));
        self::logInstall("Redirecting to one-time first-site Build Kit execution URL: ".$location, SM_LOG_DEBUG);

        if(headers_sent($file, $line)){
            throw new SmartestException('Smartest could not continue to first-site creation because HTTP headers had already been sent at '.$file.':'.$line.'.');
        }

        header("Location: ".$location, true, 303);
        exit;

    }

    protected static function redirectToInstallerLogin($controller_domain=''){

        $controller_domain = self::normalizeControllerDomain($controller_domain);
        $location = self::getAbsoluteInstallerUrl((strlen($controller_domain) ? '/'.$controller_domain : '').'/smartest/login#welcome');
        self::logInstall("Redirecting to login after first-site creation: ".$location, SM_LOG_DEBUG);

        if(headers_sent($file, $line)){
            throw new SmartestException('Smartest could not redirect to the login screen because HTTP headers had already been sent at '.$file.':'.$line.'.');
        }

        header("Location: ".$location, true, 303);
        exit;

    }

    protected static function getAbsoluteInstallerUrl($path){

        if(preg_match('#^https?://#i', $path)){
            return $path;
        }

        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && strtolower((string) $_SERVER['HTTPS']) != 'off';

        if(!$https && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){
            $https = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https';
        }

        $scheme = $https ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) && strlen((string) $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return $scheme.$host.'/'.ltrim($path, '/');

    }

    protected static function normalizeControllerDomain($controller_domain){

        $controller_domain = preg_replace('#/+#', '/', str_replace('\\', '/', trim((string) $controller_domain)));
        return trim($controller_domain, '/');

    }

    protected static function isPendingFirstSiteBuildKitExecutionRequest(){

        $path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
        $path = is_string($path) ? trim($path, '/') : '';

        return (bool) preg_match('#(^|/)smartest/settings/buildFirstSitePostInstaller/?$#', $path);

    }

    protected static function currentRequestHasValidPendingFirstSiteBuildKitToken(){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(!is_array($pending)){
            return false;
        }

        $token = '';

        if(isset($_GET['token'])){
            $token = $_GET['token'];
        }else if(isset($_POST['token'])){
            $token = $_POST['token'];
        }

        return self::pendingFirstSiteBuildKitTokenMatches($pending, $token);

    }

    protected static function pendingFirstSiteBuildKitTokenMatches($pending, $token){

        if(!is_array($pending) || !isset($pending['token_hash']) || !strlen((string) $pending['token_hash'])){
            return false;
        }

        if(!is_scalar($token) || !strlen((string) $token)){
            return false;
        }

        $candidate = hash('sha256', (string) $token);

        if(function_exists('hash_equals')){
            return hash_equals((string) $pending['token_hash'], $candidate);
        }

        return (string) $pending['token_hash'] === $candidate;

    }

    protected static function generatePendingFirstSiteBuildKitToken(){

        if(function_exists('random_bytes')){
            try{
                return bin2hex(random_bytes(32));
            }catch(Throwable $e){
                self::logInstall('Could not use random_bytes() for post-installer token generation; falling back to SmartestStringHelper::random().', SM_LOG_WARNING);
            }
        }

        return SmartestStringHelper::random(64, SM_RANDOM_ALPHANUMERIC);

    }

    protected static function getPendingFirstSiteBuildKitFailureMessage(){

        $pending = SmartestCache::load(self::PENDING_FIRST_SITE_BUILDKIT_CACHE_KEY, true);

        if(is_array($pending) && isset($pending['last_error']) && strlen((string) $pending['last_error'])){
            return "The queued first-site Build Kit failed: ".$pending['last_error'];
        }

        return '';

    }

    protected static function getPendingFirstSiteIfAlreadyCreated($db, $pending){

        if(!is_object($db) || !method_exists($db, 'queryToArray')){
            throw new SmartestException('A first-site Build Kit is pending, but the database adapter could not be used to check whether the site was already created.');
        }

        $rows = $db->queryToArray('SELECT site_id, site_name, site_domain FROM Sites ORDER BY site_id ASC');

        if(!count($rows)){
            return null;
        }

        $pending_name = isset($pending['site_name']) ? (string) $pending['site_name'] : '';
        $pending_host = isset($pending['site_host']) ? (string) $pending['site_host'] : '';
        $matched_id = null;

        if(!strlen($pending_name) || !strlen($pending_host)){
            throw new SmartestException('A first-site Build Kit is pending, but the saved site name or hostname is missing, so Smartest cannot safely resume installation.');
        }

        foreach($rows as $row){
            $name_matches = isset($row['site_name']) && $row['site_name'] == $pending_name;
            $host_matches = isset($row['site_domain']) && $row['site_domain'] == $pending_host;

            if($name_matches && $host_matches){
                $matched_id = $row['site_id'];
                break;
            }
        }

        if($matched_id !== null){
            $site = new SmartestSite;

            if($site->find($matched_id)){
                return $site;
            }
        }

        throw new SmartestException('A first-site Build Kit is pending, but an existing site could not be matched safely for resuming installation.');

    }

    protected static function installationLooksComplete(){

        if(!is_file(SM_ROOT_DIR.'Public/.htaccess') || !(is_file(SM_ROOT_DIR.'Configuration/database.ini') || is_file(SM_ROOT_DIR.'Configuration/database.yml'))){
            return false;
        }

        try{
            $db = SmartestDatabase::getInstance('SMARTEST', true);
            $tables = $db->getTables(true);
        }catch(Throwable $e){
            return false;
        }

        foreach(array('Users', 'Sites', 'Pages', 'Settings', 'Assets') as $required_table){
            if(!in_array($required_table, $tables)){
                return false;
            }
        }

        try{
            $complete = count($db->queryToArray("SELECT user_id FROM Users LIMIT 2")) > 1
                && count($db->queryToArray("SELECT site_id FROM Sites LIMIT 1")) > 0
                && count($db->queryToArray("SELECT page_id FROM Pages LIMIT 1")) > 0
                && count($db->queryToArray("SELECT setting_id FROM Settings LIMIT 1")) > 0
                && count($db->queryToArray("SELECT asset_id FROM Assets LIMIT 1")) > 0;

            if($complete && self::hasPendingFirstSiteBuildKit()){
                if(self::pendingFirstSiteBuildKitIsLocked()){
                    self::clearPendingFirstSiteBuildKit("Cleared stale pending first-site Build Kit work because Smartest is already installed.");
                }else{
                    return false;
                }
            }

            return $complete;
        }catch(Throwable $e){
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

    protected static function logInstall($message, $level=SM_LOG_DEBUG){
        SmartestLog::getInstance('installer')->log($message, $level);
    }

    protected static function describeThrowable(Throwable $e){
        return get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine();
    }

    protected static function getRequestContextForLog(){
        $parts = array();

        if(isset($_SERVER['REQUEST_METHOD'])){
            $parts[] = 'method='.$_SERVER['REQUEST_METHOD'];
        }

        if(isset($_SERVER['REQUEST_URI'])){
            $parts[] = 'uri='.$_SERVER['REQUEST_URI'];
        }

        if(count($parts)){
            return ' ['.implode(' ', $parts).']';
        }

        return '';
    }
}
