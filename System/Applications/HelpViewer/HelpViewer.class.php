<?php

class HelpViewer extends SmartestSystemApplication{
	
  	public function startPage(){
	
  	}
 
    public function getContent(){
        
    }
    
    public function getAjaxContent(){
        
        $code = $this->getRequestParameter('help_code');
        $p = explode(':', $code);
        $app_shortname = isset($p[0]) ? $p[0] : '';
        $node_id = isset($p[1]) ? $p[1] : '';
        $apps = SmartestPersistentObject::get('controller')->getAllModulesByShortName();
        
        if(isset($apps[$app_shortname])){
            $language_code = $this->getPreferredHelpLanguageCode();
            $this->trustHelpPresentationDirectories($apps[$app_shortname]['directory'], $language_code);
            $help_file = $this->getHelpIndexFile($apps[$app_shortname]['directory'], $language_code);

            if(is_file($help_file)){
                $help_config = SmartestYamlHelper::fastLoad($help_file);
                if(isset($help_config['help'][$node_id])){
                    $template = $this->getHelpContentTemplate($apps[$app_shortname]['directory'], $language_code, $help_config['help'][$node_id]['content']);
                    if(is_file($template)){
                        $this->send($help_config['help'][$node_id]['title'], 'title');
                        $this->send($template, 'content');
                        $this->send($this->getSite(), 'site');
                        $this->send($this->getUser(), 'user');
                        $this->send(isset($help_config['help'][$node_id]['wiki_url']) ? new SmartestExternalUrl($help_config['help'][$node_id]['wiki_url']) : null, 'wiki_url');
                    }else{
                        // help content file not found
                        $this->send("Oops!", 'title');
                        $this->send($this->getRequest()->getMeta('_module_dir').'Presentation/Special/notfound.tpl', 'content');
                        $this->send($template, 'lost_file');
                    }
                }else{
                    // help node id not recognised// help content file not found
                    $this->send("Node not recognized", 'title');
                    $this->send($this->getRequest()->getMeta('_module_dir').'Presentation/Special/notfound.tpl', 'content');
                }
            }else{
                // application does not support help system
            }
        }else{
            // unrecognized application code
        }
    }

    protected function getPreferredHelpLanguageCode(){

        if($this->getUser() && method_exists($this->getUser(), 'getPreferredUiLanguage')){
            $language_code = $this->getUser()->getPreferredUiLanguage();

            if(is_string($language_code) && preg_match('/^[a-z]{3}$/i', $language_code)){
                return strtolower($language_code);
            }
        }

        return 'eng';

    }

    protected function getHelpPresentationDirectory($application_directory, $language_code){

        return $application_directory.'Content/LanguagePacks/'.$language_code.'/Help/Presentation/';

    }

    protected function trustHelpPresentationDirectories($application_directory, $language_code){

        if(method_exists($this->getPresentationLayer(), 'addTrustedTemplateDirectory')){
            $this->getPresentationLayer()->addTrustedTemplateDirectory($this->getHelpPresentationDirectory($application_directory, 'eng'));

            if($language_code != 'eng'){
                $this->getPresentationLayer()->addTrustedTemplateDirectory($this->getHelpPresentationDirectory($application_directory, $language_code));
            }
        }

    }

    protected function getHelpIndexFile($application_directory, $language_code){

        $localized_help_file = $application_directory.'Content/LanguagePacks/'.$language_code.'/Help/index.yml';

        if($language_code != 'eng' && is_file($localized_help_file)){
            return $localized_help_file;
        }

        return $application_directory.'Content/LanguagePacks/eng/Help/index.yml';

    }

    protected function getHelpContentTemplate($application_directory, $language_code, $content_file){

        $content_file = basename($content_file);
        $localized_template = $this->getHelpPresentationDirectory($application_directory, $language_code).$content_file;

        if($language_code != 'eng' && is_file($localized_template)){
            return $localized_template;
        }

        return $this->getHelpPresentationDirectory($application_directory, 'eng').$content_file;

    }

    public function search($get){
        
    } 
  
}
