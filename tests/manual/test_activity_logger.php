<?php
/**
 * Manual Activity Logger Test Script
 *
 * This script can be run directly through the web server to test
 * the activity logging functionality.
 *
 * Usage: Visit this file in your browser: http://yoursite.com/tests/manual/test_activity_logger.php
 */

// Set up paths
define('PROJECT_ROOT', dirname(dirname(__DIR__)));
define('PUBLIC_HTML_DIR', PROJECT_ROOT . '/public_html');

// Load dependencies
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';
require_once PUBLIC_HTML_DIR . '/includes/activity_logger.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logger Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { margin: 20px 0; padding: 15px; background: white; border-left: 4px solid #ccc; }
        .pass { border-left-color: #4CAF50; }
        .fail { border-left-color: #f44336; }
        .test h3 { margin-top: 0; }
        .pass .status { color: #4CAF50; }
        .fail .status { color: #f44336; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .summary { background: #333; color: white; padding: 20px; margin-top: 30px; }
        .summary.success { background: #4CAF50; }
        .summary.failure { background: #f44336; }
    </style>
</head>
<body>

<h1>🧪 Activity Logger Test Suite</h1>
<p>Manual testing of activity logging functionality</p>

<?php

$passed = 0;
$failed = 0;
$tests = [];

// Create database connection
try {
    $creds = Credentials::getInstance();
    $mysqli = new mysqli(
        $creds->getDatabaseHost(),
        $creds->getDatabaseUser(),
        $creds->getDatabasePassword(),
        $creds->getDatabaseName()
    );

    if ($mysqli->connect_error) {
        die("❌ Database connection failed: " . $mysqli->connect_error);
    }
} catch (Exception $e) {
    die("❌ Failed to set up database: " . $e->getMessage());
}

// Helper function to run a test
function run_test($name, $test_func) {
    global $passed, $failed, $tests;

    try {
        $result = $test_func();
        if ($result['success']) {
            $passed++;
            $tests[] = ['name' => $name, 'success' => true, 'message' => $result['message']];
        } else {
            $failed++;
            $tests[] = ['name' => $name, 'success' => false, 'message' => $result['message']];
        }
    } catch (Exception $e) {
        $failed++;
        $tests[] = ['name' => $name, 'success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

// TEST 1: Verify activity_log table exists
run_test("Activity log table exists", function() use ($mysqli) {
    $result = $mysqli->query("SHOW TABLES LIKE 'activity_log'");
    return [
        'success' => ($result && $result->num_rows > 0),
        'message' => ($result && $result->num_rows > 0) ? 'Table found' : 'Table not found'
    ];
});

// TEST 2: Log a successful activity
run_test("Log successful activity", function() use ($mysqli) {
    $count_before = $mysqli->query("SELECT COUNT(*) as count FROM activity_log")->fetch_assoc()['count'];

    $result = log_activity(
        $mysqli,
        'manual_test_success',
        ['test' => 'data', 'timestamp' => time()],
        true,
        'Manual test - successful action',
        999
    );

    $count_after = $mysqli->query("SELECT COUNT(*) as count FROM activity_log")->fetch_assoc()['count'];

    return [
        'success' => ($result && $count_after > $count_before),
        'message' => $result ? "Log entry created (before: $count_before, after: $count_after)" : 'Failed to create log entry'
    ];
});

// TEST 3: Verify logged data
run_test("Verify logged data fields", function() use ($mysqli) {
    $log = $mysqli->query("SELECT * FROM activity_log WHERE action='manual_test_success' ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();

    $checks = [
        'action' => ($log['action'] === 'manual_test_success'),
        'success' => ($log['success'] == 1),
        'user' => ($log['user'] == 999),
        'values_json' => (json_decode($log['values_json'], true) !== null)
    ];

    $all_good = array_reduce($checks, function($carry, $item) { return $carry && $item; }, true);

    return [
        'success' => $all_good,
        'message' => $all_good ? 'All fields correct' : 'Field validation failed: ' . json_encode($checks)
    ];
});

// TEST 4: Log a failed activity
run_test("Log failed activity", function() use ($mysqli) {
    $result = log_activity(
        $mysqli,
        'manual_test_failure',
        ['error' => 'Simulated error'],
        false,
        'Manual test - failed action',
        888
    );

    $log = $mysqli->query("SELECT * FROM activity_log WHERE action='manual_test_failure' ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();

    return [
        'success' => ($result && $log['success'] == 0),
        'message' => 'Failed action logged with success=0'
    ];
});

// TEST 5: Test JSON truncation
run_test("JSON truncation for long values", function() use ($mysqli) {
    $long_data = ['long_field' => str_repeat('A', 600)];

    log_activity(
        $mysqli,
        'manual_test_truncation',
        $long_data,
        true,
        'Testing truncation',
        777
    );

    $log = $mysqli->query("SELECT values_json FROM activity_log WHERE action='manual_test_truncation' ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();

    $length = strlen($log['values_json']);

    return [
        'success' => ($length <= 500),
        'message' => "JSON length: $length chars (max 500)"
    ];
});

// TEST 6: Test with null values
run_test("Handle null values gracefully", function() use ($mysqli) {
    $result = log_activity(
        $mysqli,
        'manual_test_null',
        null,
        true,
        null,
        666
    );

    return [
        'success' => $result,
        'message' => 'Null values handled without error'
    ];
});

// TEST 7: Verify all required columns exist
run_test("All required table columns exist", function() use ($mysqli) {
    $result = $mysqli->query("DESCRIBE activity_log");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $required = ['timestamp', 'source_file', 'action', 'values_json', 'success', 'freetext', 'user'];
    $missing = array_diff($required, $columns);

    return [
        'success' => (count($missing) === 0),
        'message' => (count($missing) === 0) ? 'All columns present' : 'Missing: ' . implode(', ', $missing)
    ];
});

// TEST 8: Check cleanup script exists
run_test("Cleanup script exists", function() {
    $script_path = PROJECT_ROOT . '/db_copy/cleanup_activity_log.php';
    return [
        'success' => file_exists($script_path),
        'message' => file_exists($script_path) ? 'Script found' : 'Script not found'
    ];
});

// Display results
foreach ($tests as $test) {
    $class = $test['success'] ? 'pass' : 'fail';
    $status = $test['success'] ? '✅ PASSED' : '❌ FAILED';
    echo "<div class='test $class'>";
    echo "<h3>" . htmlspecialchars($test['name']) . "</h3>";
    echo "<p class='status'><strong>$status</strong></p>";
    echo "<p>" . htmlspecialchars($test['message']) . "</p>";
    echo "</div>";
}

// Cleanup test data
$mysqli->query("DELETE FROM activity_log WHERE action LIKE 'manual_test_%'");

// Close connection
$mysqli->close();

// Summary
$total = $passed + $failed;
$summary_class = ($failed === 0) ? 'success' : 'failure';

echo "<div class='summary $summary_class'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> $total</p>";
echo "<p><strong>Passed:</strong> $passed</p>";
echo "<p><strong>Failed:</strong> $failed</p>";
if ($failed === 0) {
    echo "<p><strong>🎉 All tests passed!</strong></p>";
} else {
    echo "<p><strong>⚠️ Some tests failed. Please review above.</strong></p>";
}
echo "</div>";

?>

</body>
</html>
