<?php
//header('Content-Type: application/json');
require 'connect.php';
$userID=$_SESSION['user_id'];
$query="SELECT * FROM scout_info WHERE user_id=".$userID;
$results = $mysqli->query($query);
$row = $results->fetch_object();
if ($row){

} else {
	if ($_SERVER['PHP_SELF'] <> "/User.php") {
		header("Location: /User.php?id=".$userID."&edit=1");
	}
}



?>
