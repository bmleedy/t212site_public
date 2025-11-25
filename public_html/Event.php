<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$id = $_GET["id"];
$varEdit = $_GET["edit"];
$showEdit = 0;
$user_id = $_SESSION['user_id'];
if ((!in_array("oe",$access)) && (!in_array("sa",$access))) {
	$varEdit=0;
} else if($varEdit <> 1) {
	$showEdit = 1;
}
if ((in_array("er",$access)) || (in_array("sa",$access))) {
	$showPR = 1;
} else {
	$showPR = 0;
}
if ((in_array("trs",$access)) || (in_array("sa",$access))) {
	$showPayButton = 1;
} else {
	$showPayButton = 0;
}
?>
<input type="hidden" id="id" value="<?php echo $id; ?>">
<input type="hidden" id="user_id" value="<?php echo $user_id; ?>">
<input type="hidden" id="edit" value="<?php echo $varEdit; ?>">
<input type="hidden" id="showEdit" value="<?php echo $showEdit; ?>">
<input type="hidden" id="showPR" value="<?php echo $showPR; ?>">
<input type="hidden" id="showPayButton" value="<?php echo $showPayButton; ?>">
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
					include("templates/Event.html");
			} else {
					include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>