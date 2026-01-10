<?php
/**
 * Store API Unit Tests
 *
 * Tests for the store/order API endpoints:
 * - order_getconfig.php
 * - order_create.php
 * - order_getall.php
 * - order_fulfill.php
 * - itemprices_getall.php
 * - itemprices_update.php
 *
 * These are static analysis tests that verify file structure,
 * security patterns, and code quality without database access.
 */

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Store API Unit Tests");

// =============================================================================
// Test 1: All store API files exist
// =============================================================================
echo "\n--- Test 1: Store API Files Existence ---\n";

$api_files = [
    'order_getconfig.php',
    'order_create.php',
    'order_getall.php',
    'order_fulfill.php',
    'itemprices_getall.php',
    'itemprices_update.php',
    'notifications_getprefs.php',
    'notifications_updatepref.php'
];

foreach ($api_files as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    if (assert_file_exists($path, "API file exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 2: Store include files exist
// =============================================================================
echo "\n--- Test 2: Store Include Files Existence ---\n";

$include_files = [
    'store_email.php',
    'tshirt_email.php'  // backward compatibility wrapper
];

foreach ($include_files as $file) {
    $path = PUBLIC_HTML_DIR . '/includes/' . $file;
    if (assert_file_exists($path, "Include file exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 3: Store page files exist
// =============================================================================
echo "\n--- Test 3: Store Page Files Existence ---\n";

$page_files = [
    'TShirtOrder.php',
    'TShirtOrderComplete.php',
    'ManageTShirtOrders.php',
    'ManageItemPrices.php'
];

foreach ($page_files as $file) {
    $path = PUBLIC_HTML_DIR . '/' . $file;
    if (assert_file_exists($path, "Page file exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

$template_files = [
    'TShirtOrder.html',
    'TShirtOrderComplete.html',
    'ManageTShirtOrders.html',
    'ManageItemPrices.html'
];

foreach ($template_files as $file) {
    $path = PUBLIC_HTML_DIR . '/templates/' . $file;
    if (assert_file_exists($path, "Template file exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 4: Public APIs don't require session_start before connect
// =============================================================================
echo "\n--- Test 4: Public API Security Check ---\n";

$public_apis = ['order_getconfig.php', 'order_create.php'];

foreach ($public_apis as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // These are public APIs - they should NOT call require_authentication
    // (order_create validates payment, not user session)
    $has_require_auth = (strpos($content, 'require_authentication()') !== false);

    if ($file === 'order_getconfig.php') {
        // getconfig should NOT require authentication (public pricing info)
        if (assert_false($has_require_auth, "$file does not require authentication (public endpoint)")) {
            $passed++;
        } else {
            $failed++;
        }
    }
}

// =============================================================================
// Test 5: Admin APIs require authentication
// =============================================================================
echo "\n--- Test 5: Admin API Authentication Check ---\n";

$admin_apis = ['order_getall.php', 'order_fulfill.php', 'itemprices_getall.php', 'itemprices_update.php'];

foreach ($admin_apis as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Admin APIs must call require_authentication
    $has_require_auth = (strpos($content, 'require_authentication()') !== false);
    if (assert_true($has_require_auth, "$file requires authentication")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 6: Admin APIs require permissions
// =============================================================================
echo "\n--- Test 6: Admin API Permission Check ---\n";

foreach ($admin_apis as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Admin APIs must call require_permission
    $has_require_perm = (strpos($content, 'require_permission(') !== false);
    if (assert_true($has_require_perm, "$file requires permission check")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 7: APIs use prepared statements
// =============================================================================
echo "\n--- Test 7: Prepared Statement Usage ---\n";

$all_apis = array_merge($public_apis, $admin_apis);

foreach ($all_apis as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Check for prepared statements usage
    $uses_prepared = (strpos($content, '->prepare(') !== false);
    if (assert_true($uses_prepared, "$file uses prepared statements")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 8: APIs include validation_helper
// =============================================================================
echo "\n--- Test 8: Input Validation Helper Usage ---\n";

$apis_needing_validation = ['order_create.php', 'order_fulfill.php', 'itemprices_update.php'];

foreach ($apis_needing_validation as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Check for validation helper
    $uses_validation = (strpos($content, 'validation_helper.php') !== false);
    if (assert_true($uses_validation, "$file includes validation_helper.php")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 9: APIs include activity_logger
// =============================================================================
echo "\n--- Test 9: Activity Logging Usage ---\n";

$apis_needing_logging = ['order_create.php', 'order_fulfill.php', 'itemprices_update.php'];

foreach ($apis_needing_logging as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Check for activity logger usage
    $uses_logging = (strpos($content, 'log_activity(') !== false);
    if (assert_true($uses_logging, "$file logs activities")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 10: store_email.php has required functions
// =============================================================================
echo "\n--- Test 10: Store Email Functions ---\n";

$email_path = PUBLIC_HTML_DIR . '/includes/store_email.php';
$email_content = file_get_contents($email_path);

$required_functions = [
    'create_mailer',
    'get_order_with_items',
    'build_order_items_list',
    'send_order_confirmation',
    'send_order_notification',
    'send_tshirt_order_confirmation',  // backward compat
    'send_tshirt_order_notification'   // backward compat
];

foreach ($required_functions as $func) {
    $has_func = (strpos($email_content, "function $func(") !== false);
    if (assert_true($has_func, "store_email.php defines $func()")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 11: Order create API handles JSON items
// =============================================================================
echo "\n--- Test 11: Order Create JSON Items Handling ---\n";

$create_path = PUBLIC_HTML_DIR . '/api/order_create.php';
$create_content = file_get_contents($create_path);

$handles_json = (strpos($create_content, 'json_decode') !== false);
if (assert_true($handles_json, "order_create.php handles JSON items")) {
    $passed++;
} else {
    $failed++;
}

$uses_transaction = (strpos($create_content, 'begin_transaction') !== false);
if (assert_true($uses_transaction, "order_create.php uses database transaction")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 12: Templates use generic API endpoints
// =============================================================================
echo "\n--- Test 12: Templates Use Generic APIs ---\n";

$order_template = PUBLIC_HTML_DIR . '/templates/TShirtOrder.html';
$template_content = file_get_contents($order_template);

$uses_generic_config = (strpos($template_content, 'order_getconfig.php') !== false);
if (assert_true($uses_generic_config, "TShirtOrder.html uses order_getconfig.php")) {
    $passed++;
} else {
    $failed++;
}

$uses_generic_create = (strpos($template_content, 'order_create.php') !== false);
if (assert_true($uses_generic_create, "TShirtOrder.html uses order_create.php")) {
    $passed++;
} else {
    $failed++;
}

$admin_template = PUBLIC_HTML_DIR . '/templates/ManageTShirtOrders.html';
$admin_content = file_get_contents($admin_template);

$uses_generic_getall = (strpos($admin_content, 'order_getall.php') !== false);
if (assert_true($uses_generic_getall, "ManageTShirtOrders.html uses order_getall.php")) {
    $passed++;
} else {
    $failed++;
}

$uses_generic_fulfill = (strpos($admin_content, 'order_fulfill.php') !== false);
if (assert_true($uses_generic_fulfill, "ManageTShirtOrders.html uses order_fulfill.php")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 13: Permission checks for item prices (wm/sa only)
// =============================================================================
echo "\n--- Test 13: Item Prices Permission Check ---\n";

$itemprices_files = ['itemprices_getall.php', 'itemprices_update.php'];

foreach ($itemprices_files as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Should require wm or sa permission
    $has_wm_check = (strpos($content, "'wm'") !== false);
    $has_sa_check = (strpos($content, "'sa'") !== false);

    if (assert_true($has_wm_check && $has_sa_check, "$file checks for wm/sa permissions")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 14: ManageItemPrices.php access control
// =============================================================================
echo "\n--- Test 14: ManageItemPrices Access Control ---\n";

$manage_prices_path = PUBLIC_HTML_DIR . '/ManageItemPrices.php';
$manage_prices_content = file_get_contents($manage_prices_path);

// Should check for wm or sa access, NOT treasurer
$checks_access = (strpos($manage_prices_content, 'hasAccess') !== false ||
                  strpos($manage_prices_content, '$access') !== false);
if (assert_true($checks_access, "ManageItemPrices.php has access control")) {
    $passed++;
} else {
    $failed++;
}

// Should NOT allow treasurer access
$trs_check = (strpos($manage_prices_content, '"trs"') !== false);
// For pricing, we want it to NOT include treasurer
$wm_sa_only = (strpos($manage_prices_content, '"wm"') !== false &&
               strpos($manage_prices_content, '"sa"') !== false);

if (assert_true($wm_sa_only, "ManageItemPrices.php requires wm/sa permission")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 15: Notification preferences use users table
// =============================================================================
echo "\n--- Test 15: Notification Preferences Use Users Table ---\n";

$notif_files = ['notifications_getprefs.php', 'notifications_updatepref.php'];

foreach ($notif_files as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    // Should reference users table notif_preferences column
    $uses_users_table = (strpos($content, 'notif_preferences') !== false &&
                         strpos($content, 'users') !== false);
    if (assert_true($uses_users_table, "$file uses users.notif_preferences column")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
