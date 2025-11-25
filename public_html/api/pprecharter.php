<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
$rc_ids= explode("," , $_POST['rc_ids']);

// Process rows, updating scout's user table entry to indicate recharter and boyslife
$recharter = null;
$query = "UPDATE scout_info SET rechartered=1, boyslife=? WHERE user_id=?";
$statement = $mysqli->prepare($query);

foreach ($rc_ids as &$id) {
	if (strpos($id, "bl") === 0) {
		$bl='1';
		$id=substr( $id, 2 );
	} else {
		$bl='0';
	}

	$rs = $statement->bind_param('ss', $bl, $id);
	$statement->execute();

	$query2 = "SELECT user_first, user_last FROM users WHERE user_id=".$id;
	$results2 = $mysqli->query($query2);
	$row2 = $results2->fetch_assoc();
	$first = $row2['user_first'];
	$last = $row2['user_last'];
	$recharter[] = [
		'username' => $first . ' ' . $last,
		'bl' => $bl
	];
}
$statement->close();

$returnMsg = array(
	'status' => 'Success',
	'recharterData' => $recharter
);
echo json_encode($returnMsg);
die;

?> 