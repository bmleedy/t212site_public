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

// CSRF Protection: Generate a token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
	exit('Sorry, this script does not run on a PHP version smaller than 5.3.7!');
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	require_once('login/libraries/password_compatibility_library.php');
}
?>
<!DOCTYPE html>
<?php require "includes/header.html"; ?>
<br />

<div class='row'>
  <?php require "includes/sidebar.html"; ?>
  <div class="large-9 columns">
    <div class="panel">
<?php

// Include the config
require_once('login/config/config.php');

// Include the to-be-used language, english by default
require_once('login/translations/en.php');

// Include the PHPMailer library
require_once('login/libraries/PHPMailer.php');

// Load the login class
require_once('login/classes/Login.php');

// Create a login object - handles login/logout automatically
$login = new Login();

// The user has just successfully entered a new password
// so we show the login page
if ($login->passwordResetWasSuccessful() && !$login->passwordResetLinkIsValid()) {
	include("login/views/not_logged_in.php");
} else {
	// Show the request-a-password-reset or type-your-new-password form
	include("login/views/password_reset.php");
}

?>

    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
