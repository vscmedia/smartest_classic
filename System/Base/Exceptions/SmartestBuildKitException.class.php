<?php

class SmartestBuildKitException extends SmartestException{

    protected $_buildkit_name = '';
    protected $_section_name = '';

    public function __construct($message, $code=100, $buildkit_name='', $section_name=''){
        parent::__construct($message, $code);
        $this->_buildkit_name = $buildkit_name;
        $this->_section_name = $section_name;
    }

    public function getBuildKitName(){
        return $this->_buildkit_name;
    }

    public function getSectionName(){
        return $this->_section_name;
    }

    public static function fromThrowable($message, Throwable $e, $buildkit_name='', $section_name=''){
        return new self($message.': '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine(), $e->getCode(), $buildkit_name, $section_name);
    }

}
