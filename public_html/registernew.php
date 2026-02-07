<?php
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
    <div class="panel">
      <?php
      if ($login->isUserLoggedIn()) {
        // Only super admins (sa), webmasters (wm), or user editors (ue) can register new users
        if (in_array('sa', $access, true) || in_array('wm', $access, true) || in_array('ue', $access, true)) {
          // Include required registration dependencies
          require_once('login/config/config.php');
          require_once('login/translations/en.php');
          require_once('login/libraries/PHPMailer.php');
          require_once('login/classes/Registration.php');

          // Create registration object - handles entire registration process
          $registration = new Registration();
          // Show registration form with messages/errors
          include("login/views/registernew.php");
        } else {
          echo "<h2>Access Denied</h2>";
          echo "<p>You do not have permission to register new users. This function is restricted to administrators.</p>";
        }
      } else {
        include "login/views/user_login.php";
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
