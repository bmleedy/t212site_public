<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$varEdit = $_GET["edit"];
$id = $_GET["id"];
$showEdit = 0;
if (($id <> $userID) && (!in_array("ue",$access)) && (!in_array("sa",$access))) {
	$varEdit=0;
} else if($varEdit <> 1) {
	$showEdit = 1;
}
?>
<input type="hidden" id="user_id" value="<?php echo $id; ?>">
<input type="hidden" id="edit" value="<?php echo $varEdit; ?>">
<input type="hidden" id="showEdit" value="<?php echo $showEdit; ?>">
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
					include("templates/User2.html");
			} else {
					include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>