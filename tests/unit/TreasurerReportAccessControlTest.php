<?php
/**
 * Treasurer Report Access Control Unit Test
 *
 * Tests for the treasurer report functionality:
 * - api/gettreasurerreport.php
 * - TreasurerReport.php
 * - templates/TreasurerReport.html
 *
 * These are static analysis tests that verify file structure,
 * security patterns, and code quality without database access.
 */

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Treasurer Report Access Control Tests");

// =============================================================================
// Test 1: Treasurer report files exist
// =============================================================================
echo "\n--- Test 1: Treasurer Report Files Existence ---\n";

$files_to_check = [
    'api/gettreasurerreport.php' => 'API file',
    'TreasurerReport.php' => 'Page file',
    'templates/TreasurerReport.html' => 'Template file'
];

foreach ($files_to_check as $file => $description) {
    $path = PUBLIC_HTML_DIR . '/' . $file;
    if (assert_file_exists($path, "$description exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 2: gettreasurerreport.php requires authentication
// =============================================================================
echo "\n--- Test 2: API Authentication Requirement ---\n";

$api_path = PUBLIC_HTML_DIR . '/api/gettreasurerreport.php';
$api_content = file_get_contents($api_path);

$has_require_auth = (strpos($api_content, 'require_authentication()') !== false);
if (assert_true($has_require_auth, "gettreasurerreport.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 3: gettreasurerreport.php intentionally skips CSRF (read-only endpoint)
// =============================================================================
echo "\n--- Test 3: API CSRF Token Requirement ---\n";

$has_csrf_comment = (strpos($api_content, 'CSRF not required') !== false);
if (assert_true($has_csrf_comment, "gettreasurerreport.php documents CSRF skip (read-only endpoint)")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 4: gettreasurerreport.php requires proper permissions (trs/wm/sa)
// =============================================================================
echo "\n--- Test 4: API Permission Requirements ---\n";

// Check for require_permission call
$has_require_permission = (strpos($api_content, 'require_permission(') !== false);
if (assert_true($has_require_permission, "gettreasurerreport.php uses require_permission()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it requires treasurer permission
$has_trs_permission = (strpos($api_content, "'trs'") !== false);
if (assert_true($has_trs_permission, "gettreasurerreport.php requires treasurer (trs) permission")) {
    $passed++;
} else {
    $failed++;
}

// Check that it requires webmaster permission
$has_wm_permission = (strpos($api_content, "'wm'") !== false);
if (assert_true($has_wm_permission, "gettreasurerreport.php requires webmaster (wm) permission")) {
    $passed++;
} else {
    $failed++;
}

// Check that it requires super admin permission
$has_sa_permission = (strpos($api_content, "'sa'") !== false);
if (assert_true($has_sa_permission, "gettreasurerreport.php requires super admin (sa) permission")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 5: Verify unauthorized roles are NOT allowed
// =============================================================================
echo "\n--- Test 5: Unauthorized Roles Denied ---\n";

// The API should NOT allow ordinary event organizer (oe) or patrol leader (pl) access
// Since the API uses require_permission with specific roles, we verify those specific roles
// are the only ones listed

// Extract the require_permission call to verify only correct roles are included
if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $api_content, $matches)) {
    $permission_string = $matches[1];

    // Check that 'oe' is NOT in the permission list
    $has_oe = (strpos($permission_string, "'oe'") !== false);
    if (assert_false($has_oe, "gettreasurerreport.php does NOT allow event organizer (oe) access")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that 'pl' is NOT in the permission list
    $has_pl = (strpos($permission_string, "'pl'") !== false);
    if (assert_false($has_pl, "gettreasurerreport.php does NOT allow patrol leader (pl) access")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: Could not parse require_permission call\n";
    $failed += 2;
}

// =============================================================================
// Test 6: API uses prepared statements for SQL injection prevention
// =============================================================================
echo "\n--- Test 6: SQL Injection Prevention ---\n";

$uses_prepared = (strpos($api_content, '->prepare(') !== false);
if (assert_true($uses_prepared, "gettreasurerreport.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// Check for bind_param usage
$uses_bind_param = (strpos($api_content, 'bind_param') !== false);
if (assert_true($uses_bind_param, "gettreasurerreport.php uses bind_param for query parameters")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 7: API validates input parameters
// =============================================================================
echo "\n--- Test 7: Input Validation ---\n";

// Check that floatval is used for amount parameters
$uses_floatval = (strpos($api_content, 'floatval(') !== false);
if (assert_true($uses_floatval, "gettreasurerreport.php validates amounts with floatval()")) {
    $passed++;
} else {
    $failed++;
}

// Check that intval is used for event type
$uses_intval = (strpos($api_content, 'intval(') !== false);
if (assert_true($uses_intval, "gettreasurerreport.php validates event type with intval()")) {
    $passed++;
} else {
    $failed++;
}

// Check for validation_helper.php inclusion
$uses_validation_helper = (strpos($api_content, 'validation_helper.php') !== false);
if (assert_true($uses_validation_helper, "gettreasurerreport.php includes validation_helper.php")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 8: API output is properly escaped (XSS prevention)
// =============================================================================
echo "\n--- Test 8: Output Escaping (XSS Prevention) ---\n";

// Check for escape_html usage in API output
$uses_escape_html = (strpos($api_content, 'escape_html(') !== false);
if (assert_true($uses_escape_html, "gettreasurerreport.php uses escape_html() for output")) {
    $passed++;
} else {
    $failed++;
}

// Check that user_name is escaped
$escapes_user_name = (strpos($api_content, "escape_html(\$row['user_name'])") !== false);
if (assert_true($escapes_user_name, "gettreasurerreport.php escapes user_name field")) {
    $passed++;
} else {
    $failed++;
}

// Check that event_name is escaped
$escapes_event_name = (strpos($api_content, "escape_html(\$row['event_name'])") !== false);
if (assert_true($escapes_event_name, "gettreasurerreport.php escapes event_name field")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 9: Template XSS prevention
// =============================================================================
echo "\n--- Test 9: Template XSS Prevention ---\n";

$template_path = PUBLIC_HTML_DIR . '/templates/TreasurerReport.html';
$template_content = file_get_contents($template_path);

// Check for escapeHtml function definition
$has_escape_function = (strpos($template_content, 'function escapeHtml') !== false);
if (assert_true($has_escape_function, "TreasurerReport.html defines escapeHtml function")) {
    $passed++;
} else {
    $failed++;
}

// Check that escapeHtml is used for displaying data
$uses_escape_html_display = (strpos($template_content, 'escapeHtml(row.user_name)') !== false);
if (assert_true($uses_escape_html_display, "TreasurerReport.html escapes user_name in display")) {
    $passed++;
} else {
    $failed++;
}

$uses_escape_event = (strpos($template_content, 'escapeHtml(row.event_name)') !== false);
if (assert_true($uses_escape_event, "TreasurerReport.html escapes event_name in display")) {
    $passed++;
} else {
    $failed++;
}

// Check that error messages are escaped
$escapes_error = (strpos($template_content, 'escapeHtml(data.error)') !== false);
if (assert_true($escapes_error, "TreasurerReport.html escapes error messages")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 10: TreasurerReport.php page access control
// =============================================================================
echo "\n--- Test 10: Page Access Control ---\n";

$page_path = PUBLIC_HTML_DIR . '/TreasurerReport.php';
$page_content = file_get_contents($page_path);

// Check for treasurer access check
$checks_trs = (strpos($page_content, "in_array('trs', \$access)") !== false);
if (assert_true($checks_trs, "TreasurerReport.php checks for treasurer access")) {
    $passed++;
} else {
    $failed++;
}

// Check for webmaster access check
$checks_wm = (strpos($page_content, "in_array('wm', \$access)") !== false);
if (assert_true($checks_wm, "TreasurerReport.php checks for webmaster access")) {
    $passed++;
} else {
    $failed++;
}

// Check for super admin access check
$checks_sa = (strpos($page_content, "in_array('sa', \$access)") !== false);
if (assert_true($checks_sa, "TreasurerReport.php checks for super admin access")) {
    $passed++;
} else {
    $failed++;
}

// Check for Access Denied message
$has_access_denied = (strpos($page_content, 'Access Denied') !== false);
if (assert_true($has_access_denied, "TreasurerReport.php has Access Denied message")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 11: Activity logging is implemented
// =============================================================================
echo "\n--- Test 11: Activity Logging ---\n";

// Check that activity_logger.php is included
$includes_logger = (strpos($api_content, 'activity_logger.php') !== false);
if (assert_true($includes_logger, "gettreasurerreport.php includes activity_logger.php")) {
    $passed++;
} else {
    $failed++;
}

// Check that log_activity is called
$calls_log_activity = (strpos($api_content, 'log_activity(') !== false);
if (assert_true($calls_log_activity, "gettreasurerreport.php calls log_activity()")) {
    $passed++;
} else {
    $failed++;
}

// Check that it logs treasurer report access
$logs_treasurer_access = (strpos($api_content, 'view_treasurer_report') !== false);
if (assert_true($logs_treasurer_access, "gettreasurerreport.php logs 'view_treasurer_report' action")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 12: API requires AJAX request
// =============================================================================
echo "\n--- Test 12: AJAX Requirement ---\n";

$has_require_ajax = (strpos($api_content, 'require_ajax()') !== false);
if (assert_true($has_require_ajax, "gettreasurerreport.php requires AJAX request")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
