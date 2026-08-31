<?php

class SmartestUnimportedTemplate implements ArrayAccess{
    
    protected $_file;
    protected $_probable_asset_type;
    protected $_probable_template_type;
    protected $_sites = array();
    protected $database;
    
    public function __construct($filename){
        
        $this->_file = new SmartestFile;
        $this->_file->loadFile($filename);
        $this->database = SmartestDatabase::getInstance('SMARTEST');
        $this->calculateProbableTypes();
        
    }
    
    public function __toString(){
        return $this->getUrl();
    }
    
    private function calculateProbableTypes(){
        
        $h = new SmartestTemplatesLibraryHelper;
        $types = $h->getTypes();
        $root_dir_length = strlen(SM_ROOT_DIR);
        
        foreach($types as $t){
            $test_location = $t['storage']['location'];
            $actual_location = SmartestFileSystemHelper::dirName(substr($this->_file->getPath(), $root_dir_length));
            if($test_location == $actual_location){
                $this->_probable_asset_type = $t['id'];
                if(isset($t['template_type'])){
                    $this->_probable_template_type = $t['template_type'];
                }
            }
        }
        
    }
    
    public function getSitesWhereUsed($type='guess'){
        
        if($type=='guess'){
            $type = $this->getProbableAssetType();
        }
        
        if(!isset($this->_sites[$type]) || !is_array($this->_sites[$type]) || !count($this->_sites[$type])){
        
            $base_name = SmartestFileSystemHelper::baseName($this->_file->getPath());
            $this->_sites[$type] = array();
            $result = array();
        
            switch($type){
                case "SM_ASSETTYPE_MASTER_TEMPLATE":
                    $sql = "SELECT DISTINCT Sites.*, page_site_id FROM Pages, Sites WHERE (page_draft_template='".$base_name."' OR page_live_template='".$base_name."') AND Pages.page_site_id=Sites.site_id";
                    $result = $this->database->queryToArray($sql);
                    break;
                case "SM_ASSETTYPE_COMPOUND_LIST_TEMPLATE":
                    $sql = "SELECT DISTINCT Sites.*, page_site_id, list_page_id FROM Pages, Sites, Lists WHERE (list_draft_template_file='".$base_name."' OR list_live_template_file='".$base_name."') AND Lists.list_type='SM_LIST_SIMPLE' AND Lists.list_page_id=Pages.page_site_id AND Pages.page_site_id=Sites.site_id";
                    $result = $this->database->queryToArray($sql);
                    break;
                case "SM_ASSETTYPE_ART_LIST_TEMPLATE":
                    $sql = "SELECT DISTINCT Sites.*, page_site_id, list_page_id FROM Pages, Sites, Lists WHERE ((list_draft_template_file='".$base_name."' OR list_live_template_file='".$base_name."') OR (list_draft_header_template='".$base_name."' OR list_live_header_template='".$base_name."') OR (list_draft_footer_template='".$base_name."' OR list_live_footer_template='".$base_name."')) AND Lists.list_type='SM_LIST_ARTICULAED' AND Lists.list_page_id=Pages.page_site_id AND Pages.page_site_id=Sites.site_id";
                    $result = $this->database->queryToArray($sql);
                    break;
                case "SM_ASSETTYPE_CONTAINER_TEMPLATE":
                    // Unimported container templates cannot be used, and this has not been possible in any prior versions
                    return array();
            }
        
            if(is_array($result)){
                foreach($result as $rs){
                    $s = new SmartestSite;
                    $s->hydrate($rs);
                    $this->_sites[$type][] = $s;
                }
            }
        
        }
        
        return $this->_sites[$type];
        
    }
    
    public function getSiteIdsWhereUsed($type='guess'){
        
        $sites = $this->getSitesWhereUsed($type);
        $ids = array();
        
        foreach($sites as $site){
            $ids[$site->getId()] = 1;
        }
        
        return array_keys($ids);
        
    }
    
    public function isInUseOnMultipleSites($type='guess'){
        
        return (count($this->getSitesWhereUsed($type)) > 1);
        
    }
    
    public function getProbableTemplateType(){
        return $this->_probable_template_type;
    }
    
    public function getProbableAssetType(){
        return $this->_probable_asset_type;
    }
    
    public function getStorageLocation($include_smartest_root=false){
        $root = $include_smartest_root ? SM_ROOT_DIR : null;
        return $root.SmartestFileSystemHelper::dirName($this->_file->getSmartestPath());
    }
    
    public function getMentionedCSSClasses(){
        
	    $regex1 = '/\bclass\s*=\s*(["\'])([\w\s_-]+)\1/mi';
	    $result = preg_match_all($regex1, $this->getContent(), $matches1);
        
        if(isset($matches1[2])){
            $classes = array();
            foreach($matches1[2] as $raw_value){
                $class_names = preg_split('/\s+/', trim($raw_value));
                if(count($class_names) > 1){
                    $classes[] = implode('.', $class_names);
                }
                foreach($class_names as $class){
                    $classes[] = $class;
                }
            }
            return $classes;
        }else{
            return array();
        }
        
    }
    
    public function getMentionedCSSIds(){
        
	    $regex1 = '/\bid\s*=\s*(["\'])([\w_-]+)\1/mi';
	    $result = preg_match_all($regex1, $this->getContent(), $matches1);
        
        if(isset($matches1[2])){
            $ids = array();
            foreach($matches1[2] as $id){
                $ids[] = $id;
            }
            return $ids;
        }else{
            return array();
        }
        
    }
    
    public function getMentionedCSSTokens(){
        
        $tokens = array();
        
        foreach($this->getMentionedCSSClasses() as $class){
            $tokens[$class] = 'class';
        }
        
        foreach($this->getMentionedCSSIds() as $id){
            $tokens[$id] = 'id';
        }
        
        return $tokens;
        
    }

    public function getReferencedStylesheetFilenames(){

        $files = array();
        $content = $this->getContent();

        if(preg_match_all('/<link\b[^>]*>/mi', $content, $link_matches)){
            foreach($link_matches[0] as $tag){
                if($this->htmlTagAttributeEquals($tag, 'rel', 'stylesheet') && preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/mi', $tag, $href_match)){
                    if($file = $this->normaliseStylesheetReference($href_match[2])){
                        $files[] = $file;
                    }
                }
            }
        }

        if(preg_match_all('/<\?sm:stylesheet\b(.*?)(?:\:)?\?>/mis', $content, $tag_matches)){
            foreach($tag_matches[1] as $attribute_string){
                if(preg_match('/\bfile\s*=\s*(["\'])(.*?)\1/mi', $attribute_string, $file_match)){
                    if($file = $this->normaliseStylesheetReference($file_match[2])){
                        $files[] = $file;
                    }
                }
            }
        }

        return array_values(array_unique($files));

    }

    protected function htmlTagAttributeEquals($tag, $attribute, $expected_value){

        if(preg_match('/\b'.preg_quote($attribute, '/').'\s*=\s*(["\'])(.*?)\1/mi', $tag, $match)){
            return strtolower(trim($match[2])) == strtolower($expected_value);
        }

        return false;

    }

    protected function normaliseStylesheetReference($reference){

        $reference = html_entity_decode(trim($reference), ENT_QUOTES, 'UTF-8');

        if(!strlen($reference)){
            return null;
        }

        if(($query_pos = strpos($reference, '?')) !== false){
            $reference = substr($reference, 0, $query_pos);
        }

        if(preg_match('/Resources\/Stylesheets\/(.+)$/i', $reference, $matches)){
            $reference = $matches[1];
        }else if(preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $reference)){
            return null;
        }else{
            $reference = ltrim($reference, '/');
        }

        return $reference;

    }
    
    public function scanStylesheetsForMentions($site_id=null){
        
        $alh = new SmartestAssetsLibraryHelper;
        $tokens = $this->getMentionedCSSTokens();
        
        $stylesheets = $alh->getAssetsByTypeCode($this->getStylesheetAssetTypeCodes(), $site_id);
        $relevant_stylesheets = array();
        $ids = array();
        
        foreach($stylesheets as $stylesheet){
            $content = $stylesheet->getContent();
            foreach($tokens as $name=>$type){
                if($type == 'id'){
                    if($this->stylesheetMentionsToken($content, $name, 'id')){
                        // echo 'found ID \''.$name.'\' in stylesheet '.$stylesheet->getUrl().'<br />';
                        if(!in_array($stylesheet->getId(), $ids)){
                            $relevant_stylesheets[] = $stylesheet;
                            $ids[] = $stylesheet->getId();
                        }
                    }
                }elseif($type == 'class'){
                    if($this->stylesheetMentionsToken($content, $name, 'class')){
                        // echo 'found class \''.$name.'\' in stylesheet '.$stylesheet->getUrl().'<br />';
                        if(!in_array($stylesheet->getId(), $ids)){
                            $relevant_stylesheets[] = $stylesheet;
                            $ids[] = $stylesheet->getId();
                        }
                    }
                }
            }
        }
        
        return $relevant_stylesheets;
        
    }

    protected function getStylesheetAssetTypeCodes(){
        return array('SM_ASSETTYPE_STYLESHEET', 'SM_ASSETTYPE_SCSS_DYNAMIC_STYLESHEET');
    }

    protected function stylesheetMentionsToken($content, $name, $type){

        $prefix = $type == 'id' ? '#' : '.';
        $selector = preg_quote($prefix.$name, '/');

        return preg_match('/(^|[^a-zA-Z0-9_-])'.$selector.'(\s*[,>{:+~.#\[]|\s*\{)/m', $content) === 1;

    }
    
    public function getContent(){
        return $this->_file->getContent();
    }
    
    public function getContentForEditor(){
	    return htmlentities($this->getContent(), ENT_COMPAT, 'UTF-8');
	}
	
	public function setContent($content){
	    return $this->_file->setContent($content, true);
	}
	
	public function getUrl(){
	    return $this->_file->getFileName();
	}
	
	public function getFullPathOnDisk(){
	    return $this->_file->getPath();
	}
	
	public function delete(){
	    return $this->_file->delete();
	}
	
	public function getLabel(){
	    return $this->_file->getFileName();
	}
    
    public function offsetGet($offset){
        switch($offset){
            case "url":
            return $this->getUrl();
            case "status":
            return 'unimported';
            case "php_class":
            return 'SmartestUnimportedTemplate';
            case "type":
            case "asset_type":
            return $this->_probable_asset_type;
            case "template_type":
            return $this->_probable_template_type;
            case "size":
            return $this->_file->getSize();
            case "raw_size":
            return $this->_file->getSize(false);
            case "file_path":
            return $this->_file->getSmartestPath();
            case "full_path":
            return $this->_file->getPath();
            case "suggested_name":
            return SmartestStringHelper::removeDotSuffix($this->_file->getFileName());
            case "storage_location":
            return $this->getStorageLocation();
            case "force_shared":
            case "multiple_sites":
            return $this->isInUseOnMultipleSites();
        }
    }
    
    public function offsetSet($offset, $value){}
    public function offsetUnset($offset){}
    public function offsetExists($offset){}

}
