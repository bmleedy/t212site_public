<?php
/**
 * Past Event Pay Access Control Unit Test
 *
 * Tests for the past event payment functionality:
 * - api/getpasteventpay.php
 *
 * These are static analysis tests that verify file structure,
 * security patterns, and code quality without database access.
 */

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Past Event Pay Access Control Tests");

// =============================================================================
// Test 1: API file exists
// =============================================================================
echo "\n--- Test 1: API File Existence ---\n";

$api_path = PUBLIC_HTML_DIR . '/api/getpasteventpay.php';
if (assert_file_exists($api_path, "API file exists: getpasteventpay.php")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 2: getpasteventpay.php requires authentication
// =============================================================================
echo "\n--- Test 2: API Authentication Requirement ---\n";

$api_content = file_get_contents($api_path);

$has_require_auth = (strpos($api_content, 'require_authentication()') !== false);
if (assert_true($has_require_auth, "getpasteventpay.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 3: getpasteventpay.php requires CSRF token
// =============================================================================
echo "\n--- Test 3: API CSRF Token Requirement ---\n";

$has_require_csrf = (strpos($api_content, 'require_csrf()') !== false);
if (assert_true($has_require_csrf, "getpasteventpay.php requires CSRF token")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 4: API requires AJAX request
// =============================================================================
echo "\n--- Test 4: AJAX Requirement ---\n";

$has_require_ajax = (strpos($api_content, 'require_ajax()') !== false);
if (assert_true($has_require_ajax, "getpasteventpay.php requires AJAX request")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 5: Authorization check for user access
// =============================================================================
echo "\n--- Test 5: User Access Authorization ---\n";

// Check that the API validates user access for the requested ID
$has_user_access_check = (strpos($api_content, 'require_user_access(') !== false);
if (assert_true($has_user_access_check, "getpasteventpay.php uses require_user_access()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it compares requested ID with current user
$checks_current_user = (strpos($api_content, '$id != $current_user_id') !== false ||
                        strpos($api_content, '$current_user_id != $id') !== false);
if (assert_true($checks_current_user, "getpasteventpay.php compares requested ID to current user")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 6: Family member access logic
// =============================================================================
echo "\n--- Test 6: Family Member Access Logic ---\n";

// Check that the API retrieves family_id for the requested user
$checks_family_id = (strpos($api_content, 'family_id') !== false);
if (assert_true($checks_family_id, "getpasteventpay.php handles family_id")) {
    $passed++;
} else {
    $failed++;
}

// Check for Scout family member lookup
$checks_scouts = (strpos($api_content, "user_type='Scout'") !== false);
if (assert_true($checks_scouts, "getpasteventpay.php looks up Scout family members")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 7: NULL handling for user not found
// =============================================================================
echo "\n--- Test 7: NULL Handling (User Not Found) ---\n";

// Check for proper NULL handling when user is not found
// The fix should check if $row exists before accessing properties
$has_null_check = (strpos($api_content, '$row ? $row[') !== false ||
                   strpos($api_content, 'if ($row)') !== false ||
                   preg_match('/\$row\s*\?\s*\$row\[/', $api_content));
if (assert_true($has_null_check, "getpasteventpay.php handles NULL result for user lookup")) {
    $passed++;
} else {
    $failed++;
}

// Check that family_id defaults to 0 if not found
$has_family_default = (strpos($api_content, ': 0') !== false);
if (assert_true($has_family_default, "getpasteventpay.php defaults family_id to 0 if not found")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 8: SQL Injection Prevention
// =============================================================================
echo "\n--- Test 8: SQL Injection Prevention ---\n";

// Check for prepared statements
$uses_prepared = (strpos($api_content, '->prepare(') !== false);
if (assert_true($uses_prepared, "getpasteventpay.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// Check for bind_param usage
$uses_bind_param = (strpos($api_content, 'bind_param') !== false);
if (assert_true($uses_bind_param, "getpasteventpay.php uses bind_param for query parameters")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 9: Input Validation
// =============================================================================
echo "\n--- Test 9: Input Validation ---\n";

// Check for validation_helper.php inclusion
$uses_validation_helper = (strpos($api_content, 'validation_helper.php') !== false);
if (assert_true($uses_validation_helper, "getpasteventpay.php includes validation_helper.php")) {
    $passed++;
} else {
    $failed++;
}

// Check for validate_int_post usage for the ID parameter
$validates_id = (strpos($api_content, 'validate_int_post') !== false);
if (assert_true($validates_id, "getpasteventpay.php uses validate_int_post for ID validation")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 10: Output Escaping (XSS Prevention)
// =============================================================================
echo "\n--- Test 10: Output Escaping (XSS Prevention) ---\n";

// Check for escape_html usage in output
$uses_escape_html = (strpos($api_content, 'escape_html(') !== false);
if (assert_true($uses_escape_html, "getpasteventpay.php uses escape_html() for output")) {
    $passed++;
} else {
    $failed++;
}

// Check that event name is escaped
$escapes_eventname = (strpos($api_content, "escape_html(\$row3['name'])") !== false);
if (assert_true($escapes_eventname, "getpasteventpay.php escapes event name")) {
    $passed++;
} else {
    $failed++;
}

// Check that startdate is escaped
$escapes_startdate = (strpos($api_content, "escape_html(\$row3['startdate'])") !== false);
if (assert_true($escapes_startdate, "getpasteventpay.php escapes start date")) {
    $passed++;
} else {
    $failed++;
}

// Check that cost is escaped
$escapes_cost = (strpos($api_content, "escape_html(\$row3['cost'])") !== false);
if (assert_true($escapes_cost, "getpasteventpay.php escapes cost")) {
    $passed++;
} else {
    $failed++;
}

// Check that scout name is escaped
$escapes_scoutname = (strpos($api_content, 'escape_html($first)') !== false ||
                      strpos($api_content, 'escape_html($last)') !== false);
if (assert_true($escapes_scoutname, "getpasteventpay.php escapes scout name")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 11: Activity Logging
// =============================================================================
echo "\n--- Test 11: Activity Logging ---\n";

// Check that activity_logger.php is included
$includes_logger = (strpos($api_content, 'activity_logger.php') !== false);
if (assert_true($includes_logger, "getpasteventpay.php includes activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

// Check that log_activity is called
$calls_log_activity = (strpos($api_content, 'log_activity(') !== false);
if (assert_true($calls_log_activity, "getpasteventpay.php calls log_activity()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it logs payment data access
$logs_payment_access = (strpos($api_content, 'view_past_event_payments') !== false);
if (assert_true($logs_payment_access, "getpasteventpay.php logs 'view_past_event_payments' action")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 12: Proper statement closing
// =============================================================================
echo "\n--- Test 12: Resource Cleanup ---\n";

// Check that prepared statements are closed
$closes_statements = (substr_count($api_content, '->close()') >= 3);
if (assert_true($closes_statements, "getpasteventpay.php closes prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 13: Session started
// =============================================================================
echo "\n--- Test 13: Session Management ---\n";

$has_session_start = (strpos($api_content, 'session_start()') !== false);
if (assert_true($has_session_start, "getpasteventpay.php starts session")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
