<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$id = isset($_GET["id"]) ? htmlspecialchars($_GET["id"], ENT_QUOTES, 'UTF-8') : '';
$user_id = isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8') : '';
?>
<input type="hidden" id="id" value="<?php echo $id; ?>">
<input type="hidden" id="user_id" value="<?php echo $user_id; ?>">
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
          include("templates/FAQ.html");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>