<?php
/**
 * Activity Logger Unit Test
 *
 * Tests the activity_logger.php utility to ensure it properly logs
 * all write operations to the activity_log database table.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load dependencies
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';
require_once PUBLIC_HTML_DIR . '/includes/activity_logger.php';

test_suite("Activity Logger Unit Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// SETUP: Create test database connection
// ============================================================================

echo "Setting up test database connection...\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();
    $mysqli = new mysqli(
        $creds->getDatabaseHost(),
        $creds->getDatabaseUser(),
        $creds->getDatabasePassword(),
        $creds->getDatabaseName()
    );

    if ($mysqli->connect_error) {
        echo "❌ Database connection failed: " . $mysqli->connect_error . "\n";
        exit(1);
    }

    echo "✅ Database connection established\n\n";
} catch (Exception $e) {
    echo "❌ Failed to set up database: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================================
// TEST 1: Verify activity_logger.php file exists
// ============================================================================

echo "Test 1: activity_logger.php file existence\n";
echo str_repeat("-", 60) . "\n";

$logger_path = PUBLIC_HTML_DIR . '/includes/activity_logger.php';
if (assert_file_exists($logger_path, "activity_logger.php file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify log_activity function exists
// ============================================================================

echo "Test 2: log_activity function exists\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(function_exists('log_activity'), "log_activity function is defined")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify activity_log table exists
// ============================================================================

echo "Test 3: activity_log table exists\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SHOW TABLES LIKE 'activity_log'");
if (assert_true($result && $result->num_rows > 0, "activity_log table exists in database")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify activity_log table schema
// ============================================================================

echo "Test 4: activity_log table schema\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("DESCRIBE activity_log");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$expected_columns = ['timestamp', 'source_file', 'action', 'values_json', 'success', 'freetext', 'user'];
$all_columns_present = true;

foreach ($expected_columns as $expected_col) {
    if (!in_array($expected_col, $columns)) {
        $all_columns_present = false;
        echo "❌ Missing column: $expected_col\n";
    }
}

if (assert_true($all_columns_present, "All required columns present in activity_log table")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Log a successful activity
// ============================================================================

echo "Test 5: Log a successful activity\n";
echo str_repeat("-", 60) . "\n";

// Get count before
$result = $mysqli->query("SELECT COUNT(*) as count FROM activity_log");
$count_before = $result->fetch_assoc()['count'];

// Log a test activity
$test_values = array(
    'test_key' => 'test_value',
    'event_id' => 999,
    'user_id' => 123
);

$log_result = log_activity(
    $mysqli,
    'test_action',
    $test_values,
    true,
    'This is a test log entry',
    123
);

// Get count after
$result = $mysqli->query("SELECT COUNT(*) as count FROM activity_log");
$count_after = $result->fetch_assoc()['count'];

if (assert_true($log_result, "log_activity returned true") &&
    assert_equals((int)$count_before + 1, (int)$count_after, "Activity log entry was created")) {
    $passed += 2;
} else {
    $failed += 2;
}

echo "\n";

// ============================================================================
// TEST 6: Verify logged data
// ============================================================================

echo "Test 6: Verify logged data\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SELECT * FROM activity_log ORDER BY timestamp DESC LIMIT 1");
$log_entry = $result->fetch_assoc();

$tests_passed = 0;
$tests_failed = 0;

if (assert_equals('test_action', $log_entry['action'], "Action field is correct")) {
    $tests_passed++;
} else {
    $tests_failed++;
}

if (assert_equals(1, (int)$log_entry['success'], "Success field is correct (1)")) {
    $tests_passed++;
} else {
    $tests_failed++;
}

if (assert_equals('This is a test log entry', $log_entry['freetext'], "Freetext field is correct")) {
    $tests_passed++;
} else {
    $tests_failed++;
}

if (assert_equals(123, (int)$log_entry['user'], "User field is correct")) {
    $tests_passed++;
} else {
    $tests_failed++;
}

$decoded_values = json_decode($log_entry['values_json'], true);
if (assert_equals('test_value', $decoded_values['test_key'], "Values JSON decoded correctly")) {
    $tests_passed++;
} else {
    $tests_failed++;
}

$passed += $tests_passed;
$failed += $tests_failed;

echo "\n";

// ============================================================================
// TEST 7: Log a failed activity
// ============================================================================

echo "Test 7: Log a failed activity\n";
echo str_repeat("-", 60) . "\n";

$log_result = log_activity(
    $mysqli,
    'test_failed_action',
    array('error' => 'Test error message'),
    false,
    'This test intentionally failed',
    456
);

$result = $mysqli->query("SELECT * FROM activity_log WHERE action='test_failed_action' ORDER BY timestamp DESC LIMIT 1");
$log_entry = $result->fetch_assoc();

if (assert_true($log_result, "log_activity returned true for failed action") &&
    assert_equals(0, (int)$log_entry['success'], "Success field is 0 for failed action")) {
    $passed += 2;
} else {
    $failed += 2;
}

echo "\n";

// ============================================================================
// TEST 8: Log with null values
// ============================================================================

echo "Test 8: Log with null/empty values\n";
echo str_repeat("-", 60) . "\n";

$log_result = log_activity(
    $mysqli,
    'test_null_values',
    null,
    true,
    null,
    789
);

if (assert_true($log_result, "log_activity handles null values gracefully")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Test JSON truncation (values > 500 chars)
// ============================================================================

echo "Test 9: Test JSON truncation for long values\n";
echo str_repeat("-", 60) . "\n";

$long_string = str_repeat('A', 600);
$long_values = array('long_field' => $long_string);

$log_result = log_activity(
    $mysqli,
    'test_long_json',
    $long_values,
    true,
    'Test long JSON values',
    999
);

$result = $mysqli->query("SELECT values_json FROM activity_log WHERE action='test_long_json' ORDER BY timestamp DESC LIMIT 1");
$log_entry = $result->fetch_assoc();

$json_length = strlen($log_entry['values_json']);

if (assert_true($json_length <= 500, "Values JSON truncated to 500 chars (actual: $json_length)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Test freetext truncation (> 500 chars)
// ============================================================================

echo "Test 10: Test freetext truncation for long text\n";
echo str_repeat("-", 60) . "\n";

$long_freetext = str_repeat('B', 600);

$log_result = log_activity(
    $mysqli,
    'test_long_freetext',
    array('test' => 'value'),
    true,
    $long_freetext,
    888
);

$result = $mysqli->query("SELECT freetext FROM activity_log WHERE action='test_long_freetext' ORDER BY timestamp DESC LIMIT 1");
$log_entry = $result->fetch_assoc();

$freetext_length = strlen($log_entry['freetext']);

if (assert_true($freetext_length <= 500, "Freetext truncated to 500 chars (actual: $freetext_length)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Test user ID fallback (no session)
// ============================================================================

echo "Test 11: Test user ID fallback when no session\n";
echo str_repeat("-", 60) . "\n";

// Clear session if it exists
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

$log_result = log_activity(
    $mysqli,
    'test_no_session',
    array('test' => 'no_session'),
    true,
    'Testing without session',
    777  // POST user ID
);

$result = $mysqli->query("SELECT user FROM activity_log WHERE action='test_no_session' ORDER BY timestamp DESC LIMIT 1");
$log_entry = $result->fetch_assoc();

if (assert_equals(777, (int)$log_entry['user'], "User ID falls back to POST parameter when no session")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify send_activity_log_failure_email function exists
// ============================================================================

echo "Test 12: send_activity_log_failure_email function exists\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(function_exists('send_activity_log_failure_email'), "send_activity_log_failure_email function is defined")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// CLEANUP: Delete test log entries
// ============================================================================

echo "Cleaning up test data...\n";
echo str_repeat("-", 60) . "\n";

$test_actions = [
    'test_action',
    'test_failed_action',
    'test_null_values',
    'test_long_json',
    'test_long_freetext',
    'test_no_session'
];

foreach ($test_actions as $action) {
    $mysqli->query("DELETE FROM activity_log WHERE action='$action'");
}

echo "✅ Test data cleaned up\n\n";

// Close database connection
$mysqli->close();

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
