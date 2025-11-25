<?php
//define a var for the directory above this one (public_html)
// This is important to be a relative path because a global like __DOCUMENT_ROOT__
//   will change depending on the server configuration.
$root_dir = dirname(dirname(__FILE__));

// include the config
require_once($root_dir.'/login/config/config.php');

// include the to-be-used language, english by default. feel free to translate your project and include something else
require_once($root_dir.'/login/translations/en.php');

// include the PHPMailer library
require_once($root_dir.'/login/libraries/PHPMailer.php');

// load the login class
require_once($root_dir.'/login/classes/Login.php');

// create a login object. when this object is created, it will do all login/logout stuff automatically
// so this single line handles the entire login process.
$login = new Login();
?>
