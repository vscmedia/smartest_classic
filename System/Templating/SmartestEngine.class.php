<?php

require_once SM_ROOT_DIR.'System/Library/vendor/autoload.php';

class SmartestEngine extends \Smarty\Smarty{

	protected $controller;
	protected $section;
	protected $method;
	protected $domain;
	protected $get;
	protected $_process_id;
	protected $_child_processes = array();
	protected $_context;
	protected $_abstractPropertyHolder = array();
	protected $_log = array();
	protected $_included_scripts = array();
	protected $_included_stylesheets = array();
	protected $_series = array();
	protected $_request_data;
	protected $_request;
	
	public $_tpl_vars = array();
	public $plugins_dir = array();
	public $template_dir;
	public $compile_dir;
	public $cache_dir;
	public $config_dir;
	public $left_delimiter = '{';
	public $right_delimiter = '}';
	public $caching = false;
	protected $_registered_smartest_plugin_dirs = array();
	
	public function __construct($process_id){
	    
	    parent::__construct();
		
		$this->_process_id = $process_id;
		$this->_context = SM_CONTEXT_GENERAL;
		
		$this->controller = SmartestPersistentObject::get('controller');
		$this->_request_data = SmartestPersistentObject::get('request_data');
		
		$this->templateHelper = new SmartestTemplateHelper;
		$this->addPluginDirectory(SM_ROOT_DIR."System/Templating/Plugins/Shared/");
		$this->addPluginDirectory(SM_ROOT_DIR."Library/Smarty/Plugins/");
        $this->registerFilter('pre', array($this, 'translateSmartestTemplateSyntax'), 'smartest_legacy_syntax');
    	
        $this->setSmartestDelimiters('{', '}');
		
		$this->assign('request_parameters', $this->_request_data->getParameter('request_parameters'));
        $this->assign('system_data_info', new SmartestFrontEndSystemInfoQueryService);
		
		$this->_tpl_vars['random'] = new SmartestRandomNumberGenerator;
		$this->assign('now', new SmartestDateTime(time()));
        
        $this->assign('sm_developer_mode', constant('SM_DEVELOPER_MODE'));
        $this->assign('random_nonce', SmartestStringHelper::randomFromFormat('RRRRRRRR'));
        
		// Sergiy: Deny access to PHP world from frontend tpls
        // (foolproof and the case of marginally trusted template editor).
        // Marcus: moved this to SmartestEngine so that all templates are affected
		$this->configureSmartestSecurity();
        $this->addTrustedCorePresentationDirectories();
        $this->addTrustedTextFragmentTemplateDirectories();
        $this->addTrustedFilterTemplateDirectories();
			
	}

    public function assign($tpl_var, $value = null, $nocache = false, $scope = null){

        if(is_array($tpl_var)){
            foreach($tpl_var as $key=>$val){
                if($key != ''){
                    $this->_tpl_vars[$key] = $val;
                }
            }
        }elseif($tpl_var != ''){
            $this->_tpl_vars[$tpl_var] = $value;
        }

        return parent::assign($tpl_var, $value, $nocache, $scope);

    }

    public function setTemplateDirectories($template_dir, $compile_dir, $cache_dir, $config_dir){

        $this->template_dir = $template_dir;
        $this->compile_dir = $compile_dir;
        $this->cache_dir = $cache_dir;
        $this->config_dir = $config_dir;

        $this->setTemplateDir($template_dir);
        $this->setCompileDir($compile_dir);
        $this->setCacheDir($cache_dir);
        $this->setConfigDir($config_dir);

    }

    public function setSmartestDelimiters($left, $right){

        $this->left_delimiter = $left;
        $this->right_delimiter = $right;
        $this->setLeftDelimiter($left);
        $this->setRightDelimiter($right);

    }

    public function setSmartestCaching($caching){

        $this->caching = $caching;
        $this->setCaching($caching ? self::CACHING_LIFETIME_CURRENT : self::CACHING_OFF);

    }

    protected function configureSmartestSecurity(){

        $policy = new \Smarty\Security($this);
        $policy->allow_super_globals = false;
        $this->enableSecurity($policy);

        foreach(array('strtolower', 'strtoupper', 'trim', 'addslashes', 'stripslashes', 'strlen', 'is_numeric', 'is_array', 'in_array', 'count', 'empty', '_b') as $modifier){
            if(is_callable($modifier)){
                $this->registerPlugin(self::PLUGIN_MODIFIER, $modifier, $modifier);
            }
        }

        if(function_exists('_item_name_escape')){
            $this->registerPlugin(self::PLUGIN_MODIFIER, '_item_name_escape', '_item_name_escape');
        }

    }

    public function addTrustedTemplateDirectory($directory){

        if(!isset($this->security_policy) || !$this->security_policy instanceof \Smarty\Security){
            return false;
        }

        if(!is_string($directory) || !strlen($directory)){
            return false;
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if(is_dir($directory)){
            $real_directory = realpath($directory);

            if($real_directory !== false){
                $directory = rtrim($real_directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            }
        }

        if(!in_array($directory, $this->security_policy->secure_dir, true)){
            $this->security_policy->secure_dir[] = $directory;
        }

        return true;

    }

    protected function addTrustedFilterTemplateDirectories(){

        $filter_root = SM_ROOT_DIR.'System/Response/Filters/';

        if(!is_dir($filter_root)){
            return;
        }

        foreach(glob($filter_root.'*/*.filter', GLOB_ONLYDIR) as $directory){
            $this->addTrustedTemplateDirectory($directory);
        }

    }

    protected function addTrustedTextFragmentTemplateDirectories(){

        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'System/Cache/TextFragments/Live/');
        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'System/Cache/TextFragments/Previews/');

    }

    protected function addTrustedCorePresentationDirectories(){

        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'System/Presentation/');
        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'Presentation/');
        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'Presentation/Masters/');
        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'Presentation/Layouts/');
        $this->addTrustedTemplateDirectory(SM_ROOT_DIR.'Presentation/SingleItem/');

    }

    protected function syncSmartestTemplateVars(){

        foreach($this->_tpl_vars as $name=>$value){
            parent::assign($name, $value);
        }

        $this->setLeftDelimiter($this->left_delimiter);
        $this->setRightDelimiter($this->right_delimiter);
        $this->setSmartestCaching($this->caching);

        if($this->template_dir){
            $this->setTemplateDir($this->template_dir);
        }

        if($this->compile_dir){
            $this->setCompileDir($this->compile_dir);
        }

        if($this->cache_dir){
            $this->setCacheDir($this->cache_dir);
        }

        if($this->config_dir){
            $this->setConfigDir($this->config_dir);
        }

    }

    public function translateSmartestTemplateSyntax($source){

        $translations = array(
            '/(<\?sm:\s*)defun\b/i' => '$1function',
            '/(<\?sm:\s*)\/defun\s*:\?>/i' => '$1/function:?>',
            '/(<\?sm:\s*)fun\b/i' => '$1call',
            '/(\{\s*)defun\b/i' => '$1function',
            '/(\{\s*)\/defun\s*\}/i' => '$1/function}',
            '/(\{\s*)fun\b/i' => '$1call',
        );

        return preg_replace(array_keys($translations), array_values($translations), $source);

    }

    public function fetch($template = null, $cache_id = null, $compile_id = null){

        $this->syncSmartestTemplateVars();

        try{
            return parent::fetch($template, $cache_id, $compile_id);
        }catch(Throwable $e){
            return $this->handleSmartyThrowable($e, $template);
        }

    }

    public function display($template = null, $cache_id = null, $compile_id = null){

        $this->syncSmartestTemplateVars();

        try{
            return parent::display($template, $cache_id, $compile_id);
        }catch(Throwable $e){
            echo $this->handleSmartyThrowable($e, $template);
            return null;
        }

    }

    public function _smarty_include($params){

        $vars = isset($params['smarty_include_vars']) && is_array($params['smarty_include_vars']) ? $params['smarty_include_vars'] : array();

        foreach($vars as $key=>$value){
            $this->assign($key, $value);
        }

        return $this->fetch($params['smarty_include_tpl_file']);

    }
	
	public function startChildProcess($pid, $type='', $caching=false){
	    
	    $pid = SmartestStringHelper::toVarName($pid);
	    
	    if(!$type){
	        $engine_type = get_class($this);
        }else{
            $engine_type = $type;
        }
        
	    $cp = new $engine_type($pid);
	    
	    $cp->setTemplateDirectories(isset($this->template_dir) ? $this->template_dir : null, $this->compile_dir, $this->cache_dir, $this->config_dir);
		
		$cp->assign('section', isset($this->_tpl_vars['section']) ? $this->_tpl_vars['section'] : null);
		$cp->assign('module', isset($this->_tpl_vars['module']) ? $this->_tpl_vars['module'] : null);
		$cp->assign('module_dir', isset($this->_tpl_vars['module_dir']) ? $this->_tpl_vars['module_dir'] : null);
		$cp->assign('method', isset($this->_tpl_vars['method']) ? $this->_tpl_vars['method'] : null);
		$cp->assign('domain', $this->_tpl_vars['domain']);
		$cp->assign('class', isset($this->_tpl_vars['class']) ? $this->_tpl_vars['class'] : null);
		$cp->assign('sm_user_agent', isset($this->_tpl_vars['sm_user_agent']) ? $this->_tpl_vars['sm_user_agent'] : $this->getUserAgent());
		$cp->assign('request_parameters', $this->_request_data->getParameter('request_parameters'));
		$cp->setSmartestCaching((bool) $caching);
		
		$this->_child_processes[$pid] = $cp;
        return $cp;
	}
	
	public function killChildProcess($pid){
	    $pid = SmartestStringHelper::toVarName($pid);
	    
	    if(isset($this->_child_processes[$pid])){
	        if($pid != $this->_process_id){
	            unset($this->_child_processes[$pid]);
	            return true;
            }else{
                return false;
            }
	        
	    }else{
	        return false;
	    }
	}
	
	public function getProcessId(){
	    return $this->_process_id;
	}
	
    public function getUserAgent(){
	    return SmartestPersistentObject::get('userAgent');
	}
	
	public function getRequestData(){
	    return $this->_request_data;
	}
	
	public function getController(){
	    return $this->controller;
	}
	
	public function hasTemplateVariable($varname){
	    return isset($this->_tpl_vars[$varname]);
	}
	
	public function getTemplateVariable($varname){
	    if(isset($this->_tpl_vars[$varname])){
	        return $this->_tpl_vars[$varname];
        }else{
            return null;
        }
	}
	
	public function getUrlFor($route_name){
	    try{
	        return $this->controller->getUrlFor($route_name);
        }catch(QuinceException $e){
            // echo $e->getmessage();
            $this->raiseError($e->getMessage());
        }
	}
	
	public function getContext(){
	    return $this->_context;
	}
	
	public function setContext($context){
	    $this->_context = $context;
	}
	
	public function getProperty($property_name){
	    $property_name = SmartestStringHelper::toVarName($property_name);
	    if(isset($this->_abstractPropertyHolder[$property_name])){
	        return $this->_abstractPropertyHolder[$property_name];
	    }
	}
	
	public function setProperty($property_name, $value){
	    $property_name = SmartestStringHelper::toVarName($property_name);
	    $this->_abstractPropertyHolder[$property_name] = $value;
	}
	
	public function getVariable($varName, $searchParents = true, $errorEnable = true){
        return parent::getVariable($varName, $searchParents, $errorEnable);
	}

    public function getSmartestVariable($varName){

        if(isset($this->_tpl_vars[$varName])){
            return $this->_tpl_vars[$varName];
        }

        $variable = parent::getVariable($varName, true, false);

        if(method_exists($variable, 'getValue')){
            return $variable->getValue();
        }

        return null;

    }
	
	public function run($template, $data){
	    
	    if(!is_array($data) && !($data instanceof SmartestParameterHolder)){
	        $data = array('data'=>$data);
	        if(isset($this->draft_mode) && $this->draft_mode){
	            echo '<br />NOTICE: $data should be and array or SmartestParameterHolder object.';
	        }
	    }
	    
	    if($data instanceof SmartestParameterHolder){
	        $data = $data->getParameters();
	    }
	    
	    if(file_exists($template)){
	        try{
	            echo $this->_smarty_include(array('smarty_include_tpl_file'=>$template, 'smarty_include_vars'=>$data));
	        }catch(Throwable $e){
	            echo $this->handleSmartyThrowable($e, $template);
	        }
        }else{
            echo '<br />ERROR: Template \''.$template.'\' does not exist.';
        }
	}
	
	protected function _log($message){
	    $this->_log[] = $message;
	}
	
	protected function _comment($message){
	    $message = str_replace('-->', '', $message);
	    $this->_log($message);
	    return "<!-- SmartestEngine Message: ".$message." -->\n";
	}
	
	public function raiseError($error_msg='Unknown Template Error'){
	    
	    $this->_log($error_msg);
	    
	    if($this->getDraftMode()){
	        $this->assign('_error_text', $error_msg);
	        $error_markup = $this->fetch(SM_ROOT_DIR."System/Presentation/WebPageBuilder/markup_error.tpl");
	        return $error_markup;
        }
	}
	
	public function evaluate($string, $compile_name=null){
	    
	    // create resource name
	    if($compile_name){
	        $resource_name = $compile_name;
        }else{
            $resource_name = sha1($string);
        }
        
        $this->syncSmartestTemplateVars();

        try{
            return parent::fetch('string:'.$string, null, $resource_name);
        }catch(Throwable $e){
            return $this->handleSmartyThrowable($e, 'string:'.$resource_name);
        }
        
	}

    protected function handleSmartyThrowable(Throwable $e, $template=null){

        $template_label = is_string($template) && strlen($template) ? $template : '[unknown template]';
        $message = get_class($e).' while rendering '.$template_label.': '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();

        SmartestResponse::debugTrace('Smarty error: '.$message);
        error_log($message);

        if($this->displayErrorsAreEnabled()){
            return $this->renderSmartyError($e, $template_label);
        }

        return '';

    }

    protected function displayErrorsAreEnabled(){

        $setting = ini_get('display_errors');

        if(is_bool($setting)){
            return $setting;
        }

        return in_array(strtolower((string) $setting), array('1', 'on', 'true', 'yes'), true);

    }

    protected function renderSmartyError(Throwable $e, $template_label){

        $escape = function($value){
            return htmlspecialchars((string) $value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        };

        return '<div style="box-sizing:border-box;margin:16px;padding:14px;border:2px solid #b00020;background:#fff7f7;color:#2b1b1b;font:14px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;text-align:left;">'
            .'<h2 style="margin:0 0 8px;font-size:18px;color:#b00020;">Smarty template error</h2>'
            .'<p style="margin:0 0 8px;"><strong>'.$escape(get_class($e)).'</strong>: '.$escape($e->getMessage()).'</p>'
            .'<p style="margin:0 0 8px;"><strong>Template:</strong> <code>'.$escape($template_label).'</code></p>'
            .'<p style="margin:0 0 8px;"><strong>Thrown at:</strong> <code>'.$escape($e->getFile().':'.$e->getLine()).'</code></p>'
            .'<details style="margin-top:10px;"><summary style="cursor:pointer;">Stack trace</summary><pre style="white-space:pre-wrap;overflow:auto;margin:8px 0 0;padding:10px;background:#2b1b1b;color:#fff;font:12px/1.4 SFMono-Regular,Consolas,monospace;">'.$escape($e->getTraceAsString()).'</pre></details>'
            .'</div>';

    }
	
	public function getScriptIncluded($script_file){
	    return in_array($script_file, $this->_included_scripts);
	}
	
	public function setScriptIncluded($script_file){
	    $this->_included_scripts[] = $script_file;
	}
	
	public function getStylesheetIncluded($file){
	    return in_array($file, $this->_included_stylesheets);
	}
	
	public function setStylesheetIncluded($file){
	    $this->_included_stylesheets[] = $file;
	}
	
	public function addPluginDirectory($directory){
	    
	    $real_directory = realpath($directory);

	    if(!$real_directory){
	        return false;
	    }
	    
	    $directory = $real_directory.'/';
	    
	    if(is_dir($directory)){
	        if(SmartestFileSystemHelper::isSafeFileName($directory)){
	            $this->plugins_dir[] = $directory;
	            $this->registerSmartestPluginDirectory($directory);
	        }else{
	            throw new SmartestException("Tried to add plugin directory outside Smartest: ".$directory, SM_ERROR_USER);
	        }
	    }else{
	        throw new SmartestException("Tried to add non-existent plugin directory: ".$directory, SM_ERROR_USER);
	    }
	}

    protected function registerSmartestPluginDirectory($directory){

        if(isset($this->_registered_smartest_plugin_dirs[$directory])){
            return;
        }

        $this->_registered_smartest_plugin_dirs[$directory] = true;

        foreach(array('function', 'block', 'modifier', 'compiler') as $type){
            foreach(glob($directory.$type.'.*.php') as $file){
                if($type == 'compiler' && basename($file) == 'compiler.defun.php'){
                    continue;
                }

                SmartestResponse::debugTrace('SmartestEngine::registerPluginFile '.$file);
                $plugin_name = substr(basename($file, '.php'), strlen($type) + 1);
                $function_name = 'smarty_'.$type.'_'.str_replace('.', '_', $plugin_name);
                $directory_key = strtolower(preg_replace('/\W+/', '', basename(rtrim($directory, '/'))));
                $specific_function_name = 'smarty_'.$type.'_'.$directory_key.'_'.str_replace('.', '_', $plugin_name);
                $require_plugin_file = true;

                if(function_exists($function_name) && !function_exists($specific_function_name)){
                    $file_contents = file_get_contents($file);
                    if(strpos($file_contents, 'function '.$specific_function_name.'(') === false){
                        SmartestResponse::debugTrace('SmartestEngine::registerPluginFile skipped duplicate callback '.$function_name.' in '.$file);
                        $require_plugin_file = false;
                    }
                }

                if($require_plugin_file){
                    require_once $file;
                }

                if(function_exists($specific_function_name)){
                    $function_name = $specific_function_name;
                }

                if(!function_exists($function_name)){
                    continue;
                }

                switch($type){
                    case 'function':
                        $engine = $this;
                        $this->registerPlugin(self::PLUGIN_FUNCTION, $plugin_name, function($params, \Smarty\Template $template) use ($function_name, $engine){
                            return $function_name($params, $engine);
                        });
                        break;

                    case 'block':
                        $engine = $this;
                        $this->registerPlugin(self::PLUGIN_BLOCK, $plugin_name, function($params, $content, \Smarty\Template $template, &$repeat) use ($function_name, $engine){
                            return $function_name($params, $content, $engine, $repeat);
                        });
                        break;

                    case 'modifier':
                        $this->registerPlugin(self::PLUGIN_MODIFIER, $plugin_name, $function_name);
                        break;

                    case 'compiler':
                        $this->registerPlugin(self::PLUGIN_COMPILER, $plugin_name, $function_name);
                        break;
                }
            }
        }

    }
	
	public function getPluginDirectories(){
	    
	    return $this->plugins_dir;
	    
	}
	
	public function initNumberSeriesByName($series_name){
	    if(isset($this->_series[$series_name])){
	        return $this->_series[$series_name];
	    }else{
	        $this->_series[$series_name] = new SmartestNumberSeries;
	        $this->_series[$series_name]->setName($series_name);
	        return $this->_series[$series_name];
	    }
	}
	
	public function getInitializedVariableNames(){
	    return array_keys($this->_tpl_vars);
	}
    
    public function isWebsitePage(){
	    $sd = SmartestYamlHelper::fastLoad(SmartestInfo::$system_info_file);
		$websiteMethodNames = $sd['system']['content_interaction_methods'];
		$method = $this->_request_data->g('module').'/'.$this->_request_data->g('action');
		return in_array($method, $websiteMethodNames);
    }
    
    protected function getSite(){
        
        $rh = new SmartestRequestUrlHelper;
        
        if(isset($GLOBALS['_site'])){
            return $GLOBALS['_site'];
        }elseif($this->isWebsitePage() && $site = $rh->getSiteByDomain(SmartestStringHelper::toValidDomain($_SERVER['HTTP_HOST']))){
            return $site;
        }elseif(is_object(SmartestSession::get('current_open_project')) && !$this->isWebsitePage()){
            return SmartestSession::get('current_open_project');
        }else{
            return null;
        }
    }

}
