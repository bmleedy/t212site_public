<?php
/**
 * Event Email Preference Test
 *
 * Tests that the event email functionality respects the 'evnt' notification preference.
 * Event emails are sent via mailto link to all registered participants and their parents.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Event Email Preference Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify getevent.php exists and has AJAX check
// ============================================================================

echo "Test 1: getevent.php file existence\n";
echo str_repeat("-", 60) . "\n";

$geteventFile = PUBLIC_HTML_DIR . '/api/getevent.php';
if (assert_file_exists($geteventFile, "getevent.php exists")) {
    $passed++;
} else {
    $failed++;
}

$geteventContents = file_get_contents($geteventFile);

if (assert_true(
    strpos($geteventContents, 'require_ajax()') !== false,
    "getevent.php checks for AJAX request"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify getevent.php queries notif_preferences for parents
// ============================================================================

echo "Test 2: getevent.php queries notification preferences (parents)\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/SELECT.*notif_preferences.*FROM users.*WHERE.*user_type.*Scout.*family_id/s', $geteventContents),
    "getevent.php selects notif_preferences for parents"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, 'json_decode($row3[\'notif_preferences\']') !== false,
    "getevent.php decodes parent JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify getevent.php queries notif_preferences for adults
// ============================================================================

echo "Test 3: getevent.php queries notification preferences (adults)\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    preg_match('/SELECT.*notif_preferences.*FROM registration.*users.*user_type.*Scout/s', $geteventContents),
    "getevent.php selects notif_preferences for adult attendees"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, 'json_decode($row[\'notif_preferences\']') !== false,
    "getevent.php decodes adult JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify 'evnt' preference checking logic
// ============================================================================

echo "Test 4: Event preference checking logic\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($geteventContents, "'evnt'") !== false || strpos($geteventContents, '"evnt"') !== false,
    "getevent.php checks 'evnt' (Event) preference"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, '$include_email') !== false,
    "getevent.php uses flag to control email inclusion"
)) {
    $passed++;
} else {
    $failed++;
}

// Count occurrences - should check preference for both parents and adults
$evnt_count = substr_count($geteventContents, "'evnt'") + substr_count($geteventContents, '"evnt"');
if (assert_true(
    $evnt_count >= 2,
    "getevent.php checks 'evnt' preference for both parents and adults (found $evnt_count checks)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify default opt-in behavior
// ============================================================================

echo "Test 5: Default opt-in behavior\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($geteventContents, '$include_email = true') !== false,
    "Default is to include email (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, '=== false') !== false,
    "Only excludes email when explicitly set to false"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify email only added when preference allows
// ============================================================================

echo "Test 6: Email only added when preference allows\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($geteventContents, 'if ($include_email') !== false,
    "Email only added when include_email is true"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, '$mailto = $mailto . $sep') !== false,
    "Emails are added to mailto link"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify mailto link is generated
// ============================================================================

echo "Test 7: Mailto link generation\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($geteventContents, 'mailto:') !== false,
    "getevent.php generates mailto link"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, 'Send Email to Attending Scouts & Parents') !== false,
    "Mailto link has descriptive text"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, 'showMailto') !== false,
    "Mailto link only shown when appropriate (showMailto check)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify NULL preference handling
// ============================================================================

echo "Test 8: NULL preference handling\n";
echo str_repeat("-", 60) . "\n";

// Check for both parent and adult preference handling
$null_checks = 0;
if (preg_match_all('/if\s*\(\s*\$row(?:3)?\[.notif_preferences.\]/', $geteventContents, $matches)) {
    $null_checks = count($matches[0]);
}

if (assert_true(
    $null_checks >= 2,
    "Checks if notif_preferences exists before decoding (found $null_checks checks)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, 'isset($prefs[\'evnt\'])') !== false,
    "Checks if 'evnt' key exists in preferences"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify scout emails always included
// ============================================================================

echo "Test 9: Scout emails always included (not filtered)\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($geteventContents, '$mailto = $mailto . $sep . $row[\'user_email\'];') !== false,
    "Scout emails added to mailto without preference check"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($geteventContents, "u.user_type='Scout'") !== false,
    "getevent.php queries scout attendees"
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
        'check' => strpos($geteventContents, 'registration') !== false,
        'desc' => 'getevent.php queries registration table'
    ),
    array(
        'check' => strpos($geteventContents, 'attendingScouts') !== false,
        'desc' => 'getevent.php builds attendingScouts array'
    ),
    array(
        'check' => strpos($geteventContents, 'attendingAdults') !== false,
        'desc' => 'getevent.php builds attendingAdults array'
    ),
    array(
        'check' => strpos($geteventContents, 'notif_preferences') !== false,
        'desc' => 'getevent.php checks preferences'
    ),
    array(
        'check' => strpos($geteventContents, 'evnt') !== false,
        'desc' => 'getevent.php checks evnt preference'
    ),
    array(
        'check' => strpos($geteventContents, 'mailto') !== false,
        'desc' => 'getevent.php builds mailto link'
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
    "Complete flow from registration to mailto with preference check exists"
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
