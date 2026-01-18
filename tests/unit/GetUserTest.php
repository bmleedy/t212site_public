<?php
/**
 * GetUser Unit Test
 *
 * Tests the getuser.php API functionality.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("GetUser API Tests");

$passed = 0;
$failed = 0;

$getUserFile = PUBLIC_HTML_DIR . '/api/getuser.php';
$getUserContents = file_get_contents($getUserFile);

// ============================================================================
// TEST 1: File exists and has basic structure
// ============================================================================

echo "Test 1: Basic file structure\n";
echo str_repeat("-", 60) . "\n";

if (assert_file_exists($getUserFile, "getuser.php exists")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'require_authentication') !== false,
    "getuser.php requires authentication"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Email display for all user types in non-edit mode
// ============================================================================

echo "Test 2: Email display for all user types (bug fix verification)\n";
echo str_repeat("-", 60) . "\n";

// Check that email is displayed for Scouts in non-edit mode
if (assert_true(
    strpos($getUserContents, 'if ($user_type=="Scout")') !== false &&
    strpos($getUserContents, 'escape_html($user_email)') !== false,
    "getuser.php displays email for Scouts in non-edit mode"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that email is displayed for non-Scouts in non-edit mode
// This was the bug: previously $varEmail was set to '' for non-Scouts
if (assert_true(
    strpos($getUserContents, '} else {') !== false &&
    preg_match('/else\s*\{\s*\$varEmail\s*=\s*[\'"]<p>[\'"]\.escape_html\(\$user_email\)/', $getUserContents),
    "getuser.php displays email for non-Scouts in non-edit mode"
)) {
    $passed++;
} else {
    $failed++;
}

// Verify the bug is fixed - email should NOT be set to empty for non-Scouts
if (assert_true(
    strpos($getUserContents, 'else {$varEmail=\'\';}') === false,
    "getuser.php does NOT set email to empty for non-Scouts (bug fix verified)"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Edit mode email field
// ============================================================================

echo "Test 3: Edit mode email field\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getUserContents, 'id="user_email"') !== false,
    "getuser.php creates email input field in edit mode"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'escape_html($user_email)') !== false,
    "getuser.php escapes email output"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: User type handling
// ============================================================================

echo "Test 4: User type handling\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    strpos($getUserContents, 'id="user_type"') !== false,
    "getuser.php creates user_type field"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($getUserContents, 'escape_html($user_type)') !== false,
    "getuser.php escapes user_type output"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: SA and UE users should always have full edit access
// ============================================================================

echo "Test 5: SA/UE users have full edit access (bug fix verification)\n";
echo str_repeat("-", 60) . "\n";

$userPhpFile = PUBLIC_HTML_DIR . '/User.php';
$userPhpContents = file_get_contents($userPhpFile);

// Check that sa users get wm=1 for full edit access
if (assert_true(
    strpos($userPhpContents, 'in_array("sa", $access)') !== false &&
    strpos($userPhpContents, '$wm = 1') !== false,
    "User.php gives sa users full edit access (wm=1)"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that ue users get wm=0 for full edit access
if (assert_true(
    strpos($userPhpContents, 'in_array("ue", $access)') !== false,
    "User.php checks for ue permission for full edit access"
)) {
    $passed++;
} else {
    $failed++;
}

// Check that sa, ue, AND wm users all get full edit access
if (assert_true(
    strpos($userPhpContents, 'if (in_array("sa", $access) || in_array("ue", $access) || in_array("wm", $access))') !== false,
    "User.php gives sa, ue, and wm users full edit access"
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
