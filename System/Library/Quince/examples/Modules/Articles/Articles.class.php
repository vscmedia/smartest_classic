<?php

use QuinceController\QuinceBase;

class Articles extends QuinceBase
{
    public function index($get = array(), $post = array())
    {
        return 'Hello from Quince';
    }

    public function view($get = array(), $post = array())
    {
        return 'Article '.$get['article_id'].' ('.$get['format'].')';
    }
}
