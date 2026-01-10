<?php
/**
 * T-Shirt Order Page
 *
 * Public page for ordering Class B T-shirts.
 * No authentication required.
 */

require "includes/header.html";
require_once "includes/credentials.php";

// Get PayPal client ID
$creds = Credentials::getInstance();
$paypal_client_id = $creds->getPayPalClientId();
?>

<br>
<div class='row'>
    <?php require "includes/sidebar.html"; ?>
    <div class="large-9 columns">
        <div class="panel">
            <?php include("templates/TShirtOrder.html"); ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
