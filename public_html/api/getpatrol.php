<?php
/**
 * Get Patrol API
 *
 * Returns detailed patrol information including leaders and all scouts in the patrol.
 * Contains contact information (email, phone) so requires authorization.
 *
 * Authorization: User must be in the patrol OR have pl/er/wm/sa permission.
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

require_ajax();
$current_user_id = require_authentication();

// Validate CSRF token
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Validate inputs
$patrol_id = validate_int_post('patrol_id', true);

// Authorization check: User can access patrol data if:
// 1. They are in the requested patrol
// 2. They have elevated permissions (pl, er, wm, sa)
$authorized = false;

// Check if user has elevated permissions
if (has_permission('pl') || has_permission('er') || has_permission('wm') || has_permission('sa')) {
    $authorized = true;
}

// Check if user is in this patrol
if (!$authorized) {
    $checkPatrolQuery = "SELECT patrol_id FROM scout_info WHERE user_id = ?";
    $checkStmt = $mysqli->prepare($checkPatrolQuery);
    $checkStmt->bind_param('i', $current_user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $patrolCheck = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($patrolCheck && $patrolCheck['patrol_id'] == $patrol_id) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode(['status' => 'Error', 'message' => 'Not authorized to view this patrol\'s data']);
    log_activity(
        $mysqli,
        'get_patrol',
        array('patrol_id' => $patrol_id),
        false,
        "Unauthorized attempt to access patrol ID: $patrol_id",
        $current_user_id
    );
    exit;
}

$scouts = null;
$patrol = getLabel('patrols',$patrol_id,$mysqli);
$patrol_leader = "";
$asst_pl = "";
$troop_guide = "";

// Patrol Leader = 1, APL=2, TG=10
// SPL = 3, ASPL = 4, Staff=1

// if staff...
if ($patrol_id==1) {
  $pl = 3;
  $apl = 4;
} else {
  $pl = 1;
  $apl = 2;
}

// get PL
$stmt = $mysqli->prepare("SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id");
$stmt->bind_param("ii", $pl, $patrol_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_object()) {
  if ($patrol_leader == "") {
    $patrol_leader = escape_html($row->user_first) . " " . escape_html($row->user_last);
  } else {
    $patrol_leader = $patrol_leader . ", " . escape_html($row->user_first) . " " . escape_html($row->user_last);
  }
}
$stmt->close();

$stmt = $mysqli->prepare("SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id");
$stmt->bind_param("ii", $apl, $patrol_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_object()) {
  if ($asst_pl == "") {
    $asst_pl = escape_html($row->user_first) . " " . escape_html($row->user_last);
  } else {
    $asst_pl = $asst_pl . ", " . escape_html($row->user_first) . " " . escape_html($row->user_last);
  }
}
$stmt->close();

$tg_position = 10;
$stmt = $mysqli->prepare("SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id=? AND si.patrol_id=? AND u.user_id = si.user_id");
$stmt->bind_param("ii", $tg_position, $patrol_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_object()) {
  if ($troop_guide == "") {
    $troop_guide = escape_html($row->user_first) . " " . escape_html($row->user_last);
  } else {
    $troop_guide = $troop_guide . ", " . escape_html($row->user_first) . " " . escape_html($row->user_last);
  }
}
$stmt->close();

$leaderData = '<div class="row"><div class="large-4 columns"><label>Patrol Leader</label>'.$patrol_leader.'</div>';
$leaderData = $leaderData . '<div class="large-4 columns"><label>Asst Patrol Leader</label>'.$asst_pl.'</div>';
$leaderData = $leaderData . '<div class="large-4 columns"><label>Troop Guide</label>'.$troop_guide.'</div>';
$leaderData = $leaderData . '</div>';


$stmt = $mysqli->prepare("SELECT u.user_name, u.user_first, u.user_last, u.user_email, u.user_id, si.patrol_id, si.rank_id, si.position_id FROM users as u INNER JOIN scout_info as si ON u.user_id=si.user_id WHERE si.patrol_id=? ORDER BY si.rank_id desc, u.user_last asc, u.user_first asc");
$stmt->bind_param("i", $patrol_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_object()) {
  $id =  $row->user_id;
  $phones = null;
  $stmt2 = $mysqli->prepare("SELECT * FROM phone WHERE user_id=?");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();
  if ($results2) {
    while ($row2 = $results2->fetch_object()){
      $phones[] = escape_html($row2->phone) . " " . escape_html($row2->type);
    }
  }
  $stmt2->close();
  $rank = getLabel('ranks',$row->rank_id,$mysqli);
  $position = getLabel('leadership',$row->position_id,$mysqli);

  $scouts[] = [
    'first' => escape_html($row->user_first),
    'last' => escape_html($row->user_last),
    'email'=> escape_html($row->user_email),
    'username'=> escape_html($row->user_name),
    'rank'=> escape_html($rank),
    'position'=> escape_html($position),
    'id'=>$id,
    'phone'=>$phones
  ];
}
$stmt->close();


$returnMsg = array(
  'patrol_name' => escape_html($patrol),
  'leaderData' => $leaderData,
  'scouts' => $scouts
);

echo json_encode($returnMsg);

// Log successful access to patrol data (contains sensitive contact info)
log_activity(
    $mysqli,
    'get_patrol',
    array('patrol_id' => $patrol_id, 'patrol_name' => $patrol, 'scout_count' => count($scouts ?? [])),
    true,
    "Retrieved patrol details for patrol ID: $patrol_id ($patrol)",
    $current_user_id
);

die();


function getLabel($strTable,$id,$mysqli){
  if ($id) {
    // Whitelist allowed tables
    $allowed_tables = ['patrols', 'ranks', 'leadership'];
    if (!in_array($strTable, $allowed_tables)) {
      return '';
    }

    $stmt = $mysqli->prepare("SELECT label FROM $strTable WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['label'];
  } else {
    return "";
  }
}
?>
