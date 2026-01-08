<?
$reg_ids = explode("," , $_POST['reg_ids']);
$token = bin2hex(openssl_random_pseudo_bytes(24));
$retURL = $_POST['returnUrl'] . '?token=' . $token;

require '../new/api/connect.php';
$query = "UPDATE registration SET pp_token=? WHERE id=?";
$statement = $mysqli->prepare($query);
foreach ($reg_ids as &$id) {
$rs = $statement->bind_param('ss', $token, $id);
$statement->execute();
}
$statement->close();

require_once("paypal_functions.php");
$returnURL = RETURN_URL;
$cancelURL = CANCEL_URL; 
$_SESSION['post_value']['RETURN_URL'] = $returnURL;
$_SESSION['post_value']['CANCEL_URL'] = $cancelURL;
  
//$_SESSION['post_value']['RETURN_URL'] = $returnURL;
//$_SESSION['post_value']['CANCEL_URL'] = $cancelURL;
//$returnURL = $_POST['RETURNURL'].'?return='.$token;
//$cancelURL = $_POST['CANCELURL'];
//$_POST["PAYMENTREQUEST_0_AMT"]=$_POST["PAYMENTREQUEST_0_AMT"];
$_POST["PAYMENTREQUEST_0_ITEMAMT"]=$_POST["PAYMENTREQUEST_0_AMT"];

$resArray = CallShortcutExpressCheckout ($_POST, $returnURL, $cancelURL);
$ack = strtoupper($resArray["ACK"]);
if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")  {
  RedirectToPayPal ( $resArray["TOKEN"] );
} else {
  //Display a user friendly Error on the page using any of the following error information returned by PayPal
  $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
  $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
  $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
  $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

  echo "SetExpressCheckout API call failed. ";
  echo "Detailed Error Message: " . $ErrorLongMsg;
  echo "Short Error Message: " . $ErrorShortMsg;
  echo "Error Code: " . $ErrorCode;
  echo "Error Severity Code: " . $ErrorSeverityCode;
}

?>
