<?php
/**
 * Position Permission Synchronization Integration Test
 *
 * Tests that scout position changes automatically synchronize with user permissions.
 * Specifically validates that:
 *   1. Assigning Patrol Leader position adds 'pl' permission
 *   2. Removing Patrol Leader position removes 'pl' permission
 *   3. Changes are logged to activity_log
 *   4. Other permissions are preserved during sync
 *
 * REQUIREMENTS:
 *   - PHP with mysqli extension enabled
 *   - Database access configured in CREDENTIALS.json
 *
 * USAGE:
 *   php tests/integration/PositionPermissionSyncTest.php
 *
 * NOTE: This test creates and deletes temporary test users in the database.
 *       It is safe to run on development and staging environments.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load dependencies
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Position Permission Synchronization Integration Tests");

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
// HELPER FUNCTIONS
// ============================================================================

/**
 * Create a test scout user
 */
function create_test_scout($mysqli, $first_name, $last_name, $position_id = 0, $user_access = '') {
    // Generate unique username
    $username = strtolower($first_name . $last_name . rand(1000, 9999));
    $email = $username . '@test.example.com';

    // Create user
    $query = "INSERT INTO users (user_name, user_password_hash, user_email, user_active,
              user_first, user_last, is_scout, user_type, user_access, user_registration_datetime, user_registration_ip)
              VALUES (?, ?, ?, 1, ?, ?, 1, 'Scout', ?, NOW(), '127.0.0.1')";

    $stmt = $mysqli->prepare($query);
    $hash = password_hash('testpass123', PASSWORD_DEFAULT);
    $stmt->bind_param('ssssss', $username, $hash, $email, $first_name, $last_name, $user_access);
    $stmt->execute();
    $user_id = $mysqli->insert_id;
    $stmt->close();

    // Create scout_info
    $query = "INSERT INTO scout_info (user_id, rank_id, patrol_id, position_id)
              VALUES (?, 1, 1, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $user_id, $position_id);
    $stmt->execute();
    $stmt->close();

    return $user_id;
}

/**
 * Get user's current access string
 */
function get_user_access($mysqli, $user_id) {
    $query = "SELECT user_access FROM users WHERE user_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['user_access'] : null;
}

/**
 * Get user's current position
 */
function get_user_position($mysqli, $user_id) {
    $query = "SELECT position_id FROM scout_info WHERE user_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['position_id'] : null;
}

/**
 * Update scout position (simulates writeScoutData function)
 */
function update_scout_position($mysqli, $user_id, $new_position) {
    // Load the actual updateuser.php functions
    require_once PUBLIC_HTML_DIR . '/api/connect.php';
    require_once PUBLIC_HTML_DIR . '/includes/activity_logger.php';

    // Get current position
    $old_position = get_user_position($mysqli, $user_id);

    // Update scout_info
    $query = "UPDATE scout_info SET position_id=? WHERE user_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $new_position, $user_id);
    $stmt->execute();
    $stmt->close();

    // Call the actual sync function from updateuser.php
    // We need to include the function definition
    include_once PUBLIC_HTML_DIR . '/api/updateuser.php';
    syncPositionPermissions($user_id, $old_position, $new_position, $mysqli);
}

/**
 * Delete test user and related data
 */
function delete_test_user($mysqli, $user_id) {
    $mysqli->query("DELETE FROM scout_info WHERE user_id=$user_id");
    $mysqli->query("DELETE FROM activity_log WHERE user=$user_id");
    $mysqli->query("DELETE FROM users WHERE user_id=$user_id");
}

/**
 * Check if user has specific permission
 */
function has_permission($user_access, $permission) {
    $access_array = explode('.', $user_access);
    return in_array($permission, $access_array);
}

// ============================================================================
// TEST 1: Verify syncPositionPermissions function exists
// ============================================================================

echo "Test 1: Verify syncPositionPermissions function exists\n";
echo str_repeat("-", 60) . "\n";

$updateuser_file = PUBLIC_HTML_DIR . '/api/updateuser.php';
$content = file_get_contents($updateuser_file);

if (assert_true(
    strpos($content, 'function syncPositionPermissions') !== false,
    "syncPositionPermissions function exists in updateuser.php"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Assigning Patrol Leader adds 'pl' permission
// ============================================================================

echo "Test 2: Assigning Patrol Leader (position_id=1) adds 'pl' permission\n";
echo str_repeat("-", 60) . "\n";

// Create test scout without patrol leader position
$test_user_1 = create_test_scout($mysqli, 'Test', 'Scout1', 0, '');

echo "Created test user ID: $test_user_1\n";
echo "Initial access: '" . get_user_access($mysqli, $test_user_1) . "'\n";

// Update to Patrol Leader position
update_scout_position($mysqli, $test_user_1, 1);

$new_access = get_user_access($mysqli, $test_user_1);
echo "Access after setting Patrol Leader: '$new_access'\n";

$has_pl = has_permission($new_access, 'pl');

if (assert_true($has_pl, "User has 'pl' permission after being assigned Patrol Leader")) {
    $passed++;
} else {
    $failed++;
}

// Cleanup
delete_test_user($mysqli, $test_user_1);

echo "\n";

// ============================================================================
// TEST 3: Removing Patrol Leader removes 'pl' permission
// ============================================================================

echo "Test 3: Removing Patrol Leader position removes 'pl' permission\n";
echo str_repeat("-", 60) . "\n";

// Create test scout WITH patrol leader position and permission
$test_user_2 = create_test_scout($mysqli, 'Test', 'Scout2', 1, 'pl');

echo "Created test user ID: $test_user_2\n";
echo "Initial access: '" . get_user_access($mysqli, $test_user_2) . "'\n";

// Remove from Patrol Leader position (change to no position)
update_scout_position($mysqli, $test_user_2, 0);

$new_access = get_user_access($mysqli, $test_user_2);
echo "Access after removing from Patrol Leader: '$new_access'\n";

$has_pl = has_permission($new_access, 'pl');

if (assert_false($has_pl, "User does NOT have 'pl' permission after removal from Patrol Leader")) {
    $passed++;
} else {
    $failed++;
}

// Cleanup
delete_test_user($mysqli, $test_user_2);

echo "\n";

// ============================================================================
// TEST 4: Other permissions are preserved during sync
// ============================================================================

echo "Test 4: Other permissions preserved when adding/removing 'pl'\n";
echo str_repeat("-", 60) . "\n";

// Create test scout with other permissions
$test_user_3 = create_test_scout($mysqli, 'Test', 'Scout3', 0, 'oe.sa');

echo "Created test user ID: $test_user_3\n";
echo "Initial access: '" . get_user_access($mysqli, $test_user_3) . "'\n";

// Add Patrol Leader
update_scout_position($mysqli, $test_user_3, 1);

$new_access = get_user_access($mysqli, $test_user_3);
echo "Access after adding Patrol Leader: '$new_access'\n";

$has_oe = has_permission($new_access, 'oe');
$has_sa = has_permission($new_access, 'sa');
$has_pl = has_permission($new_access, 'pl');

$all_preserved = $has_oe && $has_sa && $has_pl;

if (assert_true($all_preserved, "All permissions preserved (oe, sa) and 'pl' added")) {
    $passed++;
} else {
    $failed++;
    echo "   oe: " . ($has_oe ? 'YES' : 'NO') . "\n";
    echo "   sa: " . ($has_sa ? 'YES' : 'NO') . "\n";
    echo "   pl: " . ($has_pl ? 'YES' : 'NO') . "\n";
}

// Now remove Patrol Leader and check again
update_scout_position($mysqli, $test_user_3, 0);

$final_access = get_user_access($mysqli, $test_user_3);
echo "Access after removing Patrol Leader: '$final_access'\n";

$has_oe = has_permission($final_access, 'oe');
$has_sa = has_permission($final_access, 'sa');
$has_pl = has_permission($final_access, 'pl');

$preserved_without_pl = $has_oe && $has_sa && !$has_pl;

if (assert_true($preserved_without_pl, "Original permissions preserved (oe, sa) and 'pl' removed")) {
    $passed++;
} else {
    $failed++;
    echo "   oe: " . ($has_oe ? 'YES' : 'NO') . "\n";
    echo "   sa: " . ($has_sa ? 'YES' : 'NO') . "\n";
    echo "   pl: " . ($has_pl ? 'YES' : 'NO') . "\n";
}

// Cleanup
delete_test_user($mysqli, $test_user_3);

echo "\n";

// ============================================================================
// TEST 5: No change when position stays the same
// ============================================================================

echo "Test 5: No permission change when position unchanged\n";
echo str_repeat("-", 60) . "\n";

// Create test scout as Patrol Leader
$test_user_4 = create_test_scout($mysqli, 'Test', 'Scout4', 1, 'pl');

$initial_access = get_user_access($mysqli, $test_user_4);
echo "Initial access: '$initial_access'\n";

// "Update" to same position
update_scout_position($mysqli, $test_user_4, 1);

$final_access = get_user_access($mysqli, $test_user_4);
echo "Access after 'updating' to same position: '$final_access'\n";

if (assert_equals($initial_access, $final_access, "Access unchanged when position unchanged")) {
    $passed++;
} else {
    $failed++;
}

// Cleanup
delete_test_user($mysqli, $test_user_4);

echo "\n";

// ============================================================================
// TEST 6: Activity logging for permission sync
// ============================================================================

echo "Test 6: Permission sync is logged to activity_log\n";
echo str_repeat("-", 60) . "\n";

// Create test scout
$test_user_5 = create_test_scout($mysqli, 'Test', 'Scout5', 0, '');

// Clear any existing logs for this user
$mysqli->query("DELETE FROM activity_log WHERE user=$test_user_5");

// Assign Patrol Leader
update_scout_position($mysqli, $test_user_5, 1);

// Check activity log
$query = "SELECT * FROM activity_log
          WHERE user=? AND action='sync_position_permissions'
          ORDER BY timestamp DESC LIMIT 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $test_user_5);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();
$stmt->close();

if (assert_true($log !== null, "Permission sync logged to activity_log")) {
    $passed++;

    if ($log) {
        echo "Log entry details:\n";
        echo "  Action: " . $log['action'] . "\n";
        echo "  Success: " . ($log['success'] ? 'Yes' : 'No') . "\n";
        echo "  Freetext: " . $log['freetext'] . "\n";

        // Verify log has success=true
        if (assert_true($log['success'] == 1, "Log entry shows success")) {
            $passed++;
        } else {
            $failed++;
        }

        // Verify freetext contains meaningful information
        $has_meaningful_text = (
            strpos($log['freetext'], 'pl') !== false &&
            strpos($log['freetext'], 'permission') !== false
        );

        if (assert_true($has_meaningful_text, "Log freetext contains permission details")) {
            $passed++;
        } else {
            $failed++;
        }
    }
} else {
    $failed++;
    echo "   ❌ No log entry found for sync_position_permissions\n";
    $failed += 2; // Failed the sub-tests too
}

// Cleanup
delete_test_user($mysqli, $test_user_5);

echo "\n";

// ============================================================================
// TEST 7: Changing between non-PL positions doesn't affect 'pl' permission
// ============================================================================

echo "Test 7: Changing between non-Patrol-Leader positions\n";
echo str_repeat("-", 60) . "\n";

// Create test scout with Quartermaster position (id=5)
$test_user_6 = create_test_scout($mysqli, 'Test', 'Scout6', 5, '');

echo "Created test user ID: $test_user_6 with Quartermaster position\n";
$initial_access = get_user_access($mysqli, $test_user_6);
echo "Initial access: '$initial_access'\n";

// Change to Scribe position (id=9)
update_scout_position($mysqli, $test_user_6, 9);

$final_access = get_user_access($mysqli, $test_user_6);
echo "Access after changing to Scribe: '$final_access'\n";

$still_no_pl = !has_permission($final_access, 'pl');

if (assert_true($still_no_pl, "No 'pl' permission added when changing between non-PL positions")) {
    $passed++;
} else {
    $failed++;
}

// Cleanup
delete_test_user($mysqli, $test_user_6);

echo "\n";

// ============================================================================
// TEST 8: Verify writeScoutData calls syncPositionPermissions
// ============================================================================

echo "Test 8: Verify writeScoutData function calls syncPositionPermissions\n";
echo str_repeat("-", 60) . "\n";

$updateuser_content = file_get_contents(PUBLIC_HTML_DIR . '/api/updateuser.php');

// Check that writeScoutData contains a call to syncPositionPermissions
$writeScoutData_start = strpos($updateuser_content, 'function writeScoutData');
$writeScoutData_end = strpos($updateuser_content, 'function writeMeritBadgeData');

if ($writeScoutData_start !== false && $writeScoutData_end !== false) {
    $writeScoutData_function = substr($updateuser_content, $writeScoutData_start,
                                      $writeScoutData_end - $writeScoutData_start);

    $calls_sync = strpos($writeScoutData_function, 'syncPositionPermissions') !== false;

    if (assert_true($calls_sync, "writeScoutData calls syncPositionPermissions")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "❌ Could not locate writeScoutData function\n";
    $failed++;
}

echo "\n";

// Close database connection
$mysqli->close();

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Print final status
echo "\n";
if ($failed === 0) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║         ✅ ALL POSITION-PERMISSION SYNC TESTS PASSED ✅          ║\n";
    echo "║                                                                  ║\n";
    echo "║  Patrol Leader position changes correctly sync with 'pl'        ║\n";
    echo "║  permission:                                                     ║\n";
    echo "║    ✓ Adding position adds permission                            ║\n";
    echo "║    ✓ Removing position removes permission                       ║\n";
    echo "║    ✓ Other permissions are preserved                            ║\n";
    echo "║    ✓ Changes are logged to activity_log                         ║\n";
    echo "║                                                                  ║\n";
    echo "║  Position and permission synchronization is working! 🎯          ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║              ⚠️  SOME TESTS FAILED ⚠️                            ║\n";
    echo "║                                                                  ║\n";
    echo "║  Position-permission synchronization may not be working          ║\n";
    echo "║  correctly. Review the errors above.                            ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
?>
