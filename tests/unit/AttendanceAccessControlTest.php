<?php
/**
 * Attendance Page Access Control Unit Test
 *
 * Tests the access control logic for the Attendance.php page to ensure
 * only authorized users (webmaster, scoutmaster, patrol leaders) can access it.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Page Access Control Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify Attendance.php file exists
// ============================================================================

echo "Test 1: Attendance.php file existence\n";
echo str_repeat("-", 60) . "\n";

$attendanceFile = PUBLIC_HTML_DIR . '/Attendance.php';
if (assert_file_exists($attendanceFile, "Attendance.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify Attendance.html template exists
// ============================================================================

echo "Test 2: Attendance.html template existence\n";
echo str_repeat("-", 60) . "\n";

$templateFile = PUBLIC_HTML_DIR . '/templates/Attendance.html';
if (assert_file_exists($templateFile, "Attendance.html template exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify access control code is present in Attendance.php
// ============================================================================

echo "Test 3: Access control code verification\n";
echo str_repeat("-", 60) . "\n";

$fileContents = file_get_contents($attendanceFile);

// Check for webmaster access check
if (assert_true(
    strpos($fileContents, 'in_array("wm", $access)') !== false,
    "Webmaster (wm) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for scoutmaster access check
if (assert_true(
    strpos($fileContents, 'in_array("sa", $access)') !== false,
    "Scoutmaster (sa) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for patrol leader access check
if (assert_true(
    strpos($fileContents, 'in_array("pl", $access)') !== false,
    "Patrol Leader (pl) access check is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify $hasAccess variable is defined
// ============================================================================

echo "Test 4: \$hasAccess variable definition\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($fileContents, '$hasAccess') !== false,
    "\$hasAccess variable is defined"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify $canEditPastDates variable is defined
// ============================================================================

echo "Test 5: \$canEditPastDates variable definition\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($fileContents, '$canEditPastDates') !== false,
    "\$canEditPastDates variable is defined"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that it only allows wm and sa
if (assert_true(
    preg_match('/\$canEditPastDates\s*=\s*\(.*in_array\("wm".*in_array\("sa"/', $fileContents),
    "\$canEditPastDates only allows webmaster and scoutmaster"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify Access Denied message is present
// ============================================================================

echo "Test 6: Access Denied message verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($fileContents, 'Access Denied') !== false,
    "Access Denied message is present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($fileContents, '!$hasAccess') !== false,
    "Access denial check using !\$hasAccess is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify canEditPastDates hidden input is present
// ============================================================================

echo "Test 7: Hidden input for canEditPastDates\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($fileContents, 'id="canEditPastDates"') !== false,
    "Hidden input for canEditPastDates is present"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify sidebar link exists in m_sidebar.html
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

// Check for Attendance.php link
if (assert_true(
    strpos($sidebarContents, 'Attendance.php') !== false,
    "Attendance.php link is present in sidebar"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for access control on the sidebar link
if (assert_true(
    strpos($sidebarContents, 'in_array("wm",$access)') !== false &&
    strpos($sidebarContents, 'in_array("sa",$access)') !== false &&
    strpos($sidebarContents, 'in_array("pl",$access)') !== false,
    "Sidebar link has proper access control (wm, sa, pl)"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for Attendance Tracker text
if (assert_true(
    strpos($sidebarContents, 'Attendance Tracker') !== false,
    "Attendance Tracker label is present in sidebar"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify template structure
// ============================================================================

echo "Test 9: Template structure verification\n";
echo str_repeat("-", 60) . "\n";

$templateContents = file_get_contents($templateFile);

// Check for jQuery
if (assert_true(
    strpos($templateContents, 'jquery') !== false,
    "jQuery is included in template"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for attendanceContent div
if (assert_true(
    strpos($templateContents, 'attendanceContent') !== false,
    "attendanceContent div is present"
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
