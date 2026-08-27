<?php

// This file is the single entry point through which all requests to Smartest are made
// That being said, not much needs to happen here. Less is more, you could say...
if(!defined('SM_ROOT_DIR')){
    define('SM_ROOT_DIR', dirname(__DIR__).DIRECTORY_SEPARATOR);
}

chdir(SM_ROOT_DIR);
require_once(SM_ROOT_DIR."System/init.php");
SmartestInit::go();
