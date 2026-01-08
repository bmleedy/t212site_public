<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
if ( array_key_exists("edit", $_GET) ) {
  $varEdit = $_GET["edit"];
} else {
  $varEdit = 0;
}

if ( array_key_exists("id", $_GET) ) {
  $id = $_GET["id"];
} else {
  echo("<div><b>An error Occurred. User ID Not Provided.  Try again.</b></div>");
  die;
}

$showEdit = 0;

// Give webmaster access
if (in_array("wm",$access)) {
  $wm = 1;
} else {
  $wm = 0;
}
// TODO: figure out the key to the access string.
if ((!in_array("ue",$access)) && (!in_array("sa",$access))) {
  $isUserAdmin = 0;
} else {
  $isUserAdmin = 1;
}
// TODO: This logic is Fucked - fix these variables.
if (($id <> $userID) && (!in_array("ue",$access)) && (!in_array("sa",$access)) && ($wm==0)) {
  $varEdit=0;
} else if($varEdit <> 1) {
  $showEdit = 1;
}
?>

<?php
// So, based on this logic above, we're setting stuff that we submit to the form, which gets submitted
//   back to this page.  This is really insecure because what you have is a bunch of plaintext 
//   stuff here that affects permissions to take action on the page.
// TODO: get rid of hidden permission parameters from the form and base access entirely on the 
//       currently-logged-in session.
?>
<input type="hidden" id="webMaster" value="<?php echo $wm; ?>">
<input type="hidden" id="user_id" value="<?php echo $id; ?>">
<input type="hidden" id="edit" value="<?php echo $varEdit; ?>">
<input type="hidden" id="showEdit" value="<?php echo $showEdit; ?>">
<input type="hidden" id="userAdmin" value="<?php echo $isUserAdmin; ?>">

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
          include("templates/User.html");
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>
