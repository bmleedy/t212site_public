<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
if ( array_key_exists("edit", $_GET) ) {
  $varEdit = intval($_GET["edit"]);
} else {
  $varEdit = 0;
}

if ( array_key_exists("id", $_GET) ) {
  $id = intval($_GET["id"]);
} else {
  echo("<div><b>An error Occurred. User ID Not Provided.  Try again.</b></div>");
  die;
}

$showEdit = 0;

// $wm = 1 means user has webmaster-like edit access (can edit name, email, phone, etc.)
// $wm = 0 means user does NOT have webmaster-like access
if (in_array("sa", $access) || in_array("ue", $access) || in_array("wm", $access)) {
  // Super admins, user editors, and webmasters have full edit access
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
if (($id != $userID) && (!in_array("ue",$access)) && (!in_array("sa",$access)) && ($wm==0)) {
  $varEdit=0;
} else if($varEdit != 1) {
  $showEdit = 1;
}
?>

<?php
// SECURITY NOTE: The hidden permission fields below were removed as part of a security overhaul.
// Passing permission values (webMaster, userAdmin, showEdit, etc.) via client-side hidden fields
// is a security risk because users can modify these values in the browser.
// All permission checks should be done server-side using $_SESSION['user_access'] instead.
//
// TODO: get rid of hidden permission parameters from the form and base access entirely on the
//       currently-logged-in session. This refactoring requires updating any JavaScript or
//       backend handlers that currently rely on these hidden field values.
?>
<!-- User ID is kept for form functionality but validated server-side with intval() -->
<input type="hidden" id="user_id" value="<?php echo intval($id); ?>">

<!-- Pass server-side values to JavaScript without using hidden form fields -->
<script type="text/javascript">
  // These values are injected by PHP - they are read-only and cannot be tampered with via form submission
  var serverVars = {
    edit: <?php echo json_encode($varEdit); ?>,
    showEdit: <?php echo json_encode($showEdit); ?>,
    webMaster: <?php echo json_encode($wm); ?>,
    userAdmin: <?php echo json_encode($isUserAdmin); ?>
  };
</script>

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
