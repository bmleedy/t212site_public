<?php
/**
 * Cancellation Notification Preference Test
 *
 * Tests that the cancellation notification functionality works correctly:
 * - Scout in Charge (SIC) and Adult in Charge (AIC) receive emails when someone cancels
 * - Users can opt out via the 'canc' notification preference
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Cancellation Notification Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify cancellation preference added to notification_types.php
// ============================================================================

echo "Test 1: Cancellation preference in notification_types.php\n";
echo str_repeat("-", 60) . "\n";

$notifTypesFile = PUBLIC_HTML_DIR . '/includes/notification_types.php';
require_once $notifTypesFile;

$keys = array_column($notification_types, 'key');
if (assert_true(
    in_array('canc', $keys),
    "Cancellation notification type (canc) exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    count($notification_types) >= 4,
    "notification_types has at least 4 entries (including cancellation)"
)) {
    $passed++;
} else {
    $failed++;
}

// Find the canc notification
$canc_notif = null;
foreach ($notification_types as $notif) {
    if ($notif['key'] === 'canc') {
        $canc_notif = $notif;
        break;
    }
}

if (assert_true(
    $canc_notif !== null,
    "Found cancellation notification definition"
)) {
    $passed++;
} else {
    $failed++;
}

if ($canc_notif) {
    if (assert_true(
        isset($canc_notif['display_name']) && strlen($canc_notif['display_name']) > 0,
        "Cancellation notification has display_name"
    )) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(
        isset($canc_notif['tooltip']) && strlen($canc_notif['tooltip']) > 20,
        "Cancellation notification has meaningful tooltip"
    )) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 2: Verify register.php calls sendCancellationNotification
// ============================================================================

echo "Test 2: register.php cancellation logic\n";
echo str_repeat("-", 60) . "\n";

$registerFile = PUBLIC_HTML_DIR . '/api/register.php';
$registerContents = file_get_contents($registerFile);

if (assert_true(
    strpos($registerContents, 'sendCancellationNotification') !== false,
    "register.php has sendCancellationNotification function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*\$action\s*==\s*["\']cancel["\']\s*\)/', $registerContents),
    "register.php has cancellation action handler"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/sendCancellationNotification\s*\(\s*\$event_id\s*,\s*\$user_id\s*,\s*\$mysqli\s*\)/', $registerContents),
    "sendCancellationNotification called with correct parameters"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify sendCancellationNotification function structure
// ============================================================================

echo "Test 3: sendCancellationNotification function structure\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, 'function sendCancellationNotification') !== false,
    "sendCancellationNotification function is defined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'SELECT name, sic_id, aic_id FROM events') !== false,
    "Function queries event details including SIC and AIC"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'SELECT user_first, user_last FROM users WHERE user_id') !== false,
    "Function queries user who cancelled"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify SIC notification preference checking
// ============================================================================

echo "Test 4: Scout in Charge notification preference checking\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, 'sic_id') !== false,
    "Function checks for Scout in Charge ID"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*\$sic_id\s*>\s*0\s*\)/', $registerContents),
    "Function checks if SIC exists (sic_id > 0)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'notif_preferences') !== false,
    "Function queries notification preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, "'canc'") !== false || strpos($registerContents, '"canc"') !== false,
    "Function checks 'canc' preference"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'json_decode') !== false,
    "Function decodes JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify AIC notification preference checking
// ============================================================================

echo "Test 5: Adult in Charge notification preference checking\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, 'aic_id') !== false,
    "Function checks for Adult in Charge ID"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*\$aic_id\s*>\s*0\s*\)/', $registerContents),
    "Function checks if AIC exists (aic_id > 0)"
)) {
    $passed++;
} else {
    $failed++;
}

// Count occurrences of canc check - should check for both SIC and AIC
$canc_checks = substr_count($registerContents, "'canc'") + substr_count($registerContents, '"canc"');
if (assert_true(
    $canc_checks >= 2,
    "Function checks 'canc' preference for both SIC and AIC (found $canc_checks checks)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify default opt-in behavior
// ============================================================================

echo "Test 6: Default opt-in behavior\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/\$send_to_sic\s*=\s*true/', $registerContents),
    "Default is to send to SIC (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\$send_to_aic\s*=\s*true/', $registerContents),
    "Default is to send to AIC (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, '=== false') !== false,
    "Only skips email when explicitly set to false"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify email sending logic
// ============================================================================

echo "Test 7: Email sending logic\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, 'PHPMailer') !== false,
    "Function uses PHPMailer"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, '$recipients') !== false,
    "Function builds recipients array"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*empty\s*\(\s*\$recipients\s*\)\s*\)/', $registerContents),
    "Function checks if there are recipients before sending"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'AddAddress') !== false,
    "Function adds email addresses to mail"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'cancelled') !== false,
    "Email mentions cancellation"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify email content
// ============================================================================

echo "Test 8: Email content and personalization\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, '$user_name') !== false,
    "Email includes user name who cancelled"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, '$event_name') !== false,
    "Email includes event name"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'Scout in Charge') !== false &&
    strpos($registerContents, 'Adult in Charge') !== false,
    "Email mentions SIC and AIC roles"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'Event.php?id=') !== false,
    "Email includes link to event page"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'opt out') !== false,
    "Email mentions how to opt out"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify error handling and logging
// ============================================================================

echo "Test 9: Error handling and logging\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, 'error_log') !== false,
    "Function includes error logging"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*!\s*\$event\s*\)/', $registerContents),
    "Function checks if event exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*!\s*\$user\s*\)/', $registerContents),
    "Function checks if user exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'opted out') !== false,
    "Function logs when users opt out"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'No recipients') !== false,
    "Function handles case with no recipients"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify security - prepared statements
// ============================================================================

echo "Test 10: Security - SQL injection protection\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, '->prepare(') !== false,
    "Function uses prepared statements"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, '->bind_param(') !== false,
    "Function uses parameter binding"
)) {
    $passed++;
} else {
    $failed++;
}

// Count prepared statements in sendCancellationNotification
$prepare_count = substr_count($registerContents, '->prepare(');
if (assert_true(
    $prepare_count >= 4,
    "Multiple prepared statements used (found $prepare_count)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify NULL preference handling
// ============================================================================

echo "Test 11: NULL preference handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/if\s*\(\s*\$sic\[.notif_preferences.\]\s*\)/', $registerContents) ||
    preg_match('/if\s*\(\s*\$sic->notif_preferences\s*\)/', $registerContents),
    "Checks if SIC notif_preferences exists before decoding"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/if\s*\(\s*\$aic\[.notif_preferences.\]\s*\)/', $registerContents) ||
    preg_match('/if\s*\(\s*\$aic->notif_preferences\s*\)/', $registerContents),
    "Checks if AIC notif_preferences exists before decoding"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, 'isset($prefs[\'canc\'])') !== false,
    "Checks if 'canc' key exists in preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify integration with existing code
// ============================================================================

echo "Test 12: Integration with existing cancellation flow\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/UPDATE\s+registration\s+SET\s+attending\s*=\s*\?\s+WHERE/', $registerContents),
    "Cancellation still updates registration table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, "'status' => 'Success'") !== false &&
    strpos($registerContents, "'signed_up' => 'Cancelled'") !== false,
    "Cancellation returns success status"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify notification is called after database update
$cancel_section = substr($registerContents, strpos($registerContents, 'if ($action=="cancel")'));
$update_pos = strpos($cancel_section, 'UPDATE registration');
$notify_pos = strpos($cancel_section, 'sendCancellationNotification');

if (assert_true(
    $update_pos !== false && $notify_pos !== false && $update_pos < $notify_pos,
    "Notification sent after database update"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Verify recipient role tracking
// ============================================================================

echo "Test 13: Recipient role tracking\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($registerContents, "'role' => 'Scout in Charge'") !== false,
    "SIC role is tracked in recipients array"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, "'role' => 'Adult in Charge'") !== false,
    "AIC role is tracked in recipients array"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, "'name' =>") !== false,
    "Recipient name is tracked"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($registerContents, "'email' =>") !== false,
    "Recipient email is tracked"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: Verify User.php will display the new preference
// ============================================================================

echo "Test 14: User.php preference display integration\n";
echo str_repeat("-", 60) . "\n";

$getUserFile = PUBLIC_HTML_DIR . '/api/getuser.php';
$getUserContents = file_get_contents($getUserFile);

if (assert_true(
    strpos($getUserContents, 'foreach ($notification_types as') !== false,
    "getuser.php iterates through all notification types"
)) {
    $passed++;
} else {
    $failed++;
}

// Since we added to notification_types.php, getuser.php will automatically display it
if (assert_true(
    strpos($getUserContents, 'notifPrefCheckbox') !== false,
    "getuser.php creates checkboxes for all preferences"
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
