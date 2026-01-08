<?php

session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
?>
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
          include("login/views/edit.php");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
