<?php
/**
 * Get Patrol Emails API
 *
 * Returns email addresses and phone numbers for all members of a patrol plus the scoutmaster.
 * Used for "Email Patrol" and "Send Patrol Text" buttons on user profile page.
 */

error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

header('Content-Type: application/json');

require 'connect.php';

// Get user_id parameter
$user_id = validate_int_post('user_id');

if (!$user_id) {
    echo json_encode(['status' => 'Error', 'message' => 'User ID required']);
    exit;
}

// Get the patrol_id and patrol name for the specified user
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
        'status' => 'Error',
        'message' => 'User is not in a patrol'
    ]);
    exit;
}

$patrol_id = $patrol_row['patrol_id'];
$patrol_name = $patrol_row['patrol_name'];

// Get all scouts in this patrol
$emails = [];
$phones = [];

// Get scout emails and their family emails
$query = "SELECT DISTINCT u.user_email, u.user_first, u.user_last, u.user_type, u.family_id
          FROM users u
          JOIN scout_info si ON u.user_id = si.user_id
          WHERE si.patrol_id = ? AND u.user_active = 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $patrol_id);
$stmt->execute();
$result = $stmt->get_result();

$family_ids = [];
while ($row = $result->fetch_assoc()) {
    // Add scout's email if they have one
    if (!empty($row['user_email'])) {
        $emails[] = $row['user_email'];
    }
    // Collect family IDs to get parent emails
    if (!empty($row['family_id'])) {
        $family_ids[] = $row['family_id'];
    }
}
$stmt->close();

// Get parent/adult emails and user_ids for these families
$adult_user_ids = [];
if (!empty($family_ids)) {
    $placeholders = implode(',', array_fill(0, count($family_ids), '?'));
    $types = str_repeat('i', count($family_ids));

    $query = "SELECT DISTINCT user_id, user_email
              FROM users
              WHERE family_id IN ($placeholders)
              AND user_type != 'Scout'
              AND user_active = 1";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$family_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['user_email'])) {
            $emails[] = $row['user_email'];
        }
        $adult_user_ids[] = $row['user_id'];
    }
    $stmt->close();

    // Get phone numbers for these adults
    if (!empty($adult_user_ids)) {
        $placeholders = implode(',', array_fill(0, count($adult_user_ids), '?'));
        $types = str_repeat('i', count($adult_user_ids));

        $query = "SELECT DISTINCT phone
                  FROM phone
                  WHERE user_id IN ($placeholders)
                  AND phone IS NOT NULL
                  AND phone != ''";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param($types, ...$adult_user_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (!empty($row['phone'])) {
                // Clean phone number - remove non-digits except +
                $phone = preg_replace('/[^0-9+]/', '', $row['phone']);
                if (!empty($phone)) {
                    $phones[] = $phone;
                }
            }
        }
        $stmt->close();
    }
}

// Get scoutmaster email from committee table
$query = "SELECT u.user_id, u.user_email
          FROM committee c
          JOIN users u ON c.user_id = u.user_id
          WHERE c.role_name LIKE '%Scoutmaster%'
          AND u.user_active = 1
          LIMIT 1";
$result = $mysqli->query($query);
if ($row = $result->fetch_assoc()) {
    if (!empty($row['user_email'])) {
        $emails[] = $row['user_email'];
    }
    // Get scoutmaster phone
    $sm_id = $row['user_id'];
    $phoneQuery = "SELECT phone FROM phone WHERE user_id = ? LIMIT 1";
    $phoneStmt = $mysqli->prepare($phoneQuery);
    $phoneStmt->bind_param('i', $sm_id);
    $phoneStmt->execute();
    $phoneResult = $phoneStmt->get_result();
    if ($phoneRow = $phoneResult->fetch_assoc()) {
        $phone = preg_replace('/[^0-9+]/', '', $phoneRow['phone']);
        if (!empty($phone)) {
            $phones[] = $phone;
        }
    }
    $phoneStmt->close();
}

// Remove duplicates and empty values
$emails = array_unique(array_filter($emails));
$emails = array_values($emails);

$phones = array_unique(array_filter($phones));
$phones = array_values($phones);

echo json_encode([
    'status' => 'Success',
    'patrol_id' => $patrol_id,
    'patrol_name' => $patrol_name,
    'emails' => $emails,
    'phones' => $phones
]);
