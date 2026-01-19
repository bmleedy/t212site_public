<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
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
    <div>
      <?php
      if ($login->isUserLoggedIn()) {
        // Only webmasters (wm) and super admins (sa) can view the activity log
        if (in_array('wm', $access) || in_array('sa', $access)) {
          include("templates/ActivityLog.html");
        } else {
          echo "<h2>Access Denied</h2>";
          echo "<p>This page is only accessible to webmasters.</p>";
        }
      } else {
        include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
