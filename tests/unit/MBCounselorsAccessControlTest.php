<?php
/**
 * MB Counselors Access Control Unit Test
 *
 * Tests security measures for the Merit Badge Counselors feature.
 * These tests ensure authentication, CSRF, AJAX, and XSS protections
 * are properly implemented.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("MB Counselors Access Control Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify API file exists
// ============================================================================

echo "Test 1: getMBcounselors.php file existence\n";
echo str_repeat("-", 60) . "\n";

$apiFile = PUBLIC_HTML_DIR . '/api/getMBcounselors.php';
if (assert_file_exists($apiFile, "getMBcounselors.php API file exists")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify MB_Counselors.php file exists
// ============================================================================

echo "Test 2: MB_Counselors.php file existence\n";
echo str_repeat("-", 60) . "\n";

$pageFile = PUBLIC_HTML_DIR . '/MB_Counselors.php';
if (assert_file_exists($pageFile, "MB_Counselors.php page file exists")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify MB_Counselors.html template exists
// ============================================================================

echo "Test 3: MB_Counselors.html template existence\n";
echo str_repeat("-", 60) . "\n";

$templateFile = PUBLIC_HTML_DIR . '/templates/MB_Counselors.html';
if (assert_file_exists($templateFile, "MB_Counselors.html template file exists")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Authentication enforcement in API
// ============================================================================

echo "Test 4: Authentication enforcement in API\n";
echo str_repeat("-", 60) . "\n";

$apiContents = file_get_contents($apiFile);

// Check for require_authentication() call
if (assert_true(
  strpos($apiContents, 'require_authentication()') !== false,
  "require_authentication() is called in API"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for auth_helper.php require
if (assert_true(
  strpos($apiContents, "require 'auth_helper.php'") !== false ||
  strpos($apiContents, 'require "auth_helper.php"') !== false,
  "auth_helper.php is required in API"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: CSRF enforcement in API
// ============================================================================

echo "Test 5: CSRF enforcement in API\n";
echo str_repeat("-", 60) . "\n";

// Check for require_csrf() call
if (assert_true(
  strpos($apiContents, 'require_csrf()') !== false,
  "require_csrf() is called in API"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: AJAX enforcement in API
// ============================================================================

echo "Test 6: AJAX enforcement in API\n";
echo str_repeat("-", 60) . "\n";

// Check for require_ajax() call
if (assert_true(
  strpos($apiContents, 'require_ajax()') !== false,
  "require_ajax() is called in API"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Activity logging in API
// ============================================================================

echo "Test 7: Activity logging in API\n";
echo str_repeat("-", 60) . "\n";

// Check for activity_logger.php require
if (assert_true(
  strpos($apiContents, 'activity_logger.php') !== false,
  "activity_logger.php is required in API"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for log_activity call with view_mb_counselors action
if (assert_true(
  strpos($apiContents, "log_activity") !== false &&
  strpos($apiContents, "'view_mb_counselors'") !== false,
  "log_activity is called with 'view_mb_counselors' action"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for success logging (true)
// log_activity format: log_activity($mysqli, $action, $values_array, $success_bool, $freetext, $user_id)
// Success is 4th parameter, indicated by "true," on its own line
if (assert_true(
  strpos($apiContents, "  true,") !== false,
  "Activity logging includes success case (true)"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for failure logging (false)
if (assert_true(
  strpos($apiContents, "  false,") !== false,
  "Activity logging includes failure case (false)"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: XSS prevention (escape_html) in API output
// ============================================================================

echo "Test 8: XSS prevention in API output\n";
echo str_repeat("-", 60) . "\n";

// Check for escape_html usage on output fields
if (assert_true(
  strpos($apiContents, "escape_html") !== false,
  "escape_html() is used in API output"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that mb_name is escaped
if (assert_true(
  strpos($apiContents, "escape_html(\$row->mb_name)") !== false,
  "mb_name field is escaped with escape_html()"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that user_first is escaped
if (assert_true(
  strpos($apiContents, "escape_html(\$row2->user_first)") !== false,
  "user_first field is escaped with escape_html()"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that user_last is escaped
if (assert_true(
  strpos($apiContents, "escape_html(\$row2->user_last)") !== false,
  "user_last field is escaped with escape_html()"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that user_email is escaped
if (assert_true(
  strpos($apiContents, "escape_html(\$row2->user_email)") !== false,
  "user_email field is escaped with escape_html()"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Prepared statements in API
// ============================================================================

echo "Test 9: Prepared statements in API\n";
echo str_repeat("-", 60) . "\n";

// Check for prepare() usage
if (assert_true(
  strpos($apiContents, '$mysqli->prepare(') !== false,
  "mysqli->prepare() is used for database queries"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for bind_param usage
if (assert_true(
  strpos($apiContents, 'bind_param(') !== false,
  "bind_param() is used for parameter binding"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that no raw queries exist (no direct query() calls with user input)
if (assert_true(
  strpos($apiContents, '$mysqli->query(') === false,
  "No raw mysqli->query() calls (uses prepared statements)"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Secure cookie options in MB_Counselors.php
// ============================================================================

echo "Test 10: Secure cookie options in MB_Counselors.php\n";
echo str_repeat("-", 60) . "\n";

$pageContents = file_get_contents($pageFile);

// Check for secure cookie options
if (assert_true(
  strpos($pageContents, "'secure' => true") !== false ||
  strpos($pageContents, '"secure" => true') !== false,
  "Secure cookie option is set"
)) {
  $passed++;
} else {
  $failed++;
}

if (assert_true(
  strpos($pageContents, "'httponly' => true") !== false ||
  strpos($pageContents, '"httponly" => true') !== false,
  "HttpOnly cookie option is set"
)) {
  $passed++;
} else {
  $failed++;
}

if (assert_true(
  strpos($pageContents, "'samesite'") !== false ||
  strpos($pageContents, '"samesite"') !== false,
  "SameSite cookie option is set"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: CSRF token output in MB_Counselors.php
// ============================================================================

echo "Test 11: CSRF token output in MB_Counselors.php\n";
echo str_repeat("-", 60) . "\n";

// Check for CSRF token hidden field
if (assert_true(
  strpos($pageContents, 'id="csrf_token"') !== false,
  "CSRF token hidden field is present"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for htmlspecialchars on CSRF token
if (assert_true(
  strpos($pageContents, 'htmlspecialchars($_SESSION[\'csrf_token\']') !== false ||
  strpos($pageContents, 'htmlspecialchars($_SESSION["csrf_token"]') !== false,
  "CSRF token is escaped with htmlspecialchars()"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: XSS prevention in template (escapeHtml function)
// ============================================================================

echo "Test 12: XSS prevention in template\n";
echo str_repeat("-", 60) . "\n";

$templateContents = file_get_contents($templateFile);

// Check for escapeHtml function definition
if (assert_true(
  strpos($templateContents, 'function escapeHtml(') !== false,
  "escapeHtml() JavaScript function is defined"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that escapeHtml is used for data output
if (assert_true(
  strpos($templateContents, 'escapeHtml(') !== false,
  "escapeHtml() is called for data output"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: No inline onclick handlers in template
// ============================================================================

echo "Test 13: No inline onclick handlers in template\n";
echo str_repeat("-", 60) . "\n";

// Check that no inline onclick handlers exist
if (assert_true(
  strpos($templateContents, 'onclick=') === false,
  "No inline onclick handlers in template"
)) {
  $passed++;
} else {
  $failed++;
}

// Check for jQuery event delegation
if (assert_true(
  strpos($templateContents, "$(document).on('click'") !== false ||
  strpos($templateContents, '$(document).on("click"') !== false,
  "jQuery event delegation is used for click handlers"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: CSRF token sent with AJAX request in template
// ============================================================================

echo "Test 14: CSRF token sent with AJAX request\n";
echo str_repeat("-", 60) . "\n";

// Check that CSRF token is read from hidden field
if (assert_true(
  strpos($templateContents, "$('#csrf_token').val()") !== false,
  "CSRF token is read from hidden field"
)) {
  $passed++;
} else {
  $failed++;
}

// Check that CSRF token is included in AJAX request
if (assert_true(
  strpos($templateContents, 'csrf_token') !== false &&
  strpos($templateContents, 'JSON.stringify') !== false,
  "CSRF token is included in AJAX request payload"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 15: Proper indentation (2 spaces, not tabs)
// ============================================================================

echo "Test 15: Code style - 2 spaces indentation\n";
echo str_repeat("-", 60) . "\n";

// Check API file for tabs
$hasTabsInApi = preg_match('/^\t/m', $apiContents);
if (assert_true(
  !$hasTabsInApi,
  "API file uses spaces (not tabs) for indentation"
)) {
  $passed++;
} else {
  $failed++;
}

// Check template file for tabs
$hasTabsInTemplate = preg_match('/^\t/m', $templateContents);
if (assert_true(
  !$hasTabsInTemplate,
  "Template file uses spaces (not tabs) for indentation"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 16: Validation helper is included
// ============================================================================

echo "Test 16: Validation helper is included\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
  strpos($apiContents, "require 'validation_helper.php'") !== false ||
  strpos($apiContents, 'require "validation_helper.php"') !== false,
  "validation_helper.php is required in API"
)) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 17: Authentication order - require_ajax before require_authentication
// ============================================================================

echo "Test 17: Security check order\n";
echo str_repeat("-", 60) . "\n";

$ajaxPos = strpos($apiContents, 'require_ajax()');
$authPos = strpos($apiContents, 'require_authentication()');
$csrfPos = strpos($apiContents, 'require_csrf()');

if (assert_true(
  $ajaxPos !== false && $authPos !== false && $ajaxPos < $authPos,
  "require_ajax() is called before require_authentication()"
)) {
  $passed++;
} else {
  $failed++;
}

if (assert_true(
  $authPos !== false && $csrfPos !== false && $authPos < $csrfPos,
  "require_authentication() is called before require_csrf()"
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
