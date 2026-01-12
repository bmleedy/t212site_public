<?php
/**
 * Get Event Attendee Phones API
 *
 * Returns phone numbers for event attendees.
 * Used for "Text All Attendees" and "Text Adult Attendees" buttons on Event page.
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

// Get event_id parameter
$event_id = validate_int_post('event_id');

if (!$event_id) {
    echo json_encode(['status' => 'Error', 'message' => 'Event ID required']);
    exit;
}

// Get event name
$event_name = '';
$event_query = "SELECT name FROM events WHERE id = ?";
$event_stmt = $mysqli->prepare($event_query);
$event_stmt->bind_param('i', $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_row = $event_result->fetch_assoc()) {
    $event_name = $event_row['name'];
}
$event_stmt->close();

$all_phones = [];
$adult_phones = [];

// Get registered attendees (attending, not cancelled)
$query = "SELECT r.user_id, u.user_type, u.family_id
          FROM registration r
          JOIN users u ON r.user_id = u.user_id
          WHERE r.event_id = ?
          AND r.attending = 1
          AND u.user_active = 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

$scout_family_ids = [];
$adult_user_ids = [];

while ($row = $result->fetch_assoc()) {
    if ($row['user_type'] == 'Scout') {
        // Collect family IDs for scouts to get parent phones
        if (!empty($row['family_id'])) {
            $scout_family_ids[] = $row['family_id'];
        }
    } else {
        // Adult attendee - get their phone directly
        $adult_user_ids[] = $row['user_id'];
    }
}
$stmt->close();

// Get phone numbers for adult attendees
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
        $phone = preg_replace('/[^0-9+]/', '', $row['phone']);
        if (!empty($phone)) {
            $adult_phones[] = $phone;
            $all_phones[] = $phone;
        }
    }
    $stmt->close();
}

// Get phone numbers for scout families (parents)
if (!empty($scout_family_ids)) {
    // First get parent user_ids
    $placeholders = implode(',', array_fill(0, count($scout_family_ids), '?'));
    $types = str_repeat('i', count($scout_family_ids));

    $query = "SELECT DISTINCT user_id
              FROM users
              WHERE family_id IN ($placeholders)
              AND user_type != 'Scout'
              AND user_active = 1";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$scout_family_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $parent_user_ids = [];
    while ($row = $result->fetch_assoc()) {
        $parent_user_ids[] = $row['user_id'];
    }
    $stmt->close();

    // Get phone numbers for parents
    if (!empty($parent_user_ids)) {
        $placeholders = implode(',', array_fill(0, count($parent_user_ids), '?'));
        $types = str_repeat('i', count($parent_user_ids));

        $query = "SELECT DISTINCT phone
                  FROM phone
                  WHERE user_id IN ($placeholders)
                  AND phone IS NOT NULL
                  AND phone != ''";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param($types, ...$parent_user_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $phone = preg_replace('/[^0-9+]/', '', $row['phone']);
            if (!empty($phone)) {
                $all_phones[] = $phone;
            }
        }
        $stmt->close();
    }
}

// Remove duplicates
$all_phones = array_unique(array_filter($all_phones));
$all_phones = array_values($all_phones);

$adult_phones = array_unique(array_filter($adult_phones));
$adult_phones = array_values($adult_phones);

echo json_encode([
    'status' => 'Success',
    'event_id' => $event_id,
    'event_name' => $event_name,
    'all_phones' => $all_phones,
    'adult_phones' => $adult_phones
]);
