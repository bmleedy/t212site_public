<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
$id = $_POST['id'];
$edit = $_POST['edit'];
$wm = $_POST['wm'];		// = 1 if scout is wm editing another scout. =0 for adults and if editing his own record
$isAdmin = $_POST['userAdmin'];

$query="SELECT * FROM users WHERE user_id=".$id;
$results = $mysqli->query($query);
$row = $results->fetch_object();
$user_first = $row->user_first;
$user_last = $row->user_last;
$user_email = $row->user_email;
$user_name = $row->user_name;
$user_id = $row->user_id;
$user_type = $row->user_type;
$family_id = $row->family_id;

if ($edit && !$wm) {
	$varFirst = '<input type="text" id="user_first" required value="'. $user_first . '"/>';
	$varLast = '<input type="text" id="user_last" required value="'. $user_last . '"/>';
	$varEmail = '<input type="text" id="user_email" required value="'. $user_email . '"/>';

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
        $varUserName = '<p>' . $user_name . '</p>';
    if ($isAdmin) {
        $varFamilyIDData = '<div class="large-6 columns end"><label>Family ID (do not edit unless you are sure!)<input type="text" id="family_id" required value="'. $family_id . '"/></label></div>';
		$varFamilyID = '';
    } else {
        $varFamilyID = '<input type="hidden" id="family_id" value="'. $family_id . '" />';
		$varFamilyIDData = '';
    }
} else {
	if ($user_type=="Scout") {
		$mailTo = '<a href="mailto:' . $user_email ;
		$sep = ';';
		$query3 = "SELECT user_email FROM users WHERE user_type !='Scout' AND family_id=" . $family_id;
		$results3 = $mysqli->query($query3);
		while ($row3 = $results3->fetch_assoc()) {
			$mailTo = $mailTo . $sep . $row3['user_email'];
		}
		$mailTo = $mailTo . '?Subject=Troop 212 Summer Camp Merit Badge Follow-up">Email Scout & Parents</a>';
	}

	$varFirst = '<p>'.$user_first.'</p>';
	$varLast = '<p>'.$user_last.'</p>';
	if ($user_type=="Scout") {$varEmail = '<p>'.$user_email.'<br>'.$mailTo.'</p>';}
	else {$varEmail='';}

	$varUserType = '<p>'.$user_type.'</p>'.'<input type="hidden" id="user_type" value="'. $user_type . '" />';
	$varUserName = '<p>'.$user_name.'</p>';
    if ($isAdmin) {
        $varFamilyIDData = '<div class="large-6 columns end"><label>Family ID (do not edit unless you are sure!)<input type="text" id="family_id" required value="'. $family_id . '"/></label></div>';
		$varFamilyID='';
    } else {
        $varFamilyID = '<input type="hidden" id="family_id" value="'. $family_id . '" />';
    }
}

$query="SELECT * FROM phone WHERE user_id=".$id;
$results = $mysqli->query($query);
if ($results == false) {
    die("ERROR in ".__FILE__."could not query MySQL");
}
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
			$varPhone[] = '<input type="text" id="user_phone_'.$i.'" value="'. $row["phone"] . '"/>';
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
			$varPhone[] = '<p>'. $row["phone"] .'</p>';
			$varType[] = '<p>'. $row["type"] .'</p>';
		}
	}
};

$varData = $varFamilyID .
'<input type="hidden" id="phone_id_1" value="'. $varID[0] . '" />
<input type="hidden" id="phone_id_2" value="'. $varID[1] . '" />
<input type="hidden" id="phone_id_3" value="'. $varID[2] . '" />
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

  </div>'

	;

echo json_encode($varData);


?>
