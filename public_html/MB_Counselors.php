<?php
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '.t212.org',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'Strict'
]);
session_start();
require "includes/authHeader.php"; ?>

<br>
<div class='row'>
  <?php
    if ($login->isUserLoggedIn() == true) {
      require "includes/m_sidebar.html";
    } else {
      require "includes/sidebar.html";
    }
  ?>
  <div class="large-9 columns">
    <div class="panel">
      <?php
      if ($login->isUserLoggedIn() == true) {
        // Output CSRF token for AJAX requests
        $csrf_token = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
        echo '<input type="hidden" id="csrf_token" value="' . $csrf_token . '" />';
        include("templates/MB_Counselors.html");
      } else {
        include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>
