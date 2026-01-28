
<?php
// Configure secure session cookie settings (must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
  ]);
}

// the root of our directory is one level above this file
define("__ROOT__",dirname(__DIR__));


// for any file that includes this header, write a log entry for this page
$logFile = __ROOT__ . '/access_log' . date('Y-m-d') . '.txt';
$logEntry = date('Y-m-d H:i:s') . " - IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "UNKNOWN") .
    " - User Agent: " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "UNKNOWN") .
    " - user_id: " . (array_key_exists("user_id", $_SESSION) ? $_SESSION['user_id'] : "NULL") .
    " - user_type: " . (array_key_exists("user_type", $_SESSION) ? $_SESSION['user_type'] : "NULL") .
    " - user_access: " . (array_key_exists("user_access", $_SESSION) ? $_SESSION['user_access'] : "NULL") .
    " - URL: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "UNKNOWN") .
    PHP_EOL;
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);


// this runs the login class/script
require_once( __ROOT__ . '/login/inc_login.php');
if ( !array_key_exists("user_id", $_SESSION) ||
     !array_key_exists("user_access", $_SESSION) ||
     !array_key_exists("user_first", $_SESSION) ||
     !array_key_exists("user_type", $_SESSION)) {

  // be explicit and define variables if they're not provided from the session
  // if ANY key session variable is not set, the are all invalid and should be NULL
  $userID = NULL;
  $user_first = NULL;
  $user_type = NULL;
  $access = [];
} else {
  $userID = $_SESSION['user_id'];
  $user_first = $_SESSION['user_first'];
  $user_type = $_SESSION['user_type'];
  $access = explode(".",$_SESSION['user_access']);
}

// Include impersonation helper and check impersonation status
require_once(__ROOT__ . '/includes/impersonation_helper.php');
$isImpersonating = is_impersonating();
$impersonatedUserFirst = $isImpersonating ? ($_SESSION['user_first'] ?? '') : null;
$impersonatedUserName = $isImpersonating ? ($_SESSION['user_name'] ?? '') : null;

// Forces scouts to complete their profile if their profile is not entirely complete already
$checkinfo = __ROOT__ .'/api/checkinfo.php' ;
if ($user_type=='Scout') {
  require( $checkinfo );
}

// CSRF Protection: Generate a token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/**
 * Get CSRF token for use in forms
 * @return string The current CSRF token
 */
function get_csrf_token() {
  return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
}

/**
 * Generate a hidden input field with the CSRF token
 * @return string HTML hidden input element
 */
function csrf_input() {
  return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST data
 * @param string $token The token to validate (optional, reads from $_POST if not provided)
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token = null) {
  if ($token === null) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
  }
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

<html lang="en">
<head>
  <title>Boy Scout Troop 212</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">

  <script src="/js/jquery-3.7.1.min.js"></script>
  <script src="/js/jquery-migrate-3.4.1.min.js"></script>
  <script src="/js/ajaxsetup-csrf-traditional.js"></script>
  <script src="/js/modernizr-shim.js"></script>
  <script src="/js/foundation.min.js"></script>

  <link rel="stylesheet" href="/css/foundation.min.css">
  <link rel="stylesheet" href="/css/foundation-icons/foundation-icons.css">
</head>

<body>
<?php if ($isImpersonating): ?>
<div id="impersonation-banner" style="position:fixed;top:0;left:0;right:0;background:#007bff;color:white;padding:10px 20px;text-align:center;z-index:99999;font-size:14px;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
    <i class="fi-torso" style="margin-right:8px;"></i>
    <strong>Impersonating:</strong>
    <?php echo htmlspecialchars($impersonatedUserFirst . ' (' . $impersonatedUserName . ')'); ?>
    <span style="margin:0 15px;">|</span>
    <a href="/api/stopimpersonation.php" style="color:white;text-decoration:underline;font-weight:bold;">Exit Impersonation</a>
</div>
<div style="height:45px;"></div>
<?php endif; ?>
<img src="/images/BSA_color.gif">

<div class = "visible-for-large-up">

<nav class="top-bar" data-topbar="">
<ul class="title-area">
<li class="name">
<h1>
<a href="index.php">
Boy Scout Troop 212 - Gig Harbor, WA
</a>
</h1>
</li>

<li class="toggle-topbar menu-icon"><a href=""><span>menu</span></a></li>
</ul>


<section class="top-bar-section">
<ul class="right">
<li><a>Welcome <?php echo htmlspecialchars($user_first ?? ''); ?>!</a></li>
<li class="divider"></li>
<li><a href="index.php">Home</a></li>
<li class="divider"></li>
<li class="has-dropdown not-click">
<a href="">About Troop 212</a>
<ul class="dropdown"><li class="title back js-generated"><h5><a href="javascript:void(0)">Back</a></h5></li><li class="parent-link show-for-small-only"><a class="parent-link js-generated" href="">About Troop 212</a></li>
<li><a href="CurrentInfo.php">Current Information</a></li>
<li><a href="OurHistory.php">Our History</a></li>
<li><a href="EagleScouts.php">Eagle Scouts of 212</a></li>
</ul>
</li>
<li class="divider"></li>
<li class="has-dropdown not-click">
<a href="">New to Troop 212</a>
<ul class="dropdown"><li class="title back js-generated"><h5><a href="javascript:void(0)">Back</a></h5></li><li class="parent-link show-for-small-only"><a class="parent-link js-generated" href="">New to Troop 212</a></li>
<li><a href="Welcome.php">Welcome Message!</a></li>
<li><a href="NewScoutInfo.php">New Scout Information</a></li>
<li><a href="ParentInfo.php">What Parents can Expect</a></li>
<li><a href="Handbook.php">Parent Handbook</a></li>
</ul>
</li>
<li class="divider"></li>
<li><a id="logout" href="login/index.php?logout" style="display:block;">Logoff</a></li>
</ul>

</section></nav>

</div>
