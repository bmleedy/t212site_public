<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$is_sa = 0;
$is_wm = 0;
$is_oe = 0;
$is_ue = 0;
if (in_array("sa",$access)) {
	$is_sa = 1;
}
if (in_array("wm",$access)) {
	$is_wm = 1;
}
if (in_array("oe",$access)) {
	$is_oe = 1;
}
if (in_array("ue",$access)) {
	$is_ue = 1;
}
?>
<input type="hidden" id="user_type" value="<?php echo $user_type; ?>">
<input type="hidden" id="is_sa" value="<?php echo $is_sa; ?>">
<input type="hidden" id="is_wm" value="<?php echo $is_wm; ?>">
<input type="hidden" id="is_oe" value="<?php echo $is_oe; ?>">
<input type="hidden" id="is_ue" value="<?php echo $is_ue; ?>">
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
					include("templates/MyT212.html");
			} else {
					include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>