<?php

class SmartestRenderableString extends SmartestString{

    const FORMAT_PLAIN = 'plain';
    const FORMAT_MARKDOWN = 'markdown';
    const FORMAT_TEXTILE = 'textile';

    protected $_render_format = self::FORMAT_PLAIN;

    public function __construct($string='', $format=self::FORMAT_PLAIN){
        parent::__construct($string);
        $this->setRenderFormat($format);
    }

    public static function getRenderFormatOptions(){
        return array(
            self::FORMAT_PLAIN => array(
                'id' => self::FORMAT_PLAIN,
                'label' => 'Plain text',
                'description' => 'Line breaks and text are preserved without parsing formatting syntax.'
            ),
            self::FORMAT_MARKDOWN => array(
                'id' => self::FORMAT_MARKDOWN,
                'label' => 'Markdown',
                'description' => 'Markdown formatting is parsed when the value is rendered.'
            ),
            self::FORMAT_TEXTILE => array(
                'id' => self::FORMAT_TEXTILE,
                'label' => 'Textile',
                'description' => 'Textile formatting is parsed when the value is rendered.'
            )
        );
    }

    public static function normalizeRenderFormat($format){
        $format = SmartestStringHelper::toVarName((string) $format);

        switch($format){
            case 'md':
                $format = self::FORMAT_MARKDOWN;
                break;
            case 'txt':
            case 'text':
                $format = self::FORMAT_PLAIN;
                break;
        }

        return array_key_exists($format, self::getRenderFormatOptions()) ? $format : self::FORMAT_PLAIN;
    }

    public function setRenderFormat($format){
        $this->_render_format = self::normalizeRenderFormat($format);
    }

    public function getRenderFormat(){
        return $this->_render_format;
    }

    public function getRenderFormatLabel(){
        $options = self::getRenderFormatOptions();
        return $options[$this->_render_format]['label'];
    }

    public function isFormatted(){
        return $this->_render_format != self::FORMAT_PLAIN;
    }

    public function __toString(){
        if(!$this->isFormatted()){
            return parent::__toString();
        }

        try{
            return $this->getRenderedValue();
        }catch(Throwable $e){
            if(class_exists('SmartestLog')){
                SmartestLog::getInstance('system')->log('SmartestRenderableString could not render '.$this->_render_format.' content: '.$e->getMessage(), SmartestLog::ERROR);
            }

            return parent::__toString();
        }
    }

    public function getRenderedValue($draft_mode='SM_CMS_LINK_DRAFT_MODE_AUTO'){
        switch($this->_render_format){
            case self::FORMAT_MARKDOWN:
                return $this->renderSmartestLinks($this->parseMarkdown(), $draft_mode);

            case self::FORMAT_TEXTILE:
                return $this->renderSmartestLinks($this->parseTextile(), $draft_mode);

            case self::FORMAT_PLAIN:
            default:
                return parent::__toString();
        }
    }

    protected function parseMarkdown(){
        $this->loadCompiledTextPipeline();

        if(class_exists('SmartestTextAssetRenderPipeline')){
            return SmartestTextAssetRenderPipeline::parseMarkdownContent($this->_string);
        }

        return '<p>'.nl2br(htmlspecialchars($this->_string, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';
    }

    protected function parseTextile(){
        $this->loadCompiledTextPipeline();

        if(class_exists('SmartestTextAssetRenderPipeline')){
            return SmartestTextAssetRenderPipeline::parseTextileContent($this->_string);
        }

        if(class_exists('\Netcarver\Textile\Parser')){
            $textile = new \Netcarver\Textile\Parser();
            return $textile->parse($this->_string);
        }

        return '<p>'.nl2br(htmlspecialchars($this->_string, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';
    }

    protected function loadCompiledTextPipeline(){
        if(!class_exists('SmartestTextAssetRenderPipeline') && defined('SM_ROOT_DIR') && is_file(SM_ROOT_DIR.'System/Helpers/SmartestCompiledAsset.helper/SmartestTextAssetRenderPipeline.class.php')){
            require_once SM_ROOT_DIR.'System/Helpers/SmartestCompiledAsset.helper/SmartestTextAssetRenderPipeline.class.php';
        }
    }

    protected function renderSmartestLinks($content, $draft_mode){
        $this->loadCompiledTextPipeline();

        if(class_exists('SmartestTextAssetRenderPipeline')){
            return SmartestTextAssetRenderPipeline::renderSmartestLinksInHtml($content, $draft_mode);
        }

        return $content;
    }

    public function offsetExists($offset){
        return parent::offsetExists($offset) || in_array(strtolower($offset), array('format', 'render_format', 'format_label', 'rendered', 'html', 'is_formatted', 'plain', 'markdown', 'textile'));
    }

    public function offsetGet($offset){
        switch(strtolower($offset)){
            case 'format':
            case 'render_format':
                return $this->getRenderFormat();

            case 'format_label':
                return $this->getRenderFormatLabel();

            case 'rendered':
            case 'html':
                return $this->getRenderedValue();

            case 'is_formatted':
                return $this->isFormatted();

            case 'plain':
                return $this->getValue();

            case 'markdown':
                return $this->renderSmartestLinks($this->parseMarkdown(), 'SM_CMS_LINK_DRAFT_MODE_AUTO');

            case 'textile':
                return $this->renderSmartestLinks($this->parseTextile(), 'SM_CMS_LINK_DRAFT_MODE_AUTO');
        }

        return parent::offsetGet($offset);
    }

}
