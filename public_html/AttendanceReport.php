<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require "includes/authHeader.php";

$user_id = $_SESSION['user_id'];

// Check if user has permission to access this page
// Allowed: webmaster (wm), outing editors (oe), super admin (sa)
$hasAccess = (in_array("wm", $access) || in_array("oe", $access) || in_array("sa", $access));

// Check if user has extended permissions (scoutmaster or webmaster can edit attendance)
$canEditAttendance = (in_array("wm", $access) || in_array("sa", $access));
?>
<input type="hidden" id="user_id" value="<?php echo htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" id="canEditAttendance" value="<?php echo $canEditAttendance ? '1' : '0'; ?>">
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
        if (!$hasAccess) {
          echo "<h3>Access Denied</h3>";
          echo "<p>You are not authorized to view this page. This page is only available to webmasters, outing editors, and super admins.</p>";
        } else {
          include("templates/AttendanceReport.html");
        }
      } else {
        include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
