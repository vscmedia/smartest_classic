<?php

function smarty_function_make_mailto($params, $smartest_engine){
    
    if(isset($params['address'])){
        if(SmartestStringHelper::isEmailAddress($params['address'])){
            
            if(isset($params['string']) && SmartestStringHelper::toRealBool($params['string'])){
                $value = 'mailto:'.$params['address'];
            }else{
                $value = new SmartestEmailAddress($params['address']);
            }
            
            if(isset($params['assign'])){
                $smartest_engine->assign(SmartestStringHelper::toVarName($params['assign']), $value);
            }else{
                return $value;
            }
            
        }else{
            return $this->raiseError("make_mailto: 'address' parameter is not valid email address.");
        }
    }else{
        return $this->raiseError("Function make_mailto must have 'address' parameter.");
    }
    
}