<?php

// for ref: http://hobix.com/textile/

class SmartestDataBaseStoredTextAssetToolkit{
    
    protected $_renderer;
    
    public function __construct(){
        // $this->_renderer = $renderer;
    }
    
    public function parseTextileTextAsset($content, $asset, $renderer){

        $pipeline = new SmartestTextAssetRenderPipeline($asset, $renderer);
        return $pipeline->parseTextile($content);
        
    }
    
    public function convertTextileTextAssetToSmartyFile($content, $asset){
        
        return SmartestTextAssetRenderPipeline::convertFormattedTextAttachmentTags($content, 'textile');
        
    }
    
    public function storeTextileTextAsset($raw_contents){
        
        
        
    }

    public function parseMarkdownTextAsset($content, $asset, $renderer){

        $pipeline = new SmartestTextAssetRenderPipeline($asset, $renderer);
        return $pipeline->parseMarkdown($content);

    }

    public function convertMarkdownTextAssetToSmartyFile($content, $asset){

        return SmartestTextAssetRenderPipeline::convertFormattedTextAttachmentTags($content, 'markdown');

    }

    public function storeMarkdownTextAsset($raw_contents){



    }
    
    public function parseRichTextAsset($raw_contents, $asset, $renderer){
        
        $pipeline = new SmartestTextAssetRenderPipeline($asset, $renderer);
        return $pipeline->parseRichText($raw_contents);
        
    }
    
    public function storeRichTextAsset($raw_contents){
        
        
        
    }
    
    public function parsePlainTextAsset($raw_contents, $asset, $renderer){
        
       $rd = $asset->getRenderData();
       
       if(SmartestStringHelper::toRealBool($rd['parse_urls'])){
           $content = preg_replace('/(https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/', '<a href="${1}">${1}</a>', $raw_contents);
       }else{
           $content = $raw_contents;
       }
       
       $content = str_replace('<3', '♥', $content);
       
       if(SmartestStringHelper::toRealBool($rd['convert_double_line_breaks'])){
           $content = preg_replace("/[\r\n]{2,}/", '<br /><br />', $content);
       }
       
       return $content;
        
    }
    
    public function storePlainTextAsset($raw_contents){
        
        
        
    }
    
}
