<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

ini_set("enable_post_data_reading", true);
ini_set("allow_url_fopen", true);

require __DIR__ . '/../vendor/autoload.php';

use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;

// Get PayPal credentials from environment
$PAYPAL_CLIENT_ID = getenv('PAYPAL_CLIENT_ID');
$PAYPAL_CLIENT_SECRET = getenv('PAYPAL_CLIENT_SECRET');

$client = PaypalServerSdkClientBuilder::init()
	->clientCredentialsAuthCredentials(
		ClientCredentialsAuthCredentialsBuilder::init(
			$PAYPAL_CLIENT_ID,
			$PAYPAL_CLIENT_SECRET
		)
	)
	->environment(Environment::SANDBOX)
	->build();

/**
 * Create an order to start the transaction.
 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create
 */
function createOrder($cart)
{
	global $client;

	$orderBody = [
		'body' => OrderRequestBuilder::init(
			CheckoutPaymentIntent::CAPTURE,
			[
				PurchaseUnitRequestBuilder::init(
					AmountWithBreakdownBuilder::init(
						'USD',
						'100.00'
					)->build()
				)->build()
			]
		)->build()
	];

	$apiResponse = $client->getOrdersController()->ordersCreate($orderBody);

	return handleResponse($apiResponse);
}

function handleResponse($response)
{
	return [
		'jsonResponse' => $response->getResult(),
		'httpStatusCode' => $response->getStatusCode()
	];
}

//--------------------------------------------
//         BEGINNING OF RUNNING CODE
//--------------------------------------------

header('Content-Type: application/json');
require 'connect.php';

// Collect what I need for this transaction
$user_id = $current_user_id; // Use authenticated user

$order_page = NULL;
$order_id = NULL;

// Determine the place where the order comes from and fetch the items for that page
$order_page = $_SERVER["HTTP_REFERER"];

// Query the database and find out if there's an un-completed order for this user on the referring page
// Only return the highest-numbered one (most recent) if there are multiple
$query_string = "SELECT MAX(order_id) AS order_id
                 FROM orders
                 WHERE completed=FALSE AND page=? AND user_id=?";
$stmt = $mysqli->prepare($query_string);
$stmt->bind_param('si', $order_page, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_row()) {
	$order_id = $row[0];
	error_log("order_found: " . $order_id);
} else {
	echo json_encode(['error' => 'No order found for this user on this page']);
	die();
}
$stmt->close();

// Return the order details to the user
$rv = array();
$rv["id"] = $order_id;
echo json_encode($rv);
die();
?>
