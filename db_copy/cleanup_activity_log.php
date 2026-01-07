#!/usr/bin/env php
<?php
/**
 * Activity Log Cleanup Cron Script
 *
 * Deletes activity_log entries older than 90 days.
 *
 * Usage:
 *   Run manually: php /path/to/db_copy/cleanup_activity_log.php
 *   Add to crontab: 0 2 * * * /usr/bin/php /path/to/db_copy/cleanup_activity_log.php
 *   (runs daily at 2:00 AM)
 */

// Get the path to the credentials utility and activity logger
$credentials_path = __DIR__ . '/../public_html/includes/credentials.php';
$activity_logger_path = __DIR__ . '/../public_html/includes/activity_logger.php';

// Load credentials utility
try {
    require_once($credentials_path);
} catch (Exception $e) {
    error_log("Error loading credentials utility: " . $e->getMessage());
    die("Error loading credentials utility: " . $e->getMessage() . "\n");
}

// Load activity logger utility
try {
    require_once($activity_logger_path);
} catch (Exception $e) {
    error_log("Error loading activity logger utility: " . $e->getMessage());
    die("Error loading activity logger utility: " . $e->getMessage() . "\n");
}

// Get database credentials from CREDENTIALS.json
try {
    $creds = Credentials::getInstance();
    $user = $creds->getDatabaseUser();
    $password = $creds->getDatabasePassword();
    $database = $creds->getDatabaseName();
    $host = $creds->getDatabaseHost();
} catch (Exception $e) {
    error_log("Error loading credentials: " . $e->getMessage());
    die("Error loading credentials: " . $e->getMessage() . "\n");
}

// Create database connection
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    error_log("Database connection error: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    die("Database connection error: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n");
}

// Delete activity log entries older than 90 days
$sql = "DELETE FROM activity_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)";

if ($mysqli->query($sql)) {
    $deleted_count = $mysqli->affected_rows;
    $message = date('Y-m-d H:i:s') . " - Activity log cleanup: Deleted {$deleted_count} entries older than 90 days\n";
    echo $message;
    error_log($message);

    // Log the cleanup to activity log
    log_activity(
        $mysqli,
        'cleanup_activity_log.php',
        'cleanup_activity_log',
        json_encode([
            'deleted_count' => $deleted_count,
            'retention_days' => 90
        ]),
        1, // success
        "Deleted {$deleted_count} activity log entries older than 90 days",
        null // system action, no user
    );
} else {
    $error_message = date('Y-m-d H:i:s') . " - Activity log cleanup error: " . $mysqli->error . "\n";
    echo $error_message;
    error_log($error_message);

    // Log the cleanup failure to activity log
    log_activity(
        $mysqli,
        'cleanup_activity_log.php',
        'cleanup_activity_log_failed',
        json_encode([
            'error' => $mysqli->error,
            'retention_days' => 90
        ]),
        0, // failure
        "Failed to delete activity log entries: " . $mysqli->error,
        null // system action, no user
    );
}

// Close the database connection
$mysqli->close();
?>
