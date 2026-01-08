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
require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Validate inputs
$id = validate_int_post('id', true);
$edit = validate_bool_post('edit', false);
$wm = validate_bool_post('wm', false);  // = 1 if scout is wm editing another scout. =0 for adults and if editing his own record
$isAdmin = validate_bool_post('userAdmin', false);

// Authorization check - user can only view their own data unless they have permission
if ($id != $current_user_id) {
	require_user_access($id, $current_user_id);
}

$stmt = $mysqli->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_object();
$user_first = $row->user_first;
$user_last = $row->user_last;
$user_email = $row->user_email;
$user_name = $row->user_name;
$user_id = $row->user_id;
$user_type = $row->user_type;
$family_id = $row->family_id;
$notif_preferences = $row->notif_preferences;
$stmt->close();

if ($edit && !$wm) {
	$varFirst = '<input type="text" id="user_first" required value="'. escape_html($user_first) . '"/>';
	$varLast = '<input type="text" id="user_last" required value="'. escape_html($user_last) . '"/>';
	$varEmail = '<input type="text" id="user_email" required value="'. escape_html($user_email) . '"/>';

	$isScout='';
	$isAlumni='';
	$isDad='';
	$isMom='';
	$isOther='';
	$isDelete='';
	if ($user_type=="Scout") {
		$isScout =  "SELECTED" ;
	} elseif ($user_type == "Alumni") {
		$isAlumni =  "SELECTED" ;
	} elseif ($user_type == "Dad") {
		$isDad =  "SELECTED" ;
	} elseif ($user_type == "Mom") {
		$isMom =  "SELECTED" ;
	} elseif ($user_type == "Other") {
		$isOther =  "SELECTED" ;
	} elseif ($user_type == "Delete") {
		$isDelete =  "SELECTED" ;
	}

        $varUserType = '
		<select id="user_type">
			<option value="">-Type-</option>
			<option value="Scout" '.$isScout .'>Scout</option>
			<option value="Alumni" '.$isAlumni .'>Alumni</option>
			<option value="Dad" '.$isDad .'>Dad</option>
			<option value="Mom" '.$isMom .'>Mom</option>
			<option value="Other" '.$isOther .'>Other</option>
			<option value="Delete" '.$isDelete .'>Delete</option>
		</select>';
        $varUserName = '<p>' . escape_html($user_name) . '</p>';
    if ($isAdmin) {
        $varFamilyIDData = '<div class="large-6 columns end"><label>Family ID (do not edit unless you are sure!)<input type="text" id="family_id" required value="'. escape_html($family_id) . '"/></label></div>';
		$varFamilyID = '';
    } else {
        $varFamilyID = '<input type="hidden" id="family_id" value="'. escape_html($family_id) . '" />';
		$varFamilyIDData = '';
    }
} else {
	if ($user_type=="Scout") {
		$mailTo = '<a href="mailto:' . escape_html($user_email) ;
		$sep = ';';
		$stmt = $mysqli->prepare("SELECT user_email FROM users WHERE user_type !='Scout' AND family_id=?");
		$stmt->bind_param("i", $family_id);
		$stmt->execute();
		$result3 = $stmt->get_result();
		while ($row3 = $result3->fetch_assoc()) {
			$mailTo = $mailTo . $sep . escape_html($row3['user_email']);
		}
		$stmt->close();
		$mailTo = $mailTo . '?Subject=Troop 212 Summer Camp Merit Badge Follow-up">Email Scout & Parents</a>';
	}

	$varFirst = '<p>'.escape_html($user_first).'</p>';
	$varLast = '<p>'.escape_html($user_last).'</p>';
	if ($user_type=="Scout") {$varEmail = '<p>'.escape_html($user_email).'<br>'.$mailTo.'</p>';}
	else {$varEmail='';}

	$varUserType = '<p>'.escape_html($user_type).'</p>'.'<input type="hidden" id="user_type" value="'. escape_html($user_type) . '" />';
	$varUserName = '<p>'.escape_html($user_name).'</p>';
    if ($isAdmin) {
        $varFamilyIDData = '<div class="large-6 columns end"><label>Family ID (do not edit unless you are sure!)<input type="text" id="family_id" required value="'. escape_html($family_id) . '"/></label></div>';
		$varFamilyID='';
    } else {
        $varFamilyID = '<input type="hidden" id="family_id" value="'. escape_html($family_id) . '" />';
    }
}

$stmt = $mysqli->prepare("SELECT * FROM phone WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$results = $stmt->get_result();
if ($results == false) {
    die("ERROR in ".__FILE__."could not query MySQL");
}
$varPhone = [];
$varType = [];
$varID = [];
for ($i = 1; $i <= 3; $i++) {
	$row = $results->fetch_assoc();
	if (!$row) {
		if ($edit && !$wm) {
			$varID[] = "";
			$varPhone[] = '<input type="text" id="user_phone_'.$i.'" value=""/>';
			$varType[] = '
				<select id="phone_type_'.$i.'">
					<option value="">-Type-</option>
					<option value="My Cell">My Cell</option>
					<option value="Parent">Parent</option>
					<option value="Landline">Landline</option>
				</select>';
		} else {
			$varPhone[] = "";
			$varType[] = "";
			$varID[] = "";
		}
	} else {
		$isCell = "";
		$isWork = "";
		$isHome = "";
		$varID[] = $row["id"];
		if ($edit && !$wm) {
			$varPhone[] = '<input type="text" id="user_phone_'.$i.'" value="'. escape_html($row["phone"]) . '"/>';
			if ($row["type"]=="My Cell") {
				$isCell =  "SELECTED" ;
			} elseif ($row["type"] == "Parent") {
				$isHome =  "SELECTED" ;
			} elseif ($row["type"] == "Landline") {
				$isWork =  "SELECTED" ;
			}
			$varType[] = '
				<select id="phone_type_'.$i.'">
					<option value="">-Type-</option>
					<option value="My Cell" '.$isCell.'>My Cell</option>
					<option value="Parent" '.$isHome.'>Parent</option>
					<option value="Landline" '.$isWork.'>Landline</option>
				</select>';
		} else {
			$varPhone[] = '<p>'. escape_html($row["phone"]) .'</p>';
			$varType[] = '<p>'. escape_html($row["type"]) .'</p>';
		}
	}
}
$stmt->close();

// Build notification preferences section
require_once '../includes/notification_types.php';

// Parse existing preferences (if any)
$prefs_array = array();
if ($notif_preferences) {
	$prefs_array = json_decode($notif_preferences, true);
	if (!is_array($prefs_array)) {
		$prefs_array = array();
	}
}

$varNotifPrefs = '<div class="row"><div class="large-12 columns"><h5>Notification Preferences</h5></div></div>';
$varNotifPrefs .= '<div class="row">';

// Build checkboxes in two columns
$half = ceil(count($notification_types) / 2);
$column_count = 0;

foreach ($notification_types as $index => $notif) {
	// Start new column
	if ($index == 0 || $index == $half) {
		$varNotifPrefs .= '<div class="large-6 columns">';
	}

	$key = $notif['key'];
	$display_name = $notif['display_name'];
	$tooltip = $notif['tooltip'];

	// Default to checked (opted in) if no preference is set, or if preference is true
	$is_checked = '';
	if (!isset($prefs_array[$key]) || $prefs_array[$key] === true) {
		$is_checked = 'checked';
	}

	if ($edit && !$wm) {
		$varNotifPrefs .= '<label title="' . escape_html($tooltip) . '">';
		$varNotifPrefs .= '<input type="checkbox" class="notifPrefCheckbox" name="notif_' . escape_html($key) . '" id="notif_' . escape_html($key) . '" value="' . escape_html($key) . '" ' . $is_checked . ' />';
		$varNotifPrefs .= ' ' . escape_html($display_name);
		$varNotifPrefs .= '</label><br>';
	} else {
		// Display mode - show preferences as text
		$status = ($is_checked === 'checked') ? 'Enabled' : 'Disabled';
		$varNotifPrefs .= '<p><strong>' . escape_html($display_name) . ':</strong> ' . $status . '</p>';
	}

	// Close column
	if ($index == $half - 1 || $index == count($notification_types) - 1) {
		$varNotifPrefs .= '</div>';
	}
}

$varNotifPrefs .= '</div>';

$varData = $varFamilyID .
'<input type="hidden" id="phone_id_1" value="'. escape_html($varID[0]) . '" />
<input type="hidden" id="phone_id_2" value="'. escape_html($varID[1]) . '" />
<input type="hidden" id="phone_id_3" value="'. escape_html($varID[2]) . '" />
	<div class="row">
  	<div class="large-6 columns">
      <label>First Name
        ' . $varFirst . '
      </label>
		</div>
		<div class="large-6 columns">
			<label>Phone</label>
			<div class="row">
				<div class="large-8 columns">
				  ' . $varPhone[0] . '
				</div>
				<div class="large-4 columns">
					' . $varType[0] . '
				</div>
			</div>
    </div>
  </div>

	<div class="row">
    <div class="large-6 columns">
      <label>Last Name
        ' . $varLast . '
      </label>
		</div>
    <div class="large-6 columns">
			<label>Phone</label>
			<div class="row">
				<div class="large-8 columns">
				  ' . $varPhone[1] . '
				</div>
				<div class="large-4 columns">
					' . $varType[1] . '
				</div>
			</div>
    </div>
  </div>

	<div class="row">
    <div class="large-6 columns">
      <label>Email
        ' . $varEmail . '
      </label>
		</div>
    <div class="large-6 columns">
			<label>Phone</label>
			<div class="row">
				<div class="large-8 columns">
				  ' . $varPhone[2] . '
				</div>
				<div class="large-4 columns">
					' . $varType[2] . '
				</div>
			</div>
    </div>
  </div>
	<div class="row">

    <div class="large-6 columns">
      <label>Username
        ' . $varUserName . '
      </label>
    </div>

    <div class="large-6 columns">
      <label>Type
        ' . $varUserType . '
      </label>
    </div>' .
        $varFamilyIDData .
    '</div>

  </div>' .
	$varNotifPrefs

	;

echo json_encode($varData);
