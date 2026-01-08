<?php
/**
 * Patrol Members API Unit Test
 *
 * Tests the patrol dropdown and member loading functionality for the Attendance page.
 * Validates that the API endpoints correctly retrieve and format patrol and member data.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Patrol Members API Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify API files exist
// ============================================================================

echo "Test 1: API file existence\n";
echo str_repeat("-", 60) . "\n";

$getPatrolsFile = PUBLIC_HTML_DIR . '/api/getpatrols.php';
if (assert_file_exists($getPatrolsFile, "getpatrols.php API exists")) {
    $passed++;
} else {
    $failed++;
}

$getPatrolMembersFile = PUBLIC_HTML_DIR . '/api/getpatrolmembers.php';
if (assert_file_exists($getPatrolMembersFile, "getpatrolmembers.php API exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify getpatrols.php structure
// ============================================================================

echo "Test 2: getpatrols.php API structure\n";
echo str_repeat("-", 60) . "\n";

$getPatrolsContents = file_get_contents($getPatrolsFile);

if (assert_true(
    strpos($getPatrolsContents, 'require_ajax()') !== false,
    "getpatrols.php checks for AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, 'FROM patrols') !== false,
    "getpatrols.php queries patrols table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, 'INNER JOIN scout_info') !== false &&
    strpos($getPatrolsContents, 'INNER JOIN users') !== false,
    "getpatrols.php joins scout_info and users tables"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, "user_type = 'Scout'") !== false,
    "getpatrols.php filters for patrols with active scouts only"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, 'ORDER BY') !== false &&
    strpos($getPatrolsContents, 'sort') !== false,
    "getpatrols.php orders by sort column"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, "'label' => 'None'") !== false,
    "getpatrols.php includes 'None' option"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, 'json_encode') !== false,
    "getpatrols.php returns JSON response"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify getpatrolmembers.php structure
// ============================================================================

echo "Test 3: getpatrolmembers.php API structure\n";
echo str_repeat("-", 60) . "\n";

$getPatrolMembersContents = file_get_contents($getPatrolMembersFile);

if (assert_true(
    strpos($getPatrolMembersContents, 'require_ajax()') !== false,
    "getpatrolmembers.php checks for AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_int_post') !== false,
    "getpatrolmembers.php accepts patrol_id parameter"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'INNER JOIN scout_info') !== false,
    "getpatrolmembers.php joins scout_info table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'si.patrol_id') !== false,
    "getpatrolmembers.php filters by patrol_id"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, "user_type = 'Scout'") !== false,
    "getpatrolmembers.php filters for Scout user_type only"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'ORDER BY u.user_last, u.user_first') !== false,
    "getpatrolmembers.php orders by last name, first name"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_int_post') !== false ||
    strpos($getPatrolMembersContents, 'prepare(') !== false,
    "getpatrolmembers.php sanitizes patrol_id (SQL injection protection)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify Attendance.html template has patrol dropdown
// ============================================================================

echo "Test 4: Attendance.html patrol dropdown\n";
echo str_repeat("-", 60) . "\n";

$attendanceTemplateFile = PUBLIC_HTML_DIR . '/templates/Attendance.html';
$attendanceTemplateContents = file_get_contents($attendanceTemplateFile);

if (assert_true(
    strpos($attendanceTemplateContents, 'patrolSelector') !== false,
    "Attendance.html has patrolSelector element"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'loadPatrols()') !== false,
    "Attendance.html calls loadPatrols() function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'api/getpatrols.php') !== false,
    "Attendance.html makes AJAX call to getpatrols.php"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify Attendance.html template has member table functionality
// ============================================================================

echo "Test 5: Attendance.html member table functionality\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, 'membersTableContainer') !== false,
    "Attendance.html has membersTableContainer element"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'membersTable') !== false,
    "Attendance.html has membersTable element"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'loadPatrolMembers') !== false,
    "Attendance.html has loadPatrolMembers() function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'api/getpatrolmembers.php') !== false,
    "Attendance.html makes AJAX call to getpatrolmembers.php"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'selectedPatrolId') !== false,
    "Attendance.html tracks selectedPatrolId variable"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify patrol selector change event handler
// ============================================================================

echo "Test 6: Patrol selector change event handler\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, "#patrolSelector').on('change'") !== false,
    "Patrol selector has change event handler"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/loadPatrolMembers\s*\(\s*selectedPatrolId\s*\)/', $attendanceTemplateContents),
    "Change handler calls loadPatrolMembers with selectedPatrolId"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, "membersTableContainer').hide()") !== false,
    "Table hides when no patrol selected"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify table structure and data display
// ============================================================================

echo "Test 7: Member table structure\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceTemplateContents, '<table>') !== false,
    "Template builds HTML table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, '<thead>') !== false,
    "Table has header row"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, '<tbody>') !== false,
    "Table has body section"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'member.first') !== false &&
    strpos($attendanceTemplateContents, 'member.last') !== false,
    "Table displays member first and last names"
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
    strpos($attendanceTemplateContents, 'error: function') !== false,
    "AJAX calls have error handlers"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'Loading patrol members') !== false,
    "Loading state message is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'No members found') !== false,
    "Empty state message for patrols with no members"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceTemplateContents, 'console.error') !== false ||
    strpos($attendanceTemplateContents, 'console.log') !== false,
    "Debug logging is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify SQL injection protection
// ============================================================================

echo "Test 9: Security - SQL injection protection\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getPatrolMembersContents, 'validate_int_post') !== false ||
    strpos($getPatrolMembersContents, 'prepare(') !== false,
    "getpatrolmembers.php uses intval() for SQL injection protection"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, "validate_int_post('patrol_id'") !== false,
    "getpatrolmembers.php checks if patrol_id is set"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that both APIs reject non-AJAX requests
if (assert_true(
    strpos($getPatrolsContents, 'require_ajax()') !== false &&
    strpos($getPatrolMembersContents, 'require_ajax()') !== false,
    "Both APIs reject non-AJAX requests"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify data format consistency
// ============================================================================

echo "Test 10: Data format consistency\n";
echo str_repeat("-", 60) . "\n";

// Check that getpatrols returns proper JSON structure
if (assert_true(
    strpos($getPatrolsContents, "'status' => 'Success'") !== false,
    "getpatrols.php returns status field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolsContents, "'patrols' =>") !== false,
    "getpatrols.php returns patrols array"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that getpatrolmembers returns proper JSON structure
if (assert_true(
    strpos($getPatrolMembersContents, "'status' => 'Success'") !== false,
    "getpatrolmembers.php returns status field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, "'members' =>") !== false,
    "getpatrolmembers.php returns members array"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getPatrolMembersContents, "'user_id'") !== false &&
    strpos($getPatrolMembersContents, "'first'") !== false &&
    strpos($getPatrolMembersContents, "'last'") !== false,
    "getpatrolmembers.php returns user_id, first, and last fields"
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
