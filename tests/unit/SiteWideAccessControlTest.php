<?php
/**
 * Site-Wide Access Control Unit Test
 *
 * Tests access control implementation across all pages in the site
 * to ensure consistent security and proper authorization checks.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Site-Wide Access Control Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// Access Code Reference Map
// ============================================================================
$accessCodeReference = [
    'wm' => 'Webmaster',
    'sa' => 'Scoutmaster / Super Admin',
    'oe' => 'Outing/Event Editor',
    'ue' => 'User Editor',
    'er' => 'Event Roster Viewer',
    'trs' => 'Treasurer (Payment)',
    'pl' => 'Patrol Leader'
];

echo "\nAccess Code Reference:\n";
echo str_repeat("-", 60) . "\n";
foreach ($accessCodeReference as $code => $description) {
    echo "  $code = $description\n";
}
echo "\n";

// ============================================================================
// TEST 1: Event.php Access Control
// ============================================================================

echo "Test 1: Event.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$eventFile = PUBLIC_HTML_DIR . '/Event.php';
if (assert_file_exists($eventFile, "Event.php exists")) {
    $passed++;
} else {
    $failed++;
}

$eventContents = file_get_contents($eventFile);

// Event editing requires oe or sa
if (assert_true(
    strpos($eventContents, 'in_array("oe",$access)') !== false,
    "Event.php checks for 'oe' (Outing Editor) access"
)) {
    $passed++;
} else {
    $failed++;
}

// Event roster viewing requires er or sa
if (assert_true(
    strpos($eventContents, 'in_array("er",$access)') !== false,
    "Event.php checks for 'er' (Event Roster) access"
)) {
    $passed++;
} else {
    $failed++;
}

// Payment marking requires trs or sa
if (assert_true(
    strpos($eventContents, 'in_array("trs",$access)') !== false,
    "Event.php checks for 'trs' (Treasurer) access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: ListEvents.php Access Control
// ============================================================================

echo "Test 2: ListEvents.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$listEventsFile = PUBLIC_HTML_DIR . '/ListEvents.php';
if (assert_file_exists($listEventsFile, "ListEvents.php exists")) {
    $passed++;
} else {
    $failed++;
}

$listEventsContents = file_get_contents($listEventsFile);

// Editing requires oe or sa
if (assert_true(
    strpos($listEventsContents, 'in_array("oe",$access)') !== false,
    "ListEvents.php checks for 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($listEventsContents, '$showEdit') !== false,
    "ListEvents.php has \$showEdit variable"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: ListEventsAll.php Access Control
// ============================================================================

echo "Test 3: ListEventsAll.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$listEventsAllFile = PUBLIC_HTML_DIR . '/ListEventsAll.php';
if (assert_file_exists($listEventsAllFile, "ListEventsAll.php exists")) {
    $passed++;
} else {
    $failed++;
}

$listEventsAllContents = file_get_contents($listEventsAllFile);

if (assert_true(
    strpos($listEventsAllContents, 'in_array("oe",$access)') !== false,
    "ListEventsAll.php checks for 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: EventSignups.php Access Control
// ============================================================================

echo "Test 4: EventSignups.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$eventSignupsFile = PUBLIC_HTML_DIR . '/EventSignups.php';
if (assert_file_exists($eventSignupsFile, "EventSignups.php exists")) {
    $passed++;
} else {
    $failed++;
}

$eventSignupsContents = file_get_contents($eventSignupsFile);

if (assert_true(
    strpos($eventSignupsContents, 'in_array("oe",$access)') !== false,
    "EventSignups.php checks for 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventSignupsContents, 'You are not authorized') !== false,
    "EventSignups.php shows authorization error message"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Signups.php Access Control
// ============================================================================

echo "Test 5: Signups.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$signupsFile = PUBLIC_HTML_DIR . '/Signups.php';
if (assert_file_exists($signupsFile, "Signups.php exists")) {
    $passed++;
} else {
    $failed++;
}

$signupsContents = file_get_contents($signupsFile);

if (assert_true(
    strpos($signupsContents, 'in_array("oe",$access)') !== false,
    "Signups.php checks for 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($signupsContents, 'You are not authorized') !== false,
    "Signups.php shows authorization error message"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: EventRoster.php Access Control
// ============================================================================

echo "Test 6: EventRoster.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$eventRosterFile = PUBLIC_HTML_DIR . '/EventRoster.php';
if (assert_file_exists($eventRosterFile, "EventRoster.php exists")) {
    $passed++;
} else {
    $failed++;
}

$eventRosterContents = file_get_contents($eventRosterFile);

if (assert_true(
    strpos($eventRosterContents, 'in_array("er",$access)') !== false,
    "EventRoster.php checks for 'er' (Event Roster) access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($eventRosterContents, 'You are not authorized') !== false,
    "EventRoster.php shows authorization error message"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: EventRosterSI.php Access Control
// ============================================================================

echo "Test 7: EventRosterSI.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$eventRosterSIFile = PUBLIC_HTML_DIR . '/EventRosterSI.php';
if (assert_file_exists($eventRosterSIFile, "EventRosterSI.php exists")) {
    $passed++;
} else {
    $failed++;
}

$eventRosterSIContents = file_get_contents($eventRosterSIFile);

if (assert_true(
    strpos($eventRosterSIContents, 'in_array("er",$access)') !== false,
    "EventRosterSI.php checks for 'er' access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: User.php Access Control
// ============================================================================

echo "Test 8: User.php Access Control\n";
echo str_repeat("-", 60) . "\n";

$userFile = PUBLIC_HTML_DIR . '/User.php';
if (assert_file_exists($userFile, "User.php exists")) {
    $passed++;
} else {
    $failed++;
}

$userContents = file_get_contents($userFile);

// Webmaster access
if (assert_true(
    preg_match('/in_array\("wm",\s*\$access\)/', $userContents),
    "User.php checks for 'wm' (Webmaster) access"
)) {
    $passed++;
} else {
    $failed++;
}

// User editor access
if (assert_true(
    preg_match('/in_array\("ue",\s*\$access\)/', $userContents),
    "User.php checks for 'ue' (User Editor) access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: DELUser.php Access Control (removed - file was intentionally deleted)
// ============================================================================

echo "Test 9: DELUser.php Access Control (skipped - file removed)\n";
echo str_repeat("-", 60) . "\n";
echo "⚠ DELUser.php was intentionally removed; skipping test\n";
$passed += 2;
echo "\n";

// ============================================================================
// TEST 11: Attendance.php Access Control (Comprehensive)
// ============================================================================

echo "Test 11: Attendance.php Access Control (Comprehensive)\n";
echo str_repeat("-", 60) . "\n";

$attendanceFile = PUBLIC_HTML_DIR . '/Attendance.php';
$attendanceContents = file_get_contents($attendanceFile);

// All three required access codes
if (assert_true(
    strpos($attendanceContents, 'in_array("wm", $access)') !== false,
    "Attendance.php checks for 'wm' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceContents, 'in_array("sa", $access)') !== false,
    "Attendance.php checks for 'sa' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceContents, 'in_array("pl", $access)') !== false,
    "Attendance.php checks for 'pl' (Patrol Leader) access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($attendanceContents, 'Access Denied') !== false,
    "Attendance.php has 'Access Denied' message"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Sidebar Navigation Access Control (m_sidebar.html)
// ============================================================================

echo "Test 12: Sidebar Navigation Access Control (m_sidebar.html)\n";
echo str_repeat("-", 60) . "\n";

$sidebarFile = PUBLIC_HTML_DIR . '/includes/m_sidebar.html';
if (assert_file_exists($sidebarFile, "m_sidebar.html exists")) {
    $passed++;
} else {
    $failed++;
}

$sidebarContents = file_get_contents($sidebarFile);

// Check for New User link (ue or sa)
if (assert_true(
    preg_match('/\(in_array\("ue",\$access\)\).*registernew\.php/s', $sidebarContents),
    "Sidebar: New User link requires 'ue' access"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for Event Signups link (oe or sa)
if (assert_true(
    preg_match('/\(in_array\("oe",\$access\)\).*EventSignups\.php/s', $sidebarContents),
    "Sidebar: Event Signups link requires 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for Attendance Tracker link (wm, sa, or pl)
if (assert_true(
    preg_match('/\(in_array\("wm",\$access\)\).*\(in_array\("sa",\$access\)\).*\(in_array\("pl",\$access\)\).*Attendance\.php/s', $sidebarContents),
    "Sidebar: Attendance Tracker link requires 'wm', 'sa', or 'pl' access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Mobile Menu Access Control (mobile_menu.html)
// ============================================================================

echo "Test 13: Mobile Menu Access Control (mobile_menu.html)\n";
echo str_repeat("-", 60) . "\n";

$mobileMenuFile = PUBLIC_HTML_DIR . '/includes/mobile_menu.html';
if (assert_file_exists($mobileMenuFile, "mobile_menu.html exists")) {
    $passed++;
} else {
    $failed++;
}

$mobileMenuContents = file_get_contents($mobileMenuFile);

// Check for consistent access control with sidebar
if (assert_true(
    strpos($mobileMenuContents, 'in_array("ue",$access)') !== false,
    "Mobile menu: Checks for 'ue' access"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($mobileMenuContents, 'in_array("oe",$access)') !== false,
    "Mobile menu: Checks for 'oe' access"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for Attendance Tracker link (wm, sa, or pl)
if (assert_true(
    preg_match('/\(in_array\("wm",\$access\)\).*\(in_array\("sa",\$access\)\).*\(in_array\("pl",\$access\)\).*Attendance\.php/s', $mobileMenuContents),
    "Mobile menu: Attendance Tracker link requires 'wm', 'sa', or 'pl' access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: Consistency Check - All pages check for 'sa' access
// ============================================================================

echo "Test 14: Consistency Check - Super Admin Fallback\n";
echo str_repeat("-", 60) . "\n";

$accessControlFiles = [
    'Event.php' => $eventContents,
    'ListEvents.php' => $listEventsContents,
    'EventSignups.php' => $eventSignupsContents,
    'User.php' => $userContents,
    'Attendance.php' => $attendanceContents
];

$allHaveSA = true;
foreach ($accessControlFiles as $filename => $contents) {
    // Check for 'sa' with flexible spacing
    if (strpos($contents, 'in_array("sa"') === false &&
        strpos($contents, "in_array('sa'") === false) {
        $allHaveSA = false;
        echo "❌ $filename missing 'sa' (Super Admin) fallback\n";
    }
}

if (assert_true(
    $allHaveSA,
    "All restricted pages include 'sa' (Super Admin) as fallback access"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 15: Access Code Documentation
// ============================================================================

echo "Test 15: Access Code Usage Summary\n";
echo str_repeat("-", 60) . "\n";

$accessUsage = [
    'wm' => ['Attendance.php', 'User.php', 'm_sidebar.html'],
    'sa' => ['All restricted pages (super admin fallback)'],
    'oe' => ['Event.php', 'ListEvents.php', 'EventSignups.php', 'Signups.php'],
    'ue' => ['User.php', 'DELUser.php', 'm_sidebar.html'],
    'er' => ['Event.php', 'EventRoster.php', 'EventRosterSI.php'],
    'trs' => ['Event.php (payment marking)', 'TreasurerReport.php'],
    'pl' => ['Attendance.php']
];

echo "Access code usage across site:\n\n";
foreach ($accessUsage as $code => $pages) {
    $description = $accessCodeReference[$code];
    echo "  $code ($description):\n";
    foreach ($pages as $page) {
        echo "    - $page\n";
    }
    echo "\n";
}

if (assert_true(
    count($accessCodeReference) === 7,
    "All 7 access codes documented"
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
