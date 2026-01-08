<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$sort = validate_string_post('sort', false, 'name');
$scouts = null;

if ($sort == 'patrol') {
  $query = "
    SELECT u.user_name,
      u.user_first,
      u.user_last,
      u.user_email,
      u.user_id,
      si.patrol_id,
      si.rank_id,
      si.position_id
    FROM users as u
    LEFT OUTER JOIN scout_info as si ON u.user_id=si.user_id
    LEFT OUTER JOIN patrols as p ON si.patrol_id=p.id
    WHERE u.user_type='Scout'
      AND u.user_access <> 'del'
    ORDER BY p.label ASC,
      u.user_last ASC,
      u.user_first ASC
  ";
} else if ($sort == 'rank') {
  $query = "
    SELECT u.user_name,
      u.user_first,
      u.user_last,
      u.user_email,
      u.user_id,
      si.patrol_id,
      si.rank_id,
      si.position_id
    FROM users as u
    LEFT OUTER JOIN scout_info as si ON u.user_id=si.user_id
    LEFT OUTER JOIN patrols as p ON si.patrol_id=p.id
    WHERE u.user_type='Scout'
      AND u.user_access <> 'del'
    ORDER BY si.rank_id,
      u.user_last ASC,
      u.user_first ASC
  ";
} else {
  $query = "
    SELECT u.user_name,
      u.user_first,
      u.user_last,
      u.user_email,
      u.user_id,
      si.patrol_id,
      si.rank_id,
      si.position_id
    FROM users as u
    LEFT OUTER JOIN scout_info as si ON u.user_id=si.user_id
    LEFT OUTER JOIN patrols as p ON si.patrol_id=p.id
    WHERE u.user_type='Scout'
      AND u.user_access <> 'del'
    ORDER BY u.user_last ASC,
      u.user_first ASC
  ";
}

$results = $mysqli->query($query);

while ($row = $results->fetch_object()) {
  $id = $row->user_id;
  $phones = null;
  $rank = getLabel('ranks', $row->rank_id, $mysqli);
  $patrol = getLabel('patrols', $row->patrol_id, $mysqli);
  $position = getLabel('leadership', $row->position_id, $mysqli);

  // Use prepared statement for phone query
  $query2 = "
    WITH adults AS (
      SELECT user_id
      FROM users
      WHERE family_id IN (SELECT family_id FROM users WHERE user_id = ?)
        AND NOT user_type='scout'
    ),
    adult_phones AS (
      SELECT DISTINCT REGEXP_REPLACE(phone, '[^0-9]', '') AS phone
      FROM phone
      JOIN adults ON adults.user_id = phone.user_id
    )
    SELECT *,
      IF(REGEXP_REPLACE(phone, '[^0-9]', '') IN (SELECT * FROM adult_phones), True, False) AS is_adult_phone
    FROM phone
    WHERE user_id = ?
  ";

  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('ii', $id, $id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  if ($results2) {
    while ($row2 = $results2->fetch_object()) {
      $phone_entry = "<a href='tel:" . escape_html($row2->phone) . "'>" . escape_html($row2->phone) . "</a> " . escape_html($row2->type);
      if ($row2->is_adult_phone) {
        $phone_entry .= " (adult)";
      }
      $phones[] = $phone_entry;
    }
  }
  $stmt2->close();

  $scouts[] = [
    'first' => escape_html($row->user_first),
    'last' => escape_html($row->user_last),
    'email' => escape_html($row->user_email),
    'username' => escape_html($row->user_name),
    'rank' => escape_html($rank),
    'patrol' => escape_html($patrol),
    'position' => escape_html($position),
    'id' => $id,
    'phone' => $phones
  ];
}

echo json_encode($scouts);
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
