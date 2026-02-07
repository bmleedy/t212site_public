<?php
/**
 * Donate Page
 *
 * Public page for making donations to Troop 212.
 * No authentication required. Anonymous donations supported.
 * Payment processed via PayPal JavaScript SDK (PayPal, Venmo, Credit/Debit).
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
            <?php include("templates/Donate.html"); ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
