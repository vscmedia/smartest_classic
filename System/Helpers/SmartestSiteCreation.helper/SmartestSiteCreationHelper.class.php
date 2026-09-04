<?php

class SmartestSiteCreationHelper{

    public function createNewSite(SmartestParameterHolder $p, $initial_user='', $build_kit_name='', $prepared_params=''){

        if($initial_user instanceof SmartestUser){
            $u = $initial_user;
        }else if(SmartestSession::get('user') instanceof SmartestUser){
            $u = SmartestSession::get('user');
        }else{
            SmartestLog::getInstance('system')->log("Could not create new site without valid user. None given.", SM_LOG_ERROR);
            throw new SmartestException("Tried to create site without logged in user or valid user object");
        }

        $use_buildkit = false;
        $buildkit = null;
        if($build_kit_name === '_NONE'){
            $build_kit_name = SmartestBuildKitUtilities::getDefaultInstallerBuildKitShortName();
        }

        if($build_kit_name instanceof SmartestBuildKit){
            $buildkit = $build_kit_name;

            if($buildkit instanceof SmartestBuildKit && $buildkit->isValid()){
                if(is_array($prepared_params)){
                    $use_buildkit = true;
                }else{
                    SmartestLog::getInstance('system')->log("Build Kit parameters not supplied for requested Build Kit '".$buildkit->getShortName()."'.", SM_LOG_ERROR);
                    throw new SmartestException("Build Kit parameters not supplied.");
                }
            }else{
                SmartestLog::getInstance('system')->log("Invalid Build Kit supplied.", SM_LOG_ERROR);
                throw new SmartestException("Invalid Build Kit supplied.");
            }
        }else if(strlen((string) $build_kit_name)){
            $buildkit = SmartestBuildKitUtilities::getBuildKitIfInstalled($build_kit_name);

            if($buildkit instanceof SmartestBuildKit && $buildkit->isValid()){
                if(is_array($prepared_params)){
                    $use_buildkit = true;
                }else{
                    SmartestLog::getInstance('system')->log("Build Kit parameters not supplied for requested Build Kit '".$build_kit_name."'.", SM_LOG_ERROR);
                    throw new SmartestException("Build Kit parameters not supplied.");
                }
            }else{
                SmartestLog::getInstance('system')->log("Unknown Build Kit requested: ".$build_kit_name, SM_LOG_ERROR);
                throw new SmartestException("Unknown Build Kit requested: ".$build_kit_name);
            }
        }

        $ph = new SmartestPreferencesHelper;

        if(!SmartestPersistentObject::get('prefs_helper')){
            SmartestPersistentObject::set('prefs_helper', $ph);
        }

        $is_first_site = !count(SmartestDatabase::getInstance('SMARTEST')->preparedQuery('SELECT site_id FROM Sites LIMIT 1'));

        $site = new SmartestSite;
        $site->setName($p->getParameter('site_name'));
        $site->setInternalLabel($p->getParameter('site_internal_label', $p->getParameter('site_name')));
        if($use_buildkit && strlen((string) $buildkit->getTitleFormat())){
            $site->setTitleFormat($buildkit->getTitleFormat());
        }else{
            $site->setTitleFormat('$page | $section | $site');
        }
        $site->setDomain($p->getParameter('site_domain'));
        $site->setAdminEmail($p->getParameter('site_admin'));
        $site->setAutomaticUrls('OFF');
        $site->save();
	    $site->getUniqueId();
	    self::logSiteCreation("Created site record #".$site->getId()." '".$site->getName()."' on domain '".$site->getDomain()."'.");

        if($use_buildkit){
            self::logSiteCreation("Completing site #".$site->getId()." with Build Kit '".$buildkit->getLabel()."'.");
            return self::completeSiteWithBuildKit($site, $u, $buildkit, $prepared_params, $is_first_site);
        }

        self::ensureCreatorSitePermissions($u, $site, $is_first_site);
        self::ensureDefaultSystemAssets($u, $site);
	    SmartestLog::getInstance('system')->log("User {$u->__toString()} created a new site record: '{$site->getName()}/{$site->getDomain()}'", SM_LOG_DEBUG);

        $ph->setGlobalPreference('enable_site_responsive_mode', '1', '0', $site->getId());
        $ph->setGlobalPreference('site_responsive_distinguish_mobile', '1', '0', $site->getId());
        $ph->setGlobalPreference('site_responsive_distinguish_tablet', '1', '0', $site->getId());
        $ph->setGlobalPreference('site_responsive_distinguish_oldpcs', '0', '0', $site->getId());

        if($p->getParameter('site_master_template') == '_DEFAULT'){
	        $master_template = '';
	    }else if($p->getParameter('site_master_template') == '_BLANK'){

            $colours = array(
                "background: rgb(85,3,131);
    background: linear-gradient(60deg, rgba(85,3,131,1) 0%, rgba(11,1,57,1) 29%, rgba(22,96,145,1) 62%, rgba(30,246,235,1) 100%);",
                "background: rgb(131,58,180);
    background: linear-gradient(45deg, rgba(131,58,180,1) 0%, rgba(253,29,29,1) 50%, rgba(252,176,69,1) 100%);",
                "background: rgb(5,3,42);
    background: linear-gradient(45deg, rgba(5,3,42,1) 0%, rgba(107,1,100,1) 26%, rgba(222,0,122,1) 68%, rgba(255,177,0,1) 100%);",
                "background: rgb(106,169,252);
    background: linear-gradient(135deg, rgba(106,169,252,1) 0%, rgba(227,68,205,1) 34%, rgba(245,78,78,1) 70%, rgba(247,164,35,1) 98%);"
            );

            $index = rand(0, 3);
            $colour = $colours[$index];

            $ach = new SmartestAssetCreationHelper('SM_ASSETTYPE_STYLESHEET');
            $intended_file_name = SmartestStringHelper::toVarName($p->getParameter('site_name')).'.css';
            $intended_file_path = SM_ROOT_DIR.'Public/Resources/Stylesheets/'.$intended_file_name;
            $css = SmartestFileSystemHelper::load(SM_ROOT_DIR.'System/Install/Samples/default.css');
            $css = str_replace('%TIME%', date('r'), $css);
            $css = str_replace('%COLOUR%', $colour, $css);
            $actual_file_path = SmartestFileSystemHelper::getUniqueFileName($intended_file_path);
            $actual_file_name = SmartestFileSystemHelper::baseName($actual_file_path);

            if(SmartestFileSystemHelper::save($actual_file_path, $css)){
                $css_success = true;
                $ach->createNewAssetFromUnImportedFile($actual_file_name, 'Main CSS file for '.$p->getParameter('site_name'));
                $npg = new SmartestPageGroup;
    	        $npg->setName('main_nav');
    	        $npg->setLabel('Main Navigation');
    	        $npg->setSiteId($site->getId());
    	        $npg->save();
            }else{
                $css_success = false;
            }

	        $master_template_name = SmartestFileSystemHelper::getFileName(SmartestFileSystemHelper::getUniqueFileName(SM_ROOT_DIR.'Presentation/Masters/'.SmartestStringHelper::toVarName($site->getName()).'.tpl'));
	        $master_template_contents = str_replace('%DEFAULTTEMPLATENAME%.tpl', $master_template_name, file_get_contents(SM_ROOT_DIR.'System/Install/Samples/default.tpl'));

            if($css_success){
	            $master_template_contents = str_replace('%CSSLINK%', '<?sm:stylesheet file="'.$actual_file_name.'":?>'."\n", $master_template_contents);
	        }else{
	            $master_template_contents = str_replace('%CSSLINK%', '', $master_template_contents);
	        }

	        if(file_put_contents(SM_ROOT_DIR.'Presentation/Masters/'.$master_template_name, $master_template_contents)){

	            $master_template = $master_template_name;

	            // Add the template to to the templates database
	            $t = new SmartestTemplateAsset;
	            $t->setUserId($u->getId());
	            $t->setSiteId($site->getId());
	            $t->setStringId(SmartestFileSystemHelper::removeDotSuffix($master_template_name));
	            $t->setUrl($master_template_name);
	            $t->setCreated(time());
	            $t->setWebId(SmartestStringHelper::random(32));
	            $t->setType('SM_ASSETTYPE_MASTER_TEMPLATE');
	            $t->save();

        		$container = new SmartestContainer;

        		if($container->exists('page_layout')){
        	        $site->setPrimaryContainerId($container->getId());
        	    }

	        }else{
	            $master_template = '';
	            SmartestLog::getInstance('system')->log("Could not create ".SM_ROOT_DIR.'Presentation/Masters/'.$master_template_name.": Permission denied", SM_LOG_WARNING);
	        }

	    }else{
	        if(is_file(SM_ROOT_DIR.'Presentation/Masters/'.$p->getParameter('site_master_template'))){
	            $master_template = $p->getParameter('site_master_template');
	        }else{
	            $master_template = '';
	            SmartestLog::getInstance('system')->log("Could not set ".SM_ROOT_DIR.'Presentation/Masters/'.$p->getParameter('site_master_template')." as master template for new site: File does not exist", SM_LOG_WARNING);
	        }
	    }

	    $home_page = new SmartestPage;
	    $home_page->setTitle('Home');
	    $home_page->setName('home');
	    $home_page->setDraftTemplate($master_template);
	    $home_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $home_page->setSiteId($site->getId());
	    $home_page->setCreatedbyUserid($u->getId());
	    $home_page->setOrderIndex(0);
	    $home_page->save();
	    $home_page->addAuthorById($u->getId());
	    $site->setTopPageId($home_page->getId());
        if(isset($npg) && $npg instanceof SmartestPageGroup){
            $npg->addPageById($home_page->getId(), false);
        }
	    SmartestLog::getInstance('system')->log("Created home page for new site (page ID {$home_page->getId()})", SM_LOG_DEBUG);

	    $error_page = new SmartestPage;
	    $error_page->setTitle('Page not found');
	    $error_page->setName('error-404');
	    $error_page->setSiteId($site->getId());
	    $error_page->setDraftTemplate($master_template);
	    $error_page->setLiveTemplate($master_template);
	    $error_page->setParent($home_page->getId());
	    $error_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $error_page->setCreatedbyUserid($u->getId());
	    $error_page->setOrderIndex(1024);
	    $error_page->setIsPublished('TRUE');
        $error_page->setMetaDescription('The page you requested could not be found.');
	    $error_page->save();
	    $site->setErrorPageId($error_page->getId());
	    SmartestLog::getInstance('system')->log("Created and connected 404 page to new site (page ID {$error_page->getId()})", SM_LOG_DEBUG);

        $search_page = new SmartestPage;
	    $search_page->setTitle('Search Results');
	    $search_page->setName('search');
	    $search_page->setSiteId($site->getId());
	    $search_page->setDraftTemplate($master_template);
	    $search_page->setLiveTemplate($master_template);
	    $search_page->setParent($home_page->getId());
	    $search_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $search_page->setCreatedbyUserid($u->getId());
	    $search_page->setOrderIndex(1022);
	    $search_page->save();
	    $site->setSearchPageId($search_page->getId());
	    SmartestLog::getInstance('system')->log("Created and connected search page to new site (page ID {$search_page->getId()})", SM_LOG_DEBUG);

	    $tag_page = new SmartestPage;
	    $tag_page->setTitle('Tagged Content');
	    $tag_page->setName('tag');
	    $tag_page->setSiteId($site->getId());
	    $tag_page->setDraftTemplate($master_template);
	    $tag_page->setLiveTemplate($master_template);
	    $tag_page->setParent($home_page->getId());
	    $tag_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $tag_page->setCreatedbyUserid($u->getId());
	    $tag_page->setOrderIndex(1023);
	    $tag_page->save();
	    $site->setTagPageId($tag_page->getId());
	    SmartestLog::getInstance('system')->log("Created and connected tag page to new site (page ID {$tag_page->getId()})", SM_LOG_DEBUG);

	    $user_page = new SmartestPage;
	    $user_page->setTitle('User Profile');
	    $user_page->setName('user');
	    $user_page->setSiteId($site->getId());
	    $user_page->setDraftTemplate($master_template);
	    $user_page->setLiveTemplate($master_template);
	    $user_page->setParent($home_page->getId());
	    $user_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $user_page->setCreatedbyUserid($u->getId());
	    $user_page->setOrderIndex(1020);
	    $user_page->save();
	    $site->setUserPageId($user_page->getId());
	    SmartestLog::getInstance('system')->log("Created and connected user page to new site (page ID {$user_page->getId()})", SM_LOG_DEBUG);

	    $holding_page = new SmartestPage;
	    $holding_page->setTitle('Holding page');
	    $holding_page->setName('error-503');
	    $holding_page->setSiteId($site->getId());
	    $holding_page->setDraftTemplate($master_template);
	    $holding_page->setLiveTemplate($master_template);
	    $holding_page->setParent($home_page->getId());
	    $holding_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	    $holding_page->setCreatedbyUserid($u->getId());
	    $holding_page->setOrderIndex(1019);
	    $holding_page->save();
	    $site->setHoldingPageId($holding_page->getId());
	    SmartestLog::getInstance('system')->log("Created and connected holding page to new site (page ID {$holding_page->getId()})", SM_LOG_DEBUG);

	    $site->save();

	    self::createSiteDirectory($site);

		return $site;

	    }

        public function createNewSiteFromBuildKit(SmartestParameterHolder $p, $initial_user, $buildkit, $prepared_params=array()){
            return $this->createNewSite($p, $initial_user, $buildkit, $prepared_params);
        }

        public function completeExistingSiteFromBuildKit(SmartestSite $site, $initial_user, SmartestBuildKit $buildkit, $prepared_params=array()){

            if(!$initial_user instanceof SmartestUser){
                if(SmartestSession::get('user') instanceof SmartestUser){
                    $initial_user = SmartestSession::get('user');
                }else{
                    throw new SmartestException("Tried to complete site Build Kit without logged in user or valid user object");
                }
            }

            if(!is_array($prepared_params)){
                $prepared_params = array();
            }

            return self::completeSiteWithBuildKit($site, $initial_user, $buildkit, $prepared_params, self::siteIsOnlySite($site));

        }

        protected static function completeSiteWithBuildKit(SmartestSite $site, SmartestUser $user, SmartestBuildKit $buildkit, array $prepared_params, $is_first_site=false){

            self::logSiteCreation("Build Kit site completion starting for site #".$site->getId()." using '".$buildkit->getLabel()."'.");

            try{
                $ph = new SmartestPreferencesHelper;

                if(!SmartestPersistentObject::get('prefs_helper')){
                    SmartestPersistentObject::set('prefs_helper', $ph);
                }

                self::logSiteCreation("Ensuring creator permissions for user #".$user->getId()." on site #".$site->getId().'.');
                self::ensureCreatorSitePermissions($user, $site, $is_first_site);
                self::logSiteCreation("Ensuring default system assets for site #".$site->getId().'.');
                self::ensureDefaultSystemAssets($user, $site);
                SmartestLog::getInstance('system')->log("User {$user->__toString()} created or resumed a site record: '{$site->getName()}/{$site->getDomain()}'", SM_LOG_DEBUG);

                $ph->setGlobalPreference('enable_site_responsive_mode', '1', '0', $site->getId());
                $ph->setGlobalPreference('site_responsive_distinguish_mobile', '1', '0', $site->getId());
                $ph->setGlobalPreference('site_responsive_distinguish_tablet', '1', '0', $site->getId());
                $ph->setGlobalPreference('site_responsive_distinguish_oldpcs', '0', '0', $site->getId());

                if($buildkit->getResponsiveModeEnabled()){
                    $responsive_options = $buildkit->getResponsiveModeOptions();
                    $ph->setGlobalPreference('enable_site_responsive_mode', '1', '0', $site->getId());
                    $ph->setGlobalPreference('site_responsive_distinguish_mobile', (int) $responsive_options['mobiles'], '0', $site->getId());
                    $ph->setGlobalPreference('site_responsive_distinguish_tablet', (int) $responsive_options['tablets'], '0', $site->getId());
                    $ph->setGlobalPreference('site_responsive_distinguish_oldpcs', (int) $responsive_options['oldpcs'], '0', $site->getId());
                }

                $ph->setGlobalPreference('enable_eu_cookie_compliance', $buildkit->getEUCookieModeEnabled() ? '1' : '0', '0', $site->getId());

                self::logSiteCreation("Ensuring standard pages for site #".$site->getId().'.');
                self::ensureStandardPagesLayout($site, '', $user);
                self::logSiteCreation("Ensuring site directory for site #".$site->getId().'.');
                self::createSiteDirectory($site);

                self::logSiteCreation("Handing site #".$site->getId()." to Build Kit '".$buildkit->getLabel()."'.");
                $site = $buildkit->execute($site, $user, $prepared_params);

                SmartestLog::getInstance('system')->log("Executed Build Kit '".$buildkit->getLabel()."' for site '".$site->getName()."'.", SM_LOG_DEBUG);
                self::logSiteCreation("Build Kit site completion finished for site #".$site->getId().'.');

                return $site;

            }catch(SmartestBuildKitException $e){
                self::logSiteCreation("Build Kit site completion failed for site #".$site->getId().": ".$e->getMessage(), SM_LOG_ERROR);
                throw $e;
            }catch(Throwable $e){
                self::logSiteCreation("Build Kit site completion failed for site #".$site->getId().": ".self::describeThrowable($e), SM_LOG_ERROR);
                throw SmartestBuildKitException::fromThrowable("Completing site '".$site->getName()."' with Build Kit '".$buildkit->getLabel()."' failed", $e, $buildkit->getShortName());
            }

        }

        protected static function siteIsOnlySite(SmartestSite $site){

            try{
                return !count(SmartestDatabase::getInstance('SMARTEST')->preparedQuery('SELECT site_id FROM Sites WHERE site_id != :site_id LIMIT 1', array('site_id' => $site->getId())));
            }catch(Exception $e){
                return false;
            }

        }

        protected static function ensureCreatorSitePermissions($user, SmartestSite $site, $is_first_site=false){

            if(!$user instanceof SmartestSystemUser){
                SmartestLog::getInstance('system')->log("Could not grant creator site permissions because user object was not a system user.", SM_LOG_WARNING);
                return false;
            }

            if($is_first_site){
                foreach(array('root_permission', 'site_access', 'modify_user_permissions', 'modify_user_own_permissions') as $token){
                    if(!$user->hasGlobalPermission($token)){
                        $user->addToken($token, 'GLOBAL');
                    }
                }
                SmartestLog::getInstance('system')->log("Granted first-site global root and site access permissions to user {$user->getId()}.", SM_LOG_DEBUG);
            }else{
                if(!$user->hasGlobalPermission('site_access')){
                    $user->addToken('site_access', $site->getId());
                }

                if(!$user->hasGlobalPermission('modify_user_permissions')){
                    $user->addToken('modify_user_permissions', $site->getId());
                }
            }

            return true;

        }

	    protected static function ensureStandardPagesLayout(SmartestSite $site, $master_template='', $initial_user=''){

	        $home_page = self::loadPageById($site->getTopPageId());

	        if(!$home_page instanceof SmartestPage){
	            $home_page = self::findSitePageByName($site, 'home');
	        }

	        if(!$home_page instanceof SmartestPage){
	            return self::createStandardPagesLayout($site, $master_template, $initial_user);
	        }

	        if($site->getTopPageId() != $home_page->getId()){
	            $site->setTopPageId($home_page->getId());
	            $site->save();
	            SmartestLog::getInstance('system')->log("Reconnected existing home page {$home_page->getId()} as top page for site {$site->getId()}.", SM_LOG_WARNING);
	        }

	        self::ensureMissingSpecialPages($site, $home_page, $master_template, $initial_user);

	        return $home_page;

	    }

	    protected static function ensureMissingSpecialPages(SmartestSite $site, SmartestPage $home_page, $master_template='', $initial_user=''){

	        if($initial_user instanceof SmartestUser){
	            $u = $initial_user;
	        }else if(SmartestSession::get('user') instanceof SmartestUser){
	            $u = SmartestSession::get('user');
	        }else{
	            $u = null;
	        }

	        $user_id = $u instanceof SmartestUser ? $u->getId() : 0;
	        $special_pages = array(
	            array('getter' => 'getErrorPageId', 'setter' => 'setErrorPageId', 'title' => 'Page not found', 'name' => 'error-404', 'order' => 1024, 'published' => true, 'meta' => 'The page you requested could not be found.'),
	            array('getter' => 'getSearchPageId', 'setter' => 'setSearchPageId', 'title' => 'Search Results', 'name' => 'search', 'order' => 1022),
	            array('getter' => 'getTagPageId', 'setter' => 'setTagPageId', 'title' => 'Tagged Content', 'name' => 'tag', 'order' => 1023),
	            array('getter' => 'getUserPageId', 'setter' => 'setUserPageId', 'title' => 'User Profile', 'name' => 'user', 'order' => 1020),
	            array('getter' => 'getHoldingPageId', 'setter' => 'setHoldingPageId', 'title' => 'Holding page', 'name' => 'error-503', 'order' => 1019),
	        );

	        foreach($special_pages as $definition){

	            $page = self::loadPageById($site->{$definition['getter']}());

	            if(!$page instanceof SmartestPage){
	                $page = self::findSitePageByName($site, $definition['name']);
	            }

	            if(!$page instanceof SmartestPage){
	                $page = new SmartestPage;
	                $page->setTitle($definition['title']);
	                $page->setName($definition['name']);
	                $page->setSiteId($site->getId());
	                $page->setDraftTemplate($master_template);
	                $page->setLiveTemplate($master_template);
	                $page->setParent($home_page->getId());
	                $page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
	                $page->setCreatedbyUserid($user_id);
	                $page->setOrderIndex($definition['order']);

	                if(isset($definition['published']) && $definition['published']){
	                    $page->setIsPublished('TRUE');
	                }

	                if(isset($definition['meta'])){
	                    $page->setMetaDescription($definition['meta']);
	                }

	                $page->save();
	                SmartestLog::getInstance('system')->log("Created missing special page '{$definition['name']}' for site {$site->getId()} (page ID {$page->getId()}).", SM_LOG_WARNING);
	            }

	            $site->{$definition['setter']}($page->getId());

	        }

	        $site->save();

	    }

	    protected static function loadPageById($page_id){

	        $page_id = (int) $page_id;

	        if($page_id){
	            $page = new SmartestPage;

	            if($page->find($page_id)){
	                return $page;
	            }
	        }

	        return null;

	    }

	    protected static function findSitePageByName(SmartestSite $site, $name){

	        try{
	            $rows = SmartestDatabase::getInstance('SMARTEST')->preparedQuery(
	                'SELECT page_id FROM Pages WHERE page_site_id=:site_id AND page_name=:page_name ORDER BY page_id ASC LIMIT 1',
	                array('site_id' => $site->getId(), 'page_name' => $name)
	            );
	        }catch(Exception $e){
	            $rows = array();
	        }

	        if(isset($rows[0]['page_id'])){
	            return self::loadPageById($rows[0]['page_id']);
	        }

	        return null;

	    }

	    public static function createStandardPagesLayout(SmartestSite $site, $master_template='', $initial_user=''){

	        if($initial_user instanceof SmartestUser){
	            $u = $initial_user;
	        }else if(SmartestSession::get('user') instanceof SmartestUser){
	            $u = SmartestSession::get('user');
	        }else{
	            $u = null;
	        }

	        $user_id = $u instanceof SmartestUser ? $u->getId() : 0;

	        $home_page = new SmartestPage;
		    $home_page->setTitle('Home');
		    $home_page->setName('home');
		    $home_page->setDraftTemplate($master_template);
		    $home_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $home_page->setSiteId($site->getId());
		    $home_page->setCreatedbyUserid($user_id);
		    $home_page->setOrderIndex(0);
		    $home_page->save();

		    if($u instanceof SmartestUser){
		        $home_page->addAuthorById($u->getId());
	        }

		    $site->setTopPageId($home_page->getId());
		    SmartestLog::getInstance('system')->log("Created home page for new site (page ID {$home_page->getId()})", SM_LOG_DEBUG);

		    $error_page = new SmartestPage;
		    $error_page->setTitle('Page not found');
		    $error_page->setName('error-404');
		    $error_page->setSiteId($site->getId());
		    $error_page->setDraftTemplate($master_template);
		    $error_page->setLiveTemplate($master_template);
		    $error_page->setParent($home_page->getId());
		    $error_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $error_page->setCreatedbyUserid($user_id);
		    $error_page->setOrderIndex(1024);
		    $error_page->setIsPublished('TRUE');
	        $error_page->setMetaDescription('The page you requested could not be found.');
		    $error_page->save();
		    $site->setErrorPageId($error_page->getId());
		    SmartestLog::getInstance('system')->log("Created and connected 404 page to new site (page ID {$error_page->getId()})", SM_LOG_DEBUG);

	        $search_page = new SmartestPage;
		    $search_page->setTitle('Search Results');
		    $search_page->setName('search');
		    $search_page->setSiteId($site->getId());
		    $search_page->setDraftTemplate($master_template);
		    $search_page->setLiveTemplate($master_template);
		    $search_page->setParent($home_page->getId());
		    $search_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $search_page->setCreatedbyUserid($user_id);
		    $search_page->setOrderIndex(1022);
		    $search_page->save();
		    $site->setSearchPageId($search_page->getId());
		    SmartestLog::getInstance('system')->log("Created and connected search page to new site (page ID {$search_page->getId()})", SM_LOG_DEBUG);

		    $tag_page = new SmartestPage;
		    $tag_page->setTitle('Tagged Content');
		    $tag_page->setName('tag');
		    $tag_page->setSiteId($site->getId());
		    $tag_page->setDraftTemplate($master_template);
		    $tag_page->setLiveTemplate($master_template);
		    $tag_page->setParent($home_page->getId());
		    $tag_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $tag_page->setCreatedbyUserid($user_id);
		    $tag_page->setOrderIndex(1023);
		    $tag_page->save();
		    $site->setTagPageId($tag_page->getId());
		    SmartestLog::getInstance('system')->log("Created and connected tag page to new site (page ID {$tag_page->getId()})", SM_LOG_DEBUG);

		    $user_page = new SmartestPage;
		    $user_page->setTitle('User Profile');
		    $user_page->setName('user');
		    $user_page->setSiteId($site->getId());
		    $user_page->setDraftTemplate($master_template);
		    $user_page->setLiveTemplate($master_template);
		    $user_page->setParent($home_page->getId());
		    $user_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $user_page->setCreatedbyUserid($user_id);
		    $user_page->setOrderIndex(1020);
		    $user_page->save();
		    $site->setUserPageId($user_page->getId());
		    SmartestLog::getInstance('system')->log("Created and connected user page to new site (page ID {$user_page->getId()})", SM_LOG_DEBUG);

		    $holding_page = new SmartestPage;
		    $holding_page->setTitle('Holding page');
		    $holding_page->setName('error-503');
		    $holding_page->setSiteId($site->getId());
		    $holding_page->setDraftTemplate($master_template);
		    $holding_page->setLiveTemplate($master_template);
		    $holding_page->setParent($home_page->getId());
		    $holding_page->setWebid(SmartestStringHelper::random(32, SM_RANDOM_ALPHANUMERIC));
		    $holding_page->setCreatedbyUserid($user_id);
		    $holding_page->setOrderIndex(1019);
		    $holding_page->save();
		    $site->setHoldingPageId($holding_page->getId());
		    SmartestLog::getInstance('system')->log("Created and connected holding page to new site (page ID {$holding_page->getId()})", SM_LOG_DEBUG);

		    $site->save();

		    return $home_page;
	    }

	    public static function createSiteDirectory(SmartestSite $site){

	        if(!is_dir(SM_ROOT_DIR.'Sites/')){
	            if(!@mkdir(SM_ROOT_DIR.'Sites/', 0777, true)){
	                throw new SmartestException("Could not create Sites directory.");
	            }
	        }

	        if(strlen((string) $site->getDirectoryName())){
	            $site_dir = SM_ROOT_DIR.'Sites/'.trim($site->getDirectoryName(), '/').'/';
	        }else{
	            $site_dir_name = substr(SmartestStringHelper::toCamelCase($site->getName()), 0, 64);
	            $site_dir = SmartestFileSystemHelper::getUniqueFileName(SM_ROOT_DIR.'Sites/'.$site_dir_name.'/');
	        }

	        if(!strlen((string) $site_dir)){
	            throw new SmartestException("Could not determine a unique site directory for ".$site->getName().".");
	        }

	        if(!SmartestStringHelper::endsWith($site_dir, '/')){
	            $site_dir .= '/';
	        }

	        foreach(array(
	            '',
	            'Presentation',
	            'Presentation/Layouts',
	            'Presentation/Special',
	            'Configuration',
	            'Library',
	            'Library/Actions'
	        ) as $subdir){
	            $dir = $site_dir.$subdir;
	            if(!is_dir($dir) && !@mkdir($dir, 0777, true)){
	                throw new SmartestException("Could not create site directory ".$dir.".");
	            }
	        }

	        if(!is_file($site_dir.'Configuration/site.yml')){
	            SmartestFileSystemHelper::save($site_dir.'Configuration/site.yml', '', true);
	        }

		    $actions_class_name = SmartestStringHelper::toCamelCase($site->getName()).'Actions';
		    $class_file_contents = file_get_contents(SM_ROOT_DIR.'System/Base/ClassTemplates/SiteActions.class.php.txt');
		    $class_file_contents = str_replace('__TIMESTAMP__', date('Y-m-d h:i:s'), $class_file_contents);
	        if(!is_file($site_dir.'Library/Actions/SiteActions.class.php')){
	            SmartestFileSystemHelper::save($site_dir.'Library/Actions/SiteActions.class.php', $class_file_contents, true);
	        }
	        chmod($site_dir.'Library/Actions/SiteActions.class.php', 0666);
	        $site->setDirectoryName(SmartestFileSystemHelper::getFileName(rtrim($site_dir, '/')));

			$site->save();

    }

    public static function ensureDefaultSystemAssets($initial_user='', $site=null){

        $source_path = SM_ROOT_DIR.'System/Install/Samples/default_user_profile_pic.jpg';
        $target_path = SM_ROOT_DIR.'Public/Resources/Images/default_user_profile_pic.jpg';

        if(!is_file($target_path) && is_file($source_path) && is_dir(dirname($target_path)) && is_writable(dirname($target_path))){
            SmartestFileSystemHelper::copy($source_path, $target_path);
        }

        $asset = new SmartestAsset;
        $asset_id = 0;
        $user_id = $initial_user instanceof SmartestUser ? $initial_user->getId() : 0;
        $site_id = $site instanceof SmartestSite && $site->getId() ? $site->getId() : 1;
        $ph = new SmartestPreferencesHelper;

        try{
            $db = SmartestDatabase::getInstance('SMARTEST');
            $result = $db->preparedQuery(
                "SELECT asset_id FROM Assets WHERE asset_url=:url AND asset_type='SM_ASSETTYPE_JPEG_IMAGE' AND asset_is_system=1 AND asset_shared=1 AND asset_deleted=0 ORDER BY asset_id ASC LIMIT 1",
                array('url' => 'default_user_profile_pic.jpg')
            );

            if(is_array($result) && isset($result[0]['asset_id'])){
                $asset_id = (int) $result[0]['asset_id'];
            }
        }catch(Exception $e){
            $asset_id = 0;
        }

        if($asset_id && $asset->find($asset_id)){
            $ph->setGlobalPreference('default_user_profile_pic_asset_id', $asset->getId(), null, $site_id);
            return $asset;
        }

        if(!$asset->findBy('url', 'default_user_profile_pic.jpg')){
            $asset->setUrl('default_user_profile_pic.jpg');
            $asset->setWebid(SmartestStringHelper::random(32));
            $asset->setStringId('default_user_profile_picture');
            $asset->setLabel('Default User Profile Picture');
            $asset->setType('SM_ASSETTYPE_JPEG_IMAGE');
            $asset->setCreated(time());
        }

        $asset->setUserId($user_id);
        $asset->setSiteId($site_id);
        $asset->setShared(1);
        $asset->setIsSystem(1);
        $asset->setIsHidden(1);
        $asset->setIsApproved(1);
        $asset->save();

        $ph->setGlobalPreference('default_user_profile_pic_asset_id', $asset->getId(), null, $site_id);

        return $asset;
    }

    protected static function logSiteCreation($message, $level=SM_LOG_DEBUG){
        $log_name = isset($GLOBALS['_site_creation_log']) && strlen((string) $GLOBALS['_site_creation_log']) ? (string) $GLOBALS['_site_creation_log'] : 'system';
        SmartestLog::getInstance($log_name)->log('Site Creation: '.$message, $level);
    }

    protected static function describeThrowable(Throwable $e){
        return get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine();
    }

}
