<?php
$pp_root = $_SERVER['DOCUMENT_ROOT'] .'/PayPal/';
$turl =  $pp_root . 'PPBootStrap.php';
require_once($turl);
require_once($pp_root . 'Common/Constants.php');
$token = bin2hex(openssl_random_pseudo_bytes(24));
$retURL = $_POST['returnUrl'] . '?token=' . $token;
$temp = $_SERVER['DOCUMENT_ROOT'] . '/api/connect.php';
require $temp;

$address1 = $_POST['address1'];
$address2 = $_POST['address2'];
$city = $_POST['city'];
$state = $_POST['state'];
$zip = $_POST['zip'];
$family_id = $_POST['family_id'];

if ($family_id=="") {
	$query = "INSERT INTO families (address1, address2, city, state, zip) VALUES(?, ?, ?, ?, ?)";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sssss', $address1, $address2, $city, $state, $zip);
	$statement->execute();
	$family_id = $mysqli->insert_id;
} else {
	$query = "UPDATE families SET address1=?, address2=?, city=?, state=?, zip=? WHERE family_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('ssssss', $address1, $address2, $city, $state, $zip, $family_id);
	$statement->execute();
}

$user_id = $_POST['user_id'];
$query = "UPDATE users SET family_id=? WHERE user_id=?";
$statement = $mysqli->prepare($query);
$statement->bind_param('ss', $family_id, $user_id);
$statement->execute();

$rc_ids = explode("," , $_POST['rc_ids']);
$all_user_ids = null;

foreach ($rc_ids as $value) {
	if (strrpos($value,'bl')===0) {
		$scout_id = substr($value,2);
		$bl = 1;
	} else {
		$scout_id = $value;
		$bl = 0;
	}
	$all_user_ids[]=$scout_id;
	
	$query = "INSERT INTO recharter (scout_id, pp_token, boyslife) VALUES(?, ?, ?)";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $scout_id, $token, $bl);
	$statement->execute();
	
	$query = "SELECT adult_id FROM relationships WHERE scout_id=" . $scout_id;
	$results = $mysqli->query($query);
	while ($row = $results->fetch_assoc()) {
		$id = $row['adult_id'];
		if (!in_array($id, $all_user_ids)) {
			$all_user_ids[] = $id;
		}
	}
}

$query = "UPDATE users SET family_id=".$family_id." WHERE user_id in (".implode(",", $all_user_ids).")";
$statement = $mysqli->prepare($query);
$statement->execute();
	
$cost = $_POST['receiverAmount'][0];
if(isset($_POST['receiverEmail'])) {
	$receiver = array();
	$receiver = new Receiver();
	$receiver->email = $_POST['receiverEmail'];
	$receiver->amount = $_POST['receiverAmount'];
	$receiverList = new ReceiverList($receiver);
}

$payRequest = new PayRequest(new RequestEnvelope("en_US"), 'PAY', $_POST['cancelUrl'], $_POST['currencyCode'], $receiverList, $retURL);
$service = new AdaptivePaymentsService(Configuration::getAcctAndConfig());
try {
	$response = $service->Pay($payRequest);
} catch(Exception $ex) {
	require_once $pp_root . 'Common/Error.php';
	exit;
}
$ack = strtoupper($response->responseEnvelope->ack);
if($ack != "SUCCESS") {
	echo $ack;
	echo "<b>Error </b>";
	echo "<pre>";
	echo "</pre>";
	require_once $pp_root . 'Common/Response.php';
} else {

	$payKey = $response->payKey;
	$payPalURL = PAYPAL_REDIRECT_URL . '_ap-payment&paykey=' . $payKey;
	//echo $payPalURL;
	header( 'Location: ' . $payPalURL);
	die();
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
