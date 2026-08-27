<?php

class SmartestDynamicStylesheetCompiler{

    protected $_warnings = array();

    public function compileAsset(SmartestAsset $asset, $site=null, $page=null, $draft=false, $domain=''){

        $type_info = $asset->getTypeInfo();
        $source = $asset->getContent(true);
        $context = $this->buildContext($source, $site, $page, $draft, $domain);
        $prepared_source = $context['header'].$source;
        $hash = md5($prepared_source);
        $filename = $this->getCacheFilename($asset, $context, $hash, $site);
        $cache_dir = isset($type_info['storage']['live_cache']) ? $type_info['storage']['live_cache'] : 'Public/Resources/System/Cache/CSS/';
        $cache_path = SM_ROOT_DIR.$cache_dir.$filename;
        $cache_web_path = str_replace('Public/', '', $cache_dir).$filename;
        $previous_error_reporting = error_reporting();
        $css = '';
        $error = null;

        try{
            error_reporting($previous_error_reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING & ~E_NOTICE);
            $this->ensureCompilerLoaded();
            $scss = new ScssPhp\ScssPhp\Compiler();
            $scss->setImportPaths(SM_ROOT_DIR.'Public/Resources/Stylesheets/');
            $css = $scss->compileString($prepared_source)->getCss();
            SmartestFileSystemHelper::save($cache_path, $css, true);
        }catch(Throwable $e){
            $error = $e->getMessage();
            $css = "/** SCSS Error: ".$error." **/";
        }

        error_reporting($previous_error_reporting);

        return array(
            'ok' => $error === null,
            'css' => $css,
            'error' => $error,
            'warnings' => $this->_warnings,
            'source' => $source,
            'prepared_source' => $prepared_source,
            'cache_filename' => $filename,
            'cache_path' => $cache_path,
            'cache_web_path' => $cache_web_path,
            'dependencies' => $context['dependencies'],
            'page' => $context['page'],
        );

    }

    protected function ensureCompilerLoaded(){

        if(!class_exists('ScssPhp\ScssPhp\Compiler')){
            require_once SM_ROOT_DIR.'System/Library/vendor/autoload.php';
        }

    }

    protected function buildContext($source, $site=null, $page=null, $draft=false, $domain=''){

        $this->_warnings = array();
        $dependencies = $this->inspectDependencies($source);

        if(!$page instanceof SmartestPage && $site instanceof SmartestSite && ($dependencies['uses_page_fields'] || $dependencies['uses_placeholders'])){
            $page = $site->getHomePage($draft);
        }

        if($page instanceof SmartestPage){
            $page->setDraftMode($draft);
        }

        $header = '';
        $header .= '$sm_domain: '.$this->quoteScssString($domain).";\n";
        $header .= '$sm_url_base: '.$this->quoteScssString($domain).";\n";

        if($site instanceof SmartestSite){
            foreach($site->getGlobalFields($draft) as $field_name => $global_field_value){
                $safe_name = SmartestStringHelper::toVarName($field_name);
                $literal = $this->formatScssLiteral($global_field_value, $safe_name);
                $header .= '$field_'.$safe_name.': '.$literal.";\n";
                $header .= '$sm-site-field-'.$safe_name.': '.$literal.";\n";
            }
        }

        foreach($dependencies['page_fields'] as $field_name){
            $literal = $this->getPageFieldLiteral($page, $field_name);
            $header .= '$sm-page-field-'.$field_name.': '.$literal.";\n";
        }

        foreach($dependencies['placeholder_urls'] as $placeholder_name){
            $literal = $this->getPlaceholderUrlLiteral($page, $placeholder_name, $draft);
            $header .= '$sm-placeholder-'.$placeholder_name.'-url: '.$literal.";\n";
        }

        return array(
            'header' => $header."\n",
            'dependencies' => $dependencies,
            'page' => $page,
        );

    }

    protected function inspectDependencies($source){

        $page_fields = array();
        $placeholder_urls = array();

        if(preg_match_all('/\$sm-page-field-([A-Za-z0-9_]+)/', $source, $matches)){
            $page_fields = array_values(array_unique(array_map(array('SmartestStringHelper', 'toVarName'), $matches[1])));
        }

        if(preg_match_all('/\$sm-placeholder-([A-Za-z0-9_]+)-url/', $source, $matches)){
            $placeholder_urls = array_values(array_unique(array_map(array('SmartestStringHelper', 'toVarName'), $matches[1])));
        }

        return array(
            'page_fields' => $page_fields,
            'placeholder_urls' => $placeholder_urls,
            'uses_page_fields' => count($page_fields) > 0,
            'uses_placeholders' => count($placeholder_urls) > 0,
            'uses_site_fields' => (bool) preg_match('/\$sm-site-field-|\$field_/', $source),
        );

    }

    protected function getPageFieldLiteral($page, $field_name){

        if($page instanceof SmartestPage){
            $fields = $page->getPageFieldDefinitions();

            if($fields instanceof SmartestParameterHolder && $fields->hasParameter($field_name)){
                return $this->formatScssLiteral($fields->getParameter($field_name), $field_name);
            }
        }

        $this->_warnings[] = "Page field '".$field_name."' was not available; a fallback value was used.";
        return $this->defaultLiteralForName($field_name);

    }

    protected function getPlaceholderUrlLiteral($page, $placeholder_name, $draft){

        if($page instanceof SmartestPage){
            $definition = $page->getPlaceholderDefinition($placeholder_name, 'default');

            if($definition instanceof SmartestPlaceholderDefinition && $definition->hasAsset($draft)){
                $asset = $definition->getAsset($draft);

                if($asset instanceof SmartestAsset && strlen((string) $asset->getFullWebPath())){
                    return $this->quoteScssString($asset->getFullWebPath());
                }
            }
        }

        $this->_warnings[] = "Placeholder '".$placeholder_name."' did not have an image asset; an empty URL was used.";
        return $this->quoteScssString('');

    }

    protected function getCacheFilename(SmartestAsset $asset, $context, $hash, $site=null){

        $parts = array();

        if($site instanceof SmartestSite && !$asset->getShared()){
            $parts[] = 'site'.$site->getId();
        }else{
            $parts[] = 'shared';
        }

        if(($context['dependencies']['uses_page_fields'] || $context['dependencies']['uses_placeholders']) && $context['page'] instanceof SmartestPage){
            $parts[] = 'page'.$context['page']->getId();
        }else{
            $parts[] = 'global';
        }

        $parts[] = 'asset'.$asset->getId();
        $parts[] = substr($hash, 0, 16);

        return implode('_', $parts).'.css';

    }

    protected function formatScssLiteral($value, $name=''){

        if($value instanceof SmartestRgbColor){
            return '#'.(string) $value;
        }

        $string = trim((string) $value);

        if(!strlen($string)){
            return $this->defaultLiteralForName($name);
        }

        if(is_numeric($string)){
            return $string;
        }

        if(in_array(strtolower($string), array('true', 'false', 'null'))){
            return strtolower($string);
        }

        if(preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $string)){
            return $string;
        }

        return $this->quoteScssString($string);

    }

    protected function defaultLiteralForName($name){

        if(preg_match('/colou?r/i', $name)){
            return 'transparent';
        }

        if(preg_match('/url$/i', $name)){
            return $this->quoteScssString('');
        }

        return 'null';

    }

    protected function quoteScssString($value){
        return '"'.str_replace(array('\\', '"', "\r", "\n"), array('\\\\', '\\"', '', '\\A '), (string) $value).'"';
    }

}
