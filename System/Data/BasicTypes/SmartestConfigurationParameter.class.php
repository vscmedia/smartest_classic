<?php

class SmartestConfigurationParameter implements ArrayAccess{

    protected $_name = '';
    protected $_label = '';
    protected $_type_code = '';
    protected $_default = null;
    protected $_options = null;
    protected $_required = false;
    protected $_hint = '';
    protected $_form_unique_id = '';
    protected $_outer_name = '';

    public function __construct($name, $info){

        if(!is_string($name) || !strlen($name)){
            throw new SmartestException('SmartestConfigurationParameter::__construct() must be instantiated with a valid string name.');
        }

        if(!is_array($info) || !isset($info['datatype'])){
            throw new SmartestException('SmartestConfigurationParameter::__construct() requires an info array with a "datatype" key.');
        }

        $this->_name = SmartestStringHelper::toVarName($name);
        $this->_form_unique_id = SmartestStringHelper::toSlug($name).'-'.SmartestStringHelper::randomFromFormat('RRRRRR');
        $this->_type_code = $info['datatype'];

        if(isset($info['label'])){
            $this->_label = $info['label'];
        }

        if(isset($info['hint'])){
            $this->_hint = $info['hint'];
        }

        if(array_key_exists('default', $info)){
            $this->_default = SmartestDataUtility::objectize($info['default'], $info['datatype']);
            if(!$this->_default){
                $this->_default = $info['default'];
            }
        }

        if(isset($info['required'])){
            $this->_required = SmartestStringHelper::toRealBool($info['required']);
        }

        if(isset($info['options'])){
            $this->_options = new SmartestFixedOptionsList($info['options'], $this->_type_code);
        }
    }

    public function setFormOuterName($n){
        $this->_outer_name = SmartestStringHelper::toVarName($n);
    }

    public function getName(){
        return $this->_name;
    }

    public function isRequired(){
        return (bool) $this->_required;
    }

    public function getDatatype(){
        return $this->_type_code;
    }

    public function getDefault(){
        return $this->_default;
    }

    public function getDefaultForForm(){
        return self::normalizeValueForForm($this->_default);
    }

    public function offsetExists(mixed $offset): bool{
        return in_array($offset, array('name', 'id', 'form_name', 'label', 'required', 'datatype', 'type', 'default', 'hint', 'options', 'has_options', 'form_unique_id'));
    }

    public function offsetSet(mixed $offset, mixed $value): void{}

    public function offsetUnset(mixed $offset): void{}

    public function offsetGet(mixed $offset): mixed{

        switch($offset){

            case 'name':
            case 'id':
            return $this->_name;

            case 'form_name':
            if($this->_outer_name){
                return $this->_outer_name.'['.$this->_name.']';
            }
            return $this->_name;

            case 'label':
            return strlen($this->_label) ? $this->_label : $this->_name;

            case 'required':
            return $this->_required;

            case 'datatype':
            case 'type':
            return $this->_type_code;

            case 'default':
            return $this->getDefaultForForm();

            case 'hint':
            return $this->_hint;

            case 'options':
            return $this->_options;

            case 'has_options':
            return $this->_options instanceof SmartestFixedOptionsList;

            case 'form_unique_id':
            return $this->_form_unique_id;
        }

        return null;
    }

    protected static function normalizeValueForForm($value){

        if(is_object($value) && $value instanceof SmartestStorableValue){
            return $value->getStorableFormat();
        }

        if(is_object($value) && method_exists($value, 'getValue')){
            return $value->getValue();
        }

        if(is_object($value) && method_exists($value, '__toString')){
            return (string) $value;
        }

        return $value;
    }

}
