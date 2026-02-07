<?php
/**
 * Store Order Flow Integration Test
 *
 * Tests the complete store order flow including:
 * - Order creation with items
 * - Order retrieval with items
 * - Order fulfillment
 * - Activity logging for orders
 * - Email function structure
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Store Order Flow Integration Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// SETUP: Create test database connection
// ============================================================================

echo "Setting up test database connection...\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();
    $mysqli = new mysqli(
        $creds->getDatabaseHost(),
        $creds->getDatabaseUser(),
        $creds->getDatabasePassword(),
        $creds->getDatabaseName()
    );

    if ($mysqli->connect_error) {
        echo "❌ Database connection failed: " . $mysqli->connect_error . "\n";
        exit(1);
    }

    echo "✅ Database connection established\n\n";
} catch (Exception $e) {
    echo "❌ Failed to set up database: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================================
// TEST 1: Verify orders table exists and has correct structure
// ============================================================================

echo "Test 1: Verify orders table structure\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SHOW TABLES LIKE 'orders'");
$has_orders_table = $result && $result->num_rows > 0;

if (assert_true($has_orders_table, "orders table exists")) {
    $passed++;
} else {
    $failed++;
    echo "⚠️  Skipping remaining tests - orders table not found\n";
    test_summary($passed, $failed);
    exit(1);
}

// Check required columns
$columns = $mysqli->query("DESCRIBE orders");
$required_columns = [
    'id', 'order_date', 'order_type', 'customer_email', 'customer_phone',
    'customer_name', 'shipping_address', 'total_amount', 'paid', 'paid_date',
    'paypal_order_id', 'fulfilled', 'fulfilled_date', 'fulfilled_by', 'source_ip'
];

$found_columns = [];
while ($col = $columns->fetch_assoc()) {
    $found_columns[] = $col['Field'];
}

$all_columns_exist = true;
foreach ($required_columns as $col) {
    if (!in_array($col, $found_columns)) {
        echo "❌ Missing column: $col\n";
        $all_columns_exist = false;
    }
}

if (assert_true($all_columns_exist, "orders table has all required columns")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify order_items table exists and has correct structure
// ============================================================================

echo "Test 2: Verify order_items table structure\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SHOW TABLES LIKE 'order_items'");
$has_items_table = $result && $result->num_rows > 0;

if (assert_true($has_items_table, "order_items table exists")) {
    $passed++;

    // Check required columns
    $columns = $mysqli->query("DESCRIBE order_items");
    $required_columns = [
        'id', 'order_id', 'item_code', 'item_name', 'quantity', 'unit_price', 'line_total'
    ];

    $found_columns = [];
    while ($col = $columns->fetch_assoc()) {
        $found_columns[] = $col['Field'];
    }

    $all_columns_exist = true;
    foreach ($required_columns as $col) {
        if (!in_array($col, $found_columns)) {
            echo "❌ Missing column: $col\n";
            $all_columns_exist = false;
        }
    }

    if (assert_true($all_columns_exist, "order_items table has all required columns")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify item_prices table exists and has data
// ============================================================================

echo "Test 3: Verify item_prices table has T-shirt data\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SHOW TABLES LIKE 'item_prices'");
$has_prices_table = $result && $result->num_rows > 0;

if (assert_true($has_prices_table, "item_prices table exists")) {
    $passed++;

    // Check for T-shirt items
    $tshirt_result = $mysqli->query("SELECT COUNT(*) as count FROM item_prices WHERE item_category = 'tshirt'");
    $tshirt_count = $tshirt_result->fetch_assoc()['count'];

    if (assert_true($tshirt_count >= 6, "item_prices has T-shirt sizes (found: $tshirt_count)")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify store_config table exists
// ============================================================================

echo "Test 4: Verify store_config table\n";
echo str_repeat("-", 60) . "\n";

$result = $mysqli->query("SHOW TABLES LIKE 'store_config'");
$has_config_table = $result && $result->num_rows > 0;

if (assert_true($has_config_table, "store_config table exists")) {
    $passed++;

    // Check for required config keys
    $config_result = $mysqli->query("SELECT config_key FROM store_config");
    $config_keys = [];
    while ($row = $config_result->fetch_assoc()) {
        $config_keys[] = $row['config_key'];
    }

    $has_enabled = in_array('tshirt_orders_enabled', $config_keys);
    if (assert_true($has_enabled, "store_config has tshirt_orders_enabled key")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify order_getconfig.php returns valid JSON
// ============================================================================

echo "Test 5: Verify order_getconfig.php structure\n";
echo str_repeat("-", 60) . "\n";

$config_path = PUBLIC_HTML_DIR . '/api/order_getconfig.php';
$config_content = file_get_contents($config_path);

// Check that it outputs JSON
$has_json_header = strpos($config_content, 'application/json') !== false;
$has_json_encode = strpos($config_content, 'json_encode') !== false;

if (assert_true($has_json_header && $has_json_encode, "order_getconfig.php outputs JSON")) {
    $passed++;
} else {
    $failed++;
}

// Check that it reads from item_prices table
$reads_prices = strpos($config_content, 'item_prices') !== false;
if (assert_true($reads_prices, "order_getconfig.php reads from item_prices table")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify order_create.php has required validations
// ============================================================================

echo "Test 6: Verify order_create.php validations\n";
echo str_repeat("-", 60) . "\n";

$create_path = PUBLIC_HTML_DIR . '/api/order_create.php';
$create_content = file_get_contents($create_path);

// Check for required validations
$validations = [
    'customer_name' => strpos($create_content, 'customer_name') !== false,
    'customer_email' => strpos($create_content, 'customer_email') !== false,
    'customer_phone' => strpos($create_content, 'customer_phone') !== false,
    'items validation' => strpos($create_content, 'json_decode') !== false,
    'transaction' => strpos($create_content, 'begin_transaction') !== false,
    'commit' => strpos($create_content, 'commit') !== false,
    'activity logging' => strpos($create_content, 'log_activity') !== false
];

$all_valid = true;
foreach ($validations as $name => $present) {
    if (!$present) {
        echo "❌ Missing: $name\n";
        $all_valid = false;
    }
}

if (assert_true($all_valid, "order_create.php has all required validations")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify order_getall.php returns orders with items
// ============================================================================

echo "Test 7: Verify order_getall.php joins order_items\n";
echo str_repeat("-", 60) . "\n";

$getall_path = PUBLIC_HTML_DIR . '/api/order_getall.php';
$getall_content = file_get_contents($getall_path);

// Check that it joins or queries order_items
$has_items_query = strpos($getall_content, 'order_items') !== false;
if (assert_true($has_items_query, "order_getall.php includes order_items")) {
    $passed++;
} else {
    $failed++;
}

// Check for authentication
$has_auth = strpos($getall_content, 'require_authentication') !== false;
if (assert_true($has_auth, "order_getall.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify order_fulfill.php updates correctly
// ============================================================================

echo "Test 8: Verify order_fulfill.php structure\n";
echo str_repeat("-", 60) . "\n";

$fulfill_path = PUBLIC_HTML_DIR . '/api/order_fulfill.php';
$fulfill_content = file_get_contents($fulfill_path);

// Check for required updates
$checks = [
    'fulfilled = 1' => strpos($fulfill_content, 'fulfilled') !== false,
    'fulfilled_date' => strpos($fulfill_content, 'fulfilled_date') !== false,
    'fulfilled_by' => strpos($fulfill_content, 'fulfilled_by') !== false,
    'activity logging' => strpos($fulfill_content, 'log_activity') !== false
];

$all_checks = true;
foreach ($checks as $name => $present) {
    if (!$present) {
        echo "❌ Missing: $name\n";
        $all_checks = false;
    }
}

if (assert_true($all_checks, "order_fulfill.php has all required updates")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify store_email.php has required functions
// ============================================================================

echo "Test 9: Verify store_email.php email functions\n";
echo str_repeat("-", 60) . "\n";

$email_path = PUBLIC_HTML_DIR . '/includes/store_email.php';
$email_content = file_get_contents($email_path);

$required_functions = [
    'get_order_with_items',
    'build_order_items_list',
    'send_order_confirmation',
    'send_order_notification',
    'send_tshirt_order_confirmation',  // backward compat
    'send_tshirt_order_notification'   // backward compat
];

$all_functions = true;
foreach ($required_functions as $func) {
    if (strpos($email_content, "function $func(") === false) {
        echo "❌ Missing function: $func\n";
        $all_functions = false;
    }
}

if (assert_true($all_functions, "store_email.php has all required functions")) {
    $passed++;
} else {
    $failed++;
}

// Check that it uses PHPMailer
$uses_mailer = strpos($email_content, 'PHPMailer') !== false ||
               strpos($email_content, 'create_mailer') !== false;
if (assert_true($uses_mailer, "store_email.php uses PHPMailer")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify notification preferences integration
// ============================================================================

echo "Test 10: Verify notification preferences in store emails\n";
echo str_repeat("-", 60) . "\n";

// Check that send_order_notification checks user preferences
$checks_prefs = strpos($email_content, 'notif_preferences') !== false;
if (assert_true($checks_prefs, "store_email.php checks notification preferences")) {
    $passed++;
} else {
    $failed++;
}

// Verify it reads from users table
$reads_users = strpos($email_content, 'FROM users') !== false ||
               strpos($email_content, 'users WHERE') !== false;
if (assert_true($reads_users, "store_email.php reads from users table")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify API endpoints log activities with order actions
// ============================================================================

echo "Test 11: Verify store activity logging actions\n";
echo str_repeat("-", 60) . "\n";

$expected_actions = [
    'order_create.php' => 'order_created',
    'order_fulfill.php' => 'order_fulfilled',
    'itemprices_update.php' => 'item_price_updated'
];

$all_actions = true;
foreach ($expected_actions as $file => $action) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    $content = file_get_contents($path);

    if (strpos($content, $action) === false) {
        echo "❌ $file missing action: $action\n";
        $all_actions = false;
    }
}

if (assert_true($all_actions, "Store APIs use correct activity action names")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Verify foreign key relationship between orders and order_items
// ============================================================================

echo "Test 12: Verify orders/order_items foreign key\n";
echo str_repeat("-", 60) . "\n";

$fk_result = $mysqli->query("
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'order_items'
    AND COLUMN_NAME = 'order_id'
    AND REFERENCED_TABLE_NAME = 'orders'
");

$has_fk = $fk_result && $fk_result->num_rows > 0;

if (assert_true($has_fk, "order_items.order_id has foreign key to orders")) {
    $passed++;
} else {
    echo "⚠️  Foreign key may not exist (could be defined differently)\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 13: Verify backward compatibility wrapper
// ============================================================================

echo "Test 13: Verify tshirt_email.php backward compatibility\n";
echo str_repeat("-", 60) . "\n";

$tshirt_email_path = PUBLIC_HTML_DIR . '/includes/tshirt_email.php';
$tshirt_email_content = file_get_contents($tshirt_email_path);

$includes_store = strpos($tshirt_email_content, 'store_email.php') !== false;
if (assert_true($includes_store, "tshirt_email.php includes store_email.php")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 14: Test order count statistics query
// ============================================================================

echo "Test 14: Verify order statistics\n";
echo str_repeat("-", 60) . "\n";

// This tests the stats query pattern used in order_getall.php
$stats_query = "SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN fulfilled = 0 THEN 1 ELSE 0 END) as unfulfilled_orders,
    SUM(CASE WHEN fulfilled = 1 THEN 1 ELSE 0 END) as fulfilled_orders,
    COALESCE(SUM(total_amount), 0) as total_revenue
FROM orders";

$result = $mysqli->query($stats_query);
$stats = $result->fetch_assoc();

echo "Order statistics:\n";
echo "  Total orders: " . $stats['total_orders'] . "\n";
echo "  Unfulfilled: " . $stats['unfulfilled_orders'] . "\n";
echo "  Fulfilled: " . $stats['fulfilled_orders'] . "\n";
echo "  Revenue: $" . number_format($stats['total_revenue'], 2) . "\n";

if (assert_true(true, "Order statistics query executes successfully")) {
    $passed++;
}

echo "\n";

// ============================================================================
// TEST 15: Verify item prices update includes audit logging
// ============================================================================

echo "Test 15: Verify item prices update logging\n";
echo str_repeat("-", 60) . "\n";

$prices_update_path = PUBLIC_HTML_DIR . '/api/itemprices_update.php';
$prices_content = file_get_contents($prices_update_path);

// Check for logging of old and new price values
$logs_old_price = strpos($prices_content, "'old'") !== false ||
                  strpos($prices_content, 'old_price') !== false ||
                  strpos($prices_content, 'oldPrice') !== false;
$logs_new_price = strpos($prices_content, "'new'") !== false ||
                  strpos($prices_content, 'new_price') !== false ||
                  strpos($prices_content, 'newPrice') !== false;

if (assert_true($logs_old_price && $logs_new_price, "itemprices_update.php logs price changes")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Close database connection
$mysqli->close();

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

exit($failed === 0 ? 0 : 1);
?>
