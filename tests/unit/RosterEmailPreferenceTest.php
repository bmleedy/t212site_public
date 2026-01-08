<?php
/**
 * Roster Email Preference Test
 *
 * Tests that the roster email functionality respects the 'rost' notification preference.
 * Roster emails are broadcast emails sent from the adult roster page buttons.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Roster Email Preference Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify getadults.php exists and has AJAX check
// ============================================================================

echo "Test 1: getadults.php file existence\n";
echo str_repeat("-", 60) . "\n";

$getadultsFile = PUBLIC_HTML_DIR . '/api/getadults.php';
if (assert_file_exists($getadultsFile, "getadults.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getadultsContents = file_get_contents($getadultsFile);

if (assert_true(
    strpos($getadultsContents, 'require_ajax()') !== false,
    "getadults.php checks for AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify getadults.php queries notif_preferences
// ============================================================================

echo "Test 2: getadults.php queries notification preferences\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getadultsContents, 'notif_preferences') !== false,
    "getadults.php selects notif_preferences from database"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, "user_type not in ('Scout'") !== false,
    "getadults.php filters for adult users only"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify preference checking logic
// ============================================================================

echo "Test 3: Roster preference checking logic\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getadultsContents, 'json_decode($row->notif_preferences') !== false,
    "getadults.php decodes JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, "'rost'") !== false || strpos($getadultsContents, '"rost"') !== false,
    "getadults.php checks 'rost' (Roster) preference"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, '$include_in_roster_emails') !== false,
    "getadults.php uses flag to control email inclusion"
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
    strpos($getadultsContents, '$include_in_roster_emails = true') !== false,
    "Default is to include email (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, '=== false') !== false,
    "Only excludes email when explicitly set to false"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify email conditionally included in response
// ============================================================================

echo "Test 5: Email conditionally included in response\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getadultsContents, '$include_in_roster_emails ?') !== false,
    "Email field uses ternary operator based on preference"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/\$include_in_roster_emails\s*\?\s*(escape_html\()?\$row->user_email(\))?\s*:\s*[\'"][\'"]/s', $getadultsContents),
    "Returns email if opted in, empty string if opted out"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify ListAdults.html uses email from API
// ============================================================================

echo "Test 6: ListAdults.html email button generation\n";
echo str_repeat("-", 60) . "\n";

$listAdultsFile = PUBLIC_HTML_DIR . '/templates/ListAdults.html';
$listAdultsContents = file_get_contents($listAdultsFile);

if (assert_true(
    strpos($listAdultsContents, 'api/getadults.php') !== false,
    "ListAdults.html calls api/getadults.php"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($listAdultsContents, 'adultEmailList') !== false,
    "ListAdults.html builds adult email list"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($listAdultsContents, 'Email All Adults') !== false,
    "ListAdults.html has 'Email All Adults' button"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($listAdultsContents, 'if (element["email"])') !== false,
    "ListAdults.html checks if email exists before adding"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify NULL preference handling
// ============================================================================

echo "Test 7: NULL preference handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/if\s*\(\s*\$row->notif_preferences/', $getadultsContents),
    "Checks if notif_preferences exists before decoding"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, 'isset($prefs[\'rost\'])') !== false,
    "Checks if 'rost' key exists in preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify adult info still returned (name, phone, etc)
// ============================================================================

echo "Test 8: Adult info still returned regardless of preference\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getadultsContents, "'first' => escape_html(\$row->user_first)") !== false,
    "First name always returned"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, "'last' => escape_html(\$row->user_last)") !== false,
    "Last name always returned"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, "'phone' => \$phones") !== false,
    "Phone numbers always returned"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, "'id' => \$id") !== false,
    "User ID always returned"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify query optimization
// ============================================================================

echo "Test 9: Query optimization and structure\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getadultsContents, 'SELECT user_id, user_first, user_last, user_email, user_type, notif_preferences') !== false,
    "Query selects specific fields including notif_preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getadultsContents, 'ORDER BY user_last asc, user_first asc') !== false,
    "Query orders by last name, first name"
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
        'check' => strpos($getadultsContents, 'users') !== false,
        'desc' => 'getadults.php queries users table'
    ),
    array(
        'check' => strpos($getadultsContents, 'notif_preferences') !== false,
        'desc' => 'getadults.php retrieves preferences'
    ),
    array(
        'check' => strpos($getadultsContents, 'rost') !== false,
        'desc' => 'getadults.php checks rost preference'
    ),
    array(
        'check' => strpos($getadultsContents, 'json_encode($adults)') !== false,
        'desc' => 'getadults.php returns JSON with filtered emails'
    ),
    array(
        'check' => strpos($listAdultsContents, 'getadults.php') !== false,
        'desc' => 'ListAdults.html calls getadults.php'
    ),
    array(
        'check' => strpos($listAdultsContents, 'mailto:?bcc=') !== false,
        'desc' => 'ListAdults.html builds mailto link with BCC'
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
    "Complete flow from roster to email buttons with preference check exists"
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
