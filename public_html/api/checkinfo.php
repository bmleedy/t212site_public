<?php
session_start();
require 'auth_helper.php';
require 'connect.php';

// Verify authentication
$userID = require_authentication();

// Use prepared statement to check scout_info
$query = "SELECT * FROM scout_info WHERE user_id=?";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    error_log("Database error in checkinfo.php: " . $mysqli->error);
    die();
}

$stmt->bind_param('i', $userID);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_object();
$stmt->close();

if ($row) {
    // Scout info exists, continue
} else {
    // Scout info missing, redirect to User.php if not already there
    if ($_SERVER['PHP_SELF'] <> "/User.php") {
        header("Location: /User.php?id=" . (int)$userID . "&edit=1");
    }
}
?>
