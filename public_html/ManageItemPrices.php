<?php
/**
 * Manage Item Prices Page
 *
 * Admin page to manage item prices.
 * Requires webmaster or admin permission.
 */

session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";

$user_id = $_SESSION['user_id'];

// Check if user has permission to access this page (webmaster/admin only, not treasurer)
$hasAccess = (in_array("wm", $access) || in_array("sa", $access));
?>
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
                if (!$hasAccess) {
                    echo "<h3>Access Denied</h3>";
                    echo "<p>You are not authorized to view this page. This page is only available to webmasters and super admins.</p>";
                } else {
                    include("templates/ManageItemPrices.html");
                }
            } else {
                include("login/views/user_login.php");
            }
            ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
