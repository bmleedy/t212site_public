<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require( $_SERVER['DOCUMENT_ROOT'] . '/login/inc_login.php');

// Validate input
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
  die("Invalid event ID");
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$access = isset($_SESSION['user_access']) ? explode(".", $_SESSION['user_access']) : array();

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<html class=" js flexbox flexboxlegacy canvas canvastext webgl no-touch geolocation postmessage websqldatabase indexeddb hashchange history draganddrop websockets rgba hsla multiplebgs backgroundsize borderimage borderradius boxshadow textshadow opacity cssanimations csscolumns cssgradients cssreflections csstransforms csstransforms3d csstransitions fontface generatedcontent video audio localstorage sessionstorage webworkers applicationcache svg inlinesvg smil svgclippaths" lang="en" data-useragent="Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36">
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
  <script src="js/foundation.min.js"></script>

  <link rel="stylesheet" href="css/foundation.min.css">
  <link rel="stylesheet" href="/css/foundation-icons/foundation-icons.css">
  <style type="text/css"></style>
  <meta class="foundation-data-attribute-namespace">
  <meta class="foundation-mq-xxlarge">
  <meta class="foundation-mq-xlarge-only">
  <meta class="foundation-mq-xlarge">
  <meta class="foundation-mq-large-only">
  <meta class="foundation-mq-large">
  <meta class="foundation-mq-medium-only">
  <meta class="foundation-mq-medium">
  <meta class="foundation-mq-small-only">
  <meta class="foundation-mq-small">
  <meta class="foundation-mq-topbar">
</head>

<body>


<input type="hidden" id="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" id="user_id" value="<?php echo htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" id="edit" value="<?php echo $varEdit; ?>">
<input type="hidden" id="showEdit" value="<?php echo $showEdit; ?>">
<br>
<div class='row'>
  <div class="large-9 columns">
    <div class="panel">
      <?php
      if ($login->isUserLoggedIn() == true) {
        if ((!in_array("er",$access)) && (!in_array("sa",$access))) {
          echo "You are not authorized to view this page!";
        } else {
          include("templates/EventRoster.html");
        }
      } else {
          include("login/views/user_login.php");
      }
      ?>
    </div>
  </div>
</div>


</body></html>