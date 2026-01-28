<?php
/**
 * Section 3.3 Event Registration & Payment Security Tests
 *
 * Tests security implementations for:
 * - CSRF protection on payment APIs
 * - XSS protection on PPReturnPage2.php
 * - Transaction safety in approve.php
 * - Double-payment prevention in ppupdate2.php
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Section 3.3 Event Registration & Payment Security Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: PPReturnPage2.php XSS Protection
// ============================================================================

echo "Test 1: PPReturnPage2.php XSS Protection\n";
echo str_repeat("-", 60) . "\n";

$ppReturnFile = PUBLIC_HTML_DIR . '/PPReturnPage2.php';
if (assert_file_exists($ppReturnFile, "PPReturnPage2.php exists")) {
    $passed++;
} else {
    $failed++;
}

$ppReturnContents = file_get_contents($ppReturnFile);

// Check that GET parameter is escaped with htmlspecialchars
if (assert_true(
    strpos($ppReturnContents, "htmlspecialchars(\$_GET['reg_ids']") !== false,
    "PPReturnPage2.php escapes reg_ids GET parameter with htmlspecialchars"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for ENT_QUOTES flag
if (assert_true(
    strpos($ppReturnContents, "ENT_QUOTES") !== false,
    "PPReturnPage2.php uses ENT_QUOTES for complete XSS protection"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for null coalescing operator (handles missing parameter)
if (assert_true(
    strpos($ppReturnContents, "?? ''") !== false || strpos($ppReturnContents, "?? \"\"") !== false,
    "PPReturnPage2.php handles missing reg_ids parameter safely"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: pay.php CSRF Protection
// ============================================================================

echo "Test 2: pay.php CSRF Protection\n";
echo str_repeat("-", 60) . "\n";

$payFile = PUBLIC_HTML_DIR . '/api/pay.php';
if (assert_file_exists($payFile, "api/pay.php exists")) {
    $passed++;
} else {
    $failed++;
}

$payContents = file_get_contents($payFile);

// Check for require_csrf() call
if (assert_true(
    strpos($payContents, "require_csrf()") !== false,
    "pay.php calls require_csrf() for CSRF protection"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that require_csrf is called (not commented out)
$lines = explode("\n", $payContents);
$csrfFound = false;
foreach ($lines as $line) {
    $trimmed = trim($line);
    if (strpos($trimmed, 'require_csrf()') === 0 ||
        (strpos($trimmed, 'require_csrf()') !== false && strpos($trimmed, '//') !== 0)) {
        $csrfFound = true;
        break;
    }
}
if (assert_true($csrfFound, "pay.php has require_csrf() not commented out")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: ppupdate2.php CSRF Protection
// ============================================================================

echo "Test 3: ppupdate2.php CSRF Protection\n";
echo str_repeat("-", 60) . "\n";

$ppupdateFile = PUBLIC_HTML_DIR . '/api/ppupdate2.php';
if (assert_file_exists($ppupdateFile, "api/ppupdate2.php exists")) {
    $passed++;
} else {
    $failed++;
}

$ppupdateContents = file_get_contents($ppupdateFile);

// Check for require_csrf() call
if (assert_true(
    strpos($ppupdateContents, "require_csrf()") !== false,
    "ppupdate2.php calls require_csrf() for CSRF protection"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: ppupdate2.php Double-Payment Prevention
// ============================================================================

echo "Test 4: ppupdate2.php Double-Payment Prevention\n";
echo str_repeat("-", 60) . "\n";

// Check for paid status check before update
if (assert_true(
    strpos($ppupdateContents, "r.paid") !== false || strpos($ppupdateContents, "['paid']") !== false,
    "ppupdate2.php checks paid status"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for double-payment logging
if (assert_true(
    strpos($ppupdateContents, "payment_already_processed") !== false,
    "ppupdate2.php logs payment_already_processed for double-payment attempts"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: approve.php Transaction Safety
// ============================================================================

echo "Test 5: approve.php Transaction Safety\n";
echo str_repeat("-", 60) . "\n";

$approveFile = PUBLIC_HTML_DIR . '/api/approve.php';
if (assert_file_exists($approveFile, "api/approve.php exists")) {
    $passed++;
} else {
    $failed++;
}

$approveContents = file_get_contents($approveFile);

// Check for if statement around execute
if (assert_true(
    strpos($approveContents, 'if ($statement->execute())') !== false,
    "approve.php checks if execute() succeeded"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for failure logging with success=false
if (assert_true(
    strpos($approveContents, 'false,') !== false &&
    strpos($approveContents, '"Failed to approve registration') !== false,
    "approve.php logs failures with success=false"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for error response code on failure
if (assert_true(
    strpos($approveContents, 'http_response_code(500)') !== false,
    "approve.php returns 500 status on database failure"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: approve.php CSRF Protection
// ============================================================================

echo "Test 6: approve.php CSRF Protection\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($approveContents, "require_csrf()") !== false,
    "approve.php calls require_csrf() for CSRF protection"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: JavaScript XSS Protection
// ============================================================================

echo "Test 7: JavaScript XSS Protection (Defense-in-Depth)\n";
echo str_repeat("-", 60) . "\n";

$eventPayHtml = PUBLIC_HTML_DIR . '/templates/EventPay.html';
if (assert_file_exists($eventPayHtml, "templates/EventPay.html exists")) {
    $passed++;
} else {
    $failed++;
}

$eventPayContents = file_get_contents($eventPayHtml);

// Check for escapeHtml function
if (assert_true(
    strpos($eventPayContents, "function escapeHtml") !== false,
    "EventPay.html has escapeHtml function"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that escapeHtml uses createTextNode (safe method)
if (assert_true(
    strpos($eventPayContents, "createTextNode") !== false,
    "EventPay.html escapeHtml uses safe createTextNode method"
)) {
    $passed++;
} else {
    $failed++;
}

// Check PPReturnPage2.html for escapeHtml
$ppReturnHtml = PUBLIC_HTML_DIR . '/templates/PPReturnPage2.html';
if (assert_file_exists($ppReturnHtml, "templates/PPReturnPage2.html exists")) {
    $passed++;
} else {
    $failed++;
}

$ppReturnHtmlContents = file_get_contents($ppReturnHtml);

if (assert_true(
    strpos($ppReturnHtmlContents, "function escapeHtml") !== false,
    "PPReturnPage2.html has escapeHtml function"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Authorization Documentation
// ============================================================================

echo "Test 8: Authorization Documentation\n";
echo str_repeat("-", 60) . "\n";

$getapproveFile = PUBLIC_HTML_DIR . '/api/getapprove.php';
if (assert_file_exists($getapproveFile, "api/getapprove.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getapproveContents = file_get_contents($getapproveFile);

// Check for authorization documentation comments
if (assert_true(
    strpos($getapproveContents, "Authorization:") !== false,
    "getapprove.php has Authorization documentation"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($approveContents, "Authorization:") !== false,
    "approve.php has Authorization documentation"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: PayPal Limitation Documentation
// ============================================================================

echo "Test 9: PayPal Limitation Documentation\n";
echo str_repeat("-", 60) . "\n";

// Check for security limitation comment in ppupdate2.php
if (assert_true(
    strpos($ppupdateContents, "SECURITY LIMITATION") !== false,
    "ppupdate2.php documents security limitation regarding PayPal verification"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for TODO about webhook implementation
if (assert_true(
    strpos($ppupdateContents, "TODO") !== false && strpos($ppupdateContents, "webhook") !== false,
    "ppupdate2.php has TODO for PayPal webhook implementation"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// Print summary
// ============================================================================
test_summary($passed, $failed);

exit($failed === 0 ? 0 : 1);
