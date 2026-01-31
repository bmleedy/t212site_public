<?php
/**
 * Event Reminders Cron Script
 *
 * CLI-only script that sends email reminders for upcoming events.
 * Should be run via cron job (e.g., daily).
 *
 * This script:
 * - Checks for events happening within the next 24-48 hours
 * - Sends reminder emails to registered attendees
 * - Respects user notification preferences
 * - Logs all activity for audit trail
 *
 * Usage: php /path/to/reminders.php
 *
 * Cron example (run daily at 8am):
 *   0 8 * * * /usr/bin/php /var/www/public_html/scripts/reminders.php
 */

// Ensure this script can only run from CLI
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit('CLI only');
}

// Set error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to the api directory to use relative paths
$api_dir = dirname(__DIR__) . '/api';
chdir($api_dir);

// Include required files
require_once($api_dir . '/connect.php');
require_once(dirname(__DIR__) . '/login/config/config.php');
require_once(dirname(__DIR__) . '/login/translations/en.php');
require_once(dirname(__DIR__) . '/login/libraries/PHPMailer.php');
require_once(dirname(__DIR__) . '/includes/activity_logger.php');

/**
 * Create and configure a PHPMailer instance
 *
 * @return PHPMailer Configured mailer object
 */
function create_reminder_mailer() {
  $mail = new PHPMailer;

  if (defined('EMAIL_USE_SMTP') && EMAIL_USE_SMTP) {
    $mail->IsSMTP();
    $mail->SMTPAuth = EMAIL_SMTP_AUTH;
    if (defined('EMAIL_SMTP_ENCRYPTION')) {
      $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
    }
    $mail->Host = EMAIL_SMTP_HOST;
    $mail->Username = EMAIL_SMTP_USERNAME;
    $mail->Password = EMAIL_SMTP_PASSWORD;
    $mail->Port = EMAIL_SMTP_PORT;
  } else {
    $mail->IsMail();
  }

  return $mail;
}

/**
 * Send a reminder email to a user
 *
 * @param mysqli $mysqli Database connection
 * @param array $event Event data
 * @param array $user User data
 * @return bool True on success, false on failure
 */
function send_reminder_email($mysqli, $event, $user) {
  $mail = create_reminder_mailer();

  // Validate email address
  $recipient_email = $user['user_email'];
  if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email address for user {$user['user_id']}: $recipient_email");
    return false;
  }

  $mail->AddAddress($recipient_email);

  // Set from address (use configured email or default)
  $from_email = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@troop212atlanta.org';
  $from_name = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Troop 212 Atlanta';
  $mail->From = $from_email;
  $mail->FromName = $from_name;

  // Build subject line - escape for safety
  $event_name = htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8');
  $event_date = date('l, F j', strtotime($event['startDate']));
  $mail->Subject = "Reminder: $event_name - $event_date";

  // Build email body with escaped content
  $body = "Hello {$user['user_first']},\r\n\r\n";
  $body .= "This is a friendly reminder about the upcoming event:\r\n\r\n";
  $body .= "Event: $event_name\r\n";
  $body .= "Date: $event_date\r\n";
  $body .= "Start Time: " . date('g:i A', strtotime($event['startDate'])) . "\r\n";

  if (!empty($event['loc'])) {
    $body .= "Location: " . htmlspecialchars($event['loc'], ENT_QUOTES, 'UTF-8') . "\r\n";
  }

  $body .= "\r\nWe look forward to seeing you there!\r\n\r\n";
  $body .= "- Troop 212 Atlanta\r\n";

  $mail->Body = $body;

  if (!$mail->Send()) {
    // Log failed email
    log_activity(
      $mysqli,
      'reminder_email_failed',
      array(
        'event_id' => $event['id'],
        'user_id' => $user['user_id'],
        'error' => $mail->ErrorInfo
      ),
      false,
      "Failed to send reminder email for event {$event['id']} to user {$user['user_id']}",
      0
    );
    error_log("Failed to send reminder to {$user['user_email']}: " . $mail->ErrorInfo);
    return false;
  }

  // Log successful email
  log_activity(
    $mysqli,
    'reminder_email_sent',
    array(
      'event_id' => $event['id'],
      'user_id' => $user['user_id'],
      'email' => $recipient_email
    ),
    true,
    "Reminder email sent for event {$event['id']} to user {$user['user_id']}",
    0
  );

  return true;
}

// Main execution
echo "Starting event reminders script...\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Find events happening in the next 24-48 hours that haven't had reminders sent
$query = "SELECT e.id, e.name, e.startDate, e.loc
          FROM events e
          WHERE e.startDate BETWEEN DATE_ADD(NOW(), INTERVAL 24 HOUR)
                                AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
          AND e.active = 1
          AND e.id NOT IN (
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(params, '$.event_id'))
            FROM activity_log
            WHERE action = 'reminder_batch_sent'
            AND ts > DATE_SUB(NOW(), INTERVAL 48 HOUR)
          )";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
  error_log("Failed to prepare events query: " . $mysqli->error);
  exit(1);
}

$stmt->execute();
$events_result = $stmt->get_result();

$total_events = 0;
$total_emails_sent = 0;
$total_emails_failed = 0;

while ($event = $events_result->fetch_assoc()) {
  $total_events++;
  echo "Processing event: {$event['name']} (ID: {$event['id']})\n";
  echo "  Date: {$event['startDate']}\n";

  // Get registered attendees for this event who have opted in to event reminders
  $attendees_query = "SELECT DISTINCT u.user_id, u.user_first, u.user_email, u.notif_preferences
                      FROM registration r
                      JOIN users u ON r.user_id = u.user_id
                      WHERE r.event_id = ?
                      AND u.user_email IS NOT NULL
                      AND u.user_email != ''";

  $attendees_stmt = $mysqli->prepare($attendees_query);
  $attendees_stmt->bind_param('i', $event['id']);
  $attendees_stmt->execute();
  $attendees_result = $attendees_stmt->get_result();

  $event_emails_sent = 0;
  $event_emails_skipped = 0;

  while ($user = $attendees_result->fetch_assoc()) {
    // Check notification preferences
    $send_reminder = true;  // Default: opt-in

    if (!empty($user['notif_preferences'])) {
      $prefs = json_decode($user['notif_preferences'], true);
      // Check 'evnt' (Event) preference
      if (isset($prefs['evnt']) && $prefs['evnt'] === false) {
        $send_reminder = false;
        $event_emails_skipped++;
      }
    }

    if ($send_reminder) {
      if (send_reminder_email($mysqli, $event, $user)) {
        $event_emails_sent++;
        $total_emails_sent++;
      } else {
        $total_emails_failed++;
      }
    }
  }

  $attendees_stmt->close();

  echo "  Emails sent: $event_emails_sent, Skipped (opt-out): $event_emails_skipped\n";

  // Log that we've processed this event
  log_activity(
    $mysqli,
    'reminder_batch_sent',
    array(
      'event_id' => $event['id'],
      'event_name' => $event['name'],
      'emails_sent' => $event_emails_sent,
      'emails_skipped' => $event_emails_skipped
    ),
    true,
    "Reminder batch sent for event {$event['id']}: $event_emails_sent emails",
    0
  );
}

$stmt->close();

echo "\n";
echo "Summary:\n";
echo "  Events processed: $total_events\n";
echo "  Emails sent: $total_emails_sent\n";
echo "  Emails failed: $total_emails_failed\n";
echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";

$mysqli->close();
exit(0);
?>
