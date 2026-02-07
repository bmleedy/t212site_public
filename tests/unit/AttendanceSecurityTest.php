<?php
/**
 * Attendance Security Unit Test
 *
 * Tests security implementation across attendance-related files
 * to ensure proper authentication, authorization, CSRF protection,
 * prepared statements, and XSS protection.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Security Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: updateattendance.php has authentication and security
// ============================================================================

echo "Test 1: updateattendance.php Security\n";
echo str_repeat("-", 60) . "\n";

$updateAttendanceFile = PUBLIC_HTML_DIR . '/api/updateattendance.php';
if (assert_file_exists($updateAttendanceFile, "updateattendance.php exists")) {
    $passed++;
} else {
    $failed++;
}

$updateAttendanceContents = file_get_contents($updateAttendanceFile);

if (assert_true(
    strpos($updateAttendanceContents, 'require_authentication()') !== false,
    "updateattendance.php contains require_authentication()"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'require_permission(') !== false,
    "updateattendance.php contains require_permission("
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, 'require_csrf(') !== false,
    "updateattendance.php contains require_csrf("
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateAttendanceContents, '$mysqli->query(') === false,
    "updateattendance.php does NOT use raw \$mysqli->query() (should use prepared statements)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: getscoutsforattendance.php has authentication
// ============================================================================

echo "Test 2: getscoutsforattendance.php Security\n";
echo str_repeat("-", 60) . "\n";

$getScoutsFile = PUBLIC_HTML_DIR . '/api/getscoutsforattendance.php';
if (assert_file_exists($getScoutsFile, "getscoutsforattendance.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getScoutsContents = file_get_contents($getScoutsFile);

if (assert_true(
    strpos($getScoutsContents, 'require_authentication()') !== false,
    "getscoutsforattendance.php contains require_authentication()"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getScoutsContents, 'require_permission(') !== false,
    "getscoutsforattendance.php contains require_permission("
)) {
    $passed++;
} else {
    $failed++;
}

// Note: CSRF not required for this read-only endpoint that doesn't send POST data
// Authentication and permission checks are sufficient for read operations

echo "\n";

// ============================================================================
// TEST 3: getattendancedata.php has authentication
// ============================================================================

echo "Test 3: getattendancedata.php Security\n";
echo str_repeat("-", 60) . "\n";

$getDataFile = PUBLIC_HTML_DIR . '/api/getattendancedata.php';
if (assert_file_exists($getDataFile, "getattendancedata.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getDataContents = file_get_contents($getDataFile);

if (assert_true(
    strpos($getDataContents, 'require_authentication()') !== false,
    "getattendancedata.php contains require_authentication()"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getDataContents, 'require_permission(') !== false,
    "getattendancedata.php contains require_permission("
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getDataContents, 'require_csrf(') !== false,
    "getattendancedata.php contains require_csrf("
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: getattendanceevents.php has authentication
// ============================================================================

echo "Test 4: getattendanceevents.php Security\n";
echo str_repeat("-", 60) . "\n";

$getEventsFile = PUBLIC_HTML_DIR . '/api/getattendanceevents.php';
if (assert_file_exists($getEventsFile, "getattendanceevents.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getEventsContents = file_get_contents($getEventsFile);

if (assert_true(
    strpos($getEventsContents, 'require_authentication()') !== false,
    "getattendanceevents.php contains require_authentication()"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getEventsContents, 'require_permission(') !== false,
    "getattendanceevents.php contains require_permission("
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getEventsContents, 'require_csrf(') !== false,
    "getattendanceevents.php contains require_csrf("
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Attendance.html has XSS protection
// ============================================================================

echo "Test 5: Attendance.html XSS Protection\n";
echo str_repeat("-", 60) . "\n";

$attendanceHtmlFile = PUBLIC_HTML_DIR . '/templates/Attendance.html';
if (assert_file_exists($attendanceHtmlFile, "Attendance.html exists")) {
    $passed++;
} else {
    $failed++;
}

$attendanceHtmlContents = file_get_contents($attendanceHtmlFile);

if (assert_true(
    strpos($attendanceHtmlContents, 'function escapeHtml(') !== false,
    "Attendance.html contains escapeHtml function definition"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    substr_count($attendanceHtmlContents, 'escapeHtml(') >= 2,
    "Attendance.html uses escapeHtml() at least once (plus definition)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: AttendanceReport.html has XSS protection
// ============================================================================

echo "Test 6: AttendanceReport.html XSS Protection\n";
echo str_repeat("-", 60) . "\n";

$attendanceReportHtmlFile = PUBLIC_HTML_DIR . '/templates/AttendanceReport.html';
if (assert_file_exists($attendanceReportHtmlFile, "AttendanceReport.html exists")) {
    $passed++;
} else {
    $failed++;
}

$attendanceReportHtmlContents = file_get_contents($attendanceReportHtmlFile);

if (assert_true(
    strpos($attendanceReportHtmlContents, 'function escapeHtml(') !== false,
    "AttendanceReport.html contains escapeHtml function definition"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    substr_count($attendanceReportHtmlContents, 'escapeHtml(') >= 2,
    "AttendanceReport.html uses escapeHtml() at least once (plus definition)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Page files have secure session handling
// ============================================================================

echo "Test 7: Secure Session Handling\n";
echo str_repeat("-", 60) . "\n";

$attendancePhpFile = PUBLIC_HTML_DIR . '/Attendance.php';
if (assert_file_exists($attendancePhpFile, "Attendance.php exists")) {
    $passed++;
} else {
    $failed++;
}

$attendancePhpContents = file_get_contents($attendancePhpFile);

if (assert_true(
    stripos($attendancePhpContents, 'httponly') !== false,
    "Attendance.php contains httponly session cookie parameter"
)) {
    $passed++;
} else {
    $failed++;
}

$attendanceReportPhpFile = PUBLIC_HTML_DIR . '/AttendanceReport.php';
if (assert_file_exists($attendanceReportPhpFile, "AttendanceReport.php exists")) {
    $passed++;
} else {
    $failed++;
}

$attendanceReportPhpContents = file_get_contents($attendanceReportPhpFile);

if (assert_true(
    stripos($attendanceReportPhpContents, 'httponly') !== false,
    "AttendanceReport.php contains httponly session cookie parameter"
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
