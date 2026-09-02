<?php

use QuinceController\QuinceBase;

class ArticlesAjax extends QuinceBase
{
    public function index($get = array(), $post = array())
    {
        return json_encode(array('articles' => array()));
    }
}
