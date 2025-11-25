<?php 
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php"; ?>
<input type="hidden" id="rc_ids" value="<?php echo $_GET['rc_ids']; ?>">
<br />
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
			<?
			if ($login->isUserLoggedIn() == true) {
					include("templates/PPRecharterReturnPage.html");
			} else {
					include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>

<?php require "includes/footer.html"; ?>