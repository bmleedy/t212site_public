<?php session_set_cookie_params(0, '/', '.t212.org');
session_start();
require "includes/authHeader.php"; ?>
<input type="hidden" id="reg_ids" value="<?php echo htmlspecialchars($_GET['reg_ids'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<br />
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
          include("templates/PPReturnPage2.html");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>