<?php
/**
 * Committee Activity Logging Static Analysis Test
 *
 * Tests committee API files for proper activity logging implementation
 * without requiring database connection.
 *
 * Tests the following API files:
 * - createcommittee.php
 * - updatecommittee.php
 * - deletecommittee.php
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Committee Activity Logging Static Analysis Tests");

$passed = 0;
$failed = 0;

// Define the committee API files to test
$committee_api_files = [
    'createcommittee.php',
    'updatecommittee.php',
    'deletecommittee.php'
];

// ============================================================================
// TEST 1: Verify createcommittee.php calls log_activity
// ============================================================================

echo "Test 1: test_createcommittee_calls_log_activity\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    $count = substr_count($content, 'log_activity(');

    if (assert_true($count > 0, "createcommittee.php calls log_activity()")) {
        $passed++;
        echo "   Found $count log_activity() call(s)\n";
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify updatecommittee.php calls log_activity
// ============================================================================

echo "Test 2: test_updatecommittee_calls_log_activity\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/updatecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    $count = substr_count($content, 'log_activity(');

    if (assert_true($count > 0, "updatecommittee.php calls log_activity()")) {
        $passed++;
        echo "   Found $count log_activity() call(s)\n";
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: updatecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify deletecommittee.php calls log_activity
// ============================================================================

echo "Test 3: test_deletecommittee_calls_log_activity\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    $count = substr_count($content, 'log_activity(');

    if (assert_true($count > 0, "deletecommittee.php calls log_activity()")) {
        $passed++;
        echo "   Found $count log_activity() call(s)\n";
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify create uses correct action name 'create_committee'
// ============================================================================

echo "Test 4: test_create_uses_correct_action_name\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    $has_action = strpos($content, "'create_committee'") !== false ||
                  strpos($content, '"create_committee"') !== false;

    if (assert_true($has_action, "createcommittee.php uses action name 'create_committee'")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify update uses correct action name 'update_committee'
// ============================================================================

echo "Test 5: test_update_uses_correct_action_name\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/updatecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    $has_action = strpos($content, "'update_committee'") !== false ||
                  strpos($content, '"update_committee"') !== false;

    if (assert_true($has_action, "updatecommittee.php uses action name 'update_committee'")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: updatecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify delete uses correct action name 'delete_committee'
// ============================================================================

echo "Test 6: test_delete_uses_correct_action_name\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    $has_action = strpos($content, "'delete_committee'") !== false ||
                  strpos($content, '"delete_committee"') !== false;

    if (assert_true($has_action, "deletecommittee.php uses action name 'delete_committee'")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify create logs success on success (success=true)
// ============================================================================

echo "Test 7: test_create_logs_success_on_success\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'create_committee' action and true as success parameter
    // The call spans multiple lines, so use a pattern that matches the structure
    // Pattern: log_activity( $mysqli, 'create_committee', array(...), true,
    $has_success_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]create_committee[\'"][\s\S]*?\),\s*true\s*,/s', $content);

    if (assert_true($has_success_log, "createcommittee.php logs with success=true on success")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify create logs failure on error (success=false)
// ============================================================================

echo "Test 8: test_create_logs_failure_on_error\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'create_committee' action and false as success parameter
    $has_failure_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]create_committee[\'"][\s\S]*?\),\s*false\s*,/s', $content);

    if (assert_true($has_failure_log, "createcommittee.php logs with success=false on error")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify update logs success on success (success=true)
// ============================================================================

echo "Test 9: test_update_logs_success_on_success\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/updatecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'update_committee' action and true as success parameter
    $has_success_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]update_committee[\'"][\s\S]*?\),\s*true\s*,/s', $content);

    if (assert_true($has_success_log, "updatecommittee.php logs with success=true on success")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: updatecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify update logs failure on error (success=false)
// ============================================================================

echo "Test 10: test_update_logs_failure_on_error\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/updatecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'update_committee' action and false as success parameter
    $has_failure_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]update_committee[\'"][\s\S]*?\),\s*false\s*,/s', $content);

    if (assert_true($has_failure_log, "updatecommittee.php logs with success=false on error")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: updatecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify delete logs success on success (success=true)
// ============================================================================

echo "Test 11: test_delete_logs_success_on_success\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'delete_committee' action and true as success parameter
    $has_success_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]delete_committee[\'"][\s\S]*?\),\s*true\s*,/s', $content);

    if (assert_true($has_success_log, "deletecommittee.php logs with success=true on success")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify delete logs failure on error (success=false)
// ============================================================================

echo "Test 12: test_delete_logs_failure_on_error\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Look for log_activity call with 'delete_committee' action and false as success parameter
    $has_failure_log = preg_match('/log_activity\s*\(\s*\$mysqli\s*,\s*[\'"]delete_committee[\'"][\s\S]*?\),\s*false\s*,/s', $content);

    if (assert_true($has_failure_log, "deletecommittee.php logs with success=false on error")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Verify create log includes role_name in logged data
// ============================================================================

echo "Test 13: test_create_log_includes_role_name\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Check that role_name is included in the array passed to log_activity
    $has_role_name = preg_match('/log_activity\s*\([^)]*[\'"]role_name[\'"]\s*=>/s', $content);

    if (assert_true($has_role_name, "createcommittee.php includes 'role_name' in logged data")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: Verify create log includes user_id in logged data
// ============================================================================

echo "Test 14: test_create_log_includes_user_id\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Check that user_id is included in the array passed to log_activity
    $has_user_id = preg_match('/log_activity\s*\([^)]*[\'"]user_id[\'"]\s*=>/s', $content);

    if (assert_true($has_user_id, "createcommittee.php includes 'user_id' in logged data")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 15: Verify delete log includes role_name in logged data
// ============================================================================

echo "Test 15: test_delete_log_includes_role_name\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Check that role_name is included in the array passed to log_activity
    $has_role_name = preg_match('/log_activity\s*\([^)]*[\'"]role_name[\'"]\s*=>/s', $content);

    if (assert_true($has_role_name, "deletecommittee.php includes 'role_name' in logged data")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 16: Verify all committee API files include activity_logger.php
// ============================================================================

echo "Test 16: All committee API files include activity_logger.php\n";
echo str_repeat("-", 60) . "\n";

$all_include_logger = true;
$file_count = 0;

foreach ($committee_api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $file_count++;
        $content = file_get_contents($file_path);
        if (strpos($content, 'activity_logger.php') === false) {
            echo "   FAILED: $api_file does not include activity_logger.php\n";
            $all_include_logger = false;
        } else {
            echo "   PASSED: $api_file includes activity_logger.php\n";
        }
    } else {
        echo "   WARNING: $api_file not found\n";
        $all_include_logger = false;
    }
}

if (assert_true($all_include_logger, "All committee API files include activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 17: Verify update log includes role_id in logged data
// ============================================================================

echo "Test 17: test_update_log_includes_role_id\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/updatecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Check that role_id is included in the array passed to log_activity
    $has_role_id = preg_match('/log_activity\s*\([^)]*[\'"]role_id[\'"]\s*=>/s', $content);

    if (assert_true($has_role_id, "updatecommittee.php includes 'role_id' in logged data")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: updatecommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 18: Count total log_activity calls across all committee API files
// ============================================================================

echo "Test 18: Verify adequate logging coverage\n";
echo str_repeat("-", 60) . "\n";

$total_calls = 0;
$file_call_counts = [];

foreach ($committee_api_files as $api_file) {
    $file_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $count = substr_count($content, 'log_activity(');
        $file_call_counts[$api_file] = $count;
        $total_calls += $count;
        echo "   $api_file: $count log_activity() call(s)\n";
    }
}

echo "\nTotal log_activity() calls across committee files: $total_calls\n";

// Each file should have at least 2 calls (success and failure cases)
if (assert_true($total_calls >= 6, "At least 6 log_activity() calls across committee files (2+ per file)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 19: Verify create logs multiple error scenarios
// ============================================================================

echo "Test 19: test_create_logs_multiple_error_scenarios\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/createcommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Count failure log calls (with false parameter after closing paren of array)
    // Pattern matches: ), false, which is the array close followed by false success param
    preg_match_all('/\),\s*false\s*,/', $content, $matches);
    $failure_count = count($matches[0]);

    // createcommittee.php should log at least 2 failure scenarios:
    // - empty role name
    // - user does not exist
    // - database error
    if (assert_true($failure_count >= 2, "createcommittee.php logs at least 2 failure scenarios (found: $failure_count)")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: createcommittee.php not found\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 20: Verify delete logs multiple error scenarios
// ============================================================================

echo "Test 20: test_delete_logs_multiple_error_scenarios\n";
echo str_repeat("-", 60) . "\n";

$file_path = PUBLIC_HTML_DIR . '/api/deletecommittee.php';
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Count failure log calls (with false parameter after closing paren of array)
    preg_match_all('/\),\s*false\s*,/', $content, $matches);
    $failure_count = count($matches[0]);

    // deletecommittee.php should log at least 2 failure scenarios:
    // - role not found
    // - database error
    if (assert_true($failure_count >= 2, "deletecommittee.php logs at least 2 failure scenarios (found: $failure_count)")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: deletecommittee.php not found\n";
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
