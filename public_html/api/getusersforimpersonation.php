<?php
/**
 * Get Users for Impersonation
 *
 * API endpoint to list users that can be impersonated.
 * Requires Super Admin (sa) permission.
 */

session_start();
require 'auth_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['sa']);

header('Content-Type: application/json');
require 'connect.php';

// Get filter from request
$input = json_decode(file_get_contents('php://input'), true);
$userType = $input['userType'] ?? '';

// Build query - exclude current user and deleted users
$query = "SELECT user_id, user_first, user_last, user_type, user_email
          FROM users
          WHERE user_active = 1
            AND user_type != 'Delete'
            AND user_id != ?";
$params = [$current_user_id];
$types = 'i';

if (!empty($userType)) {
    if ($userType === 'Adult') {
        // Adults include Dad, Mom, Other (non-Scout types)
        $query .= " AND user_type IN ('Dad', 'Mom', 'Other')";
    } else {
        $query .= " AND user_type = ?";
        $params[] = $userType;
        $types .= 's';
    }
}

$query .= " ORDER BY user_last ASC, user_first ASC";

$stmt = $mysqli->prepare($query);
if ($stmt === false) {
    echo json_encode(['status' => 'Error', 'message' => 'Database error']);
    die();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
$mysqli->close();

echo json_encode([
    'status' => 'Success',
    'users' => $users
]);
