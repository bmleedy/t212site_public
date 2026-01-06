<?php
# ini_set('error_log', '/home/u321706752/public_html/error.log');

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
require_once('../login/config/config.php');
require_once('../login/translations/en.php');
require_once('../login/libraries/PHPMailer.php');
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

  error_log("Sending email to " . $sendTo . " from " . $from . " (" . $fromName . ") with subject: " . $subject);
	if ($sendTo=="scout parents") {
    // Get emails of all parents of the scout, checking their notification preferences
		$query = "SELECT parent.user_email, parent.notif_preferences FROM users as u JOIN users AS parent ON u.family_id = parent.family_id WHERE parent.user_type IN ('mom','dad') AND u.user_id=" . $user_id;
		$results = $mysqli->query($query);
		while ($row = $results->fetch_assoc()) {
      // Check if parent wants scout signup emails
      $send_email = true;  // Default: send email (opted in)

      if ($row['notif_preferences']) {
        $prefs = json_decode($row['notif_preferences'], true);
        // Check 'scsu' (Scout SignUp) preference
        // Only skip email if explicitly set to false
        if (isset($prefs['scsu']) && $prefs['scsu'] === false) {
          $send_email = false;
          error_log("Parent " . $row['user_email'] . " has opted out of scout signup emails");
        }
      }

      if ($send_email) {
        error_log("Adding email address: " . $row['user_email']);
        $mail->AddAddress($row['user_email']);
      }
		}
	} else {
    error_log("Sending email to " . $sendTo);
		$mail->AddAddress($sendTo);
	}


$mail->From = $from;
$mail->FromName = $fromName;
//$mail->AddAddress("mscdaryl@gmail.com");
$mail->Subject = $subject;

$newLine = "\r\n";

$body = $message . $newLine . $newLine . $link;
		
// the link to your register.php, please set this value in config/email_verification.php
$mail->Body = $body;
error_log("Sending email!)");
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
error_log("Email sent successfully - or not!");
echo json_encode($returnMsg);
die;
?>