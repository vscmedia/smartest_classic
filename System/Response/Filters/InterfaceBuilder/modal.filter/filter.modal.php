<?php

function smartest_filter_modal($html, $filter){
    
    if(SmartestPersistentObject::get('request_data')->getParameter('namespace') == "modal"){

        $html = str_replace('id="work-area"', 'id="modal-work-area"', $html);
        $html = str_replace('id="actions-area"', 'id="modal-actions-area"', $html);
        return $html;
        
    }else{
        return $html;
    }
    
}
