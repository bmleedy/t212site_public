<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Validate inputs
$event_id = validate_string_post('event_id', true);
$user_id = validate_int_post('user_id', true);
$edit = validate_bool_post('edit', false);
$showMailto = validate_bool_post('showMailto', false);

// Authorization check - user can only view their own data unless they have permission
if ($user_id != $current_user_id) {
  require_user_access($user_id, $current_user_id);
}

// Initialize variables
$user_type = null;
$user_first = null;
$registered = null;
$attendingScouts = null;
$attendingAdults = null;
$sic_id = 0;
$aic_id = 0;
$type = "";
$sic = "";
$aic = "";

if ($event_id != "New") {
  // Validate event_id is numeric if not "New"
  if (!is_numeric($event_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event_id']);
    die();
  }
  $event_id_int = (int)$event_id;

  // Get User Type (Scout, Dad, etc)
  $stmt = $mysqli->prepare("SELECT user_type, user_first FROM users WHERE user_id=?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $user_type = $row['user_type'];
  $user_first = $row['user_first'];
  $stmt->close();

  // Is User signed up already?
  $stmt = $mysqli->prepare("SELECT attending FROM registration WHERE user_id=? AND event_id=?");
  $stmt->bind_param("ii", $user_id, $event_id_int);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  if (!$row) {
    // No entry in table = No
    $registered = "No";
  } else {
    // If there is an entry, check attending flag which will be 0 if they had signed up and then cancelled.
    // It is preferred to use attending flag rather than delete entry in case they had parent approval & paid
    // in particular if they accidentally click Plans changed button
    if ($row['attending']==1) {
      $registered = "Yes";
    } else {
      $registered = "Cancelled";
    }
  }
  $stmt->close();
}

$name = "";
$location = "";
$description = "";
$startdate = date("Y-m-d 12:00");
$enddate = date("Y-m-d 12:00");
$cost = "";
$adult_cost = "";
$reg_open = "";
$checked = "";
$mailto = "<a href='mailto:";
$sep = "";
$family_id = "";

if ($event_id != "New") {
  $stmt = $mysqli->prepare("SELECT * FROM events WHERE id=?");
  $stmt->bind_param("i", $event_id_int);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $event_id = $row['id'];
    $name = $row['name'];
    $location = $row['location'];
    $description = $row['description'];
    $startdate = substr($row['startdate'],0,-3);
    $enddate = substr($row['enddate'],0,-3);
    $sic_id = $row['sic_id'];
    $aic_id = $row['aic_id'];
    $cost = $row['cost'];
    $adult_cost = $row['adult_cost'];
    $reg_open = $row['reg_open'];
    $type_id = $row['type_id'];
    $type = getLabel('event_types',$type_id,$mysqli);

    if ($sic_id > 0) {
      $stmt2 = $mysqli->prepare("SELECT user_first,user_last FROM users WHERE user_id=?");
      $stmt2->bind_param("i", $sic_id);
      $stmt2->execute();
      $result2 = $stmt2->get_result();
      if ($result2 && $row2 = $result2->fetch_assoc()) {
        $sic = escape_html($row2['user_first']) . ' ' . escape_html($row2['user_last']);
      }
      $stmt2->close();
    }
    if ($aic_id > 0) {
      $stmt2 = $mysqli->prepare("SELECT user_first,user_last FROM users WHERE user_id=?");
      $stmt2->bind_param("i", $aic_id);
      $stmt2->execute();
      $result2 = $stmt2->get_result();
      if ($result2 && $row2 = $result2->fetch_assoc()) {
        $aic = escape_html($row2['user_first']) . ' ' . escape_html($row2['user_last']);
      }
      $stmt2->close();
    }
  }
  $stmt->close();
}

if ($edit || $event_id=="New") {
  $varname = '<input type="text" id="name" required value="'. escape_html($name) . '"/>';
  $varlocation = '<input type="text" id="location" required value="'. escape_html($location) . '"/>';
  $vardescription = '<textarea id="description" required>'. escape_html($description) . '</textarea>';
  $varstartdate = '<input type="text" id="startdate" required value="'. escape_html($startdate) . '"/>';
  $varenddate = '<input type="text" id="enddate" required value="'. escape_html($enddate) . '"/>';
  $varcost = '<input type="number" id="cost" required value="'. escape_html($cost) . '"/>';
  $varadultcost = '<input type="number" id="adult_cost" required value="'. escape_html($adult_cost) . '"/>';
  if ($reg_open) {
    $checked = " checked";
  }
  $varopen = 'Sign ups &nbsp;&nbsp;<input type="checkbox" id="reg_open" value="1"'.$checked.'/>&nbsp;Enabled';
  $varsic = getUserDDL($mysqli,"Scout", $sic_id) ;
  $varsic = '<select id="sic"><option value="0">-Select-</option>' . $varsic;
  $varaic = getUserDDL($mysqli,"Dad", $aic_id) ;
  $varaic = '<select id="aic"><option value="0">-Select-</option>' . $varaic;
  $varType = getDDL("event_types","type",$type,$mysqli);
} else {
  $varname = '<p>'. escape_html($name) . '</p>';
  $varlocation = '<p>'. escape_html($location) . '</p>';
  $vardescription = '<p>'. escape_html($description) . '</p>';
  $varstartdate = '<p>'. escape_html($startdate) . '</p>';
  $varenddate = '<p>'. escape_html($enddate) . '</p>';
  $varsic = '<p>'. $sic . '</p>';
  $varaic = '<p>'. $aic . '</p>';
  $varType = '<p>'. escape_html($type) . '</p>';
  $varcost = '<p>$'. escape_html($cost) . '</p>';
  $varadultcost = '<p>$'. escape_html($adult_cost) . '</p>';
  if($reg_open) {
    $varopen = '<p>Sign ups for this event are enabled</p>';
  } else {
    $varopen = '<p>Sign ups for this event are disabled</p>';
  }

}

$returnData = '<div class="row">';
$returnData = $returnData . '<div class="large-12 columns">' . $varopen . '</div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Event Name' . $varname . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Location' . $varlocation . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Cost' . $varcost . '</label></div></div>';
$returnData = $returnData . '<div class="row">';
$returnData = $returnData . '<div class="large-5 columns"><label>Start Date' . $varstartdate . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>End Date' . $varenddate . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Adult Cost' . $varadultcost . '</div></div>';
$returnData = $returnData . '<div class="row">';
$returnData = $returnData . '<div class="large-5 columns"><label>Scout in Charge' . $varsic . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Adult in Charge' . $varaic . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Event Type</label>'.$varType.'</div></div>';
$returnData = $returnData . '<div class="row">';
$returnData = $returnData . '<div class="large-12 columns"><label>Event Description' . $vardescription . '</label>';

if ($event_id != "New") {
  $isParentOf = [];
  if ($user_type <> "Scout") {
    $stmt = $mysqli->prepare("SELECT scout_id FROM relationships WHERE adult_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $isParentOf[]=$row['scout_id'];
    }
    $stmt->close();
  }

  $stmt = $mysqli->prepare("SELECT reg.user_id, paid, seat_belts, user_first, user_last, patrol_id, user_email, reg.id as register_id, reg.approved_by FROM registration AS reg, users AS u, scout_info AS si WHERE reg.attending=1 AND u.user_type='Scout' AND reg.user_id = u.user_id AND reg.user_id = si.user_id AND reg.event_id=? ORDER BY patrol_id, user_last, user_first");
  $stmt->bind_param("i", $event_id_int);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    if (in_array($row['user_id'],$isParentOf)) {
      $show_approved=1;
    } else {
      $show_approved=0;
    }
    $attendingScouts[] = [
      'patrol' => escape_html(getLabel('patrols',$row['patrol_id'],$mysqli)),
      'id' => $row['user_id'],
      'register_id' => $row['register_id'],
      'approved' => $row['approved_by'],
      'paid' => $row['paid'],
      'first' => escape_html($row['user_first']),
      'last' => escape_html($row['user_last']),
      'show_approved' => $show_approved
    ];
    $mailto = $mailto . $sep . $row['user_email'];
    $sep = ";";

    $scout_user_id = $row['user_id'];
    $stmt2 = $mysqli->prepare("SELECT family_id FROM users WHERE user_id=?");
    $stmt2->bind_param("i", $scout_user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($row2 = $result2->fetch_assoc()) {
      $family_id = $row2["family_id"];
      // Get parent emails, checking notification preferences
      $stmt3 = $mysqli->prepare("SELECT user_email, notif_preferences FROM users WHERE user_type !='Scout' AND family_id=?");
      $stmt3->bind_param("i", $family_id);
      $stmt3->execute();
      $result3 = $stmt3->get_result();
      while ($row3 = $result3->fetch_assoc()) {
        // Check if this parent wants event emails
        $include_email = true;  // Default: include (opted in)

        if ($row3['notif_preferences']) {
          $prefs = json_decode($row3['notif_preferences'], true);
          // Check 'evnt' (Event) preference
          if (isset($prefs['evnt']) && $prefs['evnt'] === false) {
            $include_email = false;
          }
        }

        if ($include_email && strpos($mailto, $row3['user_email'])===false) {
          $mailto = $mailto . $sep . $row3['user_email'];
        }
      }
      $stmt3->close();
    }
    $stmt2->close();
  }
  $stmt->close();

  $stmt = $mysqli->prepare("SELECT reg.user_id, paid, seat_belts, user_first, user_last, user_email, notif_preferences, reg.id as register_id FROM registration AS reg, users AS u WHERE reg.attending=1 AND u.user_type<>'Scout' AND reg.user_id = u.user_id AND reg.event_id=? ORDER BY user_last, user_first");
  $stmt->bind_param("i", $event_id_int);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $attendingAdults[] = [
      'patrol' => 'Adults',
      'id' => $row['user_id'],
      'register_id' => $row['register_id'],
      'paid' => $row['paid'],
      'seat_belts' => $row['seat_belts'],
      'first' => escape_html($row['user_first']),
      'last' => escape_html($row['user_last'])
    ];

    // Check if this adult wants event emails
    $include_email = true;  // Default: include (opted in)

    if ($row['notif_preferences']) {
      $prefs = json_decode($row['notif_preferences'], true);
      // Check 'evnt' (Event) preference
      if (isset($prefs['evnt']) && $prefs['evnt'] === false) {
        $include_email = false;
      }
    }

    // Add the user to the mailto list, but only if the adult is opted in.
    if ($include_email && strpos($mailto, $row['user_email'])===false) {
      $mailto = $mailto . $sep . $row['user_email'];
      $sep = ";";
    }

  }
  $stmt->close();
}

if ($showMailto) {
  $mailto = $mailto . "?Subject=Troop 212 " . urlencode($name) . "'>Send Email to Attending Scouts & Parents</a>";
  $returnData = $returnData . $mailto . '<br><br></div></div>';
}

$returnMsg = array(
  'startdate' => $startdate,
  'enddate' => $enddate,
  'outing_name' => escape_html($name),
  'cost' => $cost,
  'adult_cost' => $adult_cost,
  'first' => escape_html($user_first),
  'user_type' => escape_html($user_type),
  'registered' => $registered,
  'reg_open' => $reg_open,
  'attendingScouts' => $attendingScouts,
  'attendingAdults' => $attendingAdults,
  'data' => $returnData
);

echo json_encode($returnMsg);
die;


function getDDL($strTable,$strSelect,$strDefault,$mysqli) {
  // Whitelist allowed tables
  $allowed_tables = ['event_types', 'patrols', 'ranks', 'leadership'];
  if (!in_array($strTable, $allowed_tables)) {
    return '';
  }

  $stmt = $mysqli->prepare("SELECT * FROM $strTable ORDER BY sort");
  $stmt->execute();
  $result = $stmt->get_result();
  $returnDDL = '<select id="'.escape_html($strSelect).'"><option value="">-Select-</option>';

  while ($row = $result->fetch_assoc()) {
    $label = escape_html($row['label']);
    $id = (int)$row['id'];
    if ($strDefault==$row['label']) {
      $sel = " SELECTED ";
    } else {
      $sel = "" ;
    }
    $returnDDL = $returnDDL . '<option value="'.$id.'" '.$sel.'>'.$label.'</option>';
  }
  $returnDDL = $returnDDL . '</select>' ;
  $stmt->close();
  return $returnDDL ;
}

function getUserDDL($mysqli,$user_type,$def_id) {
  $stmt = $mysqli->prepare("SELECT user_first, user_last, user_id FROM users WHERE user_type=? ORDER BY user_last, user_first");
  $stmt->bind_param("s", $user_type);
  $stmt->execute();
  $result = $stmt->get_result();
  $returnDDL = "";

  while ($row = $result->fetch_assoc()) {
    $label = escape_html($row['user_last']) . ", " . escape_html($row['user_first']);
    $user_id = (int)$row['user_id'];
    if ($user_id==$def_id) {
      $returnDDL = $returnDDL . '<option SELECTED value="'.$user_id.'">'.$label.'</option>';
    } else {
      $returnDDL = $returnDDL . '<option value="'.$user_id.'">'.$label.'</option>';
    }
  }
  $returnDDL = $returnDDL . '</select>' ;
  $stmt->close();
  return $returnDDL ;
}


function getLabel($strTable,$id,$mysqli){
  if ($id) {
    // Whitelist allowed tables
    $allowed_tables = ['event_types', 'patrols', 'ranks', 'leadership'];
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
