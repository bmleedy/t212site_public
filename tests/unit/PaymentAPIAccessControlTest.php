<?php
/**
 * Payment API Access Control Unit Test
 *
 * Tests for the payment update functionality:
 * - api/pay.php
 *
 * These are static analysis tests that verify file structure,
 * security patterns, and code quality without database access.
 */

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Payment API Access Control Tests");

// =============================================================================
// Test 1: API file exists
// =============================================================================
echo "\n--- Test 1: API File Existence ---\n";

$api_path = PUBLIC_HTML_DIR . '/api/pay.php';
if (assert_file_exists($api_path, "API file exists: pay.php")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 2: pay.php requires authentication
// =============================================================================
echo "\n--- Test 2: API Authentication Requirement ---\n";

$api_content = file_get_contents($api_path);

$has_require_auth = (strpos($api_content, 'require_authentication()') !== false);
if (assert_true($has_require_auth, "pay.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 3: pay.php requires CSRF token
// =============================================================================
echo "\n--- Test 3: API CSRF Token Requirement ---\n";

$has_require_csrf = (strpos($api_content, 'require_csrf()') !== false);
if (assert_true($has_require_csrf, "pay.php requires CSRF token")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 4: pay.php requires AJAX request
// =============================================================================
echo "\n--- Test 4: AJAX Requirement ---\n";

$has_require_ajax = (strpos($api_content, 'require_ajax()') !== false);
if (assert_true($has_require_ajax, "pay.php requires AJAX request")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 5: pay.php requires correct permissions (trs/sa only, NOT wm)
// =============================================================================
echo "\n--- Test 5: API Permission Requirements ---\n";

// Check for require_permission call
$has_require_permission = (strpos($api_content, 'require_permission(') !== false);
if (assert_true($has_require_permission, "pay.php uses require_permission()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it requires treasurer permission
$has_trs_permission = (strpos($api_content, "'trs'") !== false);
if (assert_true($has_trs_permission, "pay.php requires treasurer (trs) permission")) {
    $passed++;
} else {
    $failed++;
}

// Check that it requires super admin permission
$has_sa_permission = (strpos($api_content, "'sa'") !== false);
if (assert_true($has_sa_permission, "pay.php requires super admin (sa) permission")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 6: Verify webmaster is NOT allowed (payment updates are sensitive)
// =============================================================================
echo "\n--- Test 6: Webmaster NOT Allowed for Payment Updates ---\n";

// Extract the require_permission call to verify wm is NOT included
// This is a stricter permission - only treasurer and admin should mark payments
if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $api_content, $matches)) {
    $permission_string = $matches[1];

    // Check that 'wm' is NOT in the permission list
    $has_wm = (strpos($permission_string, "'wm'") !== false);
    if (assert_false($has_wm, "pay.php does NOT allow webmaster (wm) access for payment updates")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that 'oe' is NOT in the permission list
    $has_oe = (strpos($permission_string, "'oe'") !== false);
    if (assert_false($has_oe, "pay.php does NOT allow event organizer (oe) access")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that 'pl' is NOT in the permission list
    $has_pl = (strpos($permission_string, "'pl'") !== false);
    if (assert_false($has_pl, "pay.php does NOT allow patrol leader (pl) access")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: Could not parse require_permission call\n";
    $failed += 3;
}

// =============================================================================
// Test 7: SQL Injection Prevention
// =============================================================================
echo "\n--- Test 7: SQL Injection Prevention ---\n";

// Check for prepared statements
$uses_prepared = (strpos($api_content, '->prepare(') !== false);
if (assert_true($uses_prepared, "pay.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// Check for bind_param usage
$uses_bind_param = (strpos($api_content, 'bind_param') !== false);
if (assert_true($uses_bind_param, "pay.php uses bind_param for query parameters")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 8: Input Validation
// =============================================================================
echo "\n--- Test 8: Input Validation ---\n";

// Check for validation_helper.php inclusion
$uses_validation_helper = (strpos($api_content, 'validation_helper.php') !== false);
if (assert_true($uses_validation_helper, "pay.php includes validation_helper.php")) {
    $passed++;
} else {
    $failed++;
}

// Check for validate_int_post usage for user_id
$validates_user_id = (strpos($api_content, "validate_int_post('user_id')") !== false);
if (assert_true($validates_user_id, "pay.php validates user_id with validate_int_post")) {
    $passed++;
} else {
    $failed++;
}

// Check for validate_int_post usage for event_id
$validates_event_id = (strpos($api_content, "validate_int_post('event_id')") !== false);
if (assert_true($validates_event_id, "pay.php validates event_id with validate_int_post")) {
    $passed++;
} else {
    $failed++;
}

// Check for validate_string_post usage for paid status
$validates_paid = (strpos($api_content, "validate_string_post('paid')") !== false);
if (assert_true($validates_paid, "pay.php validates paid status with validate_string_post")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 9: Activity Logging on Payment Updates
// =============================================================================
echo "\n--- Test 9: Activity Logging ---\n";

// Check that activity_logger.php is included
$includes_logger = (strpos($api_content, 'activity_logger.php') !== false);
if (assert_true($includes_logger, "pay.php includes activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

// Check that log_activity is called
$calls_log_activity = (strpos($api_content, 'log_activity(') !== false);
if (assert_true($calls_log_activity, "pay.php calls log_activity()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it logs payment status update
$logs_payment_update = (strpos($api_content, 'update_payment_status') !== false);
if (assert_true($logs_payment_update, "pay.php logs 'update_payment_status' action")) {
    $passed++;
} else {
    $failed++;
}

// Check for success logging - multiline call so we check for true parameter separately
// The log_activity call has true as the 4th parameter for success
// Look for the pattern where true appears after 'array(' in log_activity context
$has_true_log = (strpos($api_content, '),
    true,') !== false || strpos($api_content, "),\n    true,") !== false);
if (assert_true($has_true_log, "pay.php logs successful payment updates")) {
    $passed++;
} else {
    $failed++;
}

// Check for failure logging - the log_activity call has false as the 4th parameter for failure
// Look for the pattern where false appears after 'array(' in log_activity context
$has_false_log = (strpos($api_content, '),
    false,') !== false || strpos($api_content, "),\n    false,") !== false);
if (assert_true($has_false_log, "pay.php logs failed payment updates")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 10: Error output is escaped
// =============================================================================
echo "\n--- Test 10: Error Output Escaping ---\n";

// Check that error messages use escape_html
$escapes_error = (strpos($api_content, 'escape_html($mysqli->error') !== false ||
                  strpos($api_content, 'escape_html($mysqli->errno') !== false);
if (assert_true($escapes_error, "pay.php escapes database error messages")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 11: Session started
// =============================================================================
echo "\n--- Test 11: Session Management ---\n";

$has_session_start = (strpos($api_content, 'session_start()') !== false);
if (assert_true($has_session_start, "pay.php starts session")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 12: Proper statement closing
// =============================================================================
echo "\n--- Test 12: Resource Cleanup ---\n";

$closes_statements = (strpos($api_content, '->close()') !== false);
if (assert_true($closes_statements, "pay.php closes prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 13: JSON response format
// =============================================================================
echo "\n--- Test 13: JSON Response Format ---\n";

$sets_json_header = (strpos($api_content, "Content-Type: application/json") !== false);
if (assert_true($sets_json_header, "pay.php sets JSON content type header")) {
    $passed++;
} else {
    $failed++;
}

$uses_json_encode = (strpos($api_content, 'json_encode(') !== false);
if (assert_true($uses_json_encode, "pay.php uses json_encode for response")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 14: API updates registration table
// =============================================================================
echo "\n--- Test 14: Database Operation Verification ---\n";

$updates_registration = (strpos($api_content, 'UPDATE registration') !== false);
if (assert_true($updates_registration, "pay.php updates registration table")) {
    $passed++;
} else {
    $failed++;
}

// Verify it updates the paid field
$updates_paid_field = (strpos($api_content, 'SET paid=') !== false);
if (assert_true($updates_paid_field, "pay.php updates the paid field")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
