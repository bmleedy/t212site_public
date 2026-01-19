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
    <div class="">
      <?php
      if ($login->isUserLoggedIn()) {
        // Only super admins can access permissions management
        if (in_array('sa', $access, true)) {
          include "templates/Permissions.html";
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
