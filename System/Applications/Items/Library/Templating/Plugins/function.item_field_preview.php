<?php

function smarty_function_item_field_preview($params, &$smarty){
    
    if(isset($params['property'])){
      
        if(isset($params['value'])){
            $value = $params['value'];
        }else{
            return 'Error: no value supplied';
        }
        
        if(isset($params['property']) && $params['property']['datatype']){
            
            $file = 'Fields/preview.'.strtolower(substr($params['property']['datatype'], 12)).'.tpl';
            $backup_file = 'Fields/preview.default.tpl';
            
            $input_data = new SmartestParameterHolder('Preview item field '.$params['property']->getName());
            $input_data->setParameter('id', 'item_property_'.$params['property']->getId());
            $input_data->setParameter('property_id', 'item_property_'.$params['property']->getId());
            $input_data->setParameter('property', $params['property']);
            $input_data->setParameter('value', $value);
            
            if(is_file(constant('SM_CONTROLLER_MODULE_PRES_DIR').$file)){
                return $smarty->_smarty_include(array('smarty_include_tpl_file'=>constant('SM_CONTROLLER_MODULE_PRES_DIR').$file, 'smarty_include_vars'=>array('value'=>$value, 'property'=>$params['property'], '_input_data'=>$input_data)));
            }else{
                return $smarty->_smarty_include(array('smarty_include_tpl_file'=>constant('SM_CONTROLLER_MODULE_PRES_DIR').$backup_file, 'smarty_include_vars'=>array('value'=>$value, 'property'=>$params['property'], '_input_data'=>$input_data)));
            }
            
        }else{
            return 'Error: no property type';
        }
        
    }else{
        return 'Error: no property';
    }
    
}
