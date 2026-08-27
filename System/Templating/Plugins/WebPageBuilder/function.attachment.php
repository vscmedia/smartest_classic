<?php

function smarty_function_attachment($params, &$smartest_engine){

    if(isset($params['system']) && SmartestStringHelper::toRealBool($params['system']) && isset($params['format']) && in_array(strtolower($params['format']), array('textile', 'markdown'))){
        return '<!--'.SmartestTextAssetRenderPipeline::ATTACHMENT_TOKEN_PREFIX.':'.$params['name'].'-->';
    }

    return $smartest_engine->renderAttachment($params['name']);

}
