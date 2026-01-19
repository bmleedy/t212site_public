<?php
/**
 * DELUser.php - DEPRECATED/DEAD CODE
 *
 * STATUS: This file is effectively dead code and should be removed.
 *
 * REASON: This file references "templates/User2.html" which does not exist.
 * The template was never created or has been deleted.
 *
 * HOW USER DELETION ACTUALLY WORKS:
 * - Users are soft-deleted by setting user_type = 'Delete' in the users table
 * - The ListDeletes.html template (via getdeletes.php API) displays deleted users
 * - Deleted users can be viewed/restored via the regular User.php page
 * - There is no dedicated deletion page - admins change user_type via updateuser.php
 *
 * RECOMMENDATION: Delete this file and update any references to it.
 * The SiteWideAccessControlTest.php file references this, so update tests if removed.
 *
 * @deprecated This file is non-functional and should be removed.
 */

session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";

// Input validation with defaults
if (array_key_exists("edit", $_GET)) {
  $varEdit = intval($_GET["edit"]);
} else {
  $varEdit = 0;
}

if (array_key_exists("id", $_GET)) {
  $id = intval($_GET["id"]);
} else {
  echo("<div><b>Error: User ID not provided.</b></div>");
  require "includes/footer.html";
  die;
}

$showEdit = 0;

// Permission check: Only allow edit if user is viewing their own profile OR has ue/sa permissions
if (($id != $userID) && (!in_array("ue", $access)) && (!in_array("sa", $access))) {
  $varEdit = 0;
} else if ($varEdit != 1) {
  $showEdit = 1;
}
?>

<!-- User ID is kept for form functionality but validated server-side with intval() -->
<input type="hidden" id="user_id" value="<?php echo intval($id); ?>">
<input type="hidden" id="edit" value="<?php echo intval($varEdit); ?>">
<input type="hidden" id="showEdit" value="<?php echo intval($showEdit); ?>">
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
          // NOTE: User2.html does not exist. This file is dead code.
          // Falling back to User.html to prevent fatal error, but this
          // entire file should be removed.
          if (file_exists("templates/User2.html")) {
              include("templates/User2.html");
          } else {
              echo "<div class='alert-box warning'>This page (DELUser.php) is deprecated. ";
              echo "Please use <a href='User.php?id=" . intval($id) . "'>User.php</a> instead.</div>";
              include("templates/User.html");
          }
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


<?php require "includes/footer.html"; ?>