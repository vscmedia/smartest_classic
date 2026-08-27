<?php

if(defined('SM_ROOT_DIR')){
    require_once SM_ROOT_DIR.'System/Library/vendor/autoload.php';
}

class SmartestTextAssetRenderPipeline{

    const ATTACHMENT_TOKEN_PREFIX = 'SMARTEST_TEXT_ATTACHMENT';

    protected $_renderer;
    protected $_asset;
    protected $_attachments = array();

    public function __construct($asset, $renderer){
        $this->_asset = $asset;
        $this->_renderer = $renderer;

        if(is_object($asset) && method_exists($asset, 'usesTextFragment') && $asset->usesTextFragment()){
            $this->_attachments = $asset->getTextFragment()->getAttachments();
        }
    }

    public static function convertFormattedTextAttachmentTags($content, $format){
        return self::replaceOutsideRawTextLiteralBlocks($content, $format, '/\{attach:([\w_-]+)\}/', function($matches) use ($format){
            return "\n\n<?sm:attachment name=\"".$matches[1]."\" system=\"true\" format=\"".$format."\":?>\n\n";
        });
    }

    public static function extractFormattedTextAttachmentNames($content, $format){
        $attachment_names = array();

        self::replaceOutsideRawTextLiteralBlocks($content, $format, '/\{attach:([\w_-]+)\}/', function($matches) use (&$attachment_names){
            $name = SmartestStringHelper::toVarName($matches[1]);

            if(strlen($name) && !in_array($name, $attachment_names)){
                $attachment_names[] = $name;
            }

            return $matches[0];
        });

        return $attachment_names;
    }

    public function parseTextile($content){
        return $this->parseFormattedText($content, 'textile', array($this, 'parseTextileMarkup'));
    }

    public function parseMarkdown($content){
        return $this->parseFormattedText($content, 'markdown', array($this, 'parseMarkdownMarkup'));
    }

    protected function parseFormattedText($content, $format, $parser_callback){
        $literal_tokens = array();
        $content = $this->protectRawTextLiteralSmartestTokens($content, $format, $literal_tokens);
        $content = $this->normalizeAttachmentTokens($content);
        $content = call_user_func($parser_callback, $content);
        $content = $this->renderAttachmentTokens($content);
        $content = $this->renderSmartestLinks($content);

        return strtr($content, $literal_tokens);
    }

    public function parseRichText($content){
        $content = $this->renderSmartestLinks($content);

        if(stripos($content, 'NewColumn') !== false){
            $content = SmartestStringHelper::separateIntoColumns($content);
        }

        return $content;
    }

    protected function normalizeAttachmentTokens($content){
        $content = preg_replace_callback('/\{attach:([\w_-]+)\}/', function($matches){
            return '<!--'.self::ATTACHMENT_TOKEN_PREFIX.':'.$matches[1].'-->';
        }, $content);

        return preg_replace_callback('/<\?sm:attachment\b[^>]*\bname="([\w_-]+)"[^>]*:\?>/', function($matches){
            return '<!--'.self::ATTACHMENT_TOKEN_PREFIX.':'.$matches[1].'-->';
        }, $content);
    }

    protected function renderAttachmentTokens($content){
        return preg_replace_callback('/<!--\s*'.self::ATTACHMENT_TOKEN_PREFIX.':([\w_-]+)\s*-->/', function($matches){
            return $this->renderAttachment($matches[1]);
        }, $content);
    }

    protected function renderAttachment($name){
        if(!is_object($this->_renderer) || !method_exists($this->_renderer, 'renderAttachment')){
            return '';
        }

        $this->enterTextFragmentRenderContext($previous_context, $previous_asset, $previous_attachments);

        ob_start();
        try{
            $return_value = $this->_renderer->renderAttachment($name);
        }finally{
            $output = ob_get_clean();
            $this->restoreRenderContext($previous_context, $previous_asset, $previous_attachments);
        }

        if(is_string($return_value) && strlen($return_value)){
            $output .= $return_value;
        }

        return $output;
    }

    protected function enterTextFragmentRenderContext(&$previous_context, &$previous_asset, &$previous_attachments){
        $previous_context = $this->_renderer->getContext();
        $previous_asset = $this->_renderer->getProperty('asset');
        $previous_attachments = $this->_renderer->getProperty('attachments');

        $this->_renderer->setContext(SM_CONTEXT_DYNAMIC_TEXTFRAGMENT);
        $this->_renderer->setProperty('asset', $this->_asset);
        $this->_renderer->setProperty('attachments', $this->_attachments);
    }

    protected function restoreRenderContext($previous_context, $previous_asset, $previous_attachments){
        $this->_renderer->setContext($previous_context);
        $this->_renderer->setProperty('asset', $previous_asset);
        $this->_renderer->setProperty('attachments', $previous_attachments);
    }

    protected function renderSmartestLinks($content){
        $protected_blocks = array();
        $content = $this->protectLiteralHtmlBlocks($content, $protected_blocks);
        $links = SmartestLinkParser::parseEasyLinks($content);

        foreach($links as $l){
            $link = new SmartestCmsLink($l, array());
            $original = $l->getParameter('original');

            if($link->hasError()){
                $replacement = $this->_renderer->raiseError($link->getErrorMessage());
            }else{
                $replacement = $link->render($this->_renderer->getDraftMode());
            }

            $content = str_replace($original, $replacement, $content);
        }

        return strtr($content, $protected_blocks);
    }

    protected function protectLiteralHtmlBlocks($content, &$protected_blocks){
        return preg_replace_callback('/<(pre|code|script|style)\b[^>]*>.*?<\/\1>/is', function($matches) use (&$protected_blocks){
            $token = '__SMARTTEST_PROTECTED_HTML_'.count($protected_blocks).'__';
            $protected_blocks[$token] = $matches[0];
            return $token;
        }, $content);
    }

    protected function parseTextileMarkup($content){
        if(preg_match('/~~NewColumn~~/i', $content)){
            return self::parseTextileIntoColumns($content);
        }

        return self::parseTextileContent($content);
    }

    public static function parseTextileContent($content){

        $content = str_replace(' (R)', ' ®', $content);
        $content = str_replace(' (C)', ' ©', $content);

        $textile = new \Netcarver\Textile\Parser();
        $content = $textile->parse($content);
        $content = str_replace('<3', '♥', $content);

        return $content;

    }

    public static function parseTextileIntoColumns($content){

        $text = str_ireplace('~~NewColumn~~', '~~NewColumn~~', $content);
        $columns = preg_split('/~~NewColumn~~/i', $text);
        $num_columns = count($columns);

        if($num_columns > 1){

            $newtext = '';
            $column_open = '<div class="smartest-column column-width-'.$num_columns.'">';
            $last_column_open = '<div class="smartest-column column-width-'.$num_columns.' last">';
            $column_close = "</div>\n";
            $i = 1;

            foreach($columns as $c){
                if($i<$num_columns){
                    $newtext .= $column_open.self::parseTextileContent($c).$column_close;
                }else{
                    $newtext .= $last_column_open.self::parseTextileContent($c).$column_close;
                }
                ++$i;
            }

            return $newtext;

        }else{
            return self::parseTextileContent($content);
        }

    }

    protected function protectRawTextLiteralSmartestTokens($content, $format, &$literal_tokens){
        return self::replaceRawTextLiteralBlocks($content, $format, function($block) use (&$literal_tokens){
            return preg_replace_callback('/\{\s*attach:[\w_-]+\s*\}|\[\[[^\]\r\n]+\]\]|\[(\+?https?:\/\/[^\]\r\n]+|@[\w_]+:[\w_]+[^\]\r\n]*)\]/i', function($matches) use (&$literal_tokens){
                $token = 'SMARTESTPROTECTEDTEXTTOKEN'.count($literal_tokens);
                $literal_tokens[$token] = $matches[0];
                return $token;
            }, $block);
        });
    }

    protected static function replaceOutsideRawTextLiteralBlocks($content, $format, $pattern, $callback){
        return self::replaceRawTextLiteralBlocks($content, $format, function($block){
            return $block;
        }, function($content) use ($pattern, $callback){
            return preg_replace_callback($pattern, $callback, $content);
        });
    }

    protected static function replaceRawTextLiteralBlocks($content, $format, $literal_callback, $outside_callback=null){
        $literal_blocks = array();
        $pattern = self::getRawLiteralBlockPattern($format);

        $content = preg_replace_callback($pattern, function($matches) use ($literal_callback, &$literal_blocks){
            $token = 'SMARTESTRAWLITERALBLOCK'.count($literal_blocks);
            $literal_blocks[$token] = call_user_func($literal_callback, $matches[2]);
            return $matches[1].$token;
        }, $content);

        if(is_callable($outside_callback)){
            $content = call_user_func($outside_callback, $content);
        }

        return strtr($content, $literal_blocks);
    }

    protected static function getRawLiteralBlockPattern($format){
        switch($format){
            case 'markdown':
                return '/(^|\n)(```.*?```)/s';

            case 'textile':
            default:
                return '/(^|\n)((?:bc|notextile)\.\s.*(?:\n(?!\s*\n).*)*)/i';
        }
    }

    protected function parseMarkdownMarkup($content){
        if(class_exists('Parsedown')){
            $parser = new Parsedown();
            return $parser->text($content);
        }

        if(class_exists('\League\CommonMark\CommonMarkConverter')){
            $converter = new \League\CommonMark\CommonMarkConverter();
            return (string) $converter->convert($content);
        }

        $attachment_tokens = array();
        $content = $this->protectAttachmentTokens($content, $attachment_tokens);
        $content = '<p>'.nl2br(htmlspecialchars($content, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';

        return strtr($content, $attachment_tokens);
    }

    protected function protectAttachmentTokens($content, &$attachment_tokens){
        return preg_replace_callback('/<!--\s*'.self::ATTACHMENT_TOKEN_PREFIX.':([\w_-]+)\s*-->/', function($matches) use (&$attachment_tokens){
            $token = 'SMARTESTPROTECTEDATTACHMENTTOKEN'.count($attachment_tokens);
            $attachment_tokens[$token] = $matches[0];
            return $token;
        }, $content);
    }

}
