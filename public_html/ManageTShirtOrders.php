<?php
/**
 * Manage T-Shirt Orders Page
 *
 * Admin page to view and manage T-shirt orders.
 * Requires treasurer, webmaster, or admin permission.
 *
 * @security Session cookies configured with httponly, samesite, and secure flags via authHeader.php
 */

require "includes/authHeader.php";

// Check if user has permission to access this page
$hasAccess = (in_array("trs", $access) || in_array("wm", $access) || in_array("sa", $access));
?>
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
                    echo "<p>You are not authorized to view this page. This page is only available to treasurers, webmasters, and super admins.</p>";
                } else {
                    include("templates/ManageTShirtOrders.html");
                }
            } else {
                include("login/views/user_login.php");
            }
            ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
