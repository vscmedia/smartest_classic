<?php

class SmartestTextFragmentCleaner{
    
    public static function clean($input){
        
        $output = self::convertDoubleLineBreaks($input);
        return $output;
        
    }
    
    public static function convertDoubleLineBreaks($input){
        
        $output = str_ireplace('<br /><br />', '</p>'."\n".'<p>', $input);
        return $output;
        
    }
    
}
