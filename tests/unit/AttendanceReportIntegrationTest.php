<?php
/**
 * Attendance Report Integration Test
 *
 * Integration tests for the complete AttendanceReport feature:
 * - End-to-end flow from page load to data display
 * - Integration between APIs and frontend
 * - Date range functionality
 * - Attendance update integration
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Report Integration Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify complete page structure integration
// ============================================================================

echo "Test 1: Complete page structure integration\n";
echo str_repeat("-", 60) . "\n";

$attendanceReportFile = PUBLIC_HTML_DIR . '/AttendanceReport.php';
$templateFile = PUBLIC_HTML_DIR . '/templates/AttendanceReport.html';

$pageContents = file_get_contents($attendanceReportFile);
$templateContents = file_get_contents($templateFile);

// Check that page includes the template
if (assert_true(
    strpos($pageContents, 'AttendanceReport.html') !== false,
    "AttendanceReport.php includes AttendanceReport.html template"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that hidden inputs are provided to JavaScript
if (assert_true(
    strpos($pageContents, 'id="user_id"') !== false,
    "user_id hidden input is provided to template"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($pageContents, 'id="canEditAttendance"') !== false,
    "canEditAttendance hidden input is provided to template"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that template reads the hidden inputs
if (assert_true(
    strpos($templateContents, "getElementById('user_id')") !== false,
    "Template reads user_id from hidden input"
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

echo "\n";

// ============================================================================
// TEST 2: Verify API and frontend data flow integration
// ============================================================================

echo "Test 2: API and frontend data flow integration\n";
echo str_repeat("-", 60) . "\n";

$eventsApiFile = PUBLIC_HTML_DIR . '/api/getattendanceevents.php';
$dataApiFile = PUBLIC_HTML_DIR . '/api/getattendancedata.php';
$scoutsApiFile = PUBLIC_HTML_DIR . '/api/getscoutsforattendance.php';

$eventsApiContents = file_get_contents($eventsApiFile);
$dataApiContents = file_get_contents($dataApiFile);
$scoutsApiContents = file_get_contents($scoutsApiFile);

// Verify that API returns 'dates' and frontend expects 'dates'
if (assert_true(
    strpos($eventsApiContents, "'dates' =>") !== false &&
    strpos($templateContents, "eventsData.dates") !== false,
    "Events API returns 'dates' array and frontend expects it"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify that API returns 'attendance' and frontend expects 'attendance'
if (assert_true(
    strpos($dataApiContents, "'attendance' =>") !== false &&
    strpos($templateContents, "attendanceDataResponse.attendance") !== false,
    "Attendance API returns 'attendance' and frontend expects it"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify that API returns 'scouts' and frontend expects 'scouts'
if (assert_true(
    strpos($scoutsApiContents, "'scouts' =>") !== false &&
    strpos($templateContents, "scoutsData.scouts") !== false,
    "Scouts API returns 'scouts' array and frontend expects it"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify date range integration
// ============================================================================

echo "Test 3: Date range integration verification\n";
echo str_repeat("-", 60) . "\n";

// Check that frontend sends start_date and end_date
if (assert_true(
    strpos($templateContents, 'start_date: startDate') !== false,
    "Frontend sends start_date to APIs"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'end_date: endDate') !== false,
    "Frontend sends end_date to APIs"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that APIs receive and validate start_date and end_date
if (assert_true(
    strpos($eventsApiContents, "validate_date_post('start_date')") !== false &&
    strpos($eventsApiContents, "validate_date_post('end_date')") !== false,
    "Events API receives start_date and end_date"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($dataApiContents, "validate_date_post('start_date')") !== false &&
    strpos($dataApiContents, "validate_date_post('end_date')") !== false,
    "Attendance data API receives start_date and end_date"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that date changes trigger reload
if (assert_true(
    (preg_match('/startDateSelector.*change.*loadScouts/s', $templateContents) ||
     preg_match('/endDateSelector.*change.*loadScouts/s', $templateContents)),
    "Date selector changes trigger data reload"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify attendance data keying integration
// ============================================================================

echo "Test 4: Attendance data keying integration\n";
echo str_repeat("-", 60) . "\n";

// Both API and frontend must use same key format: "user_id-date"
if (assert_true(
    preg_match('/\$row\[.user_id.\].*\$row\[.date.\]/', $dataApiContents),
    "API creates key using user_id-date format"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/scout\.user_id.*eventDate\.date/', $templateContents) &&
    strpos($templateContents, "attendanceData[key]") !== false,
    "Frontend looks up attendance using user_id-date key"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify checkbox editing permission integration
// ============================================================================

echo "Test 5: Checkbox editing permission integration\n";
echo str_repeat("-", 60) . "\n";

// Check that PHP sets canEditAttendance based on wm/sa roles
if (assert_true(
    preg_match('/\$canEditAttendance.*in_array\("wm".*in_array\("sa"/s', $pageContents),
    "PHP sets canEditAttendance for wm and sa only"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that JavaScript reads and uses canEditAttendance
if (assert_true(
    strpos($templateContents, "canEditAttendance = (document.getElementById('canEditAttendance').value === '1')") !== false,
    "JavaScript reads canEditAttendance from hidden input"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that JavaScript conditionally creates editable vs disabled checkboxes
if (assert_true(
    strpos($templateContents, "if (canEditAttendance)") !== false,
    "JavaScript conditionally creates editable checkboxes"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that only editable checkboxes get change handlers
if (assert_true(
    preg_match('/if\s*\(canEditAttendance\).*\.attendance-checkbox.*on.*change/s', $templateContents),
    "Only editable checkboxes get change event handlers"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify event linking integration
// ============================================================================

echo "Test 6: Event linking integration verification\n";
echo str_repeat("-", 60) . "\n";

// Check that API returns event_id
if (assert_true(
    strpos($eventsApiContents, "'event_id' =>") !== false,
    "Events API returns event_id for each event"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that Troop Meetings have null event_id
if (assert_true(
    preg_match('/Troop Meeting.*event_id.*null/s', $eventsApiContents),
    "Troop Meetings have null event_id"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that frontend conditionally creates links based on event_id
if (assert_true(
    preg_match('/if\s*\(eventDate\.event_id\).*Event\.php/s', $templateContents),
    "Frontend creates links only for events with event_id"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify CSV download data integration
// ============================================================================

echo "Test 7: CSV download data integration\n";
echo str_repeat("-", 60) . "\n";

// Check that CSV function uses same data structures as table
if (assert_true(
    preg_match('/downloadCSV.*scouts/s', $templateContents),
    "CSV download receives scouts array"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that CSV uses eventDates global
if (assert_true(
    preg_match('/downloadCSV.*eventDates\.forEach/s', $templateContents),
    "CSV download uses eventDates array"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that CSV uses attendanceData global
if (assert_true(
    preg_match('/downloadCSV.*attendanceData\[key\]/s', $templateContents),
    "CSV download uses attendanceData object"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that CSV uses same key format as table
if (assert_true(
    preg_match('/downloadCSV.*scout\.user_id.*eventDate\.date/s', $templateContents),
    "CSV download uses same user_id-date key format"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify parallel loading integration
// ============================================================================

echo "Test 8: Parallel loading integration verification\n";
echo str_repeat("-", 60) . "\n";

// Check that scouts and attendance are loaded in parallel
if (assert_true(
    strpos($templateContents, 'var scoutsPromise =') !== false &&
    strpos($templateContents, 'var attendancePromise =') !== false,
    "Scouts and attendance are loaded as separate promises"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, '$.when(scoutsPromise, attendancePromise)') !== false,
    "Promises are combined with $.when() for parallel loading"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that both responses are accessed properly
if (assert_true(
    strpos($templateContents, 'scoutsResponse[0]') !== false &&
    strpos($templateContents, 'attendanceResponse[0]') !== false,
    "Response data is extracted from promise results"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify update attendance integration
// ============================================================================

echo "Test 9: Update attendance integration verification\n";
echo str_repeat("-", 60) . "\n";

$updateApiFile = PUBLIC_HTML_DIR . '/api/updateattendance.php';
if (file_exists($updateApiFile)) {
    $updateApiContents = file_get_contents($updateApiFile);

    // Check that frontend sends proper data format
    if (assert_true(
        strpos($templateContents, 'was_present: wasPresent ? 1 : 0') !== false,
        "Frontend sends was_present as 1 or 0"
    )) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that API receives the data
    if (assert_true(
        strpos($updateApiContents, "\$_POST['was_present']") !== false &&
        strpos($updateApiContents, "\$_POST['user_id']") !== false &&
        strpos($updateApiContents, "\$_POST['date']") !== false,
        "Update API receives was_present, user_id, and date"
    )) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that frontend updates local attendanceData on success
    if (assert_true(
        preg_match('/data\.status.*Success.*attendanceData\[key\]\s*=\s*wasPresent/s', $templateContents),
        "Frontend updates local attendanceData on successful update"
    )) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "⚠ Skipping updateattendance.php tests (file not found)\n";
    echo "  This API is shared with Attendance.php\n";
}

echo "\n";

// ============================================================================
// TEST 10: Verify navigation integration
// ============================================================================

echo "Test 10: Navigation integration verification\n";
echo str_repeat("-", 60) . "\n";

$sidebarFile = PUBLIC_HTML_DIR . '/includes/m_sidebar.html';
$mobileMenuFile = PUBLIC_HTML_DIR . '/includes/mobile_menu.html';

$sidebarContents = file_get_contents($sidebarFile);
$mobileMenuContents = file_get_contents($mobileMenuFile);

// Check that both sidebar and mobile menu have the link
if (assert_true(
    strpos($sidebarContents, 'AttendanceReport.php') !== false,
    "Sidebar includes AttendanceReport.php link"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($mobileMenuContents, 'AttendanceReport.php') !== false,
    "Mobile menu includes AttendanceReport.php link"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that both have same access control
if (assert_true(
    preg_match('/in_array\("wm".*in_array\("oe".*in_array\("sa".*AttendanceReport/s', $sidebarContents),
    "Sidebar link has wm/oe/sa access control"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/in_array\("wm".*in_array\("oe".*in_array\("sa".*AttendanceReport/s', $mobileMenuContents),
    "Mobile menu link has wm/oe/sa access control"
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
