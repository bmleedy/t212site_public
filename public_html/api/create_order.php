<?php
ini_set("enable_post_data_reading", true);
ini_set("allow_url_fopen", true);
//we're using the PHP session to determine user name
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();

require __DIR__ . '/../vendor/autoload.php';

// TODO: need to install composer to get the PaypalServerSdkLib
// https://developer.paypal.com/studio/checkout/standard/getstarted?backend=php#setup-dev-environment
// https://developer.paypal.com/studio/checkout/standard/integrate

use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;

// TODO: set up paypal environment variables so that I don't have to save credentials in the code.
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

	// todo: query the database to fetch the order details
	//  ID
	// name
	// quantity

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

/**
 * Capture payment for the created order to complete the transaction.
 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_capture
 */
function captureOrder($orderID)
{
    global $client;

    $captureBody = [
        'id' => $orderID
    ];

    $apiResponse = $client->getOrdersController()->ordersCapture($captureBody);

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

$endpoint = $_SERVER['REQUEST_URI'];
// This is not the root
// if ($endpoint === '/') {
//     try {
//         $response = [
//             "message" => "Server is running"
//         ];
//         header('Content-Type: application/json');
//         echo json_encode($response);
//     } catch (Exception $e) {
//         echo json_encode(['error' => $e->getMessage()]);
//         http_response_code(500);
//     }
// }

// if ($endpoint === '/api/orders') {
$data = json_decode(file_get_contents('php://input'), true);

// todo: fill in cart data here (not from request from javascript)
$ui_cart = $data['cart'];
// todo: compare the cart from the UI with what's in the DB
// Get cart info from the database
$ui_cart =

header('Content-Type: application/json');
try {
	$orderResponse = createOrder($cart);
	echo json_encode($orderResponse['jsonResponse']);
} catch (Exception $e) {
	echo json_encode(['error' => $e->getMessage()]);
	http_response_code(500);
}
// }


// if (str_ends_with($endpoint, '/capture')) {
//     $urlSegments = explode('/', $endpoint);
//     end($urlSegments); // Will set the pointer to the end of array
//     $orderID = prev($urlSegments);
//     header('Content-Type: application/json');
//     try {
//         $captureResponse = captureOrder($orderID);
//         echo json_encode($captureResponse['jsonResponse']);
//     } catch (Exception $e) {
//         echo json_encode(['error' => $e->getMessage()]);
//         http_response_code(500);
//     }
// }



$filecontents = json_decode(file_get_contents("php://input", false, stream_context_get_default(), 0, $_SERVER["CONTENT_LENGTH"]),true);

if( $_SERVER[ 'CONTENT_TYPE' ] === 'application/json' ) {
  // respond to Ajax request
  error_log("good request to create_order");
} else {
	error_log("bad request to create_order");
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';

//Collect what I need for this transaction
$user_id = $_SESSION['user_id']; //this should never be not set - todo: return an error if not set
//error_log("user id is: ".(gettype($user_id)));
// $cart = $filecontents["cart"];
// error_log("cart = ".print_r($cart,true));

$order_page = NULL;
$order_id = NULL;

// determine the place where the order comes from and fetch the items for that page.
$order_page = $_SERVER["HTTP_REFERER"];

// Query the databse and find out if there's an un-completed order for this user on the referring page
//   if in the unlikely event there's more than one order ID open for this user,
//   only return the highest-numbered one (most recent).
$query_string = "SELECT MAX(order_id) AS order_id FROM orders WHERE completed=FALSE AND page='".$order_page."' AND user_id=".$user_id;
$result = $mysqli->query($query_string);

if($result == FALSE) {
	echo("There is no order for this user on this page!");
	die();
} else {
	$order_id = $result->fetch_row()[0];
	error_log("order_found: ".$order_id);
	$result->free_result();
}

// todo: call the paypal create_order API here to add order details.
//



// return the order details to the user
$rv["id"] = $order_id;
echo json_encode($rv);
die();

?>
