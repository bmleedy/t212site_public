<?php
// Load credentials utility
try {
    require_once(__DIR__ . '/../includes/credentials.php');
} catch (Exception $e) {
    die("Error loading credentials utility: " . $e->getMessage());
}

// Get database credentials from CREDENTIALS.json
try {
    $creds = Credentials::getInstance();
    $user = $creds->getDatabaseUser();
    $password = $creds->getDatabasePassword();
    $database = $creds->getDatabaseName();
    $host = $creds->getDatabaseHost();
} catch (Exception $e) {
    die("Error loading credentials: " . $e->getMessage());
}

// Create database connection
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
	print "fail ";
   die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
?>
