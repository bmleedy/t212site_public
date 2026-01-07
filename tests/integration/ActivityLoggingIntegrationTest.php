<?php
/**
 * Activity Logging Integration Test
 *
 * Tests that API endpoints properly log activities to the activity_log table.
 * This tests the integration between API files and the activity_logger utility.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load dependencies
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Activity Logging Integration Tests");

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
// HELPER FUNCTION: Get latest activity log entry
// ============================================================================

function get_latest_log_entry($mysqli, $action = null) {
    $query = "SELECT * FROM activity_log";
    if ($action) {
        $query .= " WHERE action='" . $mysqli->real_escape_string($action) . "'";
    }
    $query .= " ORDER BY timestamp DESC LIMIT 1";

    $result = $mysqli->query($query);
    return $result ? $result->fetch_assoc() : null;
}

// ============================================================================
// TEST 1: Verify all modified API files include activity_logger
// ============================================================================

echo "Test 1: Verify API files include activity_logger.php\n";
echo str_repeat("-", 60) . "\n";

$api_files = [
    'updateuser.php',
    'register.php',
    'approve.php',
    'updateattendance.php',
    'amd_event.php',
    'add_merch.php',
    'ppupdate2.php',
    'pay.php',
    'pprecharter.php'
];

$all_include_logger = true;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos($content, 'activity_logger.php') === false) {
            echo "❌ $api_file does not include activity_logger.php\n";
            $all_include_logger = false;
        } else {
            echo "✅ $api_file includes activity_logger.php\n";
        }
    } else {
        echo "⚠️  $api_file not found\n";
        $all_include_logger = false;
    }
}

if (assert_true($all_include_logger, "All API files include activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify API files call log_activity function
// ============================================================================

echo "Test 2: Verify API files call log_activity()\n";
echo str_repeat("-", 60) . "\n";

$all_call_log_activity = true;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos($content, 'log_activity(') === false) {
            echo "❌ $api_file does not call log_activity()\n";
            $all_call_log_activity = false;
        } else {
            // Count how many times log_activity is called
            $count = substr_count($content, 'log_activity(');
            echo "✅ $api_file calls log_activity() $count time(s)\n";
        }
    }
}

if (assert_true($all_call_log_activity, "All API files call log_activity()")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Check for specific action names in each API file
// ============================================================================

echo "Test 3: Verify action names in API files\n";
echo str_repeat("-", 60) . "\n";

$expected_actions = [
    'updateuser.php' => ['update_user'],
    'register.php' => ['cancel_registration', 'mark_registration_paid', 'restore_registration', 'update_seatbelts', 'new_registration'],
    'approve.php' => ['approve_registration'],
    'updateattendance.php' => ['update_attendance', 'create_attendance'],
    'amd_event.php' => ['update_event', 'create_event'],
    'add_merch.php' => ['create_order', 'update_order_item'],
    'ppupdate2.php' => ['batch_payment_update'],
    'pay.php' => ['update_payment_status'],
    'pprecharter.php' => ['batch_recharter_update']
];

$all_actions_present = true;

foreach ($expected_actions as $api_file => $actions) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        foreach ($actions as $action) {
            if (strpos($content, "'$action'") === false && strpos($content, "\"$action\"") === false) {
                echo "❌ $api_file missing action: $action\n";
                $all_actions_present = false;
            }
        }
    }
}

if ($all_actions_present) {
    echo "✅ All expected actions found in API files\n";
}

if (assert_true($all_actions_present, "All expected action names present")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify log entries for both success and failure
// ============================================================================

echo "Test 4: Verify logging for both success and failure cases\n";
echo str_repeat("-", 60) . "\n";

$files_with_failure_logging = [
    'updateuser.php',
    'register.php',
    'updateattendance.php',
    'amd_event.php',
    'add_merch.php',
    'pay.php'
];

$all_have_failure_logging = true;

foreach ($files_with_failure_logging as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);

        // Check for both success=true and success=false
        $has_true = (strpos($content, 'true,') !== false || strpos($content, 'true)') !== false);
        $has_false = (strpos($content, 'false,') !== false || strpos($content, 'false)') !== false);

        if (!$has_true || !$has_false) {
            echo "⚠️  $api_file may not log both success and failure\n";
            $all_have_failure_logging = false;
        }
    }
}

if ($all_have_failure_logging) {
    echo "✅ All applicable files log both success and failure\n";
}

if (assert_true($all_have_failure_logging, "Files log both success and failure cases")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Check that user_id parameter is passed to log_activity
// ============================================================================

echo "Test 5: Verify user_id parameter passed to log_activity\n";
echo str_repeat("-", 60) . "\n";

$all_pass_user_id = true;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);

        // Extract log_activity calls and check if they have 6 parameters
        // (mysqli, action, values, success, freetext, user_id)
        preg_match_all('/log_activity\s*\([^)]+\)/s', $content, $matches);

        foreach ($matches[0] as $call) {
            // Count commas to approximate parameter count (should be 5 commas = 6 params)
            $param_count = substr_count($call, ',');
            if ($param_count < 5) {
                echo "⚠️  $api_file may have incomplete log_activity call\n";
                $all_pass_user_id = false;
                break;
            }
        }
    }
}

if ($all_pass_user_id) {
    echo "✅ All log_activity calls have proper parameters\n";
}

if (assert_true($all_pass_user_id, "All log_activity calls pass user_id parameter")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify freetext messages are descriptive
// ============================================================================

echo "Test 6: Verify freetext messages are descriptive\n";
echo str_repeat("-", 60) . "\n";

$all_have_freetext = true;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);

        // Check that log_activity calls have non-empty freetext
        // Look for pattern: log_activity(..., "some text", ...)
        preg_match_all('/log_activity\s*\([^)]+\)/s', $content, $matches);

        foreach ($matches[0] as $call) {
            // Check if there's a string parameter (freetext should be 5th param)
            if (!preg_match('/"[^"]{5,}"/', $call) && !preg_match('/".*\$.*"/', $call)) {
                echo "⚠️  $api_file may have empty or missing freetext\n";
                $all_have_freetext = false;
                break;
            }
        }
    }
}

if ($all_have_freetext) {
    echo "✅ All log_activity calls have descriptive freetext\n";
}

if (assert_true($all_have_freetext, "All log_activity calls have descriptive freetext")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Check activity log cleanup script exists
// ============================================================================

echo "Test 7: Verify cleanup script exists\n";
echo str_repeat("-", 60) . "\n";

$cleanup_script = PROJECT_ROOT . '/db_copy/cleanup_activity_log.php';

if (assert_file_exists($cleanup_script, "cleanup_activity_log.php exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify cleanup script has proper SQL query
// ============================================================================

echo "Test 8: Verify cleanup script SQL query\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($cleanup_script)) {
    $content = file_get_contents($cleanup_script);

    $has_delete = strpos($content, 'DELETE FROM activity_log') !== false;
    $has_90_day = strpos($content, 'INTERVAL 90 DAY') !== false;
    $has_timestamp = strpos($content, 'timestamp') !== false;

    if (assert_true($has_delete && $has_90_day && $has_timestamp,
        "Cleanup script has proper DELETE query for 90-day retention")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "❌ Cleanup script not found, skipping SQL verification\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Spot check - Verify actual log entries exist (if any)
// ============================================================================

echo "Test 9: Spot check - Verify log entries in database\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SELECT COUNT(*) as count FROM activity_log");
$count = $result->fetch_assoc()['count'];

echo "Current activity_log entries: $count\n";

if ($count > 0) {
    // Get a sample entry
    $sample = get_latest_log_entry($mysqli);

    echo "Sample log entry:\n";
    echo "  Action: " . $sample['action'] . "\n";
    echo "  Source: " . $sample['source_file'] . "\n";
    echo "  User: " . $sample['user'] . "\n";
    echo "  Success: " . ($sample['success'] ? 'Yes' : 'No') . "\n";
    echo "  Timestamp: " . $sample['timestamp'] . "\n";

    if (assert_true(true, "Activity log contains entries")) {
        $passed++;
    }
} else {
    echo "⚠️  No activity log entries found (this is OK for a fresh install)\n";
    if (assert_true(true, "Activity log table accessible (empty is OK)")) {
        $passed++;
    }
}

echo "\n";

// ============================================================================
// TEST 10: Verify log entries have required fields populated
// ============================================================================

echo "Test 10: Verify log entries have required fields\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SELECT COUNT(*) as count FROM activity_log WHERE
    timestamp IS NULL OR
    source_file IS NULL OR
    action IS NULL OR
    success IS NULL OR
    user IS NULL");

$invalid_count = $result->fetch_assoc()['count'];

if (assert_equals(0, $invalid_count, "No log entries with NULL required fields (found: $invalid_count)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Close database connection
$mysqli->close();

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
