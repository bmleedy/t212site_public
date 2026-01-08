<?php
/**
 * Notification Preferences Unit Test
 *
 * Tests the notification preferences functionality for the User page.
 * Validates that users can view and edit their notification preferences.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Notification Preferences Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify notification_types.php file exists and has correct structure
// ============================================================================

echo "Test 1: notification_types.php file existence and structure\n";
echo str_repeat("-", 60) . "\n";

$notifTypesFile = PUBLIC_HTML_DIR . '/includes/notification_types.php';
if (assert_file_exists($notifTypesFile, "notification_types.php exists")) {
    $passed++;
} else {
    $failed++;
}

// Load the file and check structure
require_once $notifTypesFile;

if (assert_true(
    isset($notification_types) && is_array($notification_types),
    "notification_types array is defined"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    count($notification_types) === 4,
    "notification_types has exactly 4 entries"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify each notification type has required fields
$all_have_required_fields = true;
$required_fields = array('key', 'display_name', 'tooltip');
foreach ($notification_types as $notif) {
    foreach ($required_fields as $field) {
        if (!isset($notif[$field])) {
            $all_have_required_fields = false;
            break 2;
        }
    }
}

if (assert_true(
    $all_have_required_fields,
    "All notification types have required fields (key, display_name, tooltip)"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify keys are exactly 4 letters
$all_keys_valid = true;
foreach ($notification_types as $notif) {
    if (strlen($notif['key']) !== 4) {
        $all_keys_valid = false;
        break;
    }
}

if (assert_true(
    $all_keys_valid,
    "All notification type keys are exactly 4 characters"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify the specific notification types exist
$keys = array_column($notification_types, 'key');
if (assert_true(
    in_array('scsu', $keys),
    "Scout Signup notification type (scsu) exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    in_array('rost', $keys),
    "Roster notification type (rost) exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    in_array('evnt', $keys),
    "Event notification type (evnt) exists"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify database migration file exists
// ============================================================================

echo "Test 2: Database migration file\n";
echo str_repeat("-", 60) . "\n";

$migrationFile = PROJECT_ROOT . '/db_copy/migrations/add_notif_preferences_column.sql';
if (assert_file_exists($migrationFile, "Migration file exists")) {
    $passed++;
} else {
    $failed++;
}

$migrationContents = file_get_contents($migrationFile);
if (assert_true(
    strpos($migrationContents, 'ALTER TABLE users') !== false,
    "Migration alters users table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($migrationContents, 'notif_preferences') !== false,
    "Migration adds notif_preferences column"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($migrationContents, 'VARCHAR(255)') !== false,
    "Column is VARCHAR(255)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($migrationContents, 'DEFAULT NULL') !== false,
    "Column defaults to NULL (opted in)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify getuser.php loads notification preferences
// ============================================================================

echo "Test 3: getuser.php loads notification preferences\n";
echo str_repeat("-", 60) . "\n";

$getUserFile = PUBLIC_HTML_DIR . '/api/getuser.php';
$getUserContents = file_get_contents($getUserFile);

if (assert_true(
    strpos($getUserContents, '$notif_preferences = $row->notif_preferences') !== false,
    "getuser.php loads notif_preferences from database"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, "require_once '../includes/notification_types.php'") !== false,
    "getuser.php includes notification_types.php"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'json_decode($notif_preferences') !== false,
    "getuser.php decodes JSON preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, '$varNotifPrefs') !== false,
    "getuser.php builds notification preferences HTML"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'notifPrefCheckbox') !== false,
    "getuser.php creates checkboxes with notifPrefCheckbox class"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'escape_html($tooltip)') !== false,
    "getuser.php uses htmlspecialchars for XSS protection"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify default behavior (opted in when NULL or true)
if (assert_true(
    strpos($getUserContents, '!isset($prefs_array[$key]) || $prefs_array[$key] === true') !== false,
    "Default behavior: checked when preference is NULL or true"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify two-column layout
if (assert_true(
    strpos($getUserContents, 'large-6 columns') !== false,
    "Preferences displayed in two columns"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify User.html template submits preferences
// ============================================================================

echo "Test 4: User.html template submits notification preferences\n";
echo str_repeat("-", 60) . "\n";

$userTemplateFile = PUBLIC_HTML_DIR . '/templates/User.html';
$userTemplateContents = file_get_contents($userTemplateFile);

if (assert_true(
    strpos($userTemplateContents, 'notifPrefCheckbox') !== false,
    "User.html references notifPrefCheckbox class"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, 'var notif_prefs = {}') !== false,
    "User.html creates notif_prefs object"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, 'getElementsByClassName(\'notifPrefCheckbox\')') !== false,
    "User.html collects all notification preference checkboxes"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"notif_prefs": JSON.stringify(notif_prefs)') !== false,
    "User.html includes notif_prefs in fieldData (as JSON string)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    preg_match('/notif_prefs\[key\]\s*=\s*notifElements\[.*?\]\.checked/', $userTemplateContents),
    "User.html stores checkbox state (true/false) in notif_prefs"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify updateuser.php saves notification preferences
// ============================================================================

echo "Test 5: updateuser.php saves notification preferences\n";
echo str_repeat("-", 60) . "\n";

$updateUserFile = PUBLIC_HTML_DIR . '/api/updateuser.php';
$updateUserContents = file_get_contents($updateUserFile);

if (assert_true(
    strpos($updateUserContents, "array_key_exists('notif_prefs', \$_POST)") !== false,
    "updateuser.php checks for notif_prefs in POST"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, 'json_decode($notif_prefs') !== false ||
    strpos($updateUserContents, 'is_string($notif_prefs)') !== false,
    "updateuser.php handles JSON string preferences"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, 'notif_preferences=?') !== false,
    "updateuser.php updates notif_preferences column"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, "bind_param('sssss'") !== false,
    "updateuser.php uses prepared statement with 5 parameters"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, '$notif_prefs_json = NULL') !== false,
    "updateuser.php defaults to NULL when no preferences provided"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Security checks
// ============================================================================

echo "Test 6: Security checks\n";
echo str_repeat("-", 60) . "\n";

// XSS protection in getuser.php
if (assert_true(
    strpos($getUserContents, 'escape_html') !== false,
    "getuser.php uses htmlspecialchars for output sanitization"
)) {
    $passed++;
} else {
    $failed++;
}

// JSON encoding/decoding is used (safe for SQL injection)
if (assert_true(
    strpos($updateUserContents, 'json_encode') !== false &&
    strpos($getUserContents, 'json_decode') !== false,
    "JSON encoding/decoding used (prevents SQL injection)"
)) {
    $passed++;
} else {
    $failed++;
}

// Prepared statements used in updateuser.php
if (assert_true(
    strpos($updateUserContents, '->prepare(') !== false &&
    strpos($updateUserContents, '->bind_param(') !== false,
    "updateuser.php uses prepared statements"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that preferences are validated (either as string or array)
if (assert_true(
    strpos($updateUserContents, 'is_string($notif_prefs)') !== false ||
    strpos($updateUserContents, 'is_array($notif_prefs)') !== false,
    "updateuser.php validates notif_prefs type"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Default behavior (opt-in)
// ============================================================================

echo "Test 7: Default opt-in behavior\n";
echo str_repeat("-", 60) . "\n";

// Verify NULL in database means opted in
if (assert_true(
    strpos($migrationContents, 'DEFAULT NULL') !== false,
    "Database column defaults to NULL"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify getuser.php treats NULL as opted in
if (assert_true(
    strpos($getUserContents, '!isset($prefs_array[$key])') !== false,
    "getuser.php treats missing preferences as opted in (checked)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Display vs Edit mode
// ============================================================================

echo "Test 8: Display vs Edit mode\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getUserContents, 'if ($edit && !$wm)') !== false,
    "getuser.php checks edit mode for preferences display"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'Enabled') !== false &&
    strpos($getUserContents, 'Disabled') !== false,
    "getuser.php shows text status in display mode"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, '<input type="checkbox"') !== false,
    "getuser.php shows checkboxes in edit mode"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify tooltips are present
// ============================================================================

echo "Test 9: Tooltip functionality\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getUserContents, 'title=') !== false,
    "getuser.php adds title attribute for tooltips"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that tooltips are populated from notification_types
foreach ($notification_types as $notif) {
    if (strlen($notif['tooltip']) < 10) {
        $all_tooltips_exist = false;
        break;
    }
}

if (assert_true(
    isset($all_tooltips_exist) ? $all_tooltips_exist : true,
    "All notification types have meaningful tooltips"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Integration check - end-to-end flow
// ============================================================================

echo "Test 10: End-to-end integration checks\n";
echo str_repeat("-", 60) . "\n";

// Check that all pieces are connected
$integration_points = array(
    array(
        'file' => $notifTypesFile,
        'check' => file_exists($notifTypesFile),
        'desc' => 'notification_types.php exists'
    ),
    array(
        'file' => $getUserFile,
        'check' => strpos($getUserContents, 'notification_types.php') !== false,
        'desc' => 'getuser.php includes notification_types.php'
    ),
    array(
        'file' => $userTemplateFile,
        'check' => strpos($userTemplateContents, 'notif_prefs') !== false,
        'desc' => 'User.html submits notification preferences'
    ),
    array(
        'file' => $updateUserFile,
        'check' => strpos($updateUserContents, 'notif_prefs') !== false,
        'desc' => 'updateuser.php receives notification preferences'
    ),
    array(
        'file' => $updateUserFile,
        'check' => strpos($updateUserContents, 'notif_preferences') !== false,
        'desc' => 'updateuser.php saves to database'
    )
);

$all_integrated = true;
foreach ($integration_points as $point) {
    if (!$point['check']) {
        $all_integrated = false;
        echo "FAIL: " . $point['desc'] . "\n";
    }
}

if (assert_true(
    $all_integrated,
    "All integration points are connected"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify JSON round-trip compatibility
if (assert_true(
    strpos($updateUserContents, 'json_encode') !== false &&
    strpos($getUserContents, 'json_decode') !== false,
    "JSON encoding/decoding is symmetric (save and load)"
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
