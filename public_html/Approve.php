<!DOCTYPE html>
<?php session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$reg_id = $_GET["id"];
$http_ref = strtolower($_SERVER['HTTP_REFERER']);
?>
<input type="hidden" id="http_ref" value="<?php echo $http_ref; ?>">
<input type="hidden" id="reg_id" value="<?php echo $reg_id; ?>">
<input type="hidden" id="user_id" value="<?php echo $userID; ?>">
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
          include("templates/Approve.html");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>