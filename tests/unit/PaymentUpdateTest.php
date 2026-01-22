<?php
/**
 * Payment Update Unit Test
 *
 * Tests the payment update functionality in ppupdate2.php.
 * Validates:
 * 1. Family relationship authorization (parents can pay for children)
 * 2. Activity logging uses correct parameter order
 * 3. Security and input validation
 *
 * Regression tests for bugs fixed 2026-01-22:
 * - Bug: Parents couldn't update payment status for their children
 * - Bug: Activity log showed success (✅) for denied transactions
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Payment Update Tests (ppupdate2.php)");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: File exists and basic structure
// ============================================================================

echo "Test 1: ppupdate2.php file exists and has basic structure\n";
echo str_repeat("-", 60) . "\n";

$ppupdateFile = PUBLIC_HTML_DIR . '/api/ppupdate2.php';
if (assert_file_exists($ppupdateFile, "ppupdate2.php exists")) {
    $passed++;
} else {
    $failed++;
}

$ppupdateContents = file_get_contents($ppupdateFile);

if (assert_true(
    strpos($ppupdateContents, 'require_ajax()') !== false,
    "ppupdate2.php requires AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, 'require_authentication()') !== false,
    "ppupdate2.php requires authentication"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Family relationship authorization (Regression Test)
// ============================================================================

echo "Test 2: Family relationship authorization for payment updates\n";
echo str_repeat("-", 60) . "\n";

// Check that current user's family_id is retrieved
if (assert_true(
    strpos($ppupdateContents, 'current_user_family_id') !== false,
    "ppupdate2.php retrieves current user's family_id"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that family_id lookup uses prepared statement
if (assert_true(
    strpos($ppupdateContents, 'SELECT family_id FROM users WHERE user_id=?') !== false,
    "ppupdate2.php uses prepared statement to get current user's family_id"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that registration lookup includes family_id join
if (assert_true(
    strpos($ppupdateContents, 'LEFT JOIN users u ON r.user_id = u.user_id') !== false ||
    strpos($ppupdateContents, 'JOIN users u ON r.user_id = u.user_id') !== false,
    "ppupdate2.php joins users table to get registration owner's family_id"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that registration lookup includes events table join for event name
if (assert_true(
    strpos($ppupdateContents, 'LEFT JOIN events e ON r.event_id = e.id') !== false ||
    strpos($ppupdateContents, 'JOIN events e ON r.event_id = e.id') !== false,
    "ppupdate2.php joins events table to get event name for user feedback"
)) {
    $passed++;
} else {
    $failed++;
}

// Check for is_family_member variable
if (assert_true(
    strpos($ppupdateContents, 'is_family_member') !== false,
    "ppupdate2.php checks is_family_member for authorization"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that family relationship is checked in authorization
if (assert_true(
    strpos($ppupdateContents, 'current_user_family_id == $check_row[\'family_id\']') !== false ||
    strpos($ppupdateContents, "\$current_user_family_id == \$check_row['family_id']") !== false,
    "ppupdate2.php compares current user's family_id with registration owner's family_id"
)) {
    $passed++;
} else {
    $failed++;
}

// Check authorization allows: admin, owner, OR family member
if (assert_true(
    strpos($ppupdateContents, '!$is_admin && !$is_owner && !$is_family_member') !== false,
    "ppupdate2.php allows payment update if admin, owner, OR family member"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Activity logging parameter order (Regression Test)
// ============================================================================

echo "Test 3: Activity logging uses correct parameter order\n";
echo str_repeat("-", 60) . "\n";

// The log_activity signature is:
// log_activity($mysqli, $action, $values, $success, $freetext, $post_user_id)
//
// Bug was: parameters were passed in wrong order, with $success receiving
// a JSON string (truthy) instead of boolean false

// Check that log_activity calls use array() for values, not json_encode()
if (assert_true(
    strpos($ppupdateContents, "log_activity(\n            \$mysqli,\n            'payment_update_failed',\n            array(") !== false ||
    strpos($ppupdateContents, "log_activity(\n            \$mysqli,\n            'payment_update_denied',\n            array(") !== false,
    "ppupdate2.php passes array (not JSON string) for values parameter"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that false is used for failed transactions (not 0 or JSON string)
if (assert_true(
    strpos($ppupdateContents, "array('reg_id' => \$id, 'reason' => 'registration_not_found'),\n            false,") !== false,
    "ppupdate2.php uses boolean false for failed payment_update_failed log"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, "array('reg_id' => \$id, 'reg_owner' => \$check_row['user_id'], 'requester' => \$current_user_id),\n            false,") !== false,
    "ppupdate2.php uses boolean false for denied payment_update_denied log"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that true is used for successful batch update
if (assert_true(
    strpos($ppupdateContents, "'batch_payment_update',\n    array(") !== false &&
    strpos($ppupdateContents, "true,\n    \"Batch payment update completed") !== false,
    "ppupdate2.php uses boolean true for successful batch_payment_update log"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that action names are correct (not filename)
if (assert_true(
    strpos($ppupdateContents, "'ppupdate2.php',\n            'payment_update") === false &&
    strpos($ppupdateContents, "'ppupdate2.php',\n            'batch_payment") === false,
    "ppupdate2.php uses descriptive action names (not filename as action)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Security checks
// ============================================================================

echo "Test 4: Security checks for payment update\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($ppupdateContents, '$mysqli->prepare') !== false,
    "ppupdate2.php uses prepared statements"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, 'bind_param') !== false,
    "ppupdate2.php uses parameter binding"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, 'FILTER_VALIDATE_INT') !== false,
    "ppupdate2.php validates registration IDs as integers"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, "header('Content-Type: application/json')") !== false,
    "ppupdate2.php sets JSON content type header"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Admin permission check
// ============================================================================

echo "Test 5: Admin permission bypass for payment updates\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($ppupdateContents, "has_permission('trs')") !== false,
    "ppupdate2.php checks for treasurer permission"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, "has_permission('sa')") !== false,
    "ppupdate2.php checks for super admin permission"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, "has_permission('wm')") !== false,
    "ppupdate2.php checks for webmaster permission"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Batch update logging includes counts
// ============================================================================

echo "Test 6: Batch update logging includes success counts\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($ppupdateContents, 'successful_count') !== false,
    "ppupdate2.php tracks successful_count for batch operations"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppupdateContents, 'requested_count') !== false,
    "ppupdate2.php tracks requested_count for batch operations"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: User feedback for denied registrations (Regression Test)
// ============================================================================

echo "Test 7: User feedback for denied registrations\n";
echo str_repeat("-", 60) . "\n";

// Check that denied array is initialized
if (assert_true(
    strpos($ppupdateContents, '$denied = array()') !== false,
    "ppupdate2.php initializes denied array for tracking"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that denied registrations are added to array
if (assert_true(
    strpos($ppupdateContents, "\$denied[] = array(") !== false,
    "ppupdate2.php adds denied registrations to denied array"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that denied array includes username for user feedback
if (assert_true(
    strpos($ppupdateContents, "'username' => \$denied_user_name") !== false,
    "ppupdate2.php includes username in denied registration data"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that denied array includes eventname for user feedback
if (assert_true(
    strpos($ppupdateContents, "'eventname' => \$denied_event_name") !== false,
    "ppupdate2.php includes eventname in denied registration data"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that denied array includes reason for user feedback
if (assert_true(
    strpos($ppupdateContents, "'reason' => 'You are not authorized") !== false,
    "ppupdate2.php includes user-friendly reason in denied registration data"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that response includes denied array
if (assert_true(
    strpos($ppupdateContents, "'denied' => \$denied") !== false,
    "ppupdate2.php includes denied array in API response"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that status reflects denial state
if (assert_true(
    strpos($ppupdateContents, "\$status = 'Error'") !== false &&
    strpos($ppupdateContents, "\$status = 'Partial'") !== false,
    "ppupdate2.php sets appropriate status for denied registrations (Error/Partial)"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that denied_count is tracked in activity log
if (assert_true(
    strpos($ppupdateContents, "'denied_count' => \$denied_count") !== false,
    "ppupdate2.php tracks denied_count in activity log"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Frontend displays denial messages
// ============================================================================

echo "Test 8: Frontend displays denial messages to users\n";
echo str_repeat("-", 60) . "\n";

$ppReturnPageFile = PUBLIC_HTML_DIR . '/templates/PPReturnPage2.html';
if (assert_file_exists($ppReturnPageFile, "PPReturnPage2.html exists")) {
    $passed++;
} else {
    $failed++;
}

$ppReturnPageContents = file_get_contents($ppReturnPageFile);

if (assert_true(
    strpos($ppReturnPageContents, "data['denied']") !== false,
    "PPReturnPage2.html checks for denied registrations in response"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, 'writeDeniedRow') !== false,
    "PPReturnPage2.html has function to display denied registrations"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, 'alert-box warning') !== false,
    "PPReturnPage2.html shows warning message for denied registrations"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, 'Payment Updates Not Processed') !== false,
    "PPReturnPage2.html has header for denied registrations table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, '<th>Participant</th><th>Event</th><th>Reason</th>') !== false,
    "PPReturnPage2.html denied table includes Participant, Event, and Reason columns"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, "element[\"eventname\"]") !== false,
    "PPReturnPage2.html displays event name in denied row"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, "data['status'] === 'Error'") !== false,
    "PPReturnPage2.html checks for Error status to show help message"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($ppReturnPageContents, 't212webmaster@gmail.com') !== false,
    "PPReturnPage2.html includes webmaster contact for error cases"
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
