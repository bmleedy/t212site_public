<?php
/**
 * Date Selector Unit Test
 *
 * Tests the date selector functionality for the Attendance page.
 * Validates date widget, Pacific Time handling, role-based date editing, and date change events.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Date Selector Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify Attendance.php provides canEditPastDates flag
// ============================================================================

echo "Test 1: canEditPastDates permission flag\n";
echo str_repeat("-", 60) . "\n";

$attendancePhpFile = PUBLIC_HTML_DIR . '/Attendance.php';
$attendancePhpContents = file_get_contents($attendancePhpFile);

if (assert_true(
    strpos($attendancePhpContents, '$canEditPastDates') !== false,
    "Attendance.php defines canEditPastDates variable"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendancePhpContents, 'in_array("wm", $access)') !== false &&
    strpos($attendancePhpContents, 'in_array("sa", $access)') !== false,
    "canEditPastDates checks for webmaster (wm) and scoutmaster (sa)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendancePhpContents, 'id="canEditPastDates"') !== false,
    "canEditPastDates is passed to template as hidden input"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/value=.*\?.*\'1\'.*:.*\'0\'/', $attendancePhpContents),
    "canEditPastDates uses '1' or '0' as value"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify date selector widget exists in template
// ============================================================================

echo "Test 2: Date selector widget structure\n";
echo str_repeat("-", 60) . "\n";

$attendanceTemplateFile = PUBLIC_HTML_DIR . '/templates/Attendance.html';
$attendanceTemplateContents = file_get_contents($attendanceTemplateFile);

if (assert_true(
    strpos($attendanceTemplateContents, 'id="dateSelector"') !== false,
    "Date selector element exists with id='dateSelector'"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'type="date"') !== false,
    "Date selector uses HTML5 date input type"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'Attendance Date:') !== false,
    "Date selector has descriptive label"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify selectedDate variable and initialization
// ============================================================================

echo "Test 3: selectedDate variable and initialization\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'var selectedDate') !== false,
    "selectedDate variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'var canEditPastDates') !== false,
    "canEditPastDates variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'setCurrentDate()') !== false,
    "setCurrentDate() function is called on page load"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "getElementById('canEditPastDates')") !== false,
    "canEditPastDates value is read from hidden input"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify setCurrentDate() function
// ============================================================================

echo "Test 4: setCurrentDate() function implementation\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'function setCurrentDate()') !== false,
    "setCurrentDate() function is defined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'America/Los_Angeles') !== false,
    "Uses Pacific Time Zone (America/Los_Angeles)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'toLocaleString') !== false,
    "Uses toLocaleString for timezone conversion"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "padStart(2, '0')") !== false,
    "Pads month and day with leading zeros"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/dateString\s*=\s*year.*month.*day/', $attendanceTemplateContents),
    "Formats date as YYYY-MM-DD"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "dateSelector').val(dateString)") !== false,
    "Sets date selector value to formatted date"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'selectedDate = dateString') !== false,
    "Updates selectedDate variable"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify role-based date selector permissions
// ============================================================================

echo "Test 5: Role-based date selector permissions\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'if (!canEditPastDates)') !== false,
    "Checks canEditPastDates flag"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "dateSelector').prop('disabled', true)") !== false,
    "Disables date selector for patrol leaders"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "} else {") !== false &&
    strpos($attendanceTemplateContents, "dateSelector').on('change'") !== false,
    "Enables date change handler for scoutmasters/webmasters"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify date change event handler
// ============================================================================

echo "Test 6: Date change event handler\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, "#dateSelector').on('change'") !== false,
    "Date selector has change event handler"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/selectedDate\s*=\s*.*this.*val\(\)/', $attendanceTemplateContents),
    "Change handler updates selectedDate variable"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'loadPatrolMembers(selectedPatrolId)') !== false,
    "Change handler reloads patrol members when date changes"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'if (selectedPatrolId && selectedPatrolId') !== false,
    "Checks if patrol is selected before reloading"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify hover-over instructions for patrol leaders
// ============================================================================

echo "Test 7: Hover-over instructions (title attribute)\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, ".attr('title'") !== false,
    "Sets title attribute for hover-over tooltip"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'Patrol leaders can only record attendance for the current date') !== false,
    "Title explains restriction for patrol leaders"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'contact your Scoutmaster or Webmaster') !== false,
    "Title instructs to contact scoutmaster or webmaster"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'title="Mark the scout present for the selected date') !== false,
    "Table header 'Present' has tooltip with instructions"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'ask your webmaster to make updates for any day') !== false,
    "Table header tooltip instructs to ask webmaster for past dates"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify date parameter sent to APIs
// ============================================================================

echo "Test 8: Date parameter sent to APIs\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/api\/getpatrolmembers\.php.*date:\s*selectedDate/s', $attendanceTemplateContents),
    "loadPatrolMembers sends date parameter to API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/api\/updateattendance\.php.*date:\s*selectedDate/s', $attendanceTemplateContents),
    "updateAttendance sends date parameter to API"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify getpatrolmembers.php accepts date parameter
// ============================================================================

echo "Test 9: getpatrolmembers.php date parameter handling\n";
echo str_repeat("-", 60) . "\n";

$getPatrolMembersFile = PUBLIC_HTML_DIR . '/api/getpatrolmembers.php';
$getPatrolMembersContents = file_get_contents($getPatrolMembersFile);

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_date_post(\'date\'') !== false,
    "getpatrolmembers.php accepts date parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_date_post(\'date\'') !== false,
    "Validates date parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'if (!$date)') !== false,
    "Falls back to current date if not provided"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_date_post(') !== false ||
    strpos($getPatrolMembersContents, 'prepare(') !== false,
    "Uses validate_date_post or prepared statements"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify updateattendance.php date parameter validation
// ============================================================================

echo "Test 10: updateattendance.php date parameter\n";
echo str_repeat("-", 60) . "\n";

$updateAttendanceFile = PUBLIC_HTML_DIR . '/api/updateattendance.php';
$updateAttendanceContents = file_get_contents($updateAttendanceFile);

if (assert_true(
    strpos($updateAttendanceContents, "\$_POST['date']") !== false ||
    strpos($updateAttendanceContents, 'validate_date_post(\'date\'') !== false,
    "updateattendance.php accepts date parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'if (!$date)') !== false,
    "Falls back to current date if not provided"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'validate_date_post(') !== false ||
    strpos($updateAttendanceContents, 'real_escape_string') !== false ||
    strpos($updateAttendanceContents, 'prepare(') !== false,
    "Uses validate_date_post or prepared statements or real_escape_string"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify Pacific Time consistency across APIs
// ============================================================================

echo "Test 11: Pacific Time consistency across APIs\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getPatrolMembersContents, 'America/Los_Angeles') !== false,
    "getpatrolmembers.php uses Pacific Time Zone"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'America/Los_Angeles') !== false,
    "updateattendance.php uses Pacific Time Zone"
)) {
    $passed++;
} else {
    $failed++;
}

// Count occurrences of Pacific timezone in template
$pacificCount = substr_count($attendanceTemplateContents, 'America/Los_Angeles');
if (assert_true(
    $pacificCount >= 1,
    "Template uses Pacific Time Zone for date calculation (found $pacificCount occurrence(s))"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify date format consistency (YYYY-MM-DD)
// ============================================================================

echo "Test 12: Date format consistency (YYYY-MM-DD)\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getPatrolMembersContents, "format('Y-m-d')") !== false,
    "getpatrolmembers.php formats date as Y-m-d"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, "format('Y-m-d')") !== false,
    "updateattendance.php formats date as Y-m-d"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/year.*-.*month.*-.*day/', $attendanceTemplateContents),
    "Template formats date as YYYY-MM-DD"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Verify console logging for debugging
// ============================================================================

echo "Test 13: Console logging for debugging\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'console.log("Current date in Pacific Time:') !== false,
    "Logs current date in Pacific Time"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'console.log("Date changed to:') !== false,
    "Logs date changes"
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
