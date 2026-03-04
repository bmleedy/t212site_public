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
require_once(__DIR__ . '/includes/session_config.php');
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
