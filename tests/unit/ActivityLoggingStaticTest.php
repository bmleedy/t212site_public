<?php
/**
 * Activity Logging Static Analysis Test
 *
 * Tests API files for proper activity logging implementation
 * without requiring database connection.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Activity Logging Static Analysis Tests");

$passed = 0;
$failed = 0;

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
// TEST 2: Verify all modified API files exist and include activity_logger
// ============================================================================

echo "Test 2: API files include activity_logger.php\n";
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
$file_count = 0;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $file_count++;
        $content = file_get_contents($file_path);
        if (strpos($content, 'activity_logger.php') === false) {
            echo "❌ FAILED: $api_file does not include activity_logger.php\n";
            $all_include_logger = false;
        } else {
            echo "✅ PASSED: $api_file includes activity_logger.php\n";
        }
    } else {
        echo "⚠️  WARNING: $api_file not found\n";
        $all_include_logger = false;
    }
}

if (assert_equals(count($api_files), $file_count, "All expected API files exist")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true($all_include_logger, "All API files include activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify API files call log_activity function
// ============================================================================

echo "Test 3: API files call log_activity()\n";
echo str_repeat("-", 60) . "\n";

$all_call_log_activity = true;
$total_calls = 0;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $count = substr_count($content, 'log_activity(');

        if ($count === 0) {
            echo "❌ FAILED: $api_file does not call log_activity()\n";
            $all_call_log_activity = false;
        } else {
            echo "✅ PASSED: $api_file calls log_activity() $count time(s)\n";
            $total_calls += $count;
        }
    }
}

echo "\nTotal log_activity() calls across all files: $total_calls\n";

if (assert_true($all_call_log_activity, "All API files call log_activity()")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Check for specific action names in each API file
// ============================================================================

echo "Test 4: Verify expected action names in API files\n";
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
$action_count = 0;

foreach ($expected_actions as $api_file => $actions) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "\n$api_file:\n";

        foreach ($actions as $action) {
            $action_count++;
            if (strpos($content, "'$action'") !== false || strpos($content, "\"$action\"") !== false) {
                echo "  ✅ $action\n";
            } else {
                echo "  ❌ MISSING: $action\n";
                $all_actions_present = false;
            }
        }
    }
}

echo "\nTotal expected actions checked: $action_count\n";

if (assert_true($all_actions_present, "All expected action names present in API files")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify logging for both success and failure cases
// ============================================================================

echo "Test 5: Verify logging for both success and failure\n";
echo str_repeat("-", 60) . "\n";

$files_checked = 0;
$files_with_both = 0;

foreach ($api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $files_checked++;

        // Count success=true and success=false patterns
        $true_count = preg_match_all('/log_activity\s*\([^)]*true[^)]*\)/s', $content);
        $false_count = preg_match_all('/log_activity\s*\([^)]*false[^)]*\)/s', $content);

        if ($true_count > 0) {
            if ($false_count > 0) {
                echo "✅ $api_file: logs both success ($true_count) and failure ($false_count)\n";
                $files_with_both++;
            } else {
                echo "⚠️  $api_file: only logs success cases\n";
            }
        }
    }
}

echo "\nFiles logging both success and failure: $files_with_both / $files_checked\n";

if (assert_true($files_with_both >= 6, "At least 6 files log both success and failure")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify cleanup script exists and is properly configured
// ============================================================================

echo "Test 6: Cleanup script verification\n";
echo str_repeat("-", 60) . "\n";

$cleanup_script = PROJECT_ROOT . '/db_copy/cleanup_activity_log.php';

if (assert_file_exists($cleanup_script, "cleanup_activity_log.php exists")) {
    $passed++;

    $content = file_get_contents($cleanup_script);

    $has_delete = strpos($content, 'DELETE FROM activity_log') !== false;
    $has_90_day = strpos($content, 'INTERVAL 90 DAY') !== false;
    $has_timestamp = strpos($content, 'timestamp') !== false;
    $has_credentials = strpos($content, 'Credentials::getInstance()') !== false;

    echo "\nCleanup script components:\n";
    echo "  " . ($has_delete ? "✅" : "❌") . " DELETE FROM activity_log\n";
    echo "  " . ($has_90_day ? "✅" : "❌") . " INTERVAL 90 DAY\n";
    echo "  " . ($has_timestamp ? "✅" : "❌") . " timestamp column\n";
    echo "  " . ($has_credentials ? "✅" : "❌") . " Uses Credentials class\n";

    if (assert_true($has_delete && $has_90_day && $has_timestamp,
        "Cleanup script has proper configuration")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    $failed += 2;
}

echo "\n";

// ============================================================================
// TEST 7: Check that log_activity function has proper parameters
// ============================================================================

echo "Test 7: Verify log_activity function signature\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($logger_path)) {
    $content = file_get_contents($logger_path);

    // Check for function definition
    $has_function = preg_match('/function\s+log_activity\s*\([^)]*\$mysqli[^)]*\$action[^)]*\$values[^)]*\$success[^)]*\$freetext[^)]*\$post_user_id[^)]*\)/s', $content);

    if (assert_true($has_function, "log_activity has correct function signature")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check for email failure function
    $has_email_function = strpos($content, 'function send_activity_log_failure_email') !== false;

    if (assert_true($has_email_function, "send_activity_log_failure_email function exists")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    $failed += 2;
}

echo "\n";

// ============================================================================
// TEST 8: Verify email alert functionality
// ============================================================================

echo "Test 8: Verify email alert configuration\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($logger_path)) {
    $content = file_get_contents($logger_path);

    $has_email_to = strpos($content, 't212webmaster@gmail.com') !== false;
    $has_phpmailer = strpos($content, 'PHPMailer') !== false;
    $has_request_details = strpos($content, '$_POST') !== false && strpos($content, '$_SERVER') !== false;

    echo "\nEmail alert components:\n";
    echo "  " . ($has_email_to ? "✅" : "❌") . " Email recipient configured\n";
    echo "  " . ($has_phpmailer ? "✅" : "❌") . " Uses PHPMailer\n";
    echo "  " . ($has_request_details ? "✅" : "❌") . " Includes request details\n";

    if (assert_true($has_email_to && $has_phpmailer && $has_request_details,
        "Email alert properly configured")) {
        $passed++;
    } else {
        $failed++;
    }
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
