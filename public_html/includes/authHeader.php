
<?php
// the root of our directory is one level above this file
define("__ROOT__",dirname(__DIR__));


// for any file that includes this header, write a log entry for this page
$logFile = __ROOT__ . '/access_log' . date('Y-m-d') . '.txt';
$logEntry = date('Y-m-d H:i:s') . " - IP: " . $_SERVER['REMOTE_ADDR'] .
    " - User Agent: " . $_SERVER['HTTP_USER_AGENT'] .
		" - user_id: " . (array_key_exists("user_id", $_SESSION) ? $_SESSION['user_id'] : "NULL") .
		" - user_type: " . (array_key_exists("user_type", $_SESSION) ? $_SESSION['user_type'] : "NULL") .
		" - user_access: " . (array_key_exists("user_access", $_SESSION) ? $_SESSION['user_access'] : "NULL") .
		" - URL: " . $_SERVER['REQUEST_URI'] .
		PHP_EOL;
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);


// this runs the login class/script
require_once( __ROOT__ . '/login/inc_login.php');
if ( !array_key_exists("user_id", $_SESSION) ||
     !array_key_exists("user_access", $_SESSION) ||
     !array_key_exists("user_first", $_SESSION) ||
     !array_key_exists("user_type", $_SESSION)) {
	// be explicit and define variables if they're not provided from the session
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

$checkinfo = __ROOT__ .'/api/checkinfo.php' ;
if ($user_type=='Scout') {
	require( $checkinfo );
}
?>

<html class=" js flexbox flexboxlegacy canvas canvastext webgl no-touch geolocation postmessage websqldatabase indexeddb hashchange history draganddrop websockets rgba hsla multiplebgs backgroundsize borderimage borderradius boxshadow textshadow opacity cssanimations csscolumns cssgradients cssreflections csstransforms csstransforms3d csstransitions fontface generatedcontent video audio localstorage sessionstorage webworkers applicationcache svg inlinesvg smil svgclippaths" lang="en" data-useragent="Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36">
<head>
	<title>Boy Scout Troop 212</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js" ></script>
	<script src="/js/vendor/modernizr.js"></script>
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
<li><a>Welcome <?php echo $user_first; ?>!</a></li>
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
