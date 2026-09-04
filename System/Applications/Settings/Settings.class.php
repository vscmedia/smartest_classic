<?php

/**
 * Contains the Settings module for website
 *
 * PHP versions 5
 *
 * @category   WebApplication
 * @package    PHP-Controller
 * @author     Marcus Gilroy-Ware <marcus@vsccreative.com>
 */


class Settings extends SmartestSystemApplication{

	public function startPage(){

        $this->setTitle('System settings');

	}

    public function buildFirstSitePostInstaller(){

        $token = $this->getRequestParameter('token');
        SmartestLog::getInstance('installer')->log("Settings::buildFirstSitePostInstaller reached. execute=".$this->getRequestParameter('execute')."; action=".$this->getRequestParameter('action').'.', SM_LOG_DEBUG);

        try{
            if($this->getRequestParameter('execute') == '1' && $this->getRequestParameter('action') == 'createSite'){
                SmartestInstallationStatusHelper::executeFirstSiteBuildKitFromInstallerPost(true);
            }else{
                SmartestInstallationStatusHelper::executePendingFirstSiteBuildKit($token, true);
            }
        }catch(Throwable $e){
            SmartestInstallationStatusHelper::showPendingFirstSiteBuildKitFailure($e);
        }

        $this->redirect('/smartest/login#welcome');

    }

    /* public function getPreferencePanels(){

        // $c = SmartestPersistentObject::get('controller');
        // print_r($c->getAllModulesById());
        // print_r(SmartestSystemHelper::getSystemApplicationDirectories());

    } */

    public function editSite(){

        $this->requireOpenProject();

	    if($this->getUser()->hasToken('modify_site_parameters')){

    	    if($this->getSite() instanceof SmartestSite){

    		    $site_id = $this->getSite()->getId();

    		    $main_page_templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');

    		    $sitedetails = $this->getSite();
    		    $pages = $this->getSite()->getPagesList();
                $this->send($pages, 'pages');

                $this->send(!(bool) $this->getSite()->getIsEnabled(), 'site_disabled');

                $this->send($this->getSite()->getOrganizationName(), 'site_organisation');

                $elastic_search_is_possible = SmartestSystemHelper::elasticSearchIsPossible();
                $this->send($elastic_search_is_possible, 'allow_elastic_search');
                $this->send(($this->getGlobalPreference('site_search_type') == 'ELASTICSEARCH' && $elastic_search_is_possible) ? 'ELASTICSEARCH' : 'BASIC', 'search_type');
                $this->send($this->getSite()->getSslMode(), 'site_ssl_mode');

                $alh = new SmartestAssetsLibraryHelper;
                $icos = $alh->getAssetsByTypeCode('SM_ASSETTYPE_ICO_FAVICON', $this->getSite()->getId());
                $this->send($icos, 'favicon_assets');

                $this->setTitle("Edit site settings");
    		    $this->send($sitedetails, 'site');

    	    }else{

    	        $this->addUserMessageToNextRequest('You must have an open site to open edit settings.', SmartestUserMessage::INFO);
    	        $this->redirect('/smartest');

    	    }

        }else{

            $this->addUserMessageToNextRequest('You don\'t have permission to edit site settings.', SmartestUserMessage::ACCESS_DENIED);
	        $this->redirect('/smartest');

        }

	}

	public function updateSiteDetails(){

        $this->requireOpenProject();

	    if($this->getSite() instanceof SmartestSite){

	        $site = $this->getSite();

	        if($this->getUser()->hasToken('modify_site_parameters')){

    	        $site->setName($this->getRequestParameter('site_name'));
    	        $site->setInternalLabel($this->getRequestParameter('site_internal_label'));
    	        $site->setTitleFormat($this->getRequestParameter('site_title_format'));
    	        $site->setDomain(SmartestStringHelper::toValidDomain(preg_replace('/^https?:\/\//i', '', $this->getRequestParameter('site_domain'))));
    	        $site->setAdminEmail($this->getRequestParameter('site_admin_email'));
                $site->setOrganisationName($this->getRequestParameter('site_organisation_name'));
                $site->setLanguageCode($this->getRequestParameter('site_language'));
    	        $this->addUserMessageToNextRequest('Your site settings have been updated.', SmartestUserMessage::SUCCESS);
    	        $site->save();

                $this->setGlobalPreference('site_search_type', $this->getRequestParameter('site_search_type'));

                $ssl_mode = $this->getRequestParameter('site_ssl_mode');
                if(in_array($ssl_mode, SmartestSite::getValidSslModes(), true)){
                    $site->setSslMode($ssl_mode);
                }

            }else{

                $this->addUserMessageToNextRequest('You don\'t have permission to edit site settings', SmartestUserMessage::ACCESS_DENIED);

            }

            if($site->getIsEnabled() == '1' && SmartestStringHelper::toRealBool($this->getRequestParameter('site_is_disabled'))){
	            if($this->getUser()->hasToken('disable_site')){
	                $site->setIsEnabled(0);
                }else{
                    $this->addUserMessageToNextRequest('You don\'t have permission to disable sites', SmartestUserMessage::ACCESS_DENIED);
                }
	        }

            if($site->getIsEnabled() == '0' && !SmartestStringHelper::toRealBool($this->getRequestParameter('site_is_disabled'))){
	            if($this->getUser()->hasToken('enable_site')){
	                $site->setIsEnabled(1);
                }else{
                    $this->addUserMessageToNextRequest('You don\'t have permission to enable sites', SmartestUserMessage::ACCESS_DENIED);
                }
	        }

	        if(SmartestUploadHelper::uploadExists('site_logo')){

	            $alh = new SmartestAssetsLibraryHelper;
	            $upload = new SmartestUploadHelper('site_logo');
                $upload->setUploadDirectory(SM_ROOT_DIR.'System/Temporary/');
                $types = $alh->getPossibleTypesBySuffix($upload->getDotSuffix());

                if(count($types)){
                    $t = $types[0]['type']['id'];

                    $ach = new SmartestAssetCreationHelper($t);
                    $ach->createNewAssetFromFileUpload($upload, "Logo for ".$site->getInternalLabel().' - '.date('M d Y'));

                    $file = $ach->finish();
                    $file->setShared(1);
                    $file->setIsSystem(1);
                    $file->setIsHidden(1);
                    $file->save();

                    $site->setLogoImageAssetId((int) $file->getId());
                    $site->save();

                    $site_logos_group = new SmartestAssetGroup;

                    if($site_logos_group->find(SmartestSystemSettingHelper::getSiteLogosFileGroupId())){
                        $site_logos_group->addAssetById($file->getId());
                    }
                }
	        }else{
	            $site->setLogoImageAssetId((int) $this->getRequestParameter('site_logo_image_asset_id'));
	            $site->save();
	        }

            if(SmartestUploadHelper::uploadExists('site_favicon')){

	            $alh = new SmartestAssetsLibraryHelper;
	            $upload = new SmartestUploadHelper('site_favicon');
                $upload->setUploadDirectory(SM_ROOT_DIR.'System/Temporary/');

                $t = 'SM_ASSETTYPE_ICO_FAVICON';

                $ach = new SmartestAssetCreationHelper($t);
                $ach->createNewAssetFromFileUpload($upload, "Favicon for ".$site->getInternalLabel().' - '.date('M d Y'));

                $file = $ach->finish();
                $file->setShared(1);
                $file->setIsSystem(1);
                $file->setIsHidden(1);
                $file->save();

                $site->setFaviconId((int) $file->getId());
                $site->save();

            }else{
                $site->setFaviconId((int) $this->getRequestParameter('site_favicon_asset_id'));
                $site->save();
            }

	        /* if($this->getRequestParameter('site_user_page') == 'NEW' && !is_numeric($site->getUserPageId())){
	            $p = new SmartestPage;
	            $p->setTitle('User Profile');
	            $p->setName('user');
	            $p->setSiteId($site->getId());
	            $p->setParent($site->getTopPageId());
        	    $p->setWebid(SmartestStringHelper::random(32));
        	    $p->setCreatedbyUserid($this->getUser()->getId());
        	    $p->setOrderIndex(1020);
        	    $p->save();
        	    $site->setUserPageId($p->getId());
	        } */

	        SmartestCache::clear('site_pages_tree_'.$site->getId(), true);

            $this->redirect('@site_settings');

		    // $this->formForward();
	    }
	}



    public function editCmsSettings(){

        $this->requireOpenProject();

	    if($this->getUser()->hasToken('modify_site_parameters')){

    	    if($this->getSite() instanceof SmartestSite){

    		    $site_id = $this->getSite()->getId();

                $this->send($this->getSite(), 'site');

    		    $main_page_templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');

                $default_suffix = $this->getGlobalPreference('default_url_suffix', 'html');
                if($default_suffix[0] == '.'){
                    $default_suffix = substr($default_suffix, 1);
                }
                $this->send($default_suffix, 'site_pageurl_default_suffix');
                $this->send(!in_array($default_suffix, array('html', 'php', 'shtml', '_NONE')), 'site_pageurl_default_suffix_custom');

                $pmh = new SmartestPageManagementHelper;

                $this->send($pmh->getPagePresets($this->getSite()->getId()), 'page_presets');

                $default_page_preset_id = $this->getGlobalPreference('site_default_page_preset_id');
                $this->send($default_page_preset_id, 'default_page_preset_id');

                $ach = new SmartestAssetClassesHelper;
                $this->send($ach->getContainers(), 'containers');
                $this->send($this->getSite()->getPrimaryContainerId(), 'primary_container_id');
                $this->send($ach->getTextPlaceholders(), 'text_placeholders');
                $this->send($this->getSite()->getPrimaryTextPlaceholderId(), 'primary_text_placeholder_id');

                $eu_cookie_compliance = $this->getGlobalPreference('enable_eu_cookie_compliance');
                $this->send(SmartestStringHelper::toRealBool($eu_cookie_compliance), 'eu_cookie_compliance');

                $site_responsive_mode = $this->getGlobalPreference('enable_site_responsive_mode');
                $this->send(SmartestStringHelper::toRealBool($site_responsive_mode), 'site_responsive_mode');

                $override_eu_cookie_compliance_ga = $this->getGlobalPreference('override_eu_cookie_compliance_ga', 1);
                $this->send(SmartestStringHelper::toRealBool($override_eu_cookie_compliance_ga), 'override_eu_cookie_compliance_ga');

                $oembed_enabled = (bool) $this->getGlobalPreference('site_oembed_enabled');
                $this->send($oembed_enabled, 'oembed_enabled');

                $ga_id = $this->getGlobalPreference('google_analytics_id');
                $this->send($ga_id, 'site_ga_id');

                $this->send(!(bool) $this->getSite()->getIsEnabled(), 'site_disabled');

                $default_blocklist_style = $this->getSite()->getDefaultBlockListStyle();
                $this->send($default_blocklist_style, 'default_blocklist_style');

                $blocklist_styles = $this->getSite()->getBlockListStyles();
                $this->send($blocklist_styles, 'blocklist_styles');

                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $domain = $this->getRequest()->getDomain();
                $returnTo = $protocol.'://'.$host.$domain.'smartest/cmssettings';
                $this->send(urlencode($returnTo), 'cookie_set_return');

                $distinguish_mobiles = $this->getGlobalPreference('site_responsive_distinguish_mobile');
                $this->send(SmartestStringHelper::toRealBool($distinguish_mobiles), 'responsive_distinguish_mobiles');

                $distinguish_tablets = $this->getGlobalPreference('site_responsive_distinguish_tablet');
                $this->send(SmartestStringHelper::toRealBool($distinguish_tablets), 'responsive_distinguish_tablets');

                $distinguish_old_pcs = $this->getGlobalPreference('site_responsive_distinguish_oldpcs');
                $this->send(SmartestStringHelper::toRealBool($distinguish_old_pcs), 'responsive_distinguish_old_pcs');

                $this->setTitle("Edit CMS settings");

            }

        }else{

            $this->formForward();

        }

    }

    public function updateCmsSettings(){

        $this->requireOpenProject();

	    if($this->getUser()->hasToken('modify_site_parameters')){

    	    if($this->getSite() instanceof SmartestSite){

    		    $site_id = $this->getSite()->getId();
                $site = $this->getSite();

    		    $main_page_templates = SmartestFileSystemHelper::load(SM_ROOT_DIR.'Presentation/Masters/');

                if(is_numeric($this->getRequestParameter('site_default_page_preset_id'))){
                    $this->setGlobalPreference('site_default_page_preset_id', $this->getRequestParameter('site_default_page_preset_id'));
                }else{
                    $this->setGlobalPreference('site_default_page_preset_id', '0');
                }

                $suff = $this->getRequestParameter('site_default_url_suffix');

                if(is_numeric($this->getRequestParameter('site_default_container_id'))){
                    $site->setPrimaryContainerId($this->getRequestParameter('site_default_container_id'));
                }else{
                    $site->setPrimaryContainerId(0);
                }

                if(is_numeric($this->getRequestParameter('site_default_text_placeholder_id'))){
                    $site->setPrimaryTextPlaceholderId($this->getRequestParameter('site_default_text_placeholder_id'));
                }else{
                    $site->setPrimaryTextPlaceholderId(0);
                }

                if($suff == '_CUSTOM'){
                    $custom_suffix = $this->getRequestParameter('site_default_url_suffix_custom');
                    if($custom_suffix[0] == '.'){
                        $custom_suffix = substr($custom_suffix, 1);
                    }
                    $this->setGlobalPreference('default_url_suffix', $custom_suffix);
                }else{
                    $this->setGlobalPreference('default_url_suffix', $suff);
                }

                if($this->requestParameterIsSet('site_responsive_mode')){
                    $this->setGlobalPreference('site_responsive_distinguish_mobile', ($this->requestParameterIsSet('site_responsive_distinguish_mobile') ? 1 : 0));
                    $this->setGlobalPreference('site_responsive_distinguish_tablet', ($this->requestParameterIsSet('site_responsive_distinguish_tablet') ? 1 : 0));
                    $this->setGlobalPreference('site_responsive_distinguish_oldpcs', ($this->requestParameterIsSet('site_responsive_distinguish_oldpcs') ? 1 : 0));
                }

                $this->setGlobalPreference('site_oembed_enabled', (int) SmartestStringHelper::toRealBool($this->getRequestParameter('site_oembed_enabled')));
                $this->setGlobalPreference('google_analytics_id', $this->getRequestParameter('site_ga_id'));
                $this->setGlobalPreference('enable_eu_cookie_compliance', $this->getRequestParameter('site_eu_cookie_compliance'));
                $this->setGlobalPreference('enable_site_responsive_mode', SmartestStringHelper::toRealBool($this->getRequestParameter('site_responsive_mode')) ? 1 : 0);
    	        $this->setGlobalPreference('override_eu_cookie_compliance_ga', SmartestStringHelper::toRealBool($this->getRequestParameter('site_override_eu_cookie_compliance_ga')) ? 1 : 0);

                $this->addUserMessageToNextRequest('Your CMS settings have been updated.', SmartestUserMessage::SUCCESS);

                $site->save();

            }

            $this->redirect('@cms_settings');

        }else{

            $this->formForward();

        }

    }

    public function editSiteSpecialPages(){

        $this->requireOpenProject();

	    if($this->getSite() instanceof SmartestSite){

	        $site = $this->getSite();
            $pages = $this->getSite()->getPagesListWithSpecialPages(true);
            $this->send($pages, 'pages');
            $this->setTitle("Reconfigure site special pages");
		    $this->send($this->getSite(), 'site');

        }

    }

    public function updateSiteSpecialPages(){

        $this->requireOpenProject();

	    if($this->getSite() instanceof SmartestSite){

	        $site = $this->getSite();

            if($this->getUser()->hasToken('modify_site_parameters')){

    	        $site->setTagPageId($this->getRequestParameter('site_tag_page'));
    	        $site->setSearchPageId($this->getRequestParameter('site_search_page'));
    	        $site->setErrorPageId($this->getRequestParameter('site_error_page'));

    	        if($this->getRequestParameter('site_user_page') == 'NEW' && !is_numeric($site->getUserPageId())){
    	            $p = new SmartestPage;
    	            $p->setTitle('User Profile');
    	            $p->setName('user');
    	            $p->setSiteId($site->getId());
    	            $p->setParent($site->getTopPageId());
            	    $p->setWebid(SmartestStringHelper::random(32));
            	    $p->setCreatedbyUserid($this->getUser()->getId());
            	    $p->setOrderIndex(1020);
            	    $p->save();
            	    $site->setUserPageId($p->getId());
    	        }else if(is_numeric($this->getRequestParameter('site_user_page'))){
    	            $site->setUserPageId($this->getRequestParameter('site_user_page'));
    	        }

    	        if($this->getRequestParameter('site_holding_page') == 'NEW' && !is_numeric($site->getHoldingPageId())){
    	            $p = new SmartestPage;
    	            $p->setTitle('Holding page');
    	            $p->setName('error-503');
    	            $p->setSiteId($site->getId());
    	            $p->setParent($site->getTopPageId());
            	    $p->setWebid(SmartestStringHelper::random(32));
            	    $p->setCreatedbyUserid($this->getUser()->getId());
            	    $p->setOrderIndex(1019);
            	    $p->save();
            	    $site->setHoldingPageId($p->getId());
    	        }else if(is_numeric($this->getRequestParameter('site_holding_page'))){
    	            $site->setHoldingPageId($this->getRequestParameter('site_holding_page'));
    	        }

                $this->addUserMessageToNextRequest('Your site settings have been updated.', SmartestUserMessage::SUCCESS);
    	        $site->save();

    	        SmartestCache::clear('site_pages_tree_'.$site->getId(), true);

    		    $this->formForward();

            }else{

                $this->addUserMessageToNextRequest('You don\'t have permission to edit site settings', SmartestUserMessage::ACCESS_DENIED);

            }

        }

    }

    public function editCustomizationSettings(){



    }

    public function updateCustomizationSettings(){



    }

    public function getTaggedObjects(){

	    $tag_identifier = SmartestStringHelper::toSlug($this->getRequestParameter('tag'));
	    $tag = new SmartestTag;

	    if($tag->findBy('name', $tag_identifier)){
	        $this->send($tag, 'tag');
	        // $objects = $tag->getObjectsOnSite($this->getSite()->getId(), true);
	        $this->send(new SmartestArray($tag->getSimpleItems($this->getSite()->getId(), true)), 'items');
	        $this->send(new SmartestArray($tag->getPages($this->getSite()->getId())), 'pages');
	        $this->send(new SmartestArray($tag->getAssets($this->getSite()->getId())), 'assets');
            $this->send(new SmartestArray($tag->getUsers($this->getSite()->getId())), 'users');
            // echo count($tag->getUsers($this->getSite()->getId()));
	    }else{
	        $objects = array();
	        $this->addUserMessage("This tag does not exist.", SmartestUserMessage::WARNING);
	    }

	}

	public function listBuildKits(){

	    $this->setTitle('Build Kits');
	    $buildkits = SmartestBuildKitUtilities::getVisibleBuildKits();
	    $this->send($buildkits, 'buildkits');
	    $this->send(count($buildkits), 'num_buildkits');

	}

    public function buildKitInfo(){

        $buildkit_id = $this->getRequestParameter('buildkit_id');
        $buildkit = SmartestBuildKitUtilities::getBuildKitIfInstalled($buildkit_id);

        if($buildkit instanceof SmartestBuildKit && !$buildkit->isHidden()){
            $this->setTitle($buildkit->getLabel().' Build Kit');
            $this->send($buildkit, 'buildkit');
            $this->send(count($buildkit->getUnwritableRequiredWriteLocations()), 'num_unwritable_locations');
            $this->send(count($buildkit->getRequiredWriteLocations()), 'num_required_locations');
            $this->send(count($buildkit->getCreationSummary()), 'num_creation_summary_items');
            $this->send(count($buildkit->getThirdPartyLicenses()), 'num_third_party_licenses');
            $this->send(count($buildkit->getFeatureSummary()), 'num_features');
        }else{
            $this->addUserMessage('The selected Build Kit could not be found.', SmartestUserMessage::WARNING);
            $this->redirect('/smartest/buildkits');
        }

    }

    public function listElasticSearchIndices(){

        if(SmartestSystemHelper::elasticSearchIsPossible()){
            if(SmartestElasticSearchHelper::elasticSearchIsOperational()){
                if(SmartestElasticSearchHelper::isRunning()){

                    $this->send('green', 'status');
                    $this->send('Elasticsearch is activated and running.', 'status_message');
                    $this->send(true, 'list_indices');

                    $indices = SmartestElasticSearchHelper::getIndices($this->getSite()->getId());
                    $this->send($indices, 'indices');
                    $this->send($this->getSite()->getElasticSearchIndexName(), 'site_main_index_name');
                    $this->send(SmartestElasticSearchHelper::indexExists($this->getSite()->getElasticSearchIndexName()), 'site_has_index');

                }else{
                    $this->send('amber', 'status');
                    $this->send('Elasticsearch is activated but the Elasticsearch process cannot be connected to or no alive nodes can be found.', 'status_message');
                    $this->send(false, 'list_indices');
                }
            }else{
                $this->send('red', 'status');
                $this->send('Elasticsearch is not activated.', 'status_message');
                $this->send(false, 'list_indices');
            }
        }else{
            $this->send('black', 'status');
            $this->send('Elasticsearch cannot be used by this Smartest installation. PHP 5.6 and Java 1.8 are required.', 'status_message');
            $this->send(false, 'list_indices');
        }

    }

    public function deleteElasticSearchIndex(){

        if(SmartestSystemHelper::elasticSearchIsPossible()){

            $index_name = SmartestStringHelper::toVarName($this->getRequestParameter('index_name'));

            if($index_name == $this->getSite()->getElasticSearchIndexName()){
                $this->addUserMessageToNextRequest("You cannot delete the site's main search index.", SmartestUserMessage::ACCESS_DENIED);
            }else{
                if(SmartestElasticSearchHelper::indexExists($index_name)){
                    if(SmartestElasticSearchHelper::deleteIndex($index_name)){
                        $this->addUserMessageToNextRequest("The index has been deleted.", SmartestUserMessage::SUCCESS);
                    }else{
                        $this->addUserMessageToNextRequest("The index could not be deleted.");
                    }
                }else{
                    $this->addUserMessageToNextRequest("No index exists with that name.");
                }
            }

        }else{
            $this->addUserMessageToNextRequest("Elasticsearch cannot be run in the current setup.");
        }

        $this->redirect('/settings/listElasticSearchIndices');
    }

}
