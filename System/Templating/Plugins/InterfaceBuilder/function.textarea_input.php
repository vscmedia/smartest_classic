<?php

function smarty_function_textarea_input($params, &$smartest_engine){
    
    if(isset($params['name'])){
        
        $input = new SmartestParameterHolder('Input Parameters: '.$params['name']);
        $input->setParameter('name', $params['name']);
        
        if(isset($params['id'])){
            $input->setParameter('id', $params['id']);
        }else{
            $input->setParameter('id', SmartestStringHelper::toSlug($params['name']));
        }
        
        if(isset($params['value'])){
            if($params['value'] instanceof SmartestString){
                $input->setParameter('value', $params['value']);
            }else{
                $input->setParameter('value', new SmartestString($params['value']));
            }
        }else{
            $input->setParameter('value', new SmartestString(''));
        }
        
        $input->setParameter('placeholder', isset($params['placeholder']) ? new SmartestString($params['placeholder']) : null);
        $input->setParameter('form_hint', isset($params['form_hint']) ? new SmartestString($params['form_hint']) : null);
        $input->setParameter('show_hint', isset($params['form_hint']) ? (bool) strlen(trim($params['form_hint'])) : false);
        $input->setParameter('word_count', isset($params['word_count']) ? SmartestStringHelper::toRealBool($params['word_count']) : false);
        $input->setParameter('limit', isset($params['limit']) ? (int) $params['limit'] : false);
        $input->setParameter('style', isset($params['style']) ? $params['style'] : '');
        $input->setParameter('class', isset($params['class']) ? $params['class'] : '');
        $input->setParameter('data_format', isset($params['data_format']) ? SmartestRenderableString::normalizeRenderFormat($params['data_format']) : '');
        
        if(isset($params['limit'])){
            if(strlen($input->getParameter('value')) > floor(((int) $params['limit'])*0.8) && strlen($input->getParameter('value')) < (int) $params['limit']){
                $input->setParameter('limit_warning_class', 'warning');
            }elseif(strlen($input->getParameter('value')) >= (int) $params['limit']){
                $input->setParameter('limit_warning_class', 'invalid');
            }else{
                $input->setParameter('limit_warning_class', '');
            }
        }
        
        $smartest_engine->assign('_input_data', $input);
        $smartest_engine->run(SM_ROOT_DIR.'System/Presentation/InterfaceBuilder/Inputs/textarea.tpl', array());
        
    }else{
        
    }
    
}
