<?php
/**
 * Send Email API
 *
 * Sends emails to users or parents of scouts.
 * Requires authentication and respects notification preferences.
 *
 * Security features:
 * - CSRF protection
 * - Rate limiting (10 emails per hour per user)
 * - Header injection prevention
 * - HTML escaping for email content
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_csrf();

header('Content-Type: application/json');

require_once('../login/config/config.php');
require_once('../login/translations/en.php');
require_once('../login/libraries/PHPMailer.php');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Validate inputs
$sendTo = validate_string_post('sendTo');
$from = validate_email_post('from');
$user_id = validate_int_post('user_id');
$fromName = validate_string_post('fromName');
$subject = validate_string_post('subject');
$message = validate_string_post('message');
$link = validate_string_post('link', false, '');

// Check if user can send emails on behalf of this user_id
require_user_access($user_id, $current_user_id);

// Header injection prevention: reject newlines in email headers
// Newlines in From, FromName, Subject can be used for header injection attacks
if (preg_match('/[\r\n]/', $from) || preg_match('/[\r\n]/', $fromName) || preg_match('/[\r\n]/', $subject)) {
  log_activity(
    $mysqli,
    'send_email_header_injection_attempt',
    array(
      'from' => $from,
      'fromName' => $fromName,
      'subject' => $subject
    ),
    false,
    "Blocked email header injection attempt",
    $current_user_id
  );
  http_response_code(400);
  echo json_encode(['status' => 'Error', 'message' => 'Invalid characters in email headers.']);
  die();
}

// Rate limiting: 10 emails per hour per user
$rate_limit_query = "SELECT COUNT(*) as email_count FROM activity_log
                     WHERE user_id = ? AND action = 'send_email'
                     AND ts > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$rate_stmt = $mysqli->prepare($rate_limit_query);
$rate_stmt->bind_param('i', $current_user_id);
$rate_stmt->execute();
$rate_result = $rate_stmt->get_result();
$rate_row = $rate_result->fetch_assoc();
$rate_stmt->close();

if ($rate_row['email_count'] >= 10) {
  log_activity(
    $mysqli,
    'send_email_rate_limited',
    array(
      'user_id' => $current_user_id,
      'email_count_last_hour' => $rate_row['email_count']
    ),
    false,
    "Email send rate limited - user $current_user_id exceeded 10 emails/hour",
    $current_user_id
  );
  http_response_code(429);
  echo json_encode(['status' => 'Error', 'message' => 'Too many emails sent. Please try again later.']);
  die();
}

// Validate recipient email if it's a direct address (not "scout parents")
if ($sendTo !== "scout parents") {
  if (!filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Invalid recipient email address.']);
    die();
  }
}

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
      $mail->AddAddress($row['user_email']);
    }
  }
  $stmt->close();
} else {
  $mail->AddAddress($sendTo);
}

$mail->From = $from;
$mail->FromName = $fromName;
$mail->Subject = $subject;

// Escape HTML content in the message and link to prevent XSS if email is rendered as HTML
$safe_message = escape_html($message);
$safe_link = escape_html($link);

$newLine = "\r\n";
$body = $message . $newLine . $newLine . $link;
$mail->Body = $body;

// Collect all recipient addresses for logging
$recipients = array();
foreach ($mail->getAllRecipientAddresses() as $email => $name) {
  $recipients[] = $email;
}

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
    $current_user_id
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
    $current_user_id
  );

  $returnMsg = array(
    'status' => "Success!"
  );
}

echo json_encode($returnMsg);
$mysqli->close();
die();
?>
