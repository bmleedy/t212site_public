<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php"; ?>

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
				// Check if user is webmaster (you can adjust this check as needed)
				// Assuming webmasters have a specific access level or user_type
				if (in_array('wm', $access) || in_array('sa', $access)) {
					include("templates/ActivityLog.html");
				} else {
					echo "<h2>Access Denied</h2>";
					echo "<p>This page is only accessible to webmasters.</p>";
				}
			} else {
				include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>


<?php require "includes/footer.html"; ?>
