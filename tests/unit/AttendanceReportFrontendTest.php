<?php
/**
 * Attendance Report Frontend Test
 *
 * Tests the frontend JavaScript functionality in AttendanceReport.html:
 * - Date range selectors
 * - Table building logic
 * - Checkbox handling
 * - CSV download function
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Attendance Report Frontend Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify template JavaScript structure
// ============================================================================

echo "Test 1: Template JavaScript structure verification\n";
echo str_repeat("-", 60) . "\n";

$templateFile = PUBLIC_HTML_DIR . '/templates/AttendanceReport.html';
$templateContents = file_get_contents($templateFile);

if (assert_true(
    strpos($templateContents, 'var user_id;') !== false,
    "user_id variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var canEditAttendance') !== false,
    "canEditAttendance variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var startDate') !== false,
    "startDate variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var endDate') !== false,
    "endDate variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var eventDates') !== false,
    "eventDates variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var attendanceData') !== false,
    "attendanceData variable is declared"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify date range selector functionality
// ============================================================================

echo "Test 2: Date range selector functionality\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'function setDefaultDateRange()') !== false,
    "setDefaultDateRange function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'id="startDateSelector"') !== false,
    "Start date selector input exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'id="endDateSelector"') !== false,
    "End date selector input exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'type="date"') !== false,
    "HTML5 date inputs are used"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'setMonth(oneMonthAgo.getMonth() - 1)') !== false ||
    strpos($templateContents, 'getMonth() - 1)') !== false,
    "Default start date is set to 1 month ago"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'America/Los_Angeles') !== false,
    "Pacific Time Zone is used for date calculations"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, "#startDateSelector').on('change'") !== false ||
    strpos($templateContents, "\$('#startDateSelector').on('change'") !== false,
    "Start date change handler exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, "#endDateSelector').on('change'") !== false ||
    strpos($templateContents, "\$('#endDateSelector').on('change'") !== false,
    "End date change handler exists"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify AJAX loading functions
// ============================================================================

echo "Test 3: AJAX loading functions verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'function loadScouts()') !== false,
    "loadScouts function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'function loadScoutsWithEvents()') !== false,
    "loadScoutsWithEvents function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'api/getattendanceevents.php') !== false,
    "Calls getattendanceevents.php API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'api/getscoutsforattendance.php') !== false,
    "Calls getscoutsforattendance.php API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'api/getattendancedata.php') !== false,
    "Calls getattendancedata.php API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, '$.when(') !== false,
    "Uses $.when() for parallel AJAX loading"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify table building functionality
// ============================================================================

echo "Test 4: Table building functionality verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'function buildAttendanceTable(scouts)') !== false,
    "buildAttendanceTable function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'rowspan="2"') !== false ||
    strpos($templateContents, "rowspan='2'") !== false,
    "Two-row header uses rowspan"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Patrol</th>') !== false,
    "Patrol column header exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Name</th>') !== false,
    "Name column header exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/dateParts\[1\].*dateParts\[2\]/', $templateContents),
    "Dates are formatted as MM/DD"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Event.php?id=') !== false,
    "Event links are created for events with event_id"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/scout\.patrol/', $templateContents) &&
    preg_match('/scout\.first/', $templateContents) &&
    preg_match('/scout\.last/', $templateContents),
    "Scout patrol and name are displayed in table"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify checkbox handling
// ============================================================================

echo "Test 5: Checkbox handling verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'class="attendance-checkbox"') !== false,
    "Attendance checkboxes have proper class"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'data-user-id') !== false,
    "Checkboxes have data-user-id attribute"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'data-date') !== false,
    "Checkboxes have data-date attribute"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'if (canEditAttendance)') !== false,
    "Checkbox editability is controlled by canEditAttendance"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'disabled') !== false,
    "Disabled checkboxes for non-editors (patrol leaders)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, ".attendance-checkbox').on('change'") !== false ||
    strpos($templateContents, "\$('.attendance-checkbox').on('change'") !== false,
    "Checkbox change handler exists"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify attendance update functionality
// ============================================================================

echo "Test 6: Attendance update functionality verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'function updateAttendance(') !== false,
    "updateAttendance function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'api/updateattendance') !== false,
    "Calls updateattendance.php API"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'was_present: wasPresent ? 1 : 0') !== false,
    "Boolean to integer conversion for was_present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'checkbox.prop(\'disabled\', true)') !== false ||
    strpos($templateContents, 'checkbox.prop("disabled", true)') !== false,
    "Checkbox is disabled during update"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'checkbox.prop(\'disabled\', false)') !== false ||
    strpos($templateContents, 'checkbox.prop("disabled", false)') !== false,
    "Checkbox is re-enabled after update"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/checkbox\.prop\(.checked.,\s*!wasPresent\)/', $templateContents),
    "Checkbox state is reverted on error"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify CSV download functionality
// ============================================================================

echo "Test 7: CSV download functionality verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'id="downloadCSV"') !== false,
    "Download CSV button exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'function downloadCSV(scouts)') !== false,
    "downloadCSV function exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'var csvContent') !== false,
    "CSV content variable is created"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Blob') !== false,
    "Uses Blob API for file creation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'URL.createObjectURL') !== false,
    "Uses URL.createObjectURL for download"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'attendance_report_') !== false,
    "CSV filename includes 'attendance_report_'"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'wasPresent ? \'true\' : \'\'') !== false,
    "CSV uses 'true' for present attendance"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'Patrol,Name') !== false,
    "CSV header includes Patrol and Name columns"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\.replace\(\/\"\/g,\s*\'\"\"\'/', $templateContents),
    "CSV properly escapes quotes in data"
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
    strpos($templateContents, 'error: function(errorThrown)') !== false ||
    strpos($templateContents, 'error:function(errorThrown)') !== false,
    "AJAX error handlers are present"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'console.error') !== false,
    "Console error logging is used"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'alert-box alert') !== false,
    "Error messages use alert-box styling"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($templateContents, 'data.status === "Success"') !== false ||
    strpos($templateContents, 'data.status === \'Success\'') !== false,
    "Checks for Success status in responses"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify console logging for debugging
// ============================================================================

echo "Test 9: Console logging verification\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($templateContents, 'console.log') !== false,
    "Console logging is present for debugging"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/console\.log.*Date range set/', $templateContents),
    "Logs date range selection"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/console\.log.*event dates/', $templateContents),
    "Logs event count"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/console\.log.*Loading scouts and events/', $templateContents),
    "Logs scouts loading start"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/console\.log.*Displayed.*scouts/', $templateContents),
    "Logs display completion"
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
