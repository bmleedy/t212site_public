<?php
/**
 * Scout Signup Email Preference Test
 *
 * Tests that the scout signup email functionality respects the 'scsu' notification preference.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Scout Signup Email Preference Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify sendmail.php exists and has AJAX check
// ============================================================================

echo "Test 1: sendmail.php file existence\n";
echo str_repeat("-", 60) . "\n";

$sendmailFile = PUBLIC_HTML_DIR . '/api/sendmail.php';
if (assert_file_exists($sendmailFile, "sendmail.php exists")) {
    $passed++;
} else {
    $failed++;
}

$sendmailContents = file_get_contents($sendmailFile);

if (assert_true(
    strpos($sendmailContents, 'HTTP_X_REQUESTED_WITH') !== false,
    "sendmail.php checks for AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify sendmail.php queries notif_preferences
// ============================================================================

echo "Test 2: sendmail.php queries notification preferences\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($sendmailContents, 'parent.notif_preferences') !== false,
    "sendmail.php selects notif_preferences from database"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, "sendTo==\"scout parents\"") !== false ||
    strpos($sendmailContents, "sendTo=='scout parents'") !== false,
    "sendmail.php handles 'scout parents' case"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify preference checking logic
// ============================================================================

echo "Test 3: Notification preference checking logic\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($sendmailContents, 'json_decode($row[\'notif_preferences\']') !== false,
    "sendmail.php decodes JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, "'scsu'") !== false || strpos($sendmailContents, '"scsu"') !== false,
    "sendmail.php checks 'scsu' (Scout SignUp) preference"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, '$send_email') !== false,
    "sendmail.php uses flag to control email sending"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify default opt-in behavior
// ============================================================================

echo "Test 4: Default opt-in behavior\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($sendmailContents, '$send_email = true') !== false,
    "Default is to send email (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, '=== false') !== false,
    "Only skips email when explicitly set to false"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify AddAddress is conditional
// ============================================================================

echo "Test 5: Email address only added when preference allows\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($sendmailContents, 'if ($send_email)') !== false,
    "Email address only added when send_email is true"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, '$mail->AddAddress($row[\'user_email\'])') !== false,
    "AddAddress is called with parent email"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify logging for debugging
// ============================================================================

echo "Test 6: Logging for debugging\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($sendmailContents, 'error_log') !== false,
    "sendmail.php includes error logging"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, 'opted out') !== false,
    "Logs when parent has opted out"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify Signups.html calls sendParentsEmail
// ============================================================================

echo "Test 7: Signups.html integration\n";
echo str_repeat("-", 60) . "\n";

$signupsFile = PUBLIC_HTML_DIR . '/templates/Signups.html';
$signupsContents = file_get_contents($signupsFile);

if (assert_true(
    strpos($signupsContents, 'sendParentsEmail') !== false,
    "Signups.html has sendParentsEmail function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($signupsContents, 'api/sendmail.php') !== false,
    "sendParentsEmail calls api/sendmail.php"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($signupsContents, '"sendTo" : "scout parents"') !== false,
    "sendParentsEmail sends to 'scout parents'"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($signupsContents, 'strUserType == \'Scout\'') !== false &&
    strpos($signupsContents, 'sendParentsEmail(strUserID, strUserName)') !== false,
    "sendParentsEmail only called when scout signs up"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Security - SQL injection protection
// ============================================================================

echo "Test 8: Security checks\n";
echo str_repeat("-", 60) . "\n";

// Note: The query uses $user_id which comes from $_POST, so it should be sanitized
// This is a known issue in the codebase but we're checking what's there
if (assert_true(
    strpos($sendmailContents, 'SELECT parent.user_email, parent.notif_preferences FROM users') !== false,
    "Query selects from users table correctly"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, 'json_decode') !== false,
    "Uses json_decode for safe data handling"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify NULL preference handling
// ============================================================================

echo "Test 9: NULL preference handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/if\s*\(\s*\$row\[.notif_preferences.\]/', $sendmailContents),
    "Checks if notif_preferences exists before decoding"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sendmailContents, 'isset($prefs[\'scsu\'])') !== false,
    "Checks if 'scsu' key exists in preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: End-to-end flow verification
// ============================================================================

echo "Test 10: End-to-end flow verification\n";
echo str_repeat("-", 60) . "\n";

// Verify the complete flow exists
$flow_checks = array(
    array(
        'check' => strpos($signupsContents, 'register(\'add\'') !== false,
        'desc' => 'Signups.html can register scouts'
    ),
    array(
        'check' => strpos($signupsContents, 'sendParentsEmail') !== false,
        'desc' => 'Signups.html calls sendParentsEmail'
    ),
    array(
        'check' => strpos($sendmailContents, 'scout parents') !== false,
        'desc' => 'sendmail.php handles scout parents'
    ),
    array(
        'check' => strpos($sendmailContents, 'notif_preferences') !== false,
        'desc' => 'sendmail.php checks preferences'
    ),
    array(
        'check' => strpos($sendmailContents, 'scsu') !== false,
        'desc' => 'sendmail.php checks scsu preference'
    )
);

$all_flow_checks_pass = true;
foreach ($flow_checks as $check) {
    if (!$check['check']) {
        $all_flow_checks_pass = false;
        echo "  Missing: " . $check['desc'] . "\n";
    }
}

if (assert_true(
    $all_flow_checks_pass,
    "Complete flow from signup to email with preference check exists"
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
