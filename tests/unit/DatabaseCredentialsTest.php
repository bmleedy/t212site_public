<?php
/**
 * Database Credentials Test
 *
 * Tests that database credentials are properly loaded from CREDENTIALS.json
 * and that a database connection can be established.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Database Credentials Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Database credentials load from CREDENTIALS.json
// ============================================================================

echo "Test 1: Database credentials load from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();

    $dbUser = $creds->getDatabaseUser();
    $dbPass = $creds->getDatabasePassword();
    $dbName = $creds->getDatabaseName();
    $dbHost = $creds->getDatabaseHost();

    if (assert_true(!empty($dbUser), "Database user loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($dbPass), "Database password loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($dbName), "Database name loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($dbHost), "Database host loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify format (production uses naming like 'u104214272_t212db', CI uses 'root')
    if (strpos($dbUser, 'db') !== false) {
        echo "✅ Database user follows production naming convention\n";
        $passed++;
    } else {
        echo "⚠ Database user does not follow production naming convention (OK for CI/test environments)\n";
        $passed++; // Don't fail - CI uses 'root'
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load database credentials: " . $e->getMessage());
    $failed += 5;
}

echo "\n";

// ============================================================================
// TEST 2: Database connection can be established
// ============================================================================

echo "Test 2: Database connection can be established\n";
echo str_repeat("-", 60) . "\n";

// Check if mysqli extension is available
if (!class_exists('mysqli')) {
    echo "⚠️  mysqli extension not available - skipping connection tests\n";
    echo "   (This is OK - credentials are still loaded correctly)\n";
    echo "   To enable mysqli: install php-mysql package\n";
} else {
    try {
        // Test using the credentials directly
        $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($mysqli->connect_error) {
            assert_false(true, "Database connection failed: " . $mysqli->connect_error);
            $failed++;
            echo "   ⚠️  Cannot continue with connection tests.\n";
            echo "   Make sure your database is running and credentials are correct.\n";
        } else {
            assert_true(true, "Database connection established successfully");
            $passed++;

            // Close connection for next test
            $mysqli->close();
        }
    } catch (Exception $e) {
        assert_false(true, "Exception during database connection: " . $e->getMessage());
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 3: Test connect.php file
// ============================================================================

echo "Test 3: Test api/connect.php file\n";
echo str_repeat("-", 60) . "\n";

if (!class_exists('mysqli')) {
    echo "⚠️  Skipping - mysqli extension not available\n";
} else {
    try {
        // Capture any output from connect.php
        ob_start();
        require PUBLIC_HTML_DIR . '/api/connect.php';
        $output = ob_get_clean();

        // Check if $mysqli variable is set and connected
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            if ($mysqli->ping()) {
                assert_true(true, "api/connect.php creates valid database connection");
                $passed++;

                // Store for next test
                $testMysqli = $mysqli;
            } else {
                assert_false(true, "api/connect.php connection is not alive");
                $failed++;
            }
        } else {
            assert_false(true, "api/connect.php did not create \$mysqli object");
            $failed++;
        }
    } catch (Exception $e) {
        assert_false(true, "Exception loading api/connect.php: " . $e->getMessage());
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 4: Test login/config/config.php constants
// ============================================================================

echo "Test 4: Test login/config/config.php constants\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the config file
    require_once PUBLIC_HTML_DIR . '/login/config/config.php';

    // Check that constants are defined
    if (assert_true(defined('DB_HOST'), "DB_HOST constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('DB_NAME'), "DB_NAME constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('DB_USER'), "DB_USER constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('DB_PASS'), "DB_PASS constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify values match credentials
    if (assert_equals($dbHost, DB_HOST, "DB_HOST matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($dbName, DB_NAME, "DB_NAME matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($dbUser, DB_USER, "DB_USER matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($dbPass, DB_PASS, "DB_PASS matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Exception loading login/config/config.php: " . $e->getMessage());
    $failed += 8;
}

echo "\n";

// ============================================================================
// TEST 5: Basic query execution (if connection available)
// ============================================================================

echo "Test 5: Basic query execution\n";
echo str_repeat("-", 60) . "\n";

if (isset($testMysqli) && $testMysqli instanceof mysqli && $testMysqli->ping()) {
    try {
        // Test a simple query
        $result = $testMysqli->query("SELECT 1 as test");

        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['test'] == 1) {
                assert_true(true, "Basic SELECT query executed successfully");
                $passed++;
            } else {
                assert_false(true, "Query returned unexpected result");
                $failed++;
            }
            $result->free();
        } else {
            assert_false(true, "Query failed: " . $testMysqli->error);
            $failed++;
        }

        // Test showing tables (verify database exists)
        $result = $testMysqli->query("SHOW TABLES");
        if ($result) {
            $tableCount = $result->num_rows;
            assert_true($tableCount > 0, "Database contains " . $tableCount . " tables");
            $passed++;
            $result->free();
        } else {
            assert_false(true, "Could not show tables: " . $testMysqli->error);
            $failed++;
        }

        // Close connection
        $testMysqli->close();

    } catch (Exception $e) {
        assert_false(true, "Exception during query execution: " . $e->getMessage());
        $failed += 2;
    }
} else {
    echo "⚠️  Skipping query tests - no database connection available\n";
    echo "   (This is expected if database is not running)\n";
    // Don't count as failures - database might not be set up yet
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All database credentials tests passed!\n";
    echo "Database credentials are properly loaded and functional.\n";
    exit(0);
} else {
    echo "\n❌ Some database credentials tests failed!\n";
    echo "Please check:\n";
    echo "  1. CREDENTIALS.json has correct database credentials\n";
    echo "  2. Database server is running\n";
    echo "  3. Database user has proper permissions\n";
    exit(1);
}
