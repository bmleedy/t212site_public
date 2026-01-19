<?php
/**
 * Attendance Report Access Control Test
 *
 * Tests the access control for the AttendanceReport page.
 * Validates that only webmasters, outing editors, and super admins can access.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Report Access Control Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify AttendanceReport.php file exists
// ============================================================================

echo "Test 1: AttendanceReport.php file existence\n";
echo str_repeat("-", 60) . "\n";

$attendanceReportFile = PUBLIC_HTML_DIR . '/AttendanceReport.php';
if (assert_file_exists($attendanceReportFile, "AttendanceReport.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify AttendanceReport.html template exists
// ============================================================================

echo "Test 2: AttendanceReport.html template existence\n";
echo str_repeat("-", 60) . "\n";

$templateFile = PUBLIC_HTML_DIR . '/templates/AttendanceReport.html';
if (assert_file_exists($templateFile, "AttendanceReport.html template exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify access control code
// ============================================================================

echo "Test 3: Access control code verification\n";
echo str_repeat("-", 60) . "\n";

$attendanceReportContents = file_get_contents($attendanceReportFile);

if (assert_true(
    strpos($attendanceReportContents, 'in_array("wm", $access)') !== false,
    "Webmaster (wm) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceReportContents, 'in_array("sa", $access)') !== false,
    "Scoutmaster (sa) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceReportContents, 'in_array("oe", $access)') !== false,
    "Outing Editor (oe) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify $hasAccess variable definition
// ============================================================================

echo "Test 4: \$hasAccess variable definition\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceReportContents, '$hasAccess') !== false,
    "\$hasAccess variable is defined"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify $canEditAttendance variable definition
// ============================================================================

echo "Test 5: \$canEditAttendance variable definition\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceReportContents, '$canEditAttendance') !== false,
    "\$canEditAttendance variable is defined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\$canEditAttendance\s*=\s*\(in_array\("wm".*in_array\("sa"/s', $attendanceReportContents),
    "\$canEditAttendance only allows webmaster and scoutmaster"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify Access Denied message
// ============================================================================

echo "Test 6: Access Denied message verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceReportContents, 'Access Denied') !== false,
    "Access Denied message is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceReportContents, '!$hasAccess') !== false,
    "Access denial check using !\$hasAccess is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify hidden input for canEditAttendance
// ============================================================================

echo "Test 7: Hidden input for canEditAttendance\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($attendanceReportContents, 'id="canEditAttendance"') !== false,
    "Hidden input for canEditAttendance is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify sidebar navigation link
// ============================================================================

echo "Test 8: Sidebar navigation link verification\n";
echo str_repeat("-", 60) . "\n";

$sidebarFile = PUBLIC_HTML_DIR . '/includes/m_sidebar.html';
if (assert_file_exists($sidebarFile, "m_sidebar.html file exists")) {
    $passed++;
} else {
    $failed++;
}

$sidebarContents = file_get_contents($sidebarFile);

if (assert_true(
    strpos($sidebarContents, 'AttendanceReport.php') !== false,
    "AttendanceReport.php link is present in sidebar"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\(in_array\("wm",\$access\)\).*\(in_array\("oe",\$access\)\).*\(in_array\("sa",\$access\)\).*AttendanceReport\.php/s', $sidebarContents),
    "Sidebar link has proper access control (wm, oe, sa)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sidebarContents, 'Attendance Report') !== false,
    "Attendance Report label is present in sidebar"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify mobile menu navigation link
// ============================================================================

echo "Test 9: Mobile menu navigation link verification\n";
echo str_repeat("-", 60) . "\n";

$mobileMenuFile = PUBLIC_HTML_DIR . '/includes/mobile_menu.html';
if (assert_file_exists($mobileMenuFile, "mobile_menu.html file exists")) {
    $passed++;
} else {
    $failed++;
}

$mobileMenuContents = file_get_contents($mobileMenuFile);

if (assert_true(
    strpos($mobileMenuContents, 'AttendanceReport.php') !== false,
    "AttendanceReport.php link is present in mobile menu"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\(in_array\("wm",\$access\)\).*\(in_array\("oe",\$access\)\).*\(in_array\("sa",\$access\)\).*AttendanceReport\.php/s', $mobileMenuContents),
    "Mobile menu link has proper access control (wm, oe, sa)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify template structure
// ============================================================================

echo "Test 10: Template structure verification\n";
echo str_repeat("-", 60) . "\n";

$templateContents = file_get_contents($templateFile);

if (assert_true(
    strpos($templateContents, 'var canEditAttendance') !== false,
    "canEditAttendance variable is declared in template"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, "getElementById('canEditAttendance')") !== false,
    "Template reads canEditAttendance from hidden input"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'reportContent') !== false,
    "reportContent div is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Attendance Report') !== false,
    "Page title 'Attendance Report' is present"
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
