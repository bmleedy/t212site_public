<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();
require_csrf();
header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$user_id = validate_int_post('user_id');
$reg_id = validate_int_post('reg_id');

// Authorization: allow if user has 'oe'/'sa' permission, OR is in the same family as the scout
if (!has_permission('oe') && !has_permission('sa')) {
    // Look up the scout's user_id from the registration
    $auth_query = "SELECT r.user_id AS scout_id, scout.family_id AS scout_family_id, parent.family_id AS parent_family_id
                   FROM registration r
                   JOIN users scout ON scout.user_id = r.user_id
                   JOIN users parent ON parent.user_id = ?
                   WHERE r.id = ?";
    $auth_stmt = $mysqli->prepare($auth_query);
    $auth_stmt->bind_param('ii', $current_user_id, $reg_id);
    $auth_stmt->execute();
    $auth_result = $auth_stmt->get_result();
    $auth_row = $auth_result->fetch_assoc();
    $auth_stmt->close();

    if (!$auth_row || $auth_row['scout_family_id'] === '' || $auth_row['scout_family_id'] !== $auth_row['parent_family_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        die();
    }
}
$spec = validate_string_post('spec_instructions', false, '', 500);
$medical = validate_string_post('medical', false, '', 500);

// if at least one of the text fields is blank, no need to insert <br> to separate
if ($spec=='' || $medical=='') {
  $spec_inst = $spec . $medical;
} else {
  $spec_inst = $spec . "<br>" . $medical;
}
$ts_now = date('Y-m-d H:i:s');

$query = "UPDATE registration SET approved_by=?, ts_approved=?, spec_instructions=? WHERE id=?";
$statement = $mysqli->prepare($query);
$statement->bind_param('issi', $user_id, $ts_now, $spec_inst, $reg_id);

if ($statement->execute()) {
  $statement->close();

  // Log successful approval
  log_activity(
    $mysqli,
    'approve_registration',
    array('reg_id' => $reg_id, 'approved_by' => $user_id),
    true,
    "Registration $reg_id approved by user $user_id",
    $user_id
  );

  $returnMsg = array(
   'status' => 'Success',
   'message' => 'You have approved this event registration.'
  );
  echo json_encode($returnMsg);
} else {
  $error = $statement->error;
  $statement->close();

  // Log failed approval
  log_activity(
    $mysqli,
    'approve_registration',
    array('reg_id' => $reg_id, 'error' => $error),
    false,
    "Failed to approve registration $reg_id",
    $user_id
  );

  http_response_code(500);
  echo json_encode(['error' => 'Failed to process approval']);
}
die;
?>
