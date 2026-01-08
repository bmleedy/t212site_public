<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
?>
<br>
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