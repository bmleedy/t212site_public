<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
$showEdit=0;
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
		<div class="panel">
			<?php
			if ($login->isUserLoggedIn() == true) {
				if ((!in_array("oe",$access)) && (!in_array("sa",$access))) {
					echo "You are not authorized to view this page!";
				} else {
					include("templates/EventSignups.html");
				}
			} else {
				include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>
