<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

require_once('../login/config/config.php');
require_once('../login/translations/en.php');
require_once('../login/libraries/PHPMailer.php');
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

error_log("Sending email to " . $sendTo . " from " . $from . " (" . $fromName . ") with subject: " . $subject);

if ($sendTo == "scout parents") {
  // Get emails of all parents of the scout, checking their notification preferences
  $query = "SELECT parent.user_email, parent.notif_preferences
            FROM users as u
            JOIN users AS parent ON u.family_id = parent.family_id
            WHERE parent.user_type IN ('mom','dad') AND u.user_id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $results = $stmt->get_result();

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
  $stmt->close();
} else {
  error_log("Sending email to " . $sendTo);
  $mail->AddAddress($sendTo);
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

error_log("Sending email!)");
if (!$mail->Send()) {
  // Log failed email send
  log_activity(
    $mysqli,
    'send_email_failed',
    array(
      'to' => $recipients,
      'subject' => $subject,
      'from' => $from,
      'error' => $mail->ErrorInfo
    ),
    false,
    "Failed to send email: " . $subject,
    $user_id
  );

  $returnMsg = array(
    'status' => "Error",
    'info' => escape_html($mail->ErrorInfo)
  );
} else {
  // Log successful email send
  log_activity(
    $mysqli,
    'send_email',
    array(
      'to' => $recipients,
      'subject' => $subject,
      'from' => $from,
      'recipient_count' => count($recipients)
    ),
    true,
    "Email sent: " . $subject . " to " . count($recipients) . " recipient(s)",
    $user_id
  );

  $returnMsg = array(
    'status' => "Success!"
  );
}
error_log("Email sent successfully - or not!");
echo json_encode($returnMsg);
die();
?>
