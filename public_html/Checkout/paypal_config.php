<?php
// Load credentials utility
try {
    require_once(__DIR__ . '/../includes/credentials.php');
} catch (Exception $e) {
    die("Error loading credentials utility in paypal_config.php: " . $e->getMessage());
}

// Get PayPal credentials from CREDENTIALS.json
try {
    $creds = Credentials::getInstance();
} catch (Exception $e) {
    die("Error loading credentials in paypal_config.php: " . $e->getMessage());
}

//Seller Sandbox Credentials - loaded from CREDENTIALS.json
define("PP_USER_SANDBOX", $creds->getPayPalSandboxUsername());
define("PP_PASSWORD_SANDBOX", $creds->getPayPalSandboxPassword());
define("PP_SIGNATURE_SANDBOX", $creds->getPayPalSandboxSignature());

//Seller Live credentials - loaded from CREDENTIALS.json
define("PP_USER", $creds->getPayPalProductionUsername());
define("PP_PASSWORD", $creds->getPayPalProductionPassword());
define("PP_SIGNATURE", $creds->getPayPalProductionSignature());

//Set this constant EXPRESS_MARK = true to skip review page
define("EXPRESS_MARK", false);

//Set this constant ADDRESS_OVERRIDE = true to prevent from changing the shipping address
define("ADDRESS_OVERRIDE", true);

//Set this constant USERACTION_FLAG = true to skip review page
define("USERACTION_FLAG", false);

//Based on the USERACTION_FLAG assign the page
if(USERACTION_FLAG){
  $page = 'return.php';
} else {  
  $page = 'review.php';
}

//The URL in your application where Paypal returns control to -after success (RETURN_URL) using Express Checkout Mark Flow
define("RETURN_URL_MARK",'http://'.$_SERVER['HTTP_HOST'].preg_replace('/paypal_ec_redirect.php/','return.php',$_SERVER['SCRIPT_NAME']));

//The URL in your application where Paypal returns control to -after success (RETURN_URL) and after cancellation of the order (CANCEL_URL) 
define("RETURN_URL",'http://'.$_SERVER['HTTP_HOST'].preg_replace('/paypal_ec_redirect.php/','lightboxreturn.php',$_SERVER['SCRIPT_NAME']));
define("CANCEL_URL",'http://'.$_SERVER['HTTP_HOST'].preg_replace('/paypal_ec_redirect.php/','cancel.php',$_SERVER['SCRIPT_NAME']));

//Whether Sandbox environment is being used, Keep it true for testing
define("SANDBOX_FLAG", true);

if(SANDBOX_FLAG){
  $merchantID=PP_USER_SANDBOX;  /* Use Sandbox merchant id when testing in Sandbox */
  $env= 'sandbox';
}
else {
  $merchantID=PP_USER;  /* Use Live merchant ID for production environment */
  $env='production';
}

//Proxy Config
define("PROXY_HOST", "127.0.0.1");
define("PROXY_PORT", "808");

//In-Context in Express Checkout URLs for Sandbox
define("PP_CHECKOUT_URL_SANDBOX", "https://www.sandbox.paypal.com/checkoutnow?token=");
define("PP_NVP_ENDPOINT_SANDBOX","https://api-3t.sandbox.paypal.com/nvp");

//Express Checkout URLs for Sandbox
//define("PP_CHECKOUT_URL_SANDBOX", "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=");
//define("PP_NVP_ENDPOINT_SANDBOX","https://api-3t.sandbox.paypal.com/nvp");

//In-Context in Express Checkout URLs for Live
define("PP_CHECKOUT_URL_LIVE","https://www.paypal.com/checkoutnow?token=");
define("PP_NVP_ENDPOINT_LIVE","https://api-3t.paypal.com/nvp");

//Express Checkout URLs for Live
//define("PP_CHECKOUT_URL_LIVE","https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=");
//define("PP_NVP_ENDPOINT_LIVE","https://api-3t.paypal.com/nvp");

//Version of the APIs
define("API_VERSION", "109.0");

//ButtonSource Tracker Code
define("SBN_CODE","PP-DemoPortal-EC-IC-php");
?>