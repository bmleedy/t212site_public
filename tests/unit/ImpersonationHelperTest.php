<?php
/**
 * Impersonation Helper Unit Test
 *
 * Tests the impersonation_helper.php utility to ensure it properly
 * handles user impersonation for Super Admins.
 *
 * Note: Session-dependent runtime tests are skipped in CLI mode because
 * $_SESSION superglobal doesn't work properly in PHP CLI. Full runtime
 * testing should be done via the RELEASE_TESTING_CHECKLIST.md in a browser.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Check if we're in CLI mode (sessions don't work properly)
$isCliMode = php_sapi_name() === 'cli';

// Load the impersonation helper
require_once PUBLIC_HTML_DIR . '/includes/impersonation_helper.php';

test_suite("Impersonation Helper Unit Tests");

$passed = 0;
$failed = 0;
$skipped = 0;

// ============================================================================
// TEST 1: Verify impersonation_helper.php file exists
// ============================================================================

echo "Test 1: impersonation_helper.php file existence\n";
echo str_repeat("-", 60) . "\n";

$helper_path = PUBLIC_HTML_DIR . '/includes/impersonation_helper.php';
if (assert_file_exists($helper_path, "impersonation_helper.php file exists")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 2: Verify all required functions exist
// ============================================================================

echo "\nTest 2: Required functions exist\n";
echo str_repeat("-", 60) . "\n";

$required_functions = [
    'is_impersonating',
    'get_impersonator_id',
    'get_impersonator_name',
    'get_impersonator_first_name',
    'start_impersonation',
    'stop_impersonation'
];

foreach ($required_functions as $func) {
    if (assert_true(function_exists($func), "Function $func() exists")) {
        $passed++;
    } else {
        $failed++;
    }
}

// ============================================================================
// TEST 3: Verify Impersonate.php page exists
// ============================================================================

echo "\nTest 3: Impersonate.php page exists\n";
echo str_repeat("-", 60) . "\n";

$page_path = PUBLIC_HTML_DIR . '/Impersonate.php';
if (assert_file_exists($page_path, "Impersonate.php page exists")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 4: Verify Impersonate.html template exists
// ============================================================================

echo "\nTest 4: Impersonate.html template exists\n";
echo str_repeat("-", 60) . "\n";

$template_path = PUBLIC_HTML_DIR . '/templates/Impersonate.html';
if (assert_file_exists($template_path, "Impersonate.html template exists")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 5: Verify API endpoints exist
// ============================================================================

echo "\nTest 5: API endpoints exist\n";
echo str_repeat("-", 60) . "\n";

$api_files = [
    'getusersforimpersonation.php',
    'startimpersonation.php',
    'stopimpersonation.php'
];

foreach ($api_files as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (assert_file_exists($api_path, "API $api_file exists")) {
        $passed++;
    } else {
        $failed++;
    }
}

// ============================================================================
// TEST 6: Verify activity_logger.php has impersonation detection
// ============================================================================

echo "\nTest 6: Activity logger has impersonation detection\n";
echo str_repeat("-", 60) . "\n";

$logger_content = file_get_contents(PUBLIC_HTML_DIR . '/includes/activity_logger.php');
if (assert_true(
    strpos($logger_content, 'is_impersonating') !== false,
    "activity_logger.php contains impersonation detection"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($logger_content, 'impersonated_by') !== false,
    "activity_logger.php adds impersonated_by to values"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 7: Verify authHeader.php has impersonation banner
// ============================================================================

echo "\nTest 7: authHeader.php has impersonation banner\n";
echo str_repeat("-", 60) . "\n";

$header_content = file_get_contents(PUBLIC_HTML_DIR . '/includes/authHeader.php');
if (assert_true(
    strpos($header_content, 'impersonation-banner') !== false,
    "authHeader.php contains impersonation banner"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($header_content, 'stopimpersonation.php') !== false,
    "authHeader.php links to stop impersonation endpoint"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($header_content, 'impersonation_helper.php') !== false,
    "authHeader.php includes impersonation helper"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 8: Verify sidebar menus have impersonation link
// ============================================================================

echo "\nTest 8: Sidebar menus have impersonation link\n";
echo str_repeat("-", 60) . "\n";

$sidebar_content = file_get_contents(PUBLIC_HTML_DIR . '/includes/m_sidebar.html');
if (assert_true(
    strpos($sidebar_content, 'Impersonate.php') !== false,
    "m_sidebar.html contains link to Impersonate.php"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($sidebar_content, 'Impersonate User') !== false,
    "m_sidebar.html contains 'Impersonate User' menu text"
)) {
    $passed++;
} else {
    $failed++;
}

$mobile_menu_content = file_get_contents(PUBLIC_HTML_DIR . '/includes/mobile_menu.html');
if (assert_true(
    strpos($mobile_menu_content, 'Impersonate.php') !== false,
    "mobile_menu.html contains link to Impersonate.php"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 9: Verify Login.php handles impersonation logout
// ============================================================================

echo "\nTest 9: Login.php handles impersonation logout\n";
echo str_repeat("-", 60) . "\n";

$login_content = file_get_contents(PUBLIC_HTML_DIR . '/login/classes/Login.php');
if (assert_true(
    strpos($login_content, 'is_impersonating') !== false,
    "Login.php checks for impersonation during logout"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($login_content, 'stopimpersonation.php') !== false,
    "Login.php redirects to stop impersonation during logout"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 10: Verify Impersonate.php has SA permission check
// ============================================================================

echo "\nTest 10: Impersonate.php has SA permission check\n";
echo str_repeat("-", 60) . "\n";

$impersonate_page = file_get_contents(PUBLIC_HTML_DIR . '/Impersonate.php');
if (assert_true(
    strpos($impersonate_page, "in_array('sa'") !== false,
    "Impersonate.php checks for 'sa' permission"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($impersonate_page, 'Access Denied') !== false,
    "Impersonate.php shows Access Denied for non-SA users"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 11: Verify API has proper security checks
// ============================================================================

echo "\nTest 11: API endpoints have proper security\n";
echo str_repeat("-", 60) . "\n";

$start_api = file_get_contents(PUBLIC_HTML_DIR . '/api/startimpersonation.php');
if (assert_true(
    strpos($start_api, "require_permission(['sa'])") !== false,
    "startimpersonation.php requires 'sa' permission"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($start_api, 'csrf_token') !== false,
    "startimpersonation.php validates CSRF token"
)) {
    $passed++;
} else {
    $failed++;
}

$get_users_api = file_get_contents(PUBLIC_HTML_DIR . '/api/getusersforimpersonation.php');
if (assert_true(
    strpos($get_users_api, "require_permission(['sa'])") !== false,
    "getusersforimpersonation.php requires 'sa' permission"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// TEST 12: Verify impersonation helper has proper session variable handling
// ============================================================================

echo "\nTest 12: Impersonation helper handles session variables\n";
echo str_repeat("-", 60) . "\n";

$helper_content = file_get_contents(PUBLIC_HTML_DIR . '/includes/impersonation_helper.php');

$session_vars = [
    'original_user_id',
    'original_user_name',
    'original_user_first',
    'original_user_access',
    'original_user_type',
    'original_family_id'
];

foreach ($session_vars as $var) {
    if (assert_true(
        strpos($helper_content, $var) !== false,
        "Helper manages session variable '$var'"
    )) {
        $passed++;
    } else {
        $failed++;
    }
}

// ============================================================================
// TEST 13: Verify template has required UI elements
// ============================================================================

echo "\nTest 13: Template has required UI elements\n";
echo str_repeat("-", 60) . "\n";

$template_content = file_get_contents(PUBLIC_HTML_DIR . '/templates/Impersonate.html');

if (assert_true(
    strpos($template_content, 'startImpersonation') !== false,
    "Template has startImpersonation function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($template_content, 'filterUsers') !== false,
    "Template has filterUsers function"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($template_content, 'warning-box') !== false,
    "Template has warning box"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($template_content, 'All actions during impersonation are logged') !== false,
    "Template warns that actions are logged"
)) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Print final summary
// ============================================================================

echo "\n";
if ($isCliMode) {
    echo "Note: Session-dependent runtime tests skipped in CLI mode.\n";
    echo "      Use RELEASE_TESTING_CHECKLIST.md Section 8A for full testing.\n";
}

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
