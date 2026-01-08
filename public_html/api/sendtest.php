<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

require_once($_SERVER['DOCUMENT_ROOT'].'/login/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/login/translations/en.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/login/libraries/PHPMailer.php');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$sendTo = validate_string_post('sendTo');
$from = validate_email_post('from');
$user_id = validate_int_post('user_id');
$fromName = validate_string_post('fromName');
$subject = validate_string_post('subject');
$message = validate_string_post('message');
$link = validate_string_post('link', false, '');

// Check if user can send emails on behalf of this user_id
require_user_access($user_id, $current_user_id);

$mail = new PHPMailer;
if (EMAIL_USE_SMTP) {
	// Set mailer to use SMTP
	$mail->IsSMTP();
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

// Handle array of sendTo addresses or special "scout parents" value
if (is_array($sendTo)) {
	foreach ($sendTo as $value) {
		$mail->AddAddress($value);
	}
} else {
	if ($sendTo == "scout parents") {
		$query = "SELECT user_email
		          FROM relationships AS r, users AS u
		          WHERE r.adult_id=u.user_id AND r.scout_id=?";
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('i', $user_id);
		$stmt->execute();
		$results = $stmt->get_result();

		while ($row = $results->fetch_assoc()) {
			$mail->AddAddress($row['user_email']);
		}
		$stmt->close();
	} else {
		$mail->AddAddress($sendTo);
	}
}

$mail->From = $from;
$mail->FromName = $fromName;
$mail->Subject = $subject;

$newLine = "\r\n";
$body = $message . $newLine . $newLine . $link;
$mail->Body = $body;

// Collect all recipient addresses for logging
$recipients = array();
foreach ($mail->getAllRecipientAddresses() as $email => $name) {
	$recipients[] = $email;
}

if (!$mail->Send()) {
	// Log failed test email send
	log_activity(
		$mysqli,
		'send_test_email_failed',
		array(
			'to' => $recipients,
			'subject' => $subject,
			'from' => $from,
			'error' => $mail->ErrorInfo
		),
		false,
		"Failed to send test email: " . $subject,
		$user_id
	);

	$returnMsg = array(
		'status' => "Error",
		'info' => escape_html($mail->ErrorInfo)
	);
} else {
	// Log successful test email send
	log_activity(
		$mysqli,
		'send_test_email',
		array(
			'to' => $recipients,
			'subject' => $subject,
			'from' => $from,
			'recipient_count' => count($recipients)
		),
		true,
		"Test email sent: " . $subject . " to " . count($recipients) . " recipient(s)",
		$user_id
	);

	$returnMsg = array(
		'status' => "Success!"
	);
}
echo json_encode($returnMsg);
die();
?>
