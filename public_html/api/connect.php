<?php
/**
 * Database Connection
 *
 * Establishes mysqli connection using credentials from CREDENTIALS.json.
 */

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
// Note: SSL can be configured in CREDENTIALS.json if needed for production environments

if ($mysqli->connect_error) {
    error_log('Database connection error: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    die('Database connection failed. Please contact the administrator.');
}