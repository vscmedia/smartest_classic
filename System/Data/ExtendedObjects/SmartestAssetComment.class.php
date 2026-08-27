<?php

class SmartestAssetComment extends SmartestComment{
    
    protected $_user;
    
    public function hydrate($array, $site_id='', $dup=false){
        
        parent::hydrate($array, $site_id, $dup);
        
        if(isset($array['user_id']) && is_numeric($array['user_id'])){
            $u = new SmartestSystemUser;
            $u->hydrate($array);
            $this->_user = $u;
        }
        
    }
    
    public function getAssetId(){
        return $this->getObjectId();
    }
    
    public function setAssetId($id){
        return $this->setObjectId($id);
    }
    
    public function save(){
        
        if(!$this->getType()){
            $this->setType('SM_COMMENTTYPE_ASSET_PRIVATE');
        }
        
        parent::save();
        
    }
    
    public function offsetGet($offset){
        
        switch($offset){
            case "posted_at":
            return new SmartestDateTime($this->getPostedAt());
        }
        
        return parent::offsetGet($offset);
        
    }
    
}
