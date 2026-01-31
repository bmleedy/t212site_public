<?php
/**
 * Notification Preferences Access Control Tests
 *
 * Tests for security patterns in notification preference APIs:
 * - notifications_getprefs.php
 * - notifications_updatepref.php
 *
 * These are static analysis tests that verify security patterns
 * without requiring database access.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Notification Preferences Access Control Tests");

$passed = 0;
$failed = 0;

$getprefs_path = PUBLIC_HTML_DIR . '/api/notifications_getprefs.php';
$updatepref_path = PUBLIC_HTML_DIR . '/api/notifications_updatepref.php';

// =============================================================================
// Test 1: Files exist
// =============================================================================
echo "\n--- Test 1: API Files Exist ---\n";

if (assert_file_exists($getprefs_path, "notifications_getprefs.php exists")) {
  $passed++;
} else {
  $failed++;
}

if (assert_file_exists($updatepref_path, "notifications_updatepref.php exists")) {
  $passed++;
} else {
  $failed++;
}

$getprefs_content = file_get_contents($getprefs_path);
$updatepref_content = file_get_contents($updatepref_path);

// =============================================================================
// Test 2: Authentication enforcement
// =============================================================================
echo "\n--- Test 2: Authentication Enforcement ---\n";

$has_auth_getprefs = (strpos($getprefs_content, 'require_authentication()') !== false);
if (assert_true($has_auth_getprefs, "notifications_getprefs.php requires authentication")) {
  $passed++;
} else {
  $failed++;
}

$has_auth_updatepref = (strpos($updatepref_content, 'require_authentication()') !== false);
if (assert_true($has_auth_updatepref, "notifications_updatepref.php requires authentication")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 3: AJAX enforcement
// =============================================================================
echo "\n--- Test 3: AJAX Enforcement ---\n";

$has_ajax_getprefs = (strpos($getprefs_content, 'require_ajax()') !== false);
if (assert_true($has_ajax_getprefs, "notifications_getprefs.php requires AJAX")) {
  $passed++;
} else {
  $failed++;
}

$has_ajax_updatepref = (strpos($updatepref_content, 'require_ajax()') !== false);
if (assert_true($has_ajax_updatepref, "notifications_updatepref.php requires AJAX")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 4: CSRF enforcement
// =============================================================================
echo "\n--- Test 4: CSRF Enforcement ---\n";

$has_csrf_getprefs = (strpos($getprefs_content, 'require_csrf()') !== false);
if (assert_true($has_csrf_getprefs, "notifications_getprefs.php requires CSRF validation")) {
  $passed++;
} else {
  $failed++;
}

$has_csrf_updatepref = (strpos($updatepref_content, 'require_csrf()') !== false);
if (assert_true($has_csrf_updatepref, "notifications_updatepref.php requires CSRF validation")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 5: Authorization - users can only access their own preferences
// =============================================================================
echo "\n--- Test 5: Authorization Check ---\n";

// Both APIs should use $current_user_id for database queries, not user-supplied ID
$uses_current_user_getprefs = (strpos($getprefs_content, '$current_user_id') !== false);
if (assert_true($uses_current_user_getprefs, "notifications_getprefs.php uses authenticated user ID")) {
  $passed++;
} else {
  $failed++;
}

$uses_current_user_updatepref = (strpos($updatepref_content, '$current_user_id') !== false);
if (assert_true($uses_current_user_updatepref, "notifications_updatepref.php uses authenticated user ID")) {
  $passed++;
} else {
  $failed++;
}

// Verify they don't accept user_id from POST (would allow accessing other users' data)
$accepts_user_id_getprefs = (strpos($getprefs_content, 'validate_int_post(\'user_id\')') !== false);
if (assert_false($accepts_user_id_getprefs, "notifications_getprefs.php does NOT accept user_id from POST")) {
  $passed++;
} else {
  $failed++;
}

$accepts_user_id_updatepref = (strpos($updatepref_content, 'validate_int_post(\'user_id\')') !== false);
if (assert_false($accepts_user_id_updatepref, "notifications_updatepref.php does NOT accept user_id from POST")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 6: Input validation
// =============================================================================
echo "\n--- Test 6: Input Validation ---\n";

$has_validation_helper = (strpos($updatepref_content, 'validation_helper.php') !== false);
if (assert_true($has_validation_helper, "notifications_updatepref.php includes validation_helper.php")) {
  $passed++;
} else {
  $failed++;
}

// Check that notification_type is validated
$validates_type = (strpos($updatepref_content, 'validate_string_post(\'notification_type\'') !== false);
if (assert_true($validates_type, "notifications_updatepref.php validates notification_type input")) {
  $passed++;
} else {
  $failed++;
}

// Check that enabled is validated as boolean
$validates_enabled = (strpos($updatepref_content, 'validate_bool_post(\'enabled\'') !== false);
if (assert_true($validates_enabled, "notifications_updatepref.php validates enabled as boolean")) {
  $passed++;
} else {
  $failed++;
}

// Check that notification type is validated against whitelist
$validates_whitelist = (strpos($updatepref_content, '$valid_types') !== false ||
                        strpos($updatepref_content, 'in_array($notification_type') !== false);
if (assert_true($validates_whitelist, "notifications_updatepref.php validates notification_type against whitelist")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 7: Prepared statements
// =============================================================================
echo "\n--- Test 7: Prepared Statements ---\n";

$uses_prepared_getprefs = (strpos($getprefs_content, '->prepare(') !== false);
if (assert_true($uses_prepared_getprefs, "notifications_getprefs.php uses prepared statements")) {
  $passed++;
} else {
  $failed++;
}

$uses_prepared_updatepref = (strpos($updatepref_content, '->prepare(') !== false);
if (assert_true($uses_prepared_updatepref, "notifications_updatepref.php uses prepared statements")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 8: Activity logging
// =============================================================================
echo "\n--- Test 8: Activity Logging ---\n";

$has_logging_getprefs = (strpos($getprefs_content, 'log_activity(') !== false);
if (assert_true($has_logging_getprefs, "notifications_getprefs.php logs activity")) {
  $passed++;
} else {
  $failed++;
}

$has_logging_updatepref = (strpos($updatepref_content, 'log_activity(') !== false);
if (assert_true($has_logging_updatepref, "notifications_updatepref.php logs activity")) {
  $passed++;
} else {
  $failed++;
}

// Check for both success and failure logging in update
$logs_success = (strpos($updatepref_content, 'notification_pref_updated') !== false);
$logs_failure = (strpos($updatepref_content, 'notification_pref_update_failed') !== false);
if (assert_true($logs_success && $logs_failure, "notifications_updatepref.php logs both success and failure")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 9: Auth helper inclusion
// =============================================================================
echo "\n--- Test 9: Auth Helper Inclusion ---\n";

$includes_auth_getprefs = (strpos($getprefs_content, 'auth_helper.php') !== false);
if (assert_true($includes_auth_getprefs, "notifications_getprefs.php includes auth_helper.php")) {
  $passed++;
} else {
  $failed++;
}

$includes_auth_updatepref = (strpos($updatepref_content, 'auth_helper.php') !== false);
if (assert_true($includes_auth_updatepref, "notifications_updatepref.php includes auth_helper.php")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 10: JSON response format
// =============================================================================
echo "\n--- Test 10: JSON Response Format ---\n";

$sets_json_header_getprefs = (strpos($getprefs_content, 'Content-Type: application/json') !== false);
if (assert_true($sets_json_header_getprefs, "notifications_getprefs.php sets JSON content type")) {
  $passed++;
} else {
  $failed++;
}

$sets_json_header_updatepref = (strpos($updatepref_content, 'Content-Type: application/json') !== false);
if (assert_true($sets_json_header_updatepref, "notifications_updatepref.php sets JSON content type")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed === 0 ? 0 : 1);
?>
