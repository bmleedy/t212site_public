<?php
error_reporting(0);
ini_set('display_errors', '0');

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not an AJAX request']);
  die();
}
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();


header('Content-Type: application/json');

// For adults, get MB info. For Scouts, get Rank, Patrol, Position
// scoutData = patrol, rank, etc for scouts. For adults, it allows them to choose their scout.
require 'connect.php';

// Validate input
$id = validate_int_post('id');
$edit = validate_bool_post('edit', false);
$showEdit = validate_bool_post('showEdit', false);
$wm = validate_bool_post('wm', false);
$userAdmin = validate_bool_post('userAdmin', false);

// Check if current user can access this user's data
require_user_access($id, $current_user_id);

$profile = 'yes';

// Get user type and family ID using prepared statement
$query = "SELECT user_type, user_last, family_id FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    die();
}
$stmt->bind_param('i', $id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    die();
}

$user_type = $row['user_type'];
$user_last = $row['user_last'];
$family_id = $row["family_id"];
$address1 = "";
$address2 = "";
$city = "";
$state = "";
$zip = "";

if ($user_type == 'Scout') {
    $query = "SELECT * FROM scout_info WHERE user_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $results = $stmt->get_result();
    $row = $results->fetch_assoc();
    $stmt->close();

    if ($row) {
        $rank = getLabel('ranks', $row['rank_id'], $mysqli);
        $patrol = getLabel('patrols', $row['patrol_id'], $mysqli);
        $position = getLabel('leadership', $row['position_id'], $mysqli);
    } else {
        $profile = 'no';
        $rank = '';
        $patrol = '';
        $position = '';
    }

    if ($edit) {
        $varRank = getDDL("ranks", "rank", $rank, $mysqli);
        $varPatrol = getDDL("patrols", "patrol", $patrol, $mysqli);
        $varPosition = getDDL("leadership", "position", $position, $mysqli);
        if ($userAdmin == 1) {
            $varLock = ' ';
        } else {
            $varLock = 'disabled';
        }
    } else {
        $varRank = '<p>' . escape_html($rank) . '</p>';
        $varPatrol = '<p>' . escape_html($patrol) . '</p>';
        $varPosition = '<p>' . escape_html($position) . '</p>';
    }

    $scoutData = '<div class="row"><div class="large-4 columns"><label>Rank</label>' . $varRank . '</div>';
    $scoutData = $scoutData . '<div class="large-4 columns"><label>Patrol</label>' . $varPatrol . '</div>';
    $scoutData = $scoutData . '<div class="large-4 columns"><label>Leadership (If Applicable)</label>' . $varPosition . '</div>';
    $scoutData = $scoutData . '</div>';
    $scoutData = $scoutData . '<div class="large-4 columns"></div></div>';

    /************ Adult Merit Badge Info / Family Info / Address *******************************/
} else {
    /***** Get Scout Info ***********/
    $query = "SELECT user_first, user_last FROM users WHERE family_id=? AND user_type='Scout'";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $family_id);
    $stmt->execute();
    $results = $stmt->get_result();
    $scoutData = '<div class="row"><div class="large-6 columns"><label>My Scouts</label><p>';
    while ($row = $results->fetch_assoc()) {
        $scoutData = $scoutData . escape_html($row['user_first']) . ' ' . escape_html($row['user_last']) . '<br>';
    }
    $scoutData = $scoutData . '</p></div>';
    $stmt->close();

    /***** Get Address Info ***********/
    $query = "SELECT * FROM families WHERE family_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $family_id);
    $stmt->execute();
    $results = $stmt->get_result();

    if ($results->num_rows) {
        if ($row = $results->fetch_assoc()) {
            $address1 = $row["address1"];
            $address2 = $row["address2"];
            $city = $row["city"];
            $state = $row["state"];
            $zip = $row["zip"];
            if ($state == "") {
                $state = "WA";
            }
        }
    }
    $stmt->close();

    /** if read mode, append address & scout data so they appear side by side. blank addressdata when done so it's not displayed**
     ** if edit mode, need to terminate scoutData with a closing </div> **/
    if ($edit) {
        $addressData = '<input type="hidden" id="family_id" name="family_id" value="' . escape_html($family_id) . '">';
        $addressData = $addressData . '<div class="row"><div class="large-12 columns"><label>Address</label>';
        $addressData = $addressData . '<input type="text" id="address1" name="address1" required value="' . escape_html($address1) . '">';
        $addressData = $addressData . '<input type="text" id="address2" name="address2" value="' . escape_html($address2) . '">';
        $addressData = $addressData . '</div><div class="large-6 columns"><label>City</label>';
        $addressData = $addressData . '<input type="text" id="city" name="city" required value="' . escape_html($city) . '">';
        $addressData = $addressData . '</div><div class="large-3 columns"><label>State</label>';
        $addressData = $addressData . getStateDDL($state);
        $addressData = $addressData . '</div><div class="large-3 columns"><label>Zip</label>';
        $addressData = $addressData . '<input type="text" id="zip" name="zip" required value="' . escape_html($zip) . '"></div></div>';
        $scoutData = $scoutData . "</div>";
    } else {
        $addressData = '<div class="large-6 columns"><label>Address</label><p>';
        $addressData = $addressData . escape_html($address1) . "<br>";
        if ($address2 != "") {
            $addressData = $addressData . escape_html($address2) . "<br>";
        }
        $addressData = $addressData . escape_html($city) . ", " . escape_html($state) . " " . escape_html($zip) . '</p></div></div>';
        $scoutData = $scoutData . $addressData;
        $addressData = "";
    }

    /***** Get Merit Badge Info ***********/
    $query = "SELECT * FROM mb_counselors WHERE user_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $results = $stmt->get_result();
    $mb_list = array();
    while ($row = $results->fetch_assoc()) {
        array_push($mb_list, $row['mb_id']);
    }
    $stmt->close();

    $query = "SELECT * FROM mb_list ORDER BY mb_name";
    $results = $mysqli->query($query);
    if ($results == false) {
        error_log("problem reading from the mb_list DB. Does the DB exist?\n", 0);
    }
    $mbData = '';
    if ($edit && !$wm) {
        $mbData = $mbData . '<p>Please check all which you can be a Counselor for.</p>';
    } else {
        $mbData = $mbData . '<p>Counselor for the following Merit Badges:</p>';
    }
    while ($row = $results->fetch_assoc()) {
        if (in_array($row['id'], $mb_list)) {
            $isChecked = "CHECKED";
            $temp = escape_html($row['mb_name']) . "<br>";
        } else {
            $isChecked = "";
            $temp = "";
        }
        if ($edit && !$wm) {
            $mbData = $mbData . "<div class='large-4 columns'><input " . $isChecked . " type='checkbox' name='mb' class='mbCheckbox' value='" .
                (int)$row['id'] . "'/> " . escape_html($row['mb_name']) . "</div>";
        } else {
            $mbData = $mbData . $temp;
        }
    }
    if ($edit) {
        $mbData = $mbData . '<div class="clearfix"></div>';
    } else {
        $mbData = $mbData . '</p>';
    }
}

$returnMsg = array(
    'profile' => $profile,
    'user_type' => $user_type,
    'scoutData' => $scoutData,
    'addressData' => $addressData,
    'mbData' => $mbData,
    'notifData' => ''
);

// Add notification preferences for treasurers
// First, get the current user's permissions
$current_user_permissions = array();
$perm_query = "SELECT user_access FROM users WHERE user_id = ?";
$perm_stmt = $mysqli->prepare($perm_query);
$perm_stmt->bind_param('i', $id);
$perm_stmt->execute();
$perm_result = $perm_stmt->get_result();
if ($perm_row = $perm_result->fetch_assoc()) {
    $current_user_permissions = explode('.', $perm_row['user_access']);
}
$perm_stmt->close();

// If user has treasurer permission and is viewing their own profile in edit mode
if ((in_array('trs', $current_user_permissions) || in_array('sa', $current_user_permissions))
    && $id == $current_user_id) {

    // Get current notification preferences from users.notif_preferences JSON column
    $notif_prefs = [];
    $notif_query = "SELECT notif_preferences FROM users WHERE user_id = ?";
    $notif_stmt = $mysqli->prepare($notif_query);
    $notif_stmt->bind_param('i', $id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    if ($notif_row = $notif_result->fetch_assoc()) {
        if (!empty($notif_row['notif_preferences'])) {
            $decoded = json_decode($notif_row['notif_preferences'], true);
            if (is_array($decoded)) {
                $notif_prefs = $decoded;
            }
        }
    }
    $notif_stmt->close();

    // Define available notification types for treasurers
    $notif_types = array(
        'tshirt_order' => 'Email me when a new T-shirt order is placed'
    );

    $notifData = '<hr><div class="row"><div class="large-12 columns">';
    $notifData .= '<label><strong>Notification Preferences</strong></label>';
    $notifData .= '<p style="font-size:0.9em; color:#666;">Choose which email notifications you would like to receive:</p>';

    if ($edit) {
        foreach ($notif_types as $type => $label) {
            // Default to enabled if no preference set
            $is_enabled = !isset($notif_prefs[$type]) || $notif_prefs[$type] == true;
            $checked = $is_enabled ? 'checked' : '';
            $notifData .= '<div class="large-12 columns" style="margin-bottom:10px;">';
            $notifData .= '<input type="checkbox" class="notifPrefCheckbox" id="notif_' . escape_html($type) . '" value="' . escape_html($type) . '" ' . $checked . '> ';
            $notifData .= '<label for="notif_' . escape_html($type) . '" style="display:inline;">' . escape_html($label) . '</label>';
            $notifData .= '</div>';
        }
    } else {
        $notifData .= '<ul>';
        foreach ($notif_types as $type => $label) {
            $is_enabled = !isset($notif_prefs[$type]) || $notif_prefs[$type] == true;
            $status = $is_enabled ? '<span style="color:green;">Enabled</span>' : '<span style="color:#999;">Disabled</span>';
            $notifData .= '<li>' . escape_html($label) . ': ' . $status . '</li>';
        }
        $notifData .= '</ul>';
    }

    $notifData .= '</div></div>';
    $returnMsg['notifData'] = $notifData;
}

echo json_encode($returnMsg);
die;

/******************* Functions *****************/
function getStateDDL($strState)
{
    $strDDL = '<select id="state" name="state">';

    $abbrevs = array("AL", "AK", "AZ", "AR", "CA", "CO", "CT", "DE", "DC", "FL", "GA", "HI", "ID", "IL", "IN", "IA", "KS", "KY", "LA", "ME", "MD", "MA", "MI", "MN", "MS", "MO", "MT", "NE", "NV", "NH", "NJ", "NM", "NY", "NC", "ND", "OH", "OK", "OR", "PA", "RI", "SC", "SD", "TN", "TX", "UT", "VT", "VA", "WA", "WV", "WI", "WY");

    $states = array('Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'District Of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming');

    for ($i = 0; $i <= 50; $i++) {
        if ($abbrevs[$i] == $strState) {
            $strDDL = $strDDL . '<option selected="selected" value="' . escape_html($abbrevs[$i]) . '">' . escape_html($states[$i]);
        } else {
            $strDDL = $strDDL . '<option value="' . escape_html($abbrevs[$i]) . '">' . escape_html($states[$i]);
        }
    }

    $strDDL = $strDDL . '</select>';
    return $strDDL;
}

function getLabel($strTable, $id, $mysqli)
{
    if ($id) {
        // Validate table name (whitelist only)
        $allowed_tables = array('ranks', 'patrols', 'leadership');
        if (!in_array($strTable, $allowed_tables)) {
            error_log("Invalid table name attempted: " . $strTable);
            return "";
        }

        $query = "SELECT label FROM $strTable WHERE id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $results = $stmt->get_result();
        $row = $results->fetch_assoc();
        $stmt->close();

        return $row ? $row['label'] : "";
    } else {
        return "";
    }
}

function getDDL($strTable, $strSelect, $strDefault, $mysqli)
{
    // Validate table name (whitelist only)
    $allowed_tables = array('ranks', 'patrols', 'leadership');
    if (!in_array($strTable, $allowed_tables)) {
        error_log("Invalid table name attempted: " . $strTable);
        return "";
    }

    $query = "SELECT * FROM $strTable ORDER BY sort";
    $results = $mysqli->query($query);
    if ($results == false) {
        error_log("problem reading from the $strTable DB. Does the DB exist?\n", 0);
        return "";
    }
    $returnDDL = '<select id="' . escape_html($strSelect) . '"><option value="">-Select-</option>';

    while ($row = $results->fetch_assoc()) {
        $label = $row['label'];
        $id = $row['id'];
        if ($strDefault == $label) {
            $sel = " SELECTED ";
        } else {
            $sel = "";
        }
        $returnDDL = $returnDDL . '<option value="' . (int)$id . '" ' . $sel . '>' . escape_html($label) . '</option>';
    }
    $returnDDL = $returnDDL . '</select>';
    return $returnDDL;
}

function getScoutDDL($mysqli, $user_last)
{
    $query = "SELECT user_first, user_last, user_id FROM users WHERE user_type='Scout' AND user_last=? ORDER BY user_last, user_first";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $user_last);
    $stmt->execute();
    $results = $stmt->get_result();
    $returnDDL = "";

    while ($row = $results->fetch_assoc()) {
        $label = escape_html($row['user_last']) . ", " . escape_html($row['user_first']);
        $scout_id = (int)$row['user_id'];
        $returnDDL = $returnDDL . '<option value="' . $scout_id . '">' . $label . '</option>';
    }
    $stmt->close();
    $returnDDL = $returnDDL . '</select>';
    return $returnDDL;
}