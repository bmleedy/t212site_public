<?php
$activity_log_prefix = __DIR__ . '/../registration_logs/event_';
$activity_log_suffix = '.log';

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
$user_id = $_POST['user_id'];
$event_id = $_POST['event_id'];
$action = $_POST['action'];
$user_type = $_POST['user_type'];
$seat_belts = $_POST['seat_belts'];
$paid = $_POST['paid'];
$drive = $_POST['drive'];
$pay_id = 0;

error_log("you have reached here with action: " . $action . " for user_id: " . $user_id . " event_id: " . $event_id);

// Log the registration actions taken on each event.
$message = date('Y-m-d H:i:s') . " - User ID " . $user_id . " Action: " . $action . " Event ID: " . $event_id . " Seat Belts: " . $seat_belts . " Paid: " . $paid . " Drive: " . $drive . "\n";
file_put_contents($activity_log_prefix . $event_id . $activity_log_suffix, $message, FILE_APPEND | LOCK_EX);


if ($action=="cancel") {
  $attending=0;
  $query = "UPDATE registration SET attending=? WHERE event_id=? AND user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('sss', $attending, $event_id, $user_id);
  $statement->execute();
  $statement->close();

  // Send notification to Scout in Charge and Adult in Charge
  sendCancellationNotification($event_id, $user_id, $mysqli);

  // Log cancellation
  log_activity(
    $mysqli,
    'cancel_registration',
    array('event_id' => $event_id, 'user_id' => $user_id),
    true,
    "User cancelled registration for event $event_id",
    $user_id
  );

  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Cancelled',
    'message' => 'Your registration for this event has been cancelled.'
  );
  echo json_encode($returnMsg);
  die;
}

if ($action=="pay") {
  $paid=1;
  $query = "UPDATE registration SET paid=? WHERE event_id=? AND user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('sss', $paid, $event_id, $user_id);
  $statement->execute();
  $statement->close();

  // Log payment update
  log_activity(
    $mysqli,
    'mark_registration_paid',
    array('event_id' => $event_id, 'user_id' => $user_id),
    true,
    "Registration marked as paid for event $event_id",
    $user_id
  );

  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Yes',
    'message' => 'Your registration for this event has been paid.'
  );
  echo json_encode($returnMsg);
  die;
}

if ($action=="restore") {
  error_log("Restoring registration for user_id: " . $user_id . " event_id: " . $event_id);
  $attending=1;
  $query = "UPDATE registration SET attending=?, seat_belts=?, drive='na' WHERE event_id=? AND user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('ssss', $attending, $seat_belts, $event_id, $user_id);
  $statement->execute();
  $statement->close();

  // Log restoration
  log_activity(
    $mysqli,
    'restore_registration',
    array('event_id' => $event_id, 'user_id' => $user_id, 'seat_belts' => $seat_belts),
    true,
    "Registration restored for event $event_id",
    $user_id
  );

  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Yes',
    'message' => 'Your registration for this event has been reinstated.'
  );
  echo json_encode($returnMsg);
  die;
}

if ($action=="seatbelts") {
  $attending=1;
  $query = "UPDATE registration SET seat_belts=?, drive='na' WHERE event_id=? AND user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('sss', $seat_belts, $event_id, $user_id);
  $statement->execute();
  $statement->close();

  // Log seat belt update
  log_activity(
    $mysqli,
    'update_seatbelts',
    array('event_id' => $event_id, 'user_id' => $user_id, 'seat_belts' => $seat_belts),
    true,
    "Updated seat belts to $seat_belts for event $event_id",
    $user_id
  );

  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Yes',
    'message' => 'Number of seat belts has been updated.'
  );
  echo json_encode($returnMsg);
  die;
}

// If we get this far, it is an add, first check to make sure it does not already exist
$query = "SELECT id FROM registration WHERE event_id=" . $event_id . " AND user_id=" . $user_id ;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
if ($row) {
  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Yes',
    'message' => 'You are already signed up!'
  );
  echo json_encode($returnMsg);
  die;
}

$query = "INSERT INTO registration (event_id, user_id, approved_by, paid, nbrInGroup, seat_belts, ts_register, seat_belts_return, drive, ts_approved, ts_paid, spec_instructions, pp_token) VALUES(?,?,?,?,0,?,?,0,'no',0,0,'',0)";
$statement = $mysqli->prepare($query);
if ($statement === false) {
  echo ($mysqli->error);
  die;
}
if ($user_type=="Scout") {
  $approved_by = 0; // Scouts are not approved by anyone, so we set this to 0;
} else {
  $approved_by = $user_id ;
}
$ts_now = date('Y-m-d H:i:s');

$rs = $statement->bind_param('ssssss', $event_id, $user_id, $approved_by, $paid, $seat_belts, $ts_now);
if($rs == false) {
  echo ($statement->error);
  die;
}
if($statement->execute()){
  // Log new registration
  log_activity(
    $mysqli,
    'new_registration',
    array('event_id' => $event_id, 'user_id' => $user_id, 'seat_belts' => $seat_belts, 'paid' => $paid),
    true,
    "New registration created for event $event_id",
    $user_id
  );

  $returnMsg = array(
    'status' => 'Success',
    'signed_up' => 'Yes',
    'message' => 'You are now signed up for this event!'
  );
  echo json_encode($returnMsg);
}else{
  // Log failed registration
  log_activity(
    $mysqli,
    'new_registration',
    array('event_id' => $event_id, 'user_id' => $user_id, 'error' => $mysqli->error),
    false,
    "Failed to create registration for event $event_id",
    $user_id
  );

  echo ( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
  die;
}
$statement->close();



/**
 * Send cancellation notification to Scout in Charge and Adult in Charge
 *
 * Includes idempotency check to prevent duplicate emails within 5 minutes.
 *
 * @param int $event_id The event ID
 * @param int $user_id The user who cancelled
 * @param mysqli $mysqli Database connection
 */
function sendCancellationNotification($event_id, $user_id, $mysqli) {
  // Idempotency check: prevent duplicate cancellation emails within 5 minutes
  $idempotency_query = "SELECT id FROM activity_log
                        WHERE action = 'send_cancellation_email'
                        AND JSON_EXTRACT(values_json, '$.event_id') = ?
                        AND JSON_EXTRACT(values_json, '$.cancelled_by_user_id') = ?
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        LIMIT 1";
  $idempotency_stmt = $mysqli->prepare($idempotency_query);
  if ($idempotency_stmt) {
    $idempotency_stmt->bind_param('ss', $event_id, $user_id);
    $idempotency_stmt->execute();
    $idempotency_result = $idempotency_stmt->get_result();
    if ($idempotency_result->num_rows > 0) {
      error_log("Cancellation email already sent for event $event_id user $user_id within 5 minutes - skipping duplicate");
      $idempotency_stmt->close();
      return;
    }
    $idempotency_stmt->close();
  }

  // Get event details and SIC/AIC IDs
  $query = "SELECT name, sic_id, aic_id FROM events WHERE id = ?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('s', $event_id);
  $statement->execute();
  $result = $statement->get_result();
  $event = $result->fetch_assoc();
  $statement->close();

  if (!$event) {
    error_log("Could not find event $event_id for cancellation notification");
    return;
  }

  $event_name = $event['name'];
  $sic_id = $event['sic_id'];
  $aic_id = $event['aic_id'];

  // Get user who cancelled
  $query = "SELECT user_first, user_last FROM users WHERE user_id = ?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('s', $user_id);
  $statement->execute();
  $result = $statement->get_result();
  $user = $result->fetch_assoc();
  $statement->close();

  if (!$user) {
    error_log("Could not find user $user_id for cancellation notification");
    return;
  }

  $user_name = $user['user_first'] . ' ' . $user['user_last'];

  // Collect recipient emails (SIC and AIC who want cancellation notifications)
  $recipients = array();

  // Check Scout in Charge
  if ($sic_id > 0) {
    $query = "SELECT user_email, notif_preferences, user_first, user_last FROM users WHERE user_id = ?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('s', $sic_id);
    $statement->execute();
    $result = $statement->get_result();
    $sic = $result->fetch_assoc();
    $statement->close();

    if ($sic) {
      $send_to_sic = true;  // Default: send (opted in)

      if ($sic['notif_preferences']) {
        $prefs = json_decode($sic['notif_preferences'], true);
        // Check 'canc' (Cancellation) preference
        if (isset($prefs['canc']) && $prefs['canc'] === false) {
          $send_to_sic = false;
          error_log("SIC " . $sic['user_email'] . " has opted out of cancellation notifications");
        }
      }

      if ($send_to_sic) {
        $recipients[] = array(
          'email' => $sic['user_email'],
          'name' => $sic['user_first'] . ' ' . $sic['user_last'],
          'role' => 'Scout in Charge'
        );
      }
    }
  }

  // Check Adult in Charge
  if ($aic_id > 0) {
    $query = "SELECT user_email, notif_preferences, user_first, user_last FROM users WHERE user_id = ?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('s', $aic_id);
    $statement->execute();
    $result = $statement->get_result();
    $aic = $result->fetch_assoc();
    $statement->close();

    if ($aic) {
      $send_to_aic = true;  // Default: send (opted in)

      if ($aic['notif_preferences']) {
        $prefs = json_decode($aic['notif_preferences'], true);
        // Check 'canc' (Cancellation) preference
        if (isset($prefs['canc']) && $prefs['canc'] === false) {
          $send_to_aic = false;
          error_log("AIC " . $aic['user_email'] . " has opted out of cancellation notifications");
        }
      }

      if ($send_to_aic) {
        $recipients[] = array(
          'email' => $aic['user_email'],
          'name' => $aic['user_first'] . ' ' . $aic['user_last'],
          'role' => 'Adult in Charge'
        );
      }
    }
  }

  // If no recipients, don't send
  if (empty($recipients)) {
    error_log("No recipients for cancellation notification (event $event_id)");
    return;
  }

  // Load PHPMailer
  require_once('../login/config/config.php');
  require_once('../login/translations/en.php');
  require_once('../login/libraries/PHPMailer.php');

  $mail = new PHPMailer;
  if (EMAIL_USE_SMTP) {
    $mail->IsSMTP();
    $mail->SMTPAuth = EMAIL_SMTP_AUTH;
    if (defined(EMAIL_SMTP_ENCRYPTION)) {
      $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
    }
    $mail->Host = EMAIL_SMTP_HOST;
    $mail->Username = EMAIL_SMTP_USERNAME;
    $mail->Password = EMAIL_SMTP_PASSWORD;
    $mail->Port = EMAIL_SMTP_PORT;
  } else {
    $mail->IsMail();
  }

  $mail->From = "donotreply@t212.org";
  $mail->FromName = "Troop 212 Website";

  // Add all recipients
  foreach ($recipients as $recipient) {
    $mail->AddAddress($recipient['email']);
    error_log("Sending cancellation notification to " . $recipient['name'] . " (" . $recipient['role'] . "): " . $recipient['email']);
  }

  $mail->Subject = "Cancellation: $user_name cancelled for $event_name";

  $body = "Hello,\n\n";
  $body .= "$user_name has cancelled their registration for the event: $event_name.\n\n";
  $body .= "You are receiving this notification because you are the ";

  if (count($recipients) == 1) {
    $body .= $recipients[0]['role'];
  } else {
    $body .= "Scout in Charge or Adult in Charge";
  }

  $body .= " for this event.\n\n";
  $body .= "You can view the updated event roster at:\n";
  $body .= "http://www.t212.org/Event.php?id=$event_id\n\n";
  $body .= "If you do not want to receive these cancellation notifications, you can opt out in your notification preferences on your User profile page.\n\n";
  $body .= "- Troop 212 Website";

  $mail->Body = $body;

  if(!$mail->Send()) {
    error_log("Failed to send cancellation notification: " . $mail->ErrorInfo);

    // Log failed cancellation email
    $recipient_emails = array();
    foreach ($recipients as $r) {
      $recipient_emails[] = $r['email'];
    }

    log_activity(
      $mysqli,
      'send_cancellation_email_failed',
      array(
        'to' => $recipient_emails,
        'subject' => $mail->Subject,
        'event_id' => $event_id,
        'cancelled_by_user_id' => $user_id,
        'error' => $mail->ErrorInfo
      ),
      false,
      "Failed to send cancellation notification for event $event_id",
      $user_id
    );
  } else {
    error_log("Cancellation notification sent successfully for event $event_id");

    // Log successful cancellation email
    $recipient_emails = array();
    foreach ($recipients as $r) {
      $recipient_emails[] = $r['email'];
    }

    log_activity(
      $mysqli,
      'send_cancellation_email',
      array(
        'to' => $recipient_emails,
        'subject' => $mail->Subject,
        'event_id' => $event_id,
        'cancelled_by_user_id' => $user_id,
        'recipient_count' => count($recipient_emails)
      ),
      true,
      "Cancellation notification sent for event $event_id to " . count($recipient_emails) . " recipient(s)",
      $user_id
    );
  }
}

?>