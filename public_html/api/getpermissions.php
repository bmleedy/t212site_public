<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
// Only super admins can view permissions
require_permission(['sa']);
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Get filter parameters
$input = json_decode(file_get_contents('php://input'), true);

$userType = $input['userType'] ?? '';
$name = $input['name'] ?? '';
$permission = $input['permission'] ?? '';
$showDeleted = $input['showDeleted'] ?? false;
$showAlumni = $input['showAlumni'] ?? false;

// Build query with filters
$query = "SELECT user_id, user_first, user_last, user_type, user_access
          FROM users
          WHERE user_active = 1";

// Exclude deleted users by default unless showDeleted is true
if (!$showDeleted) {
  $query .= " AND user_type != 'Delete'";
}

// Exclude alumni users by default unless showAlumni is true
if (!$showAlumni) {
  $query .= " AND user_type NOT LIKE 'Alum%'";
}

$params = [];
$types = '';

// Filter by user type
if ($userType !== '') {
  $query .= " AND user_type = ?";
  $params[] = $userType;
  $types .= 's';
}

// Filter by name (search in first or last name)
if ($name !== '') {
  $query .= " AND (user_first LIKE ? OR user_last LIKE ?)";
  $searchTerm = '%' . $name . '%';
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= 'ss';
}

// Filter by permission (search in user_access field)
if ($permission !== '') {
  // Match permission as whole word (not substring)
  // Using CONCAT with dots to ensure we match the permission code exactly
  $query .= " AND (user_access = ? OR user_access LIKE ? OR user_access LIKE ? OR user_access LIKE ?)";
  // Matches: exact match, at start, at end, or in middle
  $params[] = $permission;                      // Exact match
  $params[] = $permission . '.%';                // At start
  $params[] = '%.' . $permission;                // At end
  $params[] = '%.' . $permission . '.%';         // In middle
  $types .= 'ssss';
}

// Order by user type (Scouts first), then by last name
$query .= " ORDER BY
            CASE WHEN user_type = 'Scout' THEN 1 ELSE 2 END,
            user_last, user_first";

// Prepare and execute
$statement = $mysqli->prepare($query);

if (!empty($params)) {
  $statement->bind_param($types, ...$params);
}

$statement->execute();
$result = $statement->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
  $users[] = [
    'user_id' => $row['user_id'],
    'user_first' => escape_html($row['user_first']),
    'user_last' => escape_html($row['user_last']),
    'user_type' => escape_html($row['user_type']),
    'user_access' => escape_html($row['user_access'] ?? '')
  ];
}

$statement->close();
$mysqli->close();

echo json_encode($users);
exit;
?>
