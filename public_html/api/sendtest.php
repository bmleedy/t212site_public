<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){

  // respond to Ajax request

} else {

	echo "Not sure what you are after, but it ain't here.";

  die();

}
require_once($_SERVER['DOCUMENT_ROOT'].'/login/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/login/translations/en.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/login/libraries/PHPMailer.php');
require 'connect.php';
$sendTo = $_POST['sendTo'];
$from = $_POST['from'];
$user_id = $_POST['user_id'];
$fromName = $_POST['fromName'];
$subject = $_POST['subject'];
$message = $_POST['message'];
$link = $_POST['link'];

$mail = new PHPMailer;
if (EMAIL_USE_SMTP) {
  // Set mailer to use SMTP
  $mail->IsSMTP();
  //useful for debugging, shows full SMTP errors
  //$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
  // Enable SMTP authentication
  $mail->SMTPAuth = EMAIL_SMTP_AUTH;
  // Enable encryption, usually SSL/TLS
  if (defined(EMAIL_SMTP_ENCRYPTION)) {
    $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
  }
  // Specify host server
  $mail->Host = EMAIL_SMTP_HOST;
  $mail->Username = EMAIL_SMTP_USERNAME;
  $mail->Password = EMAIL_SMTP_PASSWORD;
  $mail->Port = EMAIL_SMTP_PORT;
} else {
  $mail->IsMail();
}

if (is_array($sendTo)) {
	foreach ($sendTo as $value) {
		$mail->AddAddress($value);
	}
} else {
	if ($sendTo=="scout parents") {
		$query = "SELECT user_email FROM relationships AS r, users AS u WHERE r.adult_id=u.user_id AND r.scout_id=".$user_id;
		$results = $mysqli->query($query);
		while ($row = $results->fetch_assoc()) {
			$mail->AddAddress($row['user_email']);
		}
	} else {
		$mail->AddAddress($sendTo);
	}
}

$mail->From = $from;
$mail->FromName = $fromName;
//$mail->AddAddress("mscdaryl@gmail.com");
$mail->Subject = $subject;

$newLine = "\r\n";

$body = $message . $newLine . $newLine . $link;
		
// the link to your register.php, please set this value in config/email_verification.php
$mail->Body = $body;
if(!$mail->Send()) {
	$returnMsg = array(
		'status' => "Error",
		'info' => $mail->ErrorInfo
	);
} else {
	$returnMsg = array(
		'status' => "Success!"
	);
}
echo json_encode($returnMsg);
die;
?>