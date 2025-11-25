<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";

// Load PayPal Client ID from CREDENTIALS.json
try {
    require_once(__DIR__ . '/includes/credentials.php');
    $creds = Credentials::getInstance();
    $PAYPAL_CLIENT_ID = $creds->getPayPalClientId();
} catch (Exception $e) {
    die("Error loading PayPal Client ID in ClassBOrder.php: " . $e->getMessage());
}
?>

<!-- Link CSS for paypal buttons -->
<link
  rel="stylesheet"
  type="text/css"
  href="https://www.paypalobjects.com/webstatic/en_US/developer/docs/css/cardfields.css"
/>
        <!-- configure the script parameters, per https://developer.paypal.com/studio/checkout/standard/integrate -->
        <?php echo("<script src=\"https://www.paypal.com/sdk/js?client-id=".$PAYPAL_CLIENT_ID."&buyer-country=US&currency=USD&components=buttons&enable-funding=venmo\"></script>"); ?>
        <!-- now use javascript to render the buttons -->
        <script src="ClassBOrder.js"></script>
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
        <h1>Please enter the number of t-shirts you would like to purchase:</h1>
        <!-- This is the panel with the paypal buttons in it -->
        <div>
        </div>
        <form name="updateShirts" action="javascript:updateCart('Tshirt')">
          <label for="quantity">Quantity (between 1 and 5):</label>
          <input type="number" id="shirt_quantity" name="quantity" min="1" max="5">
          <select name="sizes" id="shirt_size">
            <option value="x-small">X-Small</option>
            <option value="small">Small</option>
            <option value="medium">Medium</option>
            <option value="large">large</option>
            <option value="x-large">X-Large</option>
          </select>
          <input type="submit">
        </form>
        <div id="paypal-button-container" class="paypal-button-container"></div>
        <p id="result-message"></p>

		</div>
	</div>
</div>
<div class="large-9 columns" id="order_info"></div>


<?php require "includes/footer.html"; ?>
