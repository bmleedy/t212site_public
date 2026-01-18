<?php
/**
 * Address Update Unit Test
 *
 * Tests the address update functionality for the User page.
 * Validates that users can view and edit their family address.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Address Update Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify getOtherUserInfo.php creates address input fields in edit mode
// ============================================================================

echo "Test 1: getOtherUserInfo.php address field generation\n";
echo str_repeat("-", 60) . "\n";

$getOtherUserInfoFile = PUBLIC_HTML_DIR . '/api/getOtherUserInfo.php';
if (assert_file_exists($getOtherUserInfoFile, "getOtherUserInfo.php exists")) {
    $passed++;
} else {
    $failed++;
}

$getOtherUserInfoContents = file_get_contents($getOtherUserInfoFile);

if (assert_true(
    strpos($getOtherUserInfoContents, 'id="address1"') !== false,
    "getOtherUserInfo.php creates address1 input field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'id="address2"') !== false,
    "getOtherUserInfo.php creates address2 input field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'id="city"') !== false,
    "getOtherUserInfo.php creates city input field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'id="zip"') !== false,
    "getOtherUserInfo.php creates zip input field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'getStateDDL') !== false,
    "getOtherUserInfo.php creates state dropdown"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'id="family_id"') !== false,
    "getOtherUserInfo.php includes hidden family_id field"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify User.html submits address fields
// ============================================================================

echo "Test 2: User.html submits address fields\n";
echo str_repeat("-", 60) . "\n";

$userTemplateFile = PUBLIC_HTML_DIR . '/templates/User.html';
$userTemplateContents = file_get_contents($userTemplateFile);

if (assert_true(
    strpos($userTemplateContents, '"address1": $(\'#address1\').val()') !== false,
    "User.html submits address1 field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"address2": $(\'#address2\').val()') !== false,
    "User.html submits address2 field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"city": $(\'#city\').val()') !== false,
    "User.html submits city field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"state": $(\'#state\').val()') !== false,
    "User.html submits state field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"zip": $(\'#zip\').val()') !== false,
    "User.html submits zip field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($userTemplateContents, '"family_id": family_id') !== false ||
    strpos($userTemplateContents, '"family_id": $(\'#family_id\').val()') !== false,
    "User.html submits family_id field"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify updateuser.php handles address updates
// ============================================================================

echo "Test 3: updateuser.php handles address updates\n";
echo str_repeat("-", 60) . "\n";

$updateUserFile = PUBLIC_HTML_DIR . '/api/updateuser.php';
$updateUserContents = file_get_contents($updateUserFile);

if (assert_true(
    strpos($updateUserContents, "array_key_exists(\"address1\", \$_POST)") !== false,
    "updateuser.php checks for address1 in POST"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, "array_key_exists(\"family_id\", \$_POST)") !== false,
    "updateuser.php checks for family_id in POST"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, 'UPDATE families SET') !== false,
    "updateuser.php updates families table"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, 'address1=?') !== false &&
    strpos($updateUserContents, 'address2=?') !== false &&
    strpos($updateUserContents, 'city=?') !== false &&
    strpos($updateUserContents, 'state=?') !== false &&
    strpos($updateUserContents, 'zip=?') !== false,
    "updateuser.php updates all address fields"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, 'WHERE family_id=?') !== false,
    "updateuser.php uses family_id in WHERE clause"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Security checks for address update
// ============================================================================

echo "Test 4: Security checks for address update\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateUserContents, '$addr_stmt = $mysqli->prepare') !== false,
    "updateuser.php uses prepared statement for address update"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, '$addr_stmt->bind_param') !== false,
    "updateuser.php uses parameter binding for address update"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, '$user_type != "Scout"') !== false,
    "updateuser.php only updates address for non-scouts"
)) {
    $passed++;
} else {
    $failed++;
}

// Check XSS protection in getOtherUserInfo.php
if (assert_true(
    strpos($getOtherUserInfoContents, 'escape_html($address1)') !== false,
    "getOtherUserInfo.php escapes address1 output"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'escape_html($city)') !== false,
    "getOtherUserInfo.php escapes city output"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Address update logging
// ============================================================================

echo "Test 5: Address update activity logging\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($updateUserContents, "log_activity") !== false &&
    strpos($updateUserContents, "'update_address'") !== false,
    "updateuser.php logs address update activity"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($updateUserContents, "'family_id' => \$family_id") !== false,
    "updateuser.php includes family_id in activity log"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Address display in read mode
// ============================================================================

echo "Test 6: Address display in read mode\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getOtherUserInfoContents, '<label>Address</label>') !== false,
    "getOtherUserInfo.php has Address label"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getOtherUserInfoContents, 'if ($edit)') !== false,
    "getOtherUserInfo.php checks edit mode for address display"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Integration check - end-to-end flow
// ============================================================================

echo "Test 7: End-to-end integration checks\n";
echo str_repeat("-", 60) . "\n";

$integration_points = array(
    array(
        'check' => strpos($getOtherUserInfoContents, 'id="address1"') !== false,
        'desc' => 'getOtherUserInfo.php creates address fields'
    ),
    array(
        'check' => strpos($userTemplateContents, '"address1"') !== false,
        'desc' => 'User.html submits address fields'
    ),
    array(
        'check' => strpos($updateUserContents, 'UPDATE families') !== false,
        'desc' => 'updateuser.php updates families table'
    ),
    array(
        'check' => strpos($updateUserContents, '$addr_stmt->bind_param') !== false,
        'desc' => 'updateuser.php uses secure parameter binding'
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
    "All address update integration points are connected"
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
