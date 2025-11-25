<?php
header('Content-Type: application/json');
require 'connect.php';

$query="SELECT id,position FROM scout_info WHERE position<>''";
$results = $mysqli->query($query);
while ($row = $results->fetch_object()) {
	$id = $row->id;
	$label = $row->position;
	print "Label: " . $label;
	if ($label) {
		$query2 = "SELECT id FROM leadership WHERE label='".$label."'";
		$results2 = $mysqli->query($query2);
		$row2 = $results2->fetch_object();
		$key_id = $row2->id;
		
		$query3 = "UPDATE scout_info SET position_id=? WHERE id=?";
		$stmt = $mysqli->prepare($query3);
		$stmt->bind_param("ss", $key_id, $id);
		$stmt->execute();
		$stmt->close();
	}
	
}


?> 