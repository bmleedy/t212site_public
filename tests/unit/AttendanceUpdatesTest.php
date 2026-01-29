<?php
/**
 * Attendance Updates API Unit Test
 *
 * Tests the AJAX attendance update functionality for the Attendance page.
 * Validates that checkboxes update attendance records idempotently for the current day.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Updates API Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify updateattendance.php API file exists
// ============================================================================

echo "Test 1: API file existence\n";
echo str_repeat("-", 60) . "\n";

$updateAttendanceFile = PUBLIC_HTML_DIR . '/api/updateattendance.php';
if (assert_file_exists($updateAttendanceFile, "updateattendance.php API exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify updateattendance.php structure
// ============================================================================

echo "Test 2: updateattendance.php API structure\n";
echo str_repeat("-", 60) . "\n";

$updateAttendanceContents = file_get_contents($updateAttendanceFile);

if (assert_true(
    strpos($updateAttendanceContents, 'require_ajax()') !== false,
    "updateattendance.php uses require_ajax() for AJAX protection"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'validate_int_post') !== false,
    "updateattendance.php uses validate_int_post for user_id parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, '$_POST[\'was_present\']') !== false,
    "updateattendance.php accepts was_present parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'validate_date_post') !== false,
    "updateattendance.php uses validate_date_post for date parameter"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify Pacific Time Zone handling
// ============================================================================

echo "Test 3: Pacific Time Zone date handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, 'America/Los_Angeles') !== false,
    "Uses Pacific Time Zone for date calculation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'new DateTimeZone') !== false,
    "Creates DateTimeZone object"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'new DateTime') !== false,
    "Creates DateTime object"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "format('Y-m-d')") !== false,
    "Formats date as Y-m-d"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'if (!$date)') !== false,
    "Uses current date if date parameter not provided"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify idempotent INSERT or UPDATE logic
// ============================================================================

echo "Test 4: Idempotent INSERT or UPDATE logic\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, 'SELECT id FROM attendance_daily') !== false,
    "Checks if attendance record already exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'WHERE user_id =') !== false &&
    strpos($updateAttendanceContents, 'AND date =') !== false,
    "Queries by user_id and date"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'UPDATE attendance_daily') !== false,
    "Updates existing record if found"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'INSERT INTO attendance_daily') !== false,
    "Inserts new record if not found"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'SET was_present =') !== false,
    "UPDATE statement sets was_present field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, '(user_id, date, was_present)') !== false,
    "INSERT statement includes user_id, date, and was_present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify parameter validation
// ============================================================================

echo "Test 5: Parameter validation\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, "validate_int_post('user_id')") !== false,
    "Uses validate_int_post() for user_id validation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "validate_date_post('date'") !== false,
    "Uses validate_date_post() for date validation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "isset(\$_POST['was_present'])") !== false,
    "Checks if was_present parameter is set"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify SQL injection protection
// ============================================================================

echo "Test 6: Security - SQL injection protection\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, 'validate_int_post') !== false,
    "Uses validate_int_post() for integer validation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'bind_param') !== false,
    "Uses prepared statements with bind_param for SQL injection protection"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'isset($_POST') !== false,
    "Checks if POST parameters are set"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify JSON response format
// ============================================================================

echo "Test 7: JSON response format\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, "header('Content-Type: application/json')") !== false,
    "Sets JSON content type header"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'json_encode') !== false,
    "Returns JSON encoded response"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'status' =>") !== false,
    "Response includes status field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'message' =>") !== false,
    "Response includes message field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'action' =>") !== false,
    "Response includes action field (created/updated)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'user_id' =>") !== false,
    "Response includes user_id field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'date' =>") !== false,
    "Response includes date field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "'was_present' =>") !== false,
    "Response includes was_present field"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify error handling
// ============================================================================

echo "Test 8: Error handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateAttendanceContents, "'status' => 'Error'") !== false,
    "Returns error status on failure"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'Failed to update attendance') !== false ||
    strpos($updateAttendanceContents, 'Failed to record attendance') !== false,
    "Returns generic error message for security (hides internal details)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify getpatrolmembers.php returns was_present flag
// ============================================================================

echo "Test 9: getpatrolmembers.php returns attendance status\n";
echo str_repeat("-", 60) . "\n";

$getPatrolMembersFile = PUBLIC_HTML_DIR . '/api/getpatrolmembers.php';
$getPatrolMembersContents = file_get_contents($getPatrolMembersFile);

if (assert_true(
    strpos($getPatrolMembersContents, 'America/Los_Angeles') !== false,
    "getpatrolmembers.php uses Pacific Time Zone"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'SELECT was_present FROM attendance_daily') !== false,
    "Queries attendance_daily table for was_present status"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, "'was_present' =>") !== false,
    "Returns was_present flag with each member"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, '$was_present = false') !== false,
    "Defaults was_present to false if no record exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, '(bool)$attendanceRow[\'was_present\']') !== false,
    "Casts was_present to boolean"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify Attendance.html checkbox functionality
// ============================================================================

echo "Test 10: Attendance.html checkbox functionality\n";
echo str_repeat("-", 60) . "\n";

$attendanceTemplateFile = PUBLIC_HTML_DIR . '/templates/Attendance.html';
$attendanceTemplateContents = file_get_contents($attendanceTemplateFile);

if (assert_true(
    strpos($attendanceTemplateContents, 'attendance-checkbox') !== false,
    "Checkboxes have attendance-checkbox class"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'data-user-id') !== false,
    "Checkboxes store user_id in data attribute"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'member.was_present') !== false,
    "Checks member.was_present flag from API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/var\s+checked\s*=\s*member\.was_present\s*\?\s*[\'"]?\s*checked/', $attendanceTemplateContents),
    "Sets checked attribute based on was_present flag"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify checkbox change event handler
// ============================================================================

echo "Test 11: Checkbox change event handler\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, ".attendance-checkbox').on('change'") !== false,
    "Attaches change event handler to checkboxes"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'checkbox.data(\'user-id\')') !== false,
    "Retrieves user_id from data attribute"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'checkbox.is(\':checked\')') !== false,
    "Checks checkbox state"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'updateAttendance(userId, wasPresent, checkbox)') !== false,
    "Calls updateAttendance function with user_id, was_present, and checkbox"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify updateAttendance() AJAX function
// ============================================================================

echo "Test 12: updateAttendance() AJAX function\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'function updateAttendance') !== false,
    "updateAttendance() function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "url: \"api/updateattendance.php\"") !== false,
    "Calls updateattendance.php API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'user_id: userId') !== false,
    "Sends user_id parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'was_present: wasPresent') !== false,
    "Sends was_present parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'checkbox.prop(\'disabled\', true)') !== false,
    "Disables checkbox during AJAX call"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'checkbox.prop(\'disabled\', false)') !== false,
    "Re-enables checkbox after AJAX call"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Verify error handling in updateAttendance()
// ============================================================================

echo "Test 13: Error handling in updateAttendance()\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/error:\s*function.*errorThrown/', $attendanceTemplateContents),
    "Has error callback function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'console.error') !== false,
    "Logs errors to console"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'alert("Error updating attendance') !== false,
    "Shows error alert to user"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'checkbox.prop(\'checked\', !wasPresent)') !== false,
    "Reverts checkbox state on error"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: Verify success handling in updateAttendance()
// ============================================================================

echo "Test 14: Success handling in updateAttendance()\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, "data.status === \"Success\"") !== false,
    "Checks for success status in response"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "if (data.status === \"Success\")") !== false,
    "Has success response handling"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 15: Verify date parameter is sent with selected date
// ============================================================================

echo "Test 15: Verify date parameter sent with selected date\n";
echo str_repeat("-", 60) . "\n";

// Extract the data object from updateAttendance AJAX call
$ajaxDataPattern = '/data:\s*\{[^}]*user_id:[^}]*was_present:[^}]*date:[^}]*\}/s';
if (preg_match($ajaxDataPattern, $attendanceTemplateContents, $matches)) {
    $ajaxDataBlock = $matches[0];

    if (assert_true(
        strpos($ajaxDataBlock, 'date:') !== false,
        "updateAttendance() sends date parameter"
    )) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(
        strpos($ajaxDataBlock, 'date: selectedDate') !== false,
        "updateAttendance() sends selectedDate variable as date parameter"
    )) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "  ❌ FAILED: Could not find AJAX data block with date parameter in updateAttendance()\n";
    $failed += 2;
}

echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
