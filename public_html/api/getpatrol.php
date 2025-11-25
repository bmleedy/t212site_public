<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';
$scouts = null;
$patrol_id = $_POST['patrol_id'];
$patrol = getLabel('patrols',$id,$mysqli);
$patrol_leader = "";
$asst_pl = "";
$troop_guide = "";

// Patrol Leader = 1, APL=2, TG=10
// SPL = 3, ASPL = 4, Staff=1

// if staff...
if ($patrol_id=='1') {
	$pl = '3';
	$apl = '4';
} else {
	$pl = '1';
	$apl = '2';
}

// get PL
$query = "SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id='" . $pl . "' AND si.patrol_id='" . $patrol_id . "' AND u.user_id = si.user_id" ;
$results = $mysqli->query($query);
while ($row = $results->fetch_object()) {
	if ($patrol_leader == "") {
		$patrol_leader = $row->user_first . " " . $row->user_last;
	} else {
		$patrol_leader = $patrol_leader . ", " . $row->user_first . " " . $row->user_last;
	}
}

$query = "SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id='" . $apl . "' AND si.patrol_id='" . $patrol_id . "' AND u.user_id = si.user_id" ;
$results = $mysqli->query($query);
while ($row = $results->fetch_object()) {
	if ($asst_pl == "") {
		$asst_pl = $row->user_first . " " . $row->user_last;
	} else {
		$asst_pl = $asst_pl . ", " . $row->user_first . " " . $row->user_last;
	}
}

$query = "SELECT u.user_id, user_first, user_last FROM users AS u, scout_info AS si WHERE si.position_id='10' AND si.patrol_id='" . $patrol_id . "' AND u.user_id = si.user_id" ;
$results = $mysqli->query($query);
while ($row = $results->fetch_object()) {
	if ($troop_guide == "") {
		$troop_guide = $row->user_first . " " . $row->user_last;
	} else {
		$troop_guide = $troop_guide . ", " . $row->user_first . " " . $row->user_last;
	}
}

$leaderData = '<div class="row"><div class="large-4 columns"><label>Patrol Leader</label>'.$patrol_leader.'</div>';
$leaderData = $leaderData . '<div class="large-4 columns"><label>Asst Patrol Leader</label>'.$asst_pl.'</div>';
$leaderData = $leaderData . '<div class="large-4 columns"><label>Troop Guide</label>'.$troop_guide.'</div>';
$leaderData = $leaderData . '</div.';

	
$query="SELECT u.user_name, u.user_first, u.user_last, u.user_email, u.user_id, si.patrol_id, si.rank_id, si.position_id FROM users as u INNER JOIN scout_info as si ON u.user_id=si.user_id WHERE si.patrol_id=".$patrol_id." ORDER BY si.rank_id desc, u.user_last asc, u.user_first asc";
$results = $mysqli->query($query);

while ($row = $results->fetch_object()) {
  $id =  $row->user_id;
	$phones = null;
  $query2="SELECT * FROM phone WHERE user_id=" . $id;
	$results2 = $mysqli->query($query2);
	if ($results2) {
		while ($row2 = $results2->fetch_object()){
			$phones[] = $row2->phone . " " . $row2->type;
		}
	}
	$rank = getLabel('ranks',$row->rank_id,$mysqli);
	$position = getLabel('leadership',$row->position_id,$mysqli);
  
	$scouts[] = [
    'first' => $row->user_first,
    'last' => $row->user_last,
    'email'=> $row->user_email,
		'username'=> $row->user_name,
		'rank'=> $rank,
		'position'=> $position,
		'id'=>$id,
		'phone'=>$phones
  ];
}


$returnMsg = array(
	'patrol_name' => $patrol,
	'leaderData' => $leaderData,
	'scouts' => $scouts
);

echo json_encode($returnMsg);
die();


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
