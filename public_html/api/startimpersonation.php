<?php
/**
 * Start Impersonation
 *
 * API endpoint to begin impersonating a user.
 * Requires Super Admin (sa) permission.
 */

session_start();
require 'auth_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['sa']);

header('Content-Type: application/json');

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// CSRF validation
$csrf_token = $input['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'Error', 'message' => 'Invalid CSRF token']);
    die();
}

require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
require_once(__DIR__ . '/../includes/impersonation_helper.php');

$target_user_id = (int)($input['target_user_id'] ?? 0);

if ($target_user_id <= 0) {
    echo json_encode(['status' => 'Error', 'message' => 'Invalid user ID']);
    die();
}

// Store admin info for logging before we swap sessions
$admin_user_id = $_SESSION['user_id'];
$admin_user_name = $_SESSION['user_name'];
$admin_user_first = $_SESSION['user_first'];

// Start impersonation
$result = start_impersonation($mysqli, $target_user_id);

if ($result['success']) {
    // Log the impersonation start (use admin's ID for the user field)
    log_activity(
        $mysqli,
        'start_impersonation',
        [
            'admin_user_id' => $admin_user_id,
            'admin_user_name' => $admin_user_name,
            'target_user_id' => $target_user_id
        ],
        true,
        "Admin '$admin_user_first' (ID: $admin_user_id) started impersonating user ID: $target_user_id",
        $admin_user_id
    );

    echo json_encode(['status' => 'Success', 'message' => $result['message']]);
} else {
    log_activity(
        $mysqli,
        'start_impersonation_failed',
        [
            'admin_user_id' => $admin_user_id,
            'target_user_id' => $target_user_id,
            'error' => $result['message']
        ],
        false,
        "Failed impersonation attempt by '$admin_user_first': " . $result['message'],
        $admin_user_id
    );

    echo json_encode(['status' => 'Error', 'message' => $result['message']]);
}

$mysqli->close();
