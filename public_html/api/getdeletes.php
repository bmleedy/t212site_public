<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
// Require admin permissions to view deleted users
require_permission(['ue', 'sa', 'wm']);

header('Content-Type: application/json');
require 'connect.php';

$query = "SELECT * FROM users WHERE user_type = 'Delete' ORDER BY user_last asc, user_first asc";
$results = $mysqli->query($query);
$adults = null;

while ($row = $results->fetch_object()) {
  $id = $row->user_id;
  $phones = null;

  $query2 = "SELECT * FROM phone WHERE user_id=?";
  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('i', $id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  if ($results2) {
    while ($row2 = $results2->fetch_object()) {
      $phones[] = "<a href='tel:" . escape_html($row2->phone) . "'>" . escape_html($row2->phone) . "</a> " . escape_html($row2->type);
    }
  }
  $stmt2->close();

  $adults[] = [
    'first' => escape_html($row->user_first),
    'last' => escape_html($row->user_last),
    'email' => escape_html($row->user_email),
    'id' => $id,
    'phone' => $phones
  ];
}

echo json_encode($adults);
die();
?>
