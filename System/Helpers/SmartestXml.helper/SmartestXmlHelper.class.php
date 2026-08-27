<?php

/**
 * undocumented class
 *
 * @package Smartest
 * @subpackage XmlHelper
 * @author Marcus Gilroy-Ware
 **/

SmartestHelper::register('Xml');

class SmartestXmlHelper extends SmartestHelper{

    protected static function elementToArray(SimpleXMLElement $element){

        $data = array();

        foreach($element->attributes() as $key => $value){
            $data[str_replace('-', '_', (string) $key)] = (string) $value;
        }

        $has_children = false;

        foreach($element->children() as $name => $child){

            $has_children = true;
            $key = str_replace('-', '_', (string) $name);
            $value = self::elementToArray($child);

            if(array_key_exists($key, $data)){
                if(!is_array($data[$key]) || !array_key_exists(0, $data[$key])){
                    $data[$key] = array($data[$key]);
                }
                $data[$key][] = $value;
            }else{
                $data[$key] = $value;
            }
        }

        $text = trim((string) $element);

        if($has_children || count($data)){
            if(strlen($text)){
                $data['_content'] = $text;
            }
            return $data;
        }

        return $text;
    }
	
    static function loadFile($filename){
		
		if(is_file($filename)){

		    $xml = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_NOCDATA);

		    if($xml instanceof SimpleXMLElement){
		        return self::elementToArray($xml);
            }else{
                throw new SmartestException('Couldn\'t parse XML file: '.$filename.'.');
            }
		}else{
			// ERROR: File does not exist
			// echo 'no such file';
		}
	}
	
	static function loadString($string){
		
		if(strlen($string)){

		    $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);

		    if($xml instanceof SimpleXMLElement){
		        return self::elementToArray($xml);
            }else{
                return false;
            }
		}else{
			// ERROR: File does not exist
			// echo 'no such file';
		}
	}
	
} // END class
