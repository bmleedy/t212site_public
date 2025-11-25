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
$sort = $_POST['sort'];
//todo: I don't think this sort order complexity is needed, but the code is not DRY
//  and it would be better to return the rows then make the rows sortable at the client,
//  which should be just fine and performance.
if ($sort=='patrol') {
	$query="
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
} else if ($sort=='rank') {
	$query="
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
	$query="SELECT u.user_name, u.user_first, u.user_last, u.user_email, u.user_id, si.patrol_id, si.rank_id, si.position_id FROM users as u INNER JOIN scout_info as si ON u.user_id=si.user_id WHERE u.user_type='Scout'  AND u.user_access <> 'del' ORDER BY user_last asc, user_first asc";
	$query="
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
  $id =  $row->user_id;
	$phones = null;
	$rank = getLabel('ranks',$row->rank_id,$mysqli);
	$patrol = getLabel('patrols',$row->patrol_id,$mysqli);
	$position = getLabel('leadership',$row->position_id,$mysqli);

	$query2 = "
		WITH adults AS (
			SELECT user_id
			FROM users
			WHERE family_id IN (SELECT family_id FROM users WHERE user_id = " . $id . ")
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
		WHERE user_id = " . $id . "
	";



	$results2 = $mysqli->query($query2);
	if ($results2) {
		while ($row2 = $results2->fetch_object()){
			$phone_entry = "<a href='tel:" . $row2->phone . "'>" . $row2->phone . "</a> " . $row2->type;
			if ($row2->is_adult_phone) {
				$phone_entry .= " (adult)";
			}
			$phones[] = $phone_entry;
		}
	}
	$scouts[] = [
    'first' => $row->user_first,
    'last' => $row->user_last,
    'email'=> $row->user_email,
		'username'=> $row->user_name,
		'rank'=> $rank,
		'patrol'=> $patrol,
		'position'=> $position,
		'id'=>$id,
		'phone'=>$phones
  ];
}

echo json_encode($scouts);
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
