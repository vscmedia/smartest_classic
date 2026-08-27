<?php

class SmartestTwitterAccountName extends SmartestString{
    
    public function setValue($v){
        
        if($v[0] == '@'){
            $this->_string = substr($v, 1);
        }else{
            $this->_string = (string) $v;
        }
        
    }
    
    public function getUrl($secure=true){
        
        return new SmartestExternalUrl('https://twitter.com/'.$this->_string);
        
    }
    
    public function offsetGet($offset){
        
        switch($offset){
            case "url":
            case "secure_url":
            return $this->getUrl();
            case "link":
            $p = new SmartestParameterHolder('Twitter account link parameters: @'.$this->_string);
            $p->setParameter('with', '@'.$this->_string);
            return SmartestCmsLinkHelper::createLink($this->getUrl(), $p)->render();
            case "secure_link":
            $p = new SmartestParameterHolder('Twitter account secure link parameters: @'.$this->_string);
            $p->setParameter('with', '@'.$this->_string);
            return SmartestCmsLinkHelper::createLink($this->getUrl(true), $p)->render();
            case "empty":
            return !strlen($this->_string);
            case "tweets_json_decoded":
            return false;
        }
        
        return parent::offsetGet($offset);
        
    }
    
    public function __toString(){
        return (string) $this->_string;
    }
    
    public function getTweetsJson(){
        return false;
    }
  
}
