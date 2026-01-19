<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
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
    <div class="panel">
    <?php
    if ($login->isUserLoggedIn()) {
        include("templates/Members.html");
    } else {
        include("login/views/user_login.php");
    }
    ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>
