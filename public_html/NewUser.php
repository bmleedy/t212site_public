<?php session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php"; ?>
<br>
<div class='row'>
  <?php require "includes/sidebar.html"; ?>
  <div class="large-9 columns">
    <div class="panel">
    <?
    if ($login->isUserLoggedIn() == true) {
      console.log("here");
        include("templates/NewUser.html");
    } else {
        include("login/views/user_login.php");
    }
    ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>