<?php
/**
 * Stop Impersonation
 *
 * Endpoint to end impersonation and restore original admin session.
 * This is a direct navigation endpoint (not AJAX).
 */

session_start();

// Must have a session
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/index.php');
    exit();
}

require_once(__DIR__ . '/../includes/impersonation_helper.php');

// Check if impersonating
if (!is_impersonating()) {
    header('Location: /index.php');
    exit();
}

// Store info for logging before restoring session
$impersonated_user_id = $_SESSION['user_id'];
$admin_user_id = $_SESSION['original_user_id'];
$admin_user_name = $_SESSION['original_user_name'];
$admin_user_first = $_SESSION['original_user_first'];

// Stop impersonation (restores original session)
$result = stop_impersonation();

if ($result['success']) {
    // Now we're back to admin session, log the event
    require 'connect.php';
    require_once(__DIR__ . '/../includes/activity_logger.php');

    log_activity(
        $mysqli,
        'stop_impersonation',
        [
            'admin_user_id' => $admin_user_id,
            'admin_user_name' => $admin_user_name,
            'impersonated_user_id' => $impersonated_user_id
        ],
        true,
        "Admin '$admin_user_first' stopped impersonating user ID: $impersonated_user_id",
        $admin_user_id
    );

    $mysqli->close();
}

// Redirect back to impersonation page
header('Location: /Impersonate.php');
exit();
