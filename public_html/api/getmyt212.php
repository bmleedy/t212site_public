<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$user_id = validate_int_post('user_id');
$patrol_id = validate_int_post('patrol_id', false, null);

// Check if user can access this data (own data or admin)
require_user_access($user_id, $current_user_id);

$scouts = null;
$patrol = "";
$leaderData = "";
$pbdata = "";

// Get patrol name if patrol_id is provided
if ($patrol_id) {
  $query = "SELECT label FROM patrols WHERE id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $patrol_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $patrol = $row['label'];
  }
  $stmt->close();

  // Get patrol leader information
  $pl = ($patrol_id == '1') ? '3' : '1'; // SPL for Staff, PL for others
  $apl = ($patrol_id == '1') ? '4' : '2'; // ASPL for Staff, APL for others

  $patrol_leader = "";
  $asst_pl = "";
  $troop_guide = "";

  // Get PL
  $query = "SELECT u.user_id, user_first, user_last
            FROM users AS u, scout_info AS si
            WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ii', $pl, $patrol_id);
  $stmt->execute();
  $results = $stmt->get_result();
  while ($row = $results->fetch_object()) {
    if ($patrol_leader == "") {
      $patrol_leader = escape_html($row->user_first . " " . $row->user_last);
    } else {
      $patrol_leader = $patrol_leader . ", " . escape_html($row->user_first . " " . $row->user_last);
    }
  }
  $stmt->close();

  // Get APL
  $query = "SELECT u.user_id, user_first, user_last
            FROM users AS u, scout_info AS si
            WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ii', $apl, $patrol_id);
  $stmt->execute();
  $results = $stmt->get_result();
  while ($row = $results->fetch_object()) {
    if ($asst_pl == "") {
      $asst_pl = escape_html($row->user_first . " " . $row->user_last);
    } else {
      $asst_pl = $asst_pl . ", " . escape_html($row->user_first . " " . $row->user_last);
    }
  }
  $stmt->close();

  // Get Troop Guide
  $tg_position = 10;
  $query = "SELECT u.user_id, user_first, user_last
            FROM users AS u, scout_info AS si
            WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ii', $tg_position, $patrol_id);
  $stmt->execute();
  $results = $stmt->get_result();
  while ($row = $results->fetch_object()) {
    if ($troop_guide == "") {
      $troop_guide = escape_html($row->user_first . " " . $row->user_last);
    } else {
      $troop_guide = $troop_guide . ", " . escape_html($row->user_first . " " . $row->user_last);
    }
  }
  $stmt->close();

  $leaderData = '<div class="row"><div class="large-4 columns"><label>Patrol Leader</label>' . $patrol_leader . '</div>';
  $leaderData = $leaderData . '<div class="large-4 columns"><label>Asst Patrol Leader</label>' . $asst_pl . '</div>';
  $leaderData = $leaderData . '<div class="large-4 columns"><label>Troop Guide</label>' . $troop_guide . '</div>';
  $leaderData = $leaderData . '</div>';

  // Get scouts in patrol
  $query = "SELECT u.user_name, u.user_first, u.user_last, u.user_email, u.user_id, si.patrol_id, si.rank_id, si.position_id
            FROM users as u
            INNER JOIN scout_info as si ON u.user_id=si.user_id
            WHERE si.patrol_id=?
            ORDER BY si.rank_id desc, u.user_last asc, u.user_first asc";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $patrol_id);
  $stmt->execute();
  $results = $stmt->get_result();

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
        $phones[] = escape_html($row2->phone) . " " . escape_html($row2->type);
      }
    }
    $stmt2->close();

    $rank = getLabel('ranks', $row->rank_id, $mysqli);
    $position = getLabel('leadership', $row->position_id, $mysqli);

    $scouts[] = [
      'first' => escape_html($row->user_first),
      'last' => escape_html($row->user_last),
      'email' => escape_html($row->user_email),
      'username' => escape_html($row->user_name),
      'rank' => escape_html($rank),
      'position' => escape_html($position),
      'id' => $id,
      'phone' => $phones
    ];
  }
  $stmt->close();
}

$returnMsg = array(
  'patrol_name' => escape_html($patrol),
  'leaderData' => $leaderData,
  'pbData' => $pbdata,
  'scouts' => $scouts
);

echo json_encode($returnMsg);
die();

function getLabel($strTable, $id, $mysqli) {
  if ($id) {
    // Whitelist allowed tables to prevent SQL injection
    $allowed_tables = ['ranks', 'patrols', 'leadership'];
    if (!in_array($strTable, $allowed_tables)) {
      return "";
    }

    $query = "SELECT label FROM " . $strTable . " WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['label'] : "";
  } else {
    return "";
  }
}
?>
