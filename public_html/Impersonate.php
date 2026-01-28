<?php
/**
 * Impersonate User Page
 *
 * Admin page for Super Admins to impersonate other users.
 * Allows selecting a user to impersonate for testing/debugging.
 */

// Temporary error display for debugging - remove after fixing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session handling is done in authHeader.php with secure settings
require "includes/authHeader.php";
?>

<br>
<div class='row'>
  <?php
    if ($login->isUserLoggedIn()) {
      require "includes/m_sidebar.html";
    } else {
      require "includes/sidebar.html";
    }
  ?>
  <div class="large-9 columns">
    <div class="">
      <?php
      if ($login->isUserLoggedIn()) {
        // Only super admins can access impersonation
        if (in_array('sa', $access, true)) {
          include "templates/Impersonate.html";
        } else {
          echo "<h2>Access Denied</h2>";
          echo "<p>This page is only accessible to super admins.</p>";
        }
      } else {
        include "login/views/user_login.php";
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
