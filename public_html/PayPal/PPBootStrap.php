<?php
require_once 'Configuration.php';
/*
 * @constant PP_CONFIG_PATH required if credentoal and configuration is to be used from a file
 * Let the SDK know where the sdk_config.ini file resides.
 */
//define('PP_CONFIG_PATH', dirname(__FILE__));

/*
 * use autoloader
 */
$turl = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require $turl;
