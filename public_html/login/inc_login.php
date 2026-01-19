<?php
// Define root directory (parent of login/ folder)
$root_dir = dirname(__DIR__);

// Include configuration
require_once($root_dir . '/login/config/config.php');

// Include translations (English by default)
require_once($root_dir . '/login/translations/en.php');

// Load the Login class
// Note: PHPMailer is lazy-loaded in Login.php when needed
require_once($root_dir . '/login/classes/Login.php');

// Create login object - handles login/logout automatically
$login = new Login();
