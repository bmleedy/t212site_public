<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$event_id = $_POST['event_id'];
$user_id = $_POST['user_id'];
$edit = $_POST['edit'];
$showMailto = $_POST['showMailto'];

if ($event_id != "New") {	
	// Get User Type (Scout, Dad, etc)
	$query="SELECT user_type, user_first FROM users WHERE user_id=".$user_id;
	$results = $mysqli->query($query);
	$row = $results->fetch_assoc();
	$user_type = $row['user_type'];
	$user_first = $row['user_first'];

	// Is User signed up already?
	$query="SELECT attending FROM registration WHERE user_id=".$user_id . " AND event_id=" . $event_id;
	$results = $mysqli->query($query);
	$row = $results->fetch_assoc();
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
	$query="SELECT * FROM events WHERE id=".$event_id;
	$results = $mysqli->query($query);
	if ($results) {
		$row = $results->fetch_assoc();
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
			$query2="SELECT user_first,user_last FROM users WHERE user_id=".$sic_id;
			$results2 = $mysqli->query($query2);
			if ($results2) {
				$row2 = $results2->fetch_assoc();
				$sic = $row2['user_first'] . ' ' . $row2['user_last'];
			}
		}
		if ($aic_id > 0) {
			$query2="SELECT user_first,user_last FROM users WHERE user_id=".$aic_id;
			$results2 = $mysqli->query($query2);
			if ($results2) {
				$row2 = $results2->fetch_assoc();
				$aic = $row2['user_first'] . ' ' . $row2['user_last'];
			}
		}
	}	
}
	
if ($edit=="1" || $event_id=="New") { 
	$varname = '<input type="text" id="name" required value="'. $name . '"/>';
	$varlocation = '<input type="text" id="location" required value="'. $location . '"/>';
	$vardescription = '<textarea id="description" required>'. $description . '</textarea>';
	$varstartdate = '<input type="text" id="startdate" required value="'. $startdate . '"/>';
	$varenddate = '<input type="text" id="enddate" required value="'. $enddate . '"/>';
	$varcost = '<input type="number" id="cost" required value="'. $cost . '"/>';
	$varadultcost = '<input type="number" id="adult_cost" required value="'. $adult_cost . '"/>';
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
	$varname = '<p>'. $name . '</p>';
	$varlocation = '<p>'. $location . '</p>';
	$vardescription = '<p>'. $description . '</p>';
	$varstartdate = '<p>'. $startdate . '</p>';
	$varenddate = '<p>'. $enddate . '</p>';
	$varsic = '<p>'. $sic . '</p>';
	$varaic = '<p>'. $aic . '</p>';
	$varType = '<p>'. $type . '</p>';
	$varcost = '<p>$'. $cost . '</p>';
	$varadultcost = '<p>$'. $adult_cost . '</p>';
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
//$returnData = $returnData . '<div class="large-2 columns"><label>Sign Ups</label>'.$varopen.'</div></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Event Type</label>'.$varType.'</div></div>';
$returnData = $returnData . '<div class="row">';
$returnData = $returnData . '<div class="large-12 columns"><label>Event Description' . $vardescription . '</label>';

if ($event_id != "New") {	
	$isParentOf[] = [];
	if ($user_type <> "Scout") {
		$query = "SELECT scout_id FROM relationships WHERE adult_id=".$user_id;
		$results=$mysqli->query($query);
		while ($row = $results->fetch_assoc()) {
			$isParentOf[]=$row['scout_id'];
		}
	}

	$attendingScouts = null;
	$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, patrol_id, user_email, reg.id as register_id, reg.approved_by FROM registration AS reg, users AS u, scout_info AS si WHERE reg.attending=1 AND u.user_type='Scout' AND reg.user_id = u.user_id AND reg.user_id = si.user_id AND reg.event_id=" . $event_id . " ORDER BY patrol_id, user_last, user_first" ;
	$results = $mysqli->query($query);
	while ($row = $results->fetch_assoc()) {
		if (in_array($row['user_id'],$isParentOf)) {
			$show_approved=1;
		} else {
			$show_approved=0;
		}
		$attendingScouts[] = [
			'patrol' => getLabel('patrols',$row['patrol_id'],$mysqli),
			'id' => $row['user_id'],
			'register_id' => $row['register_id'],
			'approved' => $row['approved_by'],
			'paid' => $row['paid'],
			'first' => $row['user_first'],
			'last' => $row['user_last'],
			'show_approved' => $show_approved
		];
		$mailto = $mailto . $sep . $row['user_email'];
		$sep = ";";
		
		$query2 = "SELECT family_id FROM users WHERE user_id=" . $row['user_id'];
		$results2 = $mysqli->query($query2);
		if ($row2 = $results2->fetch_assoc()) {
			$family_id = $row2["family_id"];
			$query3 = "SELECT user_email FROM users WHERE user_type !='Scout' AND family_id=" . $family_id;
			$results3 = $mysqli->query($query3);
			while ($row3 = $results3->fetch_assoc()) {
				if (strpos($mailto, $row3['user_email'])===false) {
					$mailto = $mailto . $sep . $row3['user_email'];
				}
			}
		}		
	}

	$attendingAdults = null;
	$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, user_email, reg.id as register_id FROM registration AS reg, users AS u WHERE reg.attending=1 AND u.user_type<>'Scout' AND reg.user_id = u.user_id AND reg.event_id=" . $event_id . " ORDER BY user_last, user_first" ;
	$results = $mysqli->query($query);
	while ($row = $results->fetch_assoc()) {
		$attendingAdults[] = [
			'patrol' => 'Adults',
			'id' => $row['user_id'],
			'register_id' => $row['register_id'],
			'paid' => $row['paid'],
			'seat_belts' => $row['seat_belts'],
			'first' => $row['user_first'],
			'last' => $row['user_last']
		];
		if (strpos($mailto, $row['user_email'])===false) {
			$mailto = $mailto . $sep . $row['user_email'];
			$sep = ";";
		}
		
	}
}

if ($showMailto == '1') {
	$mailto = $mailto . "?Subject=Troop 212 " . $name . "'>Send Email to Attending Scouts & Parents</a>";
	$returnData = $returnData . $mailto . '<br><br></div></div>';
}

$returnMsg = array(
	'startdate' => $startdate,
	'enddate' => $enddate,
	'outing_name' => $name,
	'cost' => $cost,
	'adult_cost' => $adult_cost,
	'first' => $user_first,
	'user_type' => $user_type,
	'registered' => $registered,
	'reg_open' => $reg_open,
	'attendingScouts' => $attendingScouts,
	'attendingAdults' => $attendingAdults,
	'data' => $returnData
);

echo json_encode($returnMsg);
die;


function getDDL($strTable,$strSelect,$strDefault,$mysqli) {
	$query = 'SELECT * FROM '.$strTable.' ORDER BY sort';
	$results = $mysqli->query($query);
	$returnDDL = '<select id="'.$strSelect.'"><option value="">-Select-</option>';
	
	while ($row = $results->fetch_assoc()) {
		$label = $row['label'];
		$id = $row['id'];
		if ($strDefault==$label) {
			$sel = " SELECTED ";
		} else {
			$sel = "" ;
		}
		$returnDDL = $returnDDL . '<option value="'.$id.'" '.$sel.'>'.$label.'</option>';
	}
	$returnDDL = $returnDDL . '</select>' ;
	return $returnDDL ;
}

function getUserDDL($mysqli,$user_type,$def_id) {
	$query = "SELECT user_first, user_last, user_id FROM users WHERE user_type='".$user_type."' ORDER BY user_last, user_first" ;
	$results = $mysqli->query($query);
	$returnDDL = "";
	
	while ($row = $results->fetch_assoc()) {
		$label = $row['user_last'] . ", " . $row['user_first'];
		$user_id = $row['user_id'];
		if ($user_id==$def_id) {
			$returnDDL = $returnDDL . '<option SELECTED value="'.$user_id.'">'.$label.'</option>';
		} else {
			$returnDDL = $returnDDL . '<option value="'.$user_id.'">'.$label.'</option>';
		}
	}
	$returnDDL = $returnDDL . '</select>' ;
	return $returnDDL ;
}


function getLabel($strTable,$id,$mysqli){
	if ($id) {
		$query = 'SELECT label FROM '.$strTable.' WHERE id='.$id;
		$results = $mysqli->query($query);
		$row = $results->fetch_assoc();
		return $row['label'];
	} else {
		return "";
	}
}	
?>
