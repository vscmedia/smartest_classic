<?php

/**
 * Smarty plugin
 * @package Smartest CMS Smarty Plugins
 * @subpackage shared
 */

function smarty_function_cycle($params, &$smartest_engine){

    static $cycles = array();

    $process_id = is_object($smartest_engine) && method_exists($smartest_engine, 'getProcessId') ? $smartest_engine->getProcessId() : 'default';
    $name = isset($params['name']) && strlen((string) $params['name']) ? SmartestStringHelper::toVarName($params['name']) : 'default';
    $key = $process_id.':'.$name;

    $raw_values = isset($params['values']) ? $params['values'] : (isset($params['value']) ? $params['value'] : '');
    $delimiter = isset($params['delimiter']) && strlen((string) $params['delimiter']) ? (string) $params['delimiter'] : ',';

    if(is_array($raw_values)){
        $values = $raw_values;
    }else{
        $raw_values = (string) $raw_values;
        if($delimiter == ',' && strpos($raw_values, ',') === false && strpos($raw_values, '|') !== false){
            $delimiter = '|';
        }
        $values = strlen($raw_values) ? explode($delimiter, $raw_values) : array();
    }

    $values = array_values(array_map('trim', $values));

    if(!count($values)){
        return '';
    }

    $reset = isset($params['reset']) ? SmartestStringHelper::toRealBool($params['reset']) : false;

    if(!isset($cycles[$key]) || $reset){
        $cycles[$key] = 0;
    }

    $index = $cycles[$key] % count($values);
    $value = $values[$index];
    $advance = isset($params['advance']) ? SmartestStringHelper::toRealBool($params['advance']) : true;

    if($advance){
        $cycles[$key]++;
    }

    if(isset($params['assign']) && strlen((string) $params['assign'])){
        $smartest_engine->assign(SmartestStringHelper::toVarName($params['assign']), $value);
        return '';
    }

    $print = isset($params['print']) ? SmartestStringHelper::toRealBool($params['print']) : true;
    return $print ? $value : '';

}
