<?php

function smarty_block_markdown($params, $content, &$smartest_engine, &$repeat){

    if($repeat){
        return '';
    }

    if($content === null){
        return '';
    }

    if(!class_exists('SmartestTextAssetRenderPipeline') && defined('SM_ROOT_DIR') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestCompiledAsset.helper/SmartestTextAssetRenderPipeline.class.php')){
        require_once SM_ROOT_DIR.'System/Helpers/SmartestCompiledAsset.helper/SmartestTextAssetRenderPipeline.class.php';
    }

    $parse_smartest_links = !isset($params['parse_links']) || SmartestStringHelper::toRealBool($params['parse_links']);
    $content = (string) $content;

    try{
        if(class_exists('SmartestTextAssetRenderPipeline')){
            $html = SmartestTextAssetRenderPipeline::parseMarkdownContent($content);

            if($parse_smartest_links){
                $draft_mode = (is_object($smartest_engine) && method_exists($smartest_engine, 'getDraftMode')) ? $smartest_engine->getDraftMode() : 'SM_CMS_LINK_DRAFT_MODE_AUTO';
                $html = SmartestTextAssetRenderPipeline::renderSmartestLinksInHtml($html, $draft_mode, $smartest_engine);
            }
        }else{
            $html = '<p>'.nl2br(htmlspecialchars($content, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';
        }
    }catch(Throwable $e){
        if(is_object($smartest_engine) && method_exists($smartest_engine, 'raiseError')){
            return $smartest_engine->raiseError('Markdown could not be rendered: '.$e->getMessage());
        }

        error_log('Markdown could not be rendered: '.$e->getMessage());
        return '';
    }

    if(isset($params['assign']) && strlen((string) $params['assign']) && is_object($smartest_engine) && method_exists($smartest_engine, 'assign')){
        $smartest_engine->assign(SmartestStringHelper::toVarName($params['assign']), $html);
        return '';
    }

    return $html;

}
