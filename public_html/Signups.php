
<?php session_set_cookie_params(0, '/', '.t212.org');
session_start();
require_once "includes/authHeader.php";
$id = $_GET["id"];
?>
<input type="hidden" id="id" value="<?php echo $id; ?>">
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
        if ((!in_array("oe",$access)) && (!in_array("sa",$access))) {
          echo "You are not authorized to view this page!";
        } else {
          include("templates/Signups.html");
        }
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>
