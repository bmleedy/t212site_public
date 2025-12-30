<?php
/**
 * Attendance Report API Test
 *
 * Tests the API endpoints used by the AttendanceReport page:
 * - getattendanceevents.php
 * - getattendancedata.php
 * - getscoutsforattendance.php
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Report API Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify getattendanceevents.php file exists
// ============================================================================

echo "Test 1: getattendanceevents.php file existence\n";
echo str_repeat("-", 60) . "\n";

$eventsApiFile = PUBLIC_HTML_DIR . '/api/getattendanceevents.php';
if (assert_file_exists($eventsApiFile, "getattendanceevents.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify getattendancedata.php file exists
// ============================================================================

echo "Test 2: getattendancedata.php file existence\n";
echo str_repeat("-", 60) . "\n";

$dataApiFile = PUBLIC_HTML_DIR . '/api/getattendancedata.php';
if (assert_file_exists($dataApiFile, "getattendancedata.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify getscoutsforattendance.php file exists
// ============================================================================

echo "Test 3: getscoutsforattendance.php file existence\n";
echo str_repeat("-", 60) . "\n";

$scoutsApiFile = PUBLIC_HTML_DIR . '/api/getscoutsforattendance.php';
if (assert_file_exists($scoutsApiFile, "getscoutsforattendance.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify getattendanceevents.php structure
// ============================================================================

echo "Test 4: getattendanceevents.php structure verification\n";
echo str_repeat("-", 60) . "\n";

$eventsApiContents = file_get_contents($eventsApiFile);

if (assert_true(
    strpos($eventsApiContents, 'XMLHttpRequest') !== false,
    "AJAX-only protection is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'start_date') !== false,
    "start_date parameter is handled"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'end_date') !== false,
    "end_date parameter is handled"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, "date('N', \$current) == 2") !== false,
    "Tuesday detection logic is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'Troop Meeting') !== false,
    "Troop Meeting event name is used for Tuesdays"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'FROM events') !== false,
    "Events table query is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'ksort') !== false,
    "Events are sorted by date"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify getattendancedata.php structure
// ============================================================================

echo "Test 5: getattendancedata.php structure verification\n";
echo str_repeat("-", 60) . "\n";

$dataApiContents = file_get_contents($dataApiFile);

if (assert_true(
    strpos($dataApiContents, 'XMLHttpRequest') !== false,
    "AJAX-only protection is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'FROM attendance_daily') !== false,
    "attendance_daily table query is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'user_id') !== false,
    "user_id is selected"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'was_present') !== false,
    "was_present is selected"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\$key\s*=\s*\$row\[.user_id.\].*\$row\[.date.\]/', $dataApiContents),
    "Attendance data is keyed by user_id-date"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'real_escape_string') !== false,
    "SQL injection protection is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify getscoutsforattendance.php structure
// ============================================================================

echo "Test 6: getscoutsforattendance.php structure verification\n";
echo str_repeat("-", 60) . "\n";

$scoutsApiContents = file_get_contents($scoutsApiFile);

if (assert_true(
    strpos($scoutsApiContents, 'XMLHttpRequest') !== false,
    "AJAX-only protection is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, 'FROM users') !== false,
    "users table query is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, 'LEFT JOIN scout_info') !== false,
    "scout_info table is joined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, 'LEFT JOIN patrols') !== false,
    "patrols table is joined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, "user_type = 'Scout'") !== false,
    "Only scouts are selected"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/ORDER BY.*p\.sort.*user_last.*user_first/', $scoutsApiContents),
    "Scouts are ordered by patrol, last name, first name"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify JSON response format
// ============================================================================

echo "Test 7: JSON response format verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($eventsApiContents, 'json_encode') !== false,
    "getattendanceevents.php returns JSON"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'json_encode') !== false,
    "getattendancedata.php returns JSON"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, 'json_encode') !== false,
    "getscoutsforattendance.php returns JSON"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, "'status' => 'Success'") !== false,
    "getattendanceevents.php includes status field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, "'status' => 'Success'") !== false,
    "getattendancedata.php includes status field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($scoutsApiContents, "'status' => 'Success'") !== false,
    "getscoutsforattendance.php includes status field"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify error handling
// ============================================================================

echo "Test 8: Error handling verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($eventsApiContents, "'status' => 'Error'") !== false,
    "getattendanceevents.php has error handling"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, "'status' => 'Error'") !== false,
    "getattendancedata.php has error handling"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventsApiContents, 'are required') !== false,
    "getattendanceevents.php validates required parameters"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, 'are required') !== false,
    "getattendancedata.php validates required parameters"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
