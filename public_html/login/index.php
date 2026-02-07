<?php
// Secure session configuration (must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
		'httponly' => true,
		'samesite' => 'Lax'
	]);
	session_start();
}

// Check for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
	exit('Sorry, this script does not run on a PHP version smaller than 5.3.7!');
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	require_once('libraries/password_compatibility_library.php');
}

// Include the config
require_once('config/config.php');

// Include the to-be-used language, english by default
require_once('translations/en.php');

// Include the PHPMailer library
require_once('libraries/PHPMailer.php');

// Load the login class
require_once('classes/Login.php');

// Create a login object - handles login/logout automatically
$login = new Login();

// Check if user is logged in
if ($login->isUserLoggedIn()) {
	// User is logged in - show the logged in view
	include("views/logged_in.php");
} else {
	// User is not logged in - show the login form
	include("views/not_logged_in.php");
}
?>
