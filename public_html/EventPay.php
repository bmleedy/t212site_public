<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
require_once "includes/credentials.php";

$user_id = $_SESSION['user_id'];
$ref = $_SERVER['HTTP_REFERER'];

// Get PayPal client ID from credentials
$creds = Credentials::getInstance();
$paypal_client_id = $creds->getPayPalClientId();
?>
<input type="hidden" id="user_id" value="<?php echo $user_id; ?>">
<input type="hidden" id="ref" value="<?php echo $ref; ?>">
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
          include("templates/EventPay.html");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>
