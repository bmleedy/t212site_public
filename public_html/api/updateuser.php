<?php
// Catch all fatal errors and output JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'Error',
            'message' => 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

// Prevent any output before JSON header
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not an AJAX request']);
  die();
}

header('Content-Type: application/json');
//there should be a permission check here before we allow you to connect and blindly accept
//  whatever data was thrown at us.  TODO security improvement.
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Debug: Log what we received (remove after debugging)
error_log("POST data received: " . print_r($_POST, true));

$user_type = $_POST['user_type'];

$first = $_POST['first'];
$last = $_POST['last'];
$email = $_POST['email'];

// Handle notification preferences
$notif_prefs_json = NULL;
if (array_key_exists('notif_prefs', $_POST)) {
  $notif_prefs = $_POST['notif_prefs'];

  // Check if it's already a JSON string
  if (is_string($notif_prefs)) {
    // Decode it to validate, then re-encode to ensure proper format
    $decoded = json_decode($notif_prefs, true);
    if ($decoded !== null && is_array($decoded)) {
      $notif_prefs_json = $notif_prefs;  // Use the JSON string as-is
      error_log("Notification preferences (JSON string) being saved: " . $notif_prefs_json);
    }
  } else if (is_array($notif_prefs)) {
    // If it came as an array (shouldn't happen now but handle it)
    $clean_prefs = array();
    foreach ($notif_prefs as $key => $value) {
      // Convert string booleans to actual booleans
      if ($value === 'true' || $value === true) {
        $clean_prefs[$key] = true;
      } else if ($value === 'false' || $value === false) {
        $clean_prefs[$key] = false;
      } else {
        $clean_prefs[$key] = ($value === 'on' || $value === '1' || $value === 1);
      }
    }
    $notif_prefs_json = json_encode($clean_prefs);
    error_log("Notification preferences (array) being saved: " . $notif_prefs_json);
  }
}

//Scout-specifig form fields
if ($user_type == "Scout") {
  $rank = $_POST['rank'];
  $patrol = $_POST['patrol'];
  $position = $_POST['position'];
}
$id = $_POST['id'];

if (array_key_exists("wm", $_POST)) {
  $wm = $_POST['wm'];
} else {
  $wm = 0;  // Default to 0 when not provided
}
// $wm = 1 means user has webmaster-like edit access (can edit name, email, phone, etc.)
// $wm = 0 means user does NOT have webmaster-like access

if ($wm) {
  validateField($first , "First Name" , "user_first");
  validateField($last , "Last Name" , "user_last");
  validateField($email , "Email" , "user_email");
}

if ($user_type == "Scout") {
  writeScoutData($id, $rank, $patrol, $position, $mysqli);
} else {
  if ( array_key_exists("scout_1", $_POST)) {
    $scout_1 = $_POST['scout_1'];
  } else {
    $scout_1 = NULL;
  }
  if ( array_key_exists("scout_2", $_POST)) {
    $scout_2 = $_POST['scout_2'];
  } else {
    $scout_2 = NULL;
  }
  if ( array_key_exists("scout_3", $_POST)) {
    $scout_3 = $_POST['scout_3'];
  } else {
    $scout_3 = NULL;
  }
  if ( array_key_exists("scout_4", $_POST)) {
    $scout_4 = $_POST['scout_4'];
  } else {
    $scout_4 = NULL;
  }
  if ( array_key_exists("mb_list", $_POST)) {
    $mb_list = $_POST['mb_list'];
  } else {
    $mb_list = array();
  }
  $scoutList = array();
  if ($scout_1 <> "0" && !is_null($scout_1)) { array_push( $scoutList, $scout_1 ); }
  if ($scout_2 <> "0" && !is_null($scout_2)) { array_push( $scoutList, $scout_2 ); }
  if ($scout_3 <> "0" && !is_null($scout_3)) { array_push( $scoutList, $scout_3 ); }
  if ($scout_4 <> "0" && !is_null($scout_4)) { array_push( $scoutList, $scout_4 ); }
  validateUnique($scoutList);
  checkRelationshipsForDeletes($scoutList, $id, $mysqli);
  writeMeritBadgeData($id, $mb_list, $mysqli);
  if (!is_null($scout_1)) { writeRelationshipData($scout_1, $user_type, $id, $mysqli); }
  if (!is_null($scout_2)) { writeRelationshipData($scout_2, $user_type, $id, $mysqli); }
  if (!is_null($scout_3)) { writeRelationshipData($scout_3, $user_type, $id, $mysqli); }
  if (!is_null($scout_4)) { writeRelationshipData($scout_4, $user_type, $id, $mysqli); }
}
if (!$wm) {
  $returnMsg = array(
    'status' => 'Success'
  );
  echo json_encode($returnMsg);
  die();
}

writePhoneData($_POST['phone_id_1'], $_POST['phone_1'], $_POST['phone_type_1'], $id, $mysqli);
writePhoneData($_POST['phone_id_2'], $_POST['phone_2'], $_POST['phone_type_2'], $id, $mysqli);
writePhoneData($_POST['phone_id_3'], $_POST['phone_3'], $_POST['phone_type_3'], $id, $mysqli);

// Update address in families table (only for non-scouts who have address fields)
error_log("Address update check - user_type: $user_type, family_id in POST: " . (array_key_exists("family_id", $_POST) ? 'yes' : 'no') . ", address1 in POST: " . (array_key_exists("address1", $_POST) ? 'yes' : 'no'));

if ($user_type != "Scout" && array_key_exists("family_id", $_POST) && array_key_exists("address1", $_POST)) {
  $family_id = $_POST['family_id'];
  $address1 = $_POST['address1'];
  $address2 = array_key_exists("address2", $_POST) ? $_POST['address2'] : '';
  $city = $_POST['city'];
  $state = $_POST['state'];
  $zip = $_POST['zip'];

  error_log("Address update: family_id=$family_id, address1=$address1, city=$city, state=$state, zip=$zip");

  // Check if family record exists first
  $check_query = "SELECT family_id FROM families WHERE family_id=?";
  $check_stmt = $mysqli->prepare($check_query);
  $check_stmt->bind_param('i', $family_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();
  $family_exists = $check_result->num_rows > 0;
  $check_stmt->close();

  if ($family_exists) {
    // Update existing family record
    $addr_query = "UPDATE families SET address1=?, address2=?, city=?, state=?, zip=? WHERE family_id=?";
    $addr_stmt = $mysqli->prepare($addr_query);
    if ($addr_stmt) {
      $addr_stmt->bind_param('sssssi', $address1, $address2, $city, $state, $zip, $family_id);
      if ($addr_stmt->execute()) {
        $affected_rows = $addr_stmt->affected_rows;
        error_log("Address UPDATE executed successfully. Rows affected: $affected_rows");
        log_activity(
          $mysqli,
          'update_address',
          array(
            'family_id' => $family_id,
            'user_id' => $id
          ),
          true,
          "Address updated for family ID: $family_id",
          $id
        );
      } else {
        error_log("Failed to update address for family_id $family_id: " . $mysqli->error);
      }
      $addr_stmt->close();
    }
  } else {
    // Insert new family record
    error_log("Family record does not exist for family_id $family_id, creating new record");
    $addr_query = "INSERT INTO families (family_id, address1, address2, city, state, zip) VALUES (?, ?, ?, ?, ?, ?)";
    $addr_stmt = $mysqli->prepare($addr_query);
    if ($addr_stmt) {
      $addr_stmt->bind_param('isssss', $family_id, $address1, $address2, $city, $state, $zip);
      if ($addr_stmt->execute()) {
        error_log("Address INSERT executed successfully for new family_id: $family_id");
        log_activity(
          $mysqli,
          'create_address',
          array(
            'family_id' => $family_id,
            'user_id' => $id
          ),
          true,
          "Address created for family ID: $family_id",
          $id
        );
      } else {
        error_log("Failed to insert address for family_id $family_id: " . $mysqli->error);
      }
      $addr_stmt->close();
    }
  }
} else {
  error_log("Address update SKIPPED - condition not met");
}

$query = "UPDATE users SET user_first=?, user_last=?, user_email=?, notif_preferences=? WHERE user_id=?";
$statement = $mysqli->prepare($query);
if ($statement === false) {
  echo json_encode($mysqli->error);
  die;
}
$rs = $statement->bind_param('sssss', $first, $last, $email, $notif_prefs_json, $id);
if($rs == false) {
    echo json_encode($statement->error);
    die;
}
if($statement->execute()){
  $returnMsg = array(
    'status' => 'Success'
  );
  echo json_encode($returnMsg);

  // Log successful user update
  log_activity(
    $mysqli,
    'update_user',
    array(
      'user_id' => $id,
      'user_type' => $user_type,
      'fields_updated' => array('first', 'last', 'email', 'notif_prefs')
    ),
    true,
    "User profile updated for user ID: $id",
    $id
  );
}else{
  echo json_encode( 'Error : ('. $mysqli->errno .') '. $mysqli->error);

  // Log failed user update
  log_activity(
    $mysqli,
    'update_user',
    array(
      'user_id' => $id,
      'error' => $mysqli->error
    ),
    false,
    "Failed to update user profile for user ID: $id",
    $id
  );
  die;
}
$statement->close();

function validateUnique( $arrayList ) {
  if (array_unique($arrayList) == $arrayList || count($arrayList)==0) {

  } else {
    $returnMsg = array(
      'status' => 'validation',
      'message' => 'Please do not choose the same scout twice!',
      'varDiff' => $test,
      'field' => 'scout_l'
    );
    echo json_encode($returnMsg);
    die;
  }


}

function validateField( $strValue, $strLabel, $strFieldName) {
  if ($strValue=="") {
    $returnMsg = array(
      'status' => 'validation',
      'message' => 'Please Enter: ' . $strLabel,
      'field' => $strFieldName
    );
    echo json_encode($returnMsg);
    die;
  }
}

function writeScoutData($user_id, $rank, $patrol, $position, $mysqli) {
  // Get the current position before updating
  $query="SELECT position_id FROM scout_info WHERE user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('s', $user_id);
  $statement->execute();
  $result = $statement->get_result();
  $old_position = null;
  if ($row = $result->fetch_assoc()) {
    $old_position = $row['position_id'];
  }
  $statement->close();

  // Update or insert scout info
  $query="SELECT user_id FROM scout_info WHERE user_id=".$user_id;
  $results = $mysqli->query($query);
  if ($results->fetch_assoc()) {
    $query = "UPDATE scout_info SET rank_id=?, patrol_id=?, position_id=? WHERE user_id=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('ssss', $rank, $patrol, $position, $user_id);
    $statement->execute();
  } else {
    $query = "INSERT INTO scout_info (user_id, rank_id, patrol_id, position_id) VALUES(?, ?, ?, ?)";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('ssss', $user_id, $rank, $patrol, $position);
    $statement->execute();
  }

  // Sync permissions based on position change
  // Position ID 1 = Patrol Leader (should have 'pl' permission)
  syncPositionPermissions($user_id, $old_position, $position, $mysqli);
}

function writeMeritBadgeData($user_id, $mb_list, $mysqli) {
  /*
  Query table for current list of mb for this user
  verify each of those entries is in mb_list, and remove from mb_list (so we don't add)
  if entry not in mb_list, delete from table
  if after verification there are remaining items in mb_list, add those to table
  */
  $mb_del_list = array();

  $query="SELECT * FROM mb_counselors WHERE user_id=".$user_id;
  $results = $mysqli->query($query);
  while ($row = $results->fetch_assoc()) {
    $mb_id = $row['mb_id'];
    if(($key = array_search($mb_id, $mb_list)) !== false) {
      unset($mb_list[$key]);
    } else {
      array_push($mb_del_list , $mb_id);
    }
  };
  if ($mb_list) {
    $query = "INSERT INTO mb_counselors (mb_id, user_id) VALUES(?, ?)";
    $statement = $mysqli->prepare($query);
    foreach($mb_list as $val) {
      $statement->bind_param('ss', $val, $user_id);
      $statement->execute();
    }
  }
  if ($mb_del_list) {
    $query = "DELETE FROM mb_counselors WHERE mb_id=? AND user_id=?";
    $statement = $mysqli->prepare($query);
    foreach($mb_del_list as $val) {
      $statement->bind_param('ss', $val, $user_id);
      $statement->execute();
    }
  }
}

function checkRelationshipsForDeletes($scoutList, $id, $mysqli){
  $query = "SELECT scout_id FROM relationships WHERE adult_id=".$id;
  $results = $mysqli->query($query);
  while ($row = $results->fetch_assoc()) {
    $scoutID = $row['scout_id'];
    if (!in_array($scoutID, $scoutList)) {
      $query2 = "DELETE FROM relationships WHERE adult_id=? AND scout_id=?" ;
      $statement = $mysqli->prepare($query2);
      $statement->bind_param('ss', $id, $scoutID);
      $statement->execute();
      $statement->close();
    }
  }
}

function writeRelationshipData($scout_id, $type, $adult_id, $mysqli) {
  if ($scout_id == "0") {
    return;
  }
  $query = "SELECT type FROM relationships WHERE adult_id='" . $adult_id . "' AND scout_id='" . $scout_id . "'" ;
  $results = $mysqli->query($query);
  $row = $results->fetch_assoc();

  if ($row) {
    if ($row['type'] <> $type) {
      // Update
      $query = "UPDATE relationships SET type=? WHERE adult_id=? AND scout_id=?";
      $statement = $mysqli->prepare($query);
      $statement->bind_param('sss', $type, $scout_id, $adult_id);
      if ($statement->execute()){
        //success
      }else{
        echo json_encode( 'Update Relationship Error : ('. $mysqli->errno .') '. $mysqli->error);
        die;
      }
      $statement->close();
    }
  } else {
    // Add
    $query = "INSERT INTO relationships (scout_id, adult_id, type) VALUES(?, ?, ?)";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('sss', $scout_id, $adult_id, $type);
    if ($statement->execute()){
      //success
    }else{
      echo json_encode( 'Add Relationship Error : ('. $mysqli->errno .') '. $mysqli->error);
      die;
    }
    $statement->close();
  }
}

function writePhoneData($id, $phone, $type, $user_id, $mysqli) {
  /* 4 Cases
  No id in field, no data in phone number = do nothing
  Data in ID field, no data in phone number = delete
  Data in ID field, data in phone number = update
  No Data in ID field, data in phone number = create
  */
  if (($id==="") && ($phone==="")) { return true;}

  $action = '';
  $phone_details = array(
    'phone_id' => $id,
    'phone_number' => $phone,
    'phone_type' => $type,
    'user_id' => $user_id
  );

  if ($phone==="") {
    $action = 'delete';
    $query = "DELETE FROM phone WHERE id=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('s', $id);
  } else if ($id==="") {
    $action = 'create';
    $query = "INSERT INTO phone (phone , type , user_id) VALUES(?, ?, ?)";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('sss', $phone, $type, $user_id);
  } else {
    $action = 'update';
    $query = "UPDATE phone SET phone=?, type=? WHERE id=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('sss', $phone, $type, $id);
  }
  if ($statement->execute()){
    // Log successful phone data change
    log_activity(
      $mysqli,
      'update_phone',
      $phone_details,
      true,
      "Phone data {$action}d for user ID: $user_id",
      $user_id
    );
  }else{
    // Log failed phone data change
    log_activity(
      $mysqli,
      'update_phone',
      array_merge($phone_details, array('error' => $mysqli->error)),
      false,
      "Failed to {$action} phone data for user ID: $user_id",
      $user_id
    );
    echo json_encode( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
    die;
  }
  $statement->close();
}

/**
 * Synchronize user permissions based on scout position changes
 * Position ID 1 = Patrol Leader (gets 'pl' permission)
 */
function syncPositionPermissions($user_id, $old_position, $new_position, $mysqli) {
  // Position ID 1 = Patrol Leader
  $PATROL_LEADER_ID = 1;

  // Only process if position actually changed
  if ($old_position == $new_position) {
    return;
  }

  // Get current user_access
  $query = "SELECT user_access FROM users WHERE user_id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('s', $user_id);
  $statement->execute();
  $result = $statement->get_result();
  $row = $result->fetch_assoc();
  $current_access = $row['user_access'];
  $statement->close();

  // Convert to array
  $access_array = array_filter(explode('.', $current_access));
  $original_access = $current_access;
  $permission_changed = false;

  // Adding Patrol Leader position
  if ($new_position == $PATROL_LEADER_ID && !in_array('pl', $access_array)) {
    $access_array[] = 'pl';
    $permission_changed = true;
    $change_description = "Added 'pl' permission (assigned Patrol Leader position)";
  }
  // Removing Patrol Leader position
  else if ($old_position == $PATROL_LEADER_ID && $new_position != $PATROL_LEADER_ID) {
    $access_array = array_diff($access_array, array('pl'));
    $permission_changed = true;
    $change_description = "Removed 'pl' permission (removed from Patrol Leader position)";
  }

  // Update database if permission changed
  if ($permission_changed) {
    $new_access = implode('.', $access_array);

    $query = "UPDATE users SET user_access=? WHERE user_id=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param('ss', $new_access, $user_id);

    if ($statement->execute()) {
      // Log successful permission sync
      log_activity(
        $mysqli,
        'sync_position_permissions',
        array(
          'user_id' => $user_id,
          'old_position' => $old_position,
          'new_position' => $new_position,
          'old_access' => $original_access,
          'new_access' => $new_access
        ),
        true,
        $change_description . " for user ID: $user_id",
        $user_id
      );
    } else {
      // Log failed permission sync
      log_activity(
        $mysqli,
        'sync_position_permissions',
        array(
          'user_id' => $user_id,
          'old_position' => $old_position,
          'new_position' => $new_position,
          'error' => $mysqli->error
        ),
        false,
        "Failed to sync permissions for user ID: $user_id",
        $user_id
      );
    }
    $statement->close();
  }
}