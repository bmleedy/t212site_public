<?
$pp_root = $_SERVER['DOCUMENT_ROOT'] .'/PayPal/';
$turl =  $pp_root . 'PPBootStrap.php';
require_once($turl);
require_once($pp_root . 'Common/Constants.php');
$cost = $_POST['receiverAmount'][0];
if(isset($_POST['receiverEmail'])) {
	$receiver = array();
	$receiver = new Receiver();
	$receiver->email = $_POST['receiverEmail'];
	$receiver->amount = $_POST['receiverAmount'];
	$receiverList = new ReceiverList($receiver);
}
$reg_ids = explode("," , $_POST['reg_ids']);
$token = bin2hex(openssl_random_pseudo_bytes(24));
$retURL = $_POST['returnUrl'] . '?token=' . $token;
$temp = $_SERVER['DOCUMENT_ROOT'] . '/api/connect.php';
require $temp;
$query = "UPDATE registration SET pp_token=? WHERE id=?";
$statement = $mysqli->prepare($query);
foreach ($reg_ids as &$id) {
	$rs = $statement->bind_param('ss', $token, $id);
	$statement->execute();
}
$statement->close();
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
