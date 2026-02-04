<?php
/**
 * Mobile.php - Mobile Navigation Page
 *
 * Displays the mobile-friendly navigation menu for logged-in users.
 * Shows login form for unauthenticated users.
 *
 * @security Requires authentication to view mobile menu
 */

// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
require "includes/authHeader.php";
?>
<!DOCTYPE html>
<div class='row'>
  <?php
    if ($login->isUserLoggedIn() == true) {
      require "includes/mobile_menu.html";
    } else {
      include("login/views/user_login.php");
    }
  ?>
</div>

<?php require "includes/footer.html"; ?>
