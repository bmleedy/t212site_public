<?php
/**
 * Committee API Authorization Unit Test
 *
 * Tests the authentication and authorization logic for the committee management APIs:
 * - api/getallcommittee.php
 * - api/createcommittee.php
 * - api/updatecommittee.php
 * - api/deletecommittee.php
 *
 * All committee APIs are restricted to Super Admin (sa) permission only.
 * These are static analysis tests that verify security patterns without database access.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Committee API Authorization Tests");

$passed = 0;
$failed = 0;

// Define the committee API files to test
$committee_apis = [
    'getallcommittee.php',
    'createcommittee.php',
    'updatecommittee.php',
    'deletecommittee.php'
];

// ============================================================================
// TEST 1: Verify all committee API files exist
// ============================================================================

echo "\nTest 1: Committee API File Existence\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    if (assert_file_exists($api_path, "API file exists: $api_file")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 2: Authentication Tests - All APIs require authentication
// ============================================================================

echo "Test 2: Authentication Requirements (should return 401 when not authenticated)\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_getallcommittee_requires_authentication
 * test_createcommittee_requires_authentication
 * test_updatecommittee_requires_authentication
 * test_deletecommittee_requires_authentication
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_require_auth = (strpos($content, 'require_authentication()') !== false);
    if (assert_true($has_require_auth, "$api_file requires authentication (401 on failure)")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 3: Authorization Tests - All APIs require 'sa' permission
// ============================================================================

echo "Test 3: Authorization Requirements (should return 403 for non-sa users)\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_getallcommittee_requires_sa_permission
 * test_createcommittee_requires_sa_permission
 * test_updatecommittee_requires_sa_permission
 * test_deletecommittee_requires_sa_permission
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Check for require_permission call
    $has_require_permission = (strpos($content, 'require_permission(') !== false);
    if (assert_true($has_require_permission, "$api_file uses require_permission()")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that it requires 'sa' permission
    $has_sa_permission = (strpos($content, "'sa'") !== false);
    if (assert_true($has_sa_permission, "$api_file requires super admin (sa) permission")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 4: Negative Tests - Verify 'wm' cannot access committee endpoints
// ============================================================================

echo "Test 4: Webmaster (wm) cannot access committee endpoints\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_wm_cannot_access_committee_endpoints
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Extract the require_permission call to verify wm is NOT included
    if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $content, $matches)) {
        $permission_string = $matches[1];
        $has_wm = (strpos($permission_string, "'wm'") !== false);
        if (assert_false($has_wm, "$api_file does NOT allow webmaster (wm) access")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        // Single permission format like require_permission(['sa'])
        // This means only 'sa' is allowed, so wm is correctly excluded
        $has_only_sa = (preg_match("/require_permission\s*\(\s*\[\s*'sa'\s*\]\s*\)/", $content) === 1);
        if (assert_true($has_only_sa, "$api_file restricts access to sa only (wm excluded)")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "\n";

// ============================================================================
// TEST 5: Negative Tests - Verify 'ue' cannot access committee endpoints
// ============================================================================

echo "Test 5: User Editor (ue) cannot access committee endpoints\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_ue_cannot_access_committee_endpoints
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Extract the require_permission call to verify ue is NOT included
    if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $content, $matches)) {
        $permission_string = $matches[1];
        $has_ue = (strpos($permission_string, "'ue'") !== false);
        if (assert_false($has_ue, "$api_file does NOT allow user editor (ue) access")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        // Single permission format like require_permission(['sa'])
        $has_only_sa = (preg_match("/require_permission\s*\(\s*\[\s*'sa'\s*\]\s*\)/", $content) === 1);
        if (assert_true($has_only_sa, "$api_file restricts access to sa only (ue excluded)")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "\n";

// ============================================================================
// TEST 6: Negative Tests - Verify 'pl' cannot access committee endpoints
// ============================================================================

echo "Test 6: Patrol Leader (pl) cannot access committee endpoints\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_pl_cannot_access_committee_endpoints
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Extract the require_permission call to verify pl is NOT included
    if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $content, $matches)) {
        $permission_string = $matches[1];
        $has_pl = (strpos($permission_string, "'pl'") !== false);
        if (assert_false($has_pl, "$api_file does NOT allow patrol leader (pl) access")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        // Single permission format like require_permission(['sa'])
        $has_only_sa = (preg_match("/require_permission\s*\(\s*\[\s*'sa'\s*\]\s*\)/", $content) === 1);
        if (assert_true($has_only_sa, "$api_file restricts access to sa only (pl excluded)")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "\n";

// ============================================================================
// TEST 7: Negative Tests - Verify 'trs' cannot access committee endpoints
// ============================================================================

echo "Test 7: Treasurer (trs) cannot access committee endpoints\n";
echo str_repeat("-", 60) . "\n";

/**
 * test_trs_cannot_access_committee_endpoints
 */
foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Extract the require_permission call to verify trs is NOT included
    if (preg_match("/require_permission\s*\(\s*\[([^\]]+)\]/", $content, $matches)) {
        $permission_string = $matches[1];
        $has_trs = (strpos($permission_string, "'trs'") !== false);
        if (assert_false($has_trs, "$api_file does NOT allow treasurer (trs) access")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        // Single permission format like require_permission(['sa'])
        $has_only_sa = (preg_match("/require_permission\s*\(\s*\[\s*'sa'\s*\]\s*\)/", $content) === 1);
        if (assert_true($has_only_sa, "$api_file restricts access to sa only (trs excluded)")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "\n";

// ============================================================================
// TEST 8: Verify AJAX request requirement
// ============================================================================

echo "Test 8: AJAX Request Requirement\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_require_ajax = (strpos($content, 'require_ajax()') !== false);
    if (assert_true($has_require_ajax, "$api_file requires AJAX request")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 9: Verify authentication comes before permission check
// ============================================================================

echo "Test 9: Authentication Before Permission Check Order\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $auth_pos = strpos($content, 'require_authentication()');
    $perm_pos = strpos($content, 'require_permission(');

    if ($auth_pos !== false && $perm_pos !== false) {
        if (assert_true($auth_pos < $perm_pos, "$api_file checks authentication before permission")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        echo "   FAILED: Could not verify order for $api_file\n";
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 10: Verify session_start is called
// ============================================================================

echo "Test 10: Session Management\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_session_start = (strpos($content, 'session_start()') !== false);
    if (assert_true($has_session_start, "$api_file starts session")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 11: Verify auth_helper.php is included
// ============================================================================

echo "Test 11: Auth Helper Inclusion\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_auth_helper = (strpos($content, 'auth_helper.php') !== false);
    if (assert_true($has_auth_helper, "$api_file includes auth_helper.php")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 12: Verify JSON content type is set
// ============================================================================

echo "Test 12: JSON Response Format\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $sets_json_header = (strpos($content, "Content-Type: application/json") !== false);
    if (assert_true($sets_json_header, "$api_file sets JSON content type header")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 13: Verify prepared statements are used (SQL injection prevention)
// ============================================================================

echo "Test 13: SQL Injection Prevention\n";
echo str_repeat("-", 60) . "\n";

foreach ($committee_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    // Check for prepared statements or safe query patterns
    $uses_prepared = (strpos($content, '->prepare(') !== false);
    $uses_query = (strpos($content, '->query(') !== false);

    // getallcommittee uses a direct query with no user input, which is acceptable
    if ($api_file === 'getallcommittee.php') {
        if (assert_true($uses_query || $uses_prepared, "$api_file uses database queries")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        if (assert_true($uses_prepared, "$api_file uses prepared statements")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

echo "\n";

// ============================================================================
// TEST 14: Verify validation_helper.php is included (for APIs with input)
// ============================================================================

echo "Test 14: Input Validation Helper Usage\n";
echo str_repeat("-", 60) . "\n";

// APIs that accept user input should use validation_helper
$apis_with_input = ['createcommittee.php', 'updatecommittee.php', 'deletecommittee.php'];

foreach ($apis_with_input as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_validation_helper = (strpos($content, 'validation_helper.php') !== false);
    if (assert_true($has_validation_helper, "$api_file includes validation_helper.php")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 15: Verify activity logging for write operations
// ============================================================================

echo "Test 15: Activity Logging for Write Operations\n";
echo str_repeat("-", 60) . "\n";

// Write APIs should log activities
$write_apis = ['createcommittee.php', 'updatecommittee.php', 'deletecommittee.php'];

foreach ($write_apis as $api_file) {
    $api_path = PUBLIC_HTML_DIR . '/api/' . $api_file;
    $content = file_get_contents($api_path);

    $has_activity_logger = (strpos($content, 'activity_logger.php') !== false);
    if (assert_true($has_activity_logger, "$api_file includes activity_logger.php")) {
        $passed++;
    } else {
        $failed++;
    }

    $has_log_activity = (strpos($content, 'log_activity(') !== false);
    if (assert_true($has_log_activity, "$api_file calls log_activity()")) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 16: Verify auth_helper.php defines correct HTTP status codes
// ============================================================================

echo "Test 16: HTTP Status Codes in auth_helper.php\n";
echo str_repeat("-", 60) . "\n";

$auth_helper_path = PUBLIC_HTML_DIR . '/api/auth_helper.php';
if (file_exists($auth_helper_path)) {
    $auth_helper_content = file_get_contents($auth_helper_path);

    // Check for 401 status code in require_authentication
    $has_401 = (strpos($auth_helper_content, 'http_response_code(401)') !== false);
    if (assert_true($has_401, "auth_helper.php returns 401 for unauthenticated requests")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check for 403 status code in require_permission
    $has_403 = (strpos($auth_helper_content, 'http_response_code(403)') !== false);
    if (assert_true($has_403, "auth_helper.php returns 403 for unauthorized requests")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "   FAILED: auth_helper.php not found\n";
    $failed += 2;
}

echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
