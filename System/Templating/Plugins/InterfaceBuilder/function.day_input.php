<?php

function smarty_function_day_input($params, &$smartest_engine){
    
    if(isset($params['name'])){
        
        $input = new SmartestParameterHolder('Input Parameters: '.$params['name']);
        $input->setParameter('name', $params['name']);
        
        if(isset($params['id'])){
            $input->setParameter('id', $params['id']);
        }else{
            $input->setParameter('id', SmartestStringHelper::toSlug($params['name']));
        }
        
        if(isset($params['value'])){
            if($params['value'] instanceof SmartestDateTime){
                $input->setParameter('value', $params['value']);
            }elseif($params['value'] === null || $params['value'] === ''){
                $input->setParameter('value', new SmartestDateTime(SmartestDateTime::NEVER));
            }else{
                $input->setParameter('value', new SmartestDateTime($params['value']));
            }
        }else{
            $input->setParameter('value', new SmartestDateTime(SmartestDateTime::NEVER));
        }
        
        // TODO: fill these defaults in properly
        $input->setParameter('default_day', date("d"));
        $input->setParameter('default_month', date("m"));
        $input->setParameter('default_year', date("Y"));
        
        $smartest_engine->assign('_input_data', $input);
        return $smartest_engine->_smarty_include(array(
            'smarty_include_tpl_file' => SM_ROOT_DIR.'System/Presentation/InterfaceBuilder/Inputs/date.tpl',
            'smarty_include_vars' => $input->getParameters()
        ));
        
    }else{
        
    }
    
}
