<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
if ((!in_array("oe",$access)) && (!in_array("sa",$access))) {
	$showEdit=0;
} else {
	$showEdit=1;
}
?>
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
		<div class="">
			<?php
			if ($login->isUserLoggedIn() == true) {
					include("templates/ListEventsAll.html");
			} else {
					include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>