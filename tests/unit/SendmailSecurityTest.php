<?php
/**
 * Sendmail Security Tests
 *
 * Tests for security patterns in the sendmail API:
 * - Authentication enforcement
 * - CSRF protection
 * - Rate limiting
 * - Header injection prevention
 * - Content escaping
 *
 * These are static analysis tests that verify security patterns
 * without requiring database access.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Sendmail Security Tests");

$passed = 0;
$failed = 0;

$sendmail_path = PUBLIC_HTML_DIR . '/api/sendmail.php';

// =============================================================================
// Test 1: File exists
// =============================================================================
echo "\n--- Test 1: API File Exists ---\n";

if (assert_file_exists($sendmail_path, "sendmail.php exists")) {
  $passed++;
} else {
  $failed++;
}

$sendmail_content = file_get_contents($sendmail_path);

// =============================================================================
// Test 2: Authentication enforcement
// =============================================================================
echo "\n--- Test 2: Authentication Enforcement ---\n";

$has_auth = (strpos($sendmail_content, 'require_authentication()') !== false);
if (assert_true($has_auth, "sendmail.php requires authentication")) {
  $passed++;
} else {
  $failed++;
}

// Verify auth happens before any email sending
$auth_pos = strpos($sendmail_content, 'require_authentication()');
$send_pos = strpos($sendmail_content, '->Send()');
if (assert_true($auth_pos < $send_pos, "Authentication check happens before sending email")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 3: AJAX enforcement
// =============================================================================
echo "\n--- Test 3: AJAX Enforcement ---\n";

$has_ajax = (strpos($sendmail_content, 'require_ajax()') !== false);
if (assert_true($has_ajax, "sendmail.php requires AJAX")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 4: CSRF enforcement
// =============================================================================
echo "\n--- Test 4: CSRF Enforcement ---\n";

$has_csrf = (strpos($sendmail_content, 'require_csrf()') !== false);
if (assert_true($has_csrf, "sendmail.php requires CSRF validation")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 5: Rate limiting
// =============================================================================
echo "\n--- Test 5: Rate Limiting ---\n";

$has_rate_limit_query = (strpos($sendmail_content, 'email_count') !== false ||
                         strpos($sendmail_content, 'rate_limit') !== false);
if (assert_true($has_rate_limit_query, "sendmail.php implements rate limiting query")) {
  $passed++;
} else {
  $failed++;
}

$has_rate_limit_check = (strpos($sendmail_content, '>= 10') !== false);
if (assert_true($has_rate_limit_check, "sendmail.php checks for 10 emails per hour limit")) {
  $passed++;
} else {
  $failed++;
}

$has_429_response = (strpos($sendmail_content, 'http_response_code(429)') !== false);
if (assert_true($has_429_response, "sendmail.php returns 429 status for rate limit exceeded")) {
  $passed++;
} else {
  $failed++;
}

$logs_rate_limit = (strpos($sendmail_content, 'send_email_rate_limited') !== false);
if (assert_true($logs_rate_limit, "sendmail.php logs rate limit events")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 6: Header injection prevention
// =============================================================================
echo "\n--- Test 6: Header Injection Prevention ---\n";

$checks_newlines_in_headers = (preg_match('/preg_match.*\\\\r\\\\n.*\\$from/s', $sendmail_content) ||
                                strpos($sendmail_content, 'header_injection') !== false);
if (assert_true($checks_newlines_in_headers, "sendmail.php checks for newlines in email headers")) {
  $passed++;
} else {
  $failed++;
}

$logs_injection_attempt = (strpos($sendmail_content, 'header_injection') !== false);
if (assert_true($logs_injection_attempt, "sendmail.php logs header injection attempts")) {
  $passed++;
} else {
  $failed++;
}

$rejects_with_400 = (strpos($sendmail_content, 'http_response_code(400)') !== false);
if (assert_true($rejects_with_400, "sendmail.php returns 400 status for invalid headers")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 7: Content escaping
// =============================================================================
echo "\n--- Test 7: Content Escaping ---\n";

$uses_escape_html = (strpos($sendmail_content, 'escape_html(') !== false);
if (assert_true($uses_escape_html, "sendmail.php uses escape_html() for content")) {
  $passed++;
} else {
  $failed++;
}

// Check that error messages are escaped
$escapes_error_info = (strpos($sendmail_content, 'escape_html($mail->ErrorInfo)') !== false);
if (assert_true($escapes_error_info, "sendmail.php escapes error messages in response")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 8: Input validation
// =============================================================================
echo "\n--- Test 8: Input Validation ---\n";

$has_validation_helper = (strpos($sendmail_content, 'validation_helper.php') !== false);
if (assert_true($has_validation_helper, "sendmail.php includes validation_helper.php")) {
  $passed++;
} else {
  $failed++;
}

$validates_from_email = (strpos($sendmail_content, 'validate_email_post(\'from\'') !== false);
if (assert_true($validates_from_email, "sendmail.php validates from email")) {
  $passed++;
} else {
  $failed++;
}

$validates_subject = (strpos($sendmail_content, 'validate_string_post(\'subject\'') !== false);
if (assert_true($validates_subject, "sendmail.php validates subject")) {
  $passed++;
} else {
  $failed++;
}

$validates_recipient = (strpos($sendmail_content, 'FILTER_VALIDATE_EMAIL') !== false);
if (assert_true($validates_recipient, "sendmail.php validates recipient email address")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 9: Authorization check
// =============================================================================
echo "\n--- Test 9: Authorization Check ---\n";

$has_user_access_check = (strpos($sendmail_content, 'require_user_access(') !== false);
if (assert_true($has_user_access_check, "sendmail.php checks user access permission")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 10: Activity logging
// =============================================================================
echo "\n--- Test 10: Activity Logging ---\n";

$has_activity_logger = (strpos($sendmail_content, 'activity_logger.php') !== false);
if (assert_true($has_activity_logger, "sendmail.php includes activity_logger.php")) {
  $passed++;
} else {
  $failed++;
}

$logs_success = (strpos($sendmail_content, "'send_email'") !== false ||
                 strpos($sendmail_content, '"send_email"') !== false);
if (assert_true($logs_success, "sendmail.php logs successful email sends")) {
  $passed++;
} else {
  $failed++;
}

$logs_failure = (strpos($sendmail_content, 'send_email_failed') !== false);
if (assert_true($logs_failure, "sendmail.php logs failed email sends")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 11: Prepared statements
// =============================================================================
echo "\n--- Test 11: Prepared Statements ---\n";

$uses_prepared = (strpos($sendmail_content, '->prepare(') !== false);
if (assert_true($uses_prepared, "sendmail.php uses prepared statements")) {
  $passed++;
} else {
  $failed++;
}

$uses_bind_param = (strpos($sendmail_content, '->bind_param(') !== false);
if (assert_true($uses_bind_param, "sendmail.php uses parameter binding")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 12: No debug output in production
// =============================================================================
echo "\n--- Test 12: Debug Output Removed ---\n";

$has_debug_error_log = (preg_match_all('/error_log\s*\(/', $sendmail_content, $matches));
// We should minimize error_log usage (some is acceptable for actual errors)
if (assert_true($has_debug_error_log <= 2, "sendmail.php has minimal debug logging (<=2 error_log calls)")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 13: JSON response format
// =============================================================================
echo "\n--- Test 13: JSON Response Format ---\n";

$sets_json_header = (strpos($sendmail_content, 'Content-Type: application/json') !== false);
if (assert_true($sets_json_header, "sendmail.php sets JSON content type header")) {
  $passed++;
} else {
  $failed++;
}

$uses_json_encode = (strpos($sendmail_content, 'json_encode(') !== false);
if (assert_true($uses_json_encode, "sendmail.php uses json_encode for response")) {
  $passed++;
} else {
  $failed++;
}

// =============================================================================
// Test 14: Notification preferences respected
// =============================================================================
echo "\n--- Test 14: Notification Preferences Respected ---\n";

$checks_notif_prefs = (strpos($sendmail_content, 'notif_preferences') !== false);
if (assert_true($checks_notif_prefs, "sendmail.php checks notification preferences")) {
  $passed++;
} else {
  $failed++;
}

$respects_optout = (strpos($sendmail_content, 'scsu') !== false);
if (assert_true($respects_optout, "sendmail.php respects scout signup (scsu) opt-out preference")) {
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
