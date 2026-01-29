<?php
/**
 * Get Patrol Members For User API
 *
 * Returns all scouts in the same patrol as the specified user.
 * Used for "Patrol Members" table on user profile page.
 */

error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

// Validate CSRF token
require_csrf();

header('Content-Type: application/json');

require 'connect.php';

// Get user_id parameter
$user_id = validate_int_post('user_id');

if (!$user_id) {
    echo json_encode(['status' => 'Error', 'message' => 'User ID required']);
    exit;
}

// Authorization check: User can access patrol data if:
// 1. They are requesting their own data
// 2. They are in the same patrol as the requested user
// 3. They have elevated permissions (pl, er, wm, sa)
$authorized = false;

// Check if user is accessing their own data
if ($user_id == $current_user_id) {
    $authorized = true;
}

// Check if user has elevated permissions
if (!$authorized && (has_permission('pl') || has_permission('er') || has_permission('wm') || has_permission('sa'))) {
    $authorized = true;
}

// Check if users are in the same patrol
if (!$authorized) {
    $checkPatrolQuery = "SELECT si1.patrol_id as current_patrol, si2.patrol_id as requested_patrol
                         FROM scout_info si1, scout_info si2
                         WHERE si1.user_id = ? AND si2.user_id = ?";
    $checkStmt = $mysqli->prepare($checkPatrolQuery);
    $checkStmt->bind_param('ii', $current_user_id, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $patrolCheck = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($patrolCheck && $patrolCheck['current_patrol'] &&
        $patrolCheck['current_patrol'] == $patrolCheck['requested_patrol']) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode(['status' => 'Error', 'message' => 'Not authorized to view this patrol\'s data']);
    log_activity(
        $mysqli,
        'get_patrol_members',
        array('requested_user_id' => $user_id),
        false,
        "Unauthorized attempt to access patrol members for user ID: $user_id",
        $current_user_id
    );
    exit;
}

// Get the patrol_id for the specified user
$query = "SELECT si.patrol_id, p.label as patrol_name
          FROM scout_info si
          JOIN patrols p ON si.patrol_id = p.id
          WHERE si.user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patrol_row = $result->fetch_assoc();
$stmt->close();

if (!$patrol_row || !$patrol_row['patrol_id']) {
    echo json_encode([
        'status' => 'NoPatrol',
        'message' => 'User is not in a patrol'
    ]);
    exit;
}

$patrol_id = $patrol_row['patrol_id'];
$patrol_name = $patrol_row['patrol_name'];

// Get all scouts in this patrol with their info (only active, non-alumni scouts)
$query = "SELECT u.user_id, u.user_first, u.user_last,
                 r.label as rank_name, l.label as position_name
          FROM users u
          JOIN scout_info si ON u.user_id = si.user_id
          LEFT JOIN ranks r ON si.rank_id = r.id
          LEFT JOIN leadership l ON si.position_id = l.id
          WHERE si.patrol_id = ?
          AND u.user_active = 1
          AND u.user_type = 'Scout'
          ORDER BY u.user_last, u.user_first";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $patrol_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = [
        'user_id' => (int)$row['user_id'],
        'first_name' => $row['user_first'],
        'last_name' => $row['user_last'],
        'rank' => $row['rank_name'] ?: '',
        'position' => $row['position_name'] ?: ''
    ];
}
$stmt->close();

echo json_encode([
    'status' => 'Success',
    'patrol_id' => $patrol_id,
    'patrol_name' => $patrol_name,
    'members' => $members
]);

log_activity(
    $mysqli,
    'get_patrol_members',
    array('requested_user_id' => $user_id, 'patrol_id' => $patrol_id, 'member_count' => count($members)),
    true,
    "Retrieved patrol members for user ID: $user_id (patrol: $patrol_name)",
    $current_user_id
);
