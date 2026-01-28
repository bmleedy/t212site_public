<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();
// Authorization: Requires 'oe' (Outing Edit) or 'sa' (Super Admin) permission
// This allows event organizers and admins to process approvals
// Note: Parents view the form via getapprove.php (requires family relationship)
// but the actual approval is processed by someone with oe/sa permission
require_permission(['oe', 'sa']);
require_csrf();
header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$user_id = validate_int_post('user_id');
$reg_id = validate_int_post('reg_id');
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
