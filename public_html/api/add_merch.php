<?php
ini_set("enable_post_data_reading", true);
ini_set("allow_url_fopen", true);
//we're using the PHP session to determine user name
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();

//error_log("server= ".json_encode($_SERVER));
//error_log("post= ".json_encode($_POST));
//error_log("request= ".json_encode($_REQUEST));
//error_log("files= ".json_encode($_FILES));
$filecontents = json_decode(file_get_contents("php://input", false, stream_context_get_default(), 0, $_SERVER["CONTENT_LENGTH"]),true);
//error_log("input_contents = ".print_r($filecontents,true));
//error_log("http raw post data - ".$HTTP_RAW_POST_DATA);



if( $_SERVER[ 'CONTENT_TYPE' ] === 'application/json' ) {
  // respond to Ajax request
  error_log("good request to add_merch");
} else {
	error_log("bad request to add_merch");
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
$item_type = $filecontents["cart"][0]['type'];
$item_id = $filecontents["cart"][0]['id'];
$item_qty= $filecontents["cart"][0]['quantity'];

// Determine whether there's an active order for this user by querying the database

// determine the place where the order comes from and fetch the items for that page.
if(str_contains($_SERVER["HTTP_REFERER"], "ClassBOrder.php")) {
	$order_page = "ClassBOrder.php";

	// Query the databse for all un-completed orders for this user on this page
	//   if in the unlikely event there's more than one order ID open for this user,
	//   only return the highest-numbered one (most recent).
	//   todo: in the future, check if there ever are any "orphaned" order ID's.
	for ($x = 0; $x <= 1; $x++) { //
		$query_string = "SELECT MAX(order_id) AS order_id FROM orders WHERE completed=FALSE AND page='".$order_page."' AND user_id=".$user_id;
		//error_log($query_string);
		$result = $mysqli->query($query_string);

		// print the output, for debugging
		// error_log('rows returned is '.$result->num_rows);
		// while ($row = $result->fetch_row()) {
		// 	error_log(print_r($row, true));
		// }




		if ($result->num_rows > 0 ) {
			// an order exists.  Record the order ID.
			$order_id = $result->fetch_row()[0];
			error_log("order_found: ".$order_id);
			$result->free_result();
			break; //break out of the for loop because success fetching an order_id
		} else {
			// no order exists. Create a new order.
			// page and user_id are the only fields that do not have defaults
			error_log("need to create a new order");
			$result->free_result();
			$query = "INSERT INTO orders (page, user_id) VALUES (?, ?)";
			$statement = $mysqli->prepare($query);
			$statement->bind_param('ss', $order_page, $user_id);
			if ($statement->execute()) {
				// nothing to do. it was successful
				$statement->close();
			} else {
				echo ( 'Error creating new order in db: ('. $mysqli->errno .') '. $mysqli->error);
				$statement->close();
				die;
			}
		}

	}
	// Just a safety / sanity check here to make sure we created an order successfully.
	if(is_null($order_id)) {
		error_log("null order id where there shouldn't be. Check DB code.");
		echo ("Could not create a valid order ID - check with Brett.");
		die;
	}
} else {
	error_log("add_merch.php: This HTTP referrer is not recognized: ".$_SERVER["HTTP_REFERER"]);
}

// Add or replace the quantities of the current item for the current order in the orders database
// using the stored routine (specified by the .sql file in the db_copy folder)
$query = "CALL update_order_item({$order_id}, '{$item_id}', {$item_qty})";
//error_log($query);
$statement = $mysqli->prepare($query);
if ($statement->execute()) {
	// nothing to do. it was successful
	$statement->close();
} else {
	echo ( 'Error creating new order in db: ('. $mysqli->errno .') '. $mysqli->error);
	$statement->close();
	die;
}
// query the database to get all current items on the order
//todo: put error checking around these SQL statements
$query_string = "SELECT item_id, item_quantity FROM order_items WHERE order_id={$order_id}";
$result = $mysqli->query($query_string);

//print the output, for debugging
error_log('rows returned is '.$result->num_rows);
$items_in_cart=[];
$i=0;
while ($row = $result->fetch_row()) {
	//error_log(print_r($row, true));
	$items_in_cart[$i]["item_id"]=$row[0];
	$items_in_cart[$i]["item_quantity"]=$row[1];
	$i++;
}
error_log(json_encode($items_in_cart));




// return the order details to the user
if (true) {
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'Registration for this event has been paid.'
	);
	// Assemble the return value JSON
	$rv=[];
	$rv["status"] = 'Success';
	$rv["message"] = ''; //todo: think of how to use this
	$rv["order_id"] = $order_id; //may have been newly created
	$rv["items"] = $items_in_cart;
	$rv["page"] = $order_page; //for debugging, mostly
	echo json_encode($rv);
}else{
	// todo: think of error conditions to check for
	//echo ( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
}

die;

?>
