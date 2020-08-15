<?php

class SmartestGeoPoint extends SmartestObject implements SmartestBasicType, SmartestStorableValue, SmartestSubmittableValue{
    
    protected $_lat = 0;
    protected $_long = 0;
    
    public function setValue($v){
        $parts = explode(',', $v);
        $this->_lat = $parts[0];
        $this->_long = $parts[1];
    }
    
    public function getValue(){
        return $this->_lat.','.$this->_long;
    }
    
    public function stdObjectOrScalar(){
        $obj = new stdClass;
        $obj->lat = (float) $this->_lat;
        $obj->long = (float) $this->_long;
        return $obj;
    }
    
    public function isPresent(){
        return (abs($this->_lat) > 0 || abs($this->_long) > 0);
    }
    
    public function hydrateFromFormData($v){
        if(is_array($v)){
            $this->_lat = $v['lat'];
            $this->_long = $v['long'];
        }else{
            $this->setValue($v);
        }
    }
    
    public function getStorableFormat(){
        return $this->getValue();
    }
    
    public function hydrateFromStorableFormat($v){
        $this->setValue($v);
    }
    
    public function isNorth(){
        return $this->_lat > 0;
    }
    
    public function isEast(){
        return $this->_long > 0;
    }
    
    public function __toString(){
        return ''.$this->getValue();
    }
    
    public function offsetGet($offset){
        
        switch($offset){
            case "latitude":
            case "lat":
            return number_format($this->_value, 2, '.', ',');
            case "longitude":
            case "long":
            return number_format($this->_value, 2, ',', '.');
            case "is_west":
            return !$this->isEast();
            case "is_north":
            return $this->isNorth();
            case "is_south":
            return !$this->isNorth();
            case "is_east":
            return $this->isEast();
        }
        
        return null;
    }
    
    public function offsetExists($offset){
        return false;
    }
    
}