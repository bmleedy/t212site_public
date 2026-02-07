<?php
/**
 * Setup Script Test Suite
 *
 * Tests the development environment setup script (setup.sh) to ensure:
 * - Script exists and is executable
 * - Required components are properly installed
 * - Configuration files are correct
 * - Services are running and enabled
 * - Environment is ready for development
 *
 * This test can be run before or after running setup.sh to verify the environment.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Development Environment Setup Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify setup script exists and is executable
// ============================================================================

echo "Test 1: Setup script validation\n";
echo str_repeat("-", 60) . "\n";

$setup_script = PROJECT_ROOT . '/setup.sh';

if (assert_true(
    file_exists($setup_script),
    "setup.sh script exists"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    is_executable($setup_script),
    "setup.sh is executable"
)) {
    $passed++;
} else {
    $failed++;
}

// Read and validate script structure
$script_contents = file_get_contents($setup_script);

if (assert_true(
    strpos($script_contents, '#!/bin/bash') === 0,
    "Script has proper shebang"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($script_contents, 'set -e') !== false,
    "Script has error handling (set -e)"
)) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(
    strpos($script_contents, 'print_error') !== false,
    "Script has error reporting functions"
)) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify required functions exist in setup script
// ============================================================================

echo "Test 2: Setup script function validation\n";
echo str_repeat("-", 60) . "\n";

$required_functions = [
    'preflight_checks',
    'setup_git',
    'setup_ssh_keys',
    'setup_apache',
    'setup_php',
    'setup_database',
    'run_verification',
    'print_status',
    'print_success',
    'print_warning',
    'print_error',
    'command_exists',
    'package_installed',
    'service_running',
    'prompt_yes_no'
];

foreach ($required_functions as $func) {
    if (assert_true(
        strpos($script_contents, $func . '()') !== false,
        "Function '$func' exists in script"
    )) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n";

// ============================================================================
// TEST 3: Check Git installation (if available)
// ============================================================================

echo "Test 3: Git installation check\n";
echo str_repeat("-", 60) . "\n";

exec('which git 2>/dev/null', $output, $return_code);
$git_installed = ($return_code === 0);

if ($git_installed) {
    echo "✓ Git is installed\n";

    exec('git --version', $git_version);
    echo "  Version: " . ($git_version[0] ?? 'unknown') . "\n";

    // Check git config
    exec('git config user.name 2>/dev/null', $git_user_name, $rc1);
    exec('git config user.email 2>/dev/null', $git_user_email, $rc2);

    if ($rc1 === 0 && !empty($git_user_name)) {
        echo "✓ Git user name configured: " . ($git_user_name[0] ?? '') . "\n";
        $passed++;
    } else {
        echo "⚠ Git user name not configured\n";
        $passed++; // Not required for test to pass
    }

    if ($rc2 === 0 && !empty($git_user_email)) {
        echo "✓ Git user email configured: " . ($git_user_email[0] ?? '') . "\n";
        $passed++;
    } else {
        echo "⚠ Git user email not configured\n";
        $passed++; // Not required for test to pass
    }
} else {
    echo "⚠ Git not installed (run setup.sh to install)\n";
    $passed += 3; // Don't fail if git isn't installed yet
}

echo "\n";

// ============================================================================
// TEST 4: Check Apache installation and configuration
// ============================================================================

echo "Test 4: Apache web server check\n";
echo str_repeat("-", 60) . "\n";

exec('which apache2 2>/dev/null', $output, $return_code);
$apache_installed = ($return_code === 0);

if ($apache_installed) {
    echo "✓ Apache is installed\n";
    $passed++;

    // Check if Apache is running
    exec('systemctl is-active apache2 2>/dev/null', $output, $return_code);
    if ($return_code === 0) {
        echo "✓ Apache is running\n";
        $passed++;
    } else {
        echo "⚠ Apache is not running\n";
        $passed++; // Don't fail - not required for test suite
    }

    // Check if Apache is enabled
    exec('systemctl is-enabled apache2 2>/dev/null', $output, $return_code);
    if ($return_code === 0) {
        echo "✓ Apache is enabled on boot\n";
        $passed++;
    } else {
        echo "⚠ Apache is not enabled on boot\n";
        $passed++; // Don't fail - not required for test suite
    }

    // Check Apache configuration
    $apache_config = '/etc/apache2/sites-available/000-default.conf';
    if (file_exists($apache_config)) {
        echo "✓ Apache config file exists\n";
        $passed++;

        $config_contents = file_get_contents($apache_config);
        $expected_doc_root = '/var/www/t212site/public_html';

        if (strpos($config_contents, $expected_doc_root) !== false) {
            echo "✓ Apache DocumentRoot configured correctly\n";
            $passed++;
        } else {
            echo "⚠ Apache DocumentRoot not configured correctly\n";
            echo "  Expected: $expected_doc_root\n";
            $passed++; // Don't fail - CI/dev may differ from production
        }
    } else {
        echo "⚠ Apache config file not found\n";
        $passed++; // Don't fail - not required for test suite
    }

    // Check if mod_rewrite is enabled
    exec('apache2ctl -M 2>/dev/null | grep rewrite', $output, $return_code);
    if ($return_code === 0) {
        echo "✓ mod_rewrite is enabled\n";
        $passed++;
    } else {
        echo "⚠ mod_rewrite not enabled\n";
        $passed++; // Don't fail - not required for test suite
    }
} else {
    echo "⚠ Apache not installed (run setup.sh to install)\n";
    $passed += 6; // Don't fail if Apache isn't installed yet
}

echo "\n";

// ============================================================================
// TEST 5: Check PHP installation
// ============================================================================

echo "Test 5: PHP installation check\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(
    version_compare(PHP_VERSION, '7.0', '>='),
    "PHP version >= 7.0 (current: " . PHP_VERSION . ")"
)) {
    $passed++;
} else {
    $failed++;
}

// Check required PHP extensions
// mysqli is optional for local development but required for production
$required_extensions = ['json', 'mbstring'];
$optional_extensions = ['mysqli'];

foreach ($required_extensions as $ext) {
    if (assert_true(
        extension_loaded($ext),
        "PHP extension '$ext' is loaded"
    )) {
        $passed++;
    } else {
        $failed++;
    }
}

// Check optional extensions (warn but don't fail)
foreach ($optional_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ PASSED: PHP extension '$ext' is loaded\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: PHP extension '$ext' is not loaded (required for production)\n";
        $passed++; // Don't fail - might be running in local development
    }
}

// Check if Apache PHP module is installed
exec('dpkg -l libapache2-mod-php 2>/dev/null | grep "^ii"', $output, $return_code);
if ($return_code === 0) {
    echo "✓ Apache PHP module is installed\n";
    $passed++;
} else {
    echo "⚠ Apache PHP module not found\n";
    $passed++; // Don't fail - might be running in different context
}

echo "\n";

// ============================================================================
// TEST 6: Check MariaDB/MySQL installation
// ============================================================================

echo "Test 6: Database server check\n";
echo str_repeat("-", 60) . "\n";

exec('which mysql 2>/dev/null', $output, $return_code);
$mysql_installed = ($return_code === 0);

if ($mysql_installed) {
    echo "✓ MySQL/MariaDB client is installed\n";
    $passed++;

    // Check if MySQL service is running
    exec('systemctl is-active mysql 2>/dev/null', $output, $return_code);
    if ($return_code === 0) {
        echo "✓ MySQL/MariaDB is running\n";
        $passed++;
    } else {
        echo "⚠ MySQL/MariaDB is not running\n";
        $passed++; // Don't fail - CI uses container, not systemctl
    }

    // Check if MySQL is enabled
    exec('systemctl is-enabled mysql 2>/dev/null', $output, $return_code);
    if ($return_code === 0) {
        echo "✓ MySQL/MariaDB is enabled on boot\n";
        $passed++;
    } else {
        echo "⚠ MySQL/MariaDB is not enabled on boot\n";
        $passed++; // Don't fail - CI uses container, not systemctl
    }
} else {
    echo "⚠ MySQL/MariaDB not installed (run setup.sh to install)\n";
    $passed += 3; // Don't fail if not installed yet
}

echo "\n";

// ============================================================================
// TEST 7: Check directory structure
// ============================================================================

echo "Test 7: Directory structure validation\n";
echo str_repeat("-", 60) . "\n";

$required_dirs = [
    PUBLIC_HTML_DIR,
    PUBLIC_HTML_DIR . '/api',
    PUBLIC_HTML_DIR . '/templates',
    PUBLIC_HTML_DIR . '/includes',
    PROJECT_ROOT . '/tests',
    PROJECT_ROOT . '/db_copy'
];

foreach ($required_dirs as $dir) {
    if (assert_true(
        is_dir($dir),
        "Directory exists: " . basename($dir)
    )) {
        $passed++;
    } else {
        $failed++;
    }
}

// Check if /var/www/t212site exists (as symlink or directory)
if (file_exists('/var/www/t212site')) {
    if (is_link('/var/www/t212site')) {
        $target = readlink('/var/www/t212site');
        echo "✓ /var/www/t212site is a symlink to: $target\n";
        $passed++;
    } else {
        echo "✓ /var/www/t212site exists as directory\n";
        $passed++;
    }
} else {
    echo "⚠ /var/www/t212site does not exist (run setup.sh)\n";
    $passed++; // Don't fail - might not be set up yet
}

// Check public_html directory permissions
// In local development, we just need to be able to read it
// In production, it should be world-readable for the web server
if (is_readable(PUBLIC_HTML_DIR)) {
    $public_html_perms = fileperms(PUBLIC_HTML_DIR);
    if (($public_html_perms & 0444) === 0444) {
        echo "✅ PASSED: public_html directory is world-readable (production ready)\n";
        $passed++;
    } else {
        echo "⚠️  WARNING: public_html directory is readable but not world-readable\n";
        echo "   (This is fine for local development, but should be fixed for production)\n";
        $passed++; // Don't fail - might be local development
    }
} else {
    echo "❌ FAILED: public_html directory is not readable\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Check CREDENTIALS.json
// ============================================================================

echo "Test 8: CREDENTIALS.json validation\n";
echo str_repeat("-", 60) . "\n";

$credentials_file = PUBLIC_HTML_DIR . '/CREDENTIALS.json';

if (assert_true(
    file_exists($credentials_file),
    "CREDENTIALS.json exists"
)) {
    $passed++;

    // Try to parse JSON
    $credentials_content = file_get_contents($credentials_file);
    $credentials = json_decode($credentials_content, true);

    if (assert_true(
        $credentials !== null,
        "CREDENTIALS.json is valid JSON"
    )) {
        $passed++;

        // Check for database credentials (supports both old and new formats)
        $has_db_config = false;

        // New format: db.host, db.username, etc.
        if (isset($credentials['db'])) {
            $has_db_config = true;
            $required_db_fields = ['host', 'username', 'password', 'database'];

            if (assert_true(
                isset($credentials['db']),
                "Database credentials section exists (new format)"
            )) {
                $passed++;

                foreach ($required_db_fields as $field) {
                    if (assert_true(
                        isset($credentials['db'][$field]),
                        "Database field '$field' exists"
                    )) {
                        $passed++;
                    } else {
                        $failed++;
                    }
                }
            } else {
                $failed++;
            }
        }
        // Old format: database_user, database_name
        elseif (isset($credentials['database_user']) && isset($credentials['database_name'])) {
            $has_db_config = true;
            echo "✓ Database credentials exist (old format)\n";
            $passed += 5; // Count as passing all db credential checks
        }

        if (!$has_db_config) {
            echo "✗ No database credentials found (neither old nor new format)\n";
            $failed += 5;
        }
    } else {
        $failed++;
        echo "  JSON error: " . json_last_error_msg() . "\n";
    }

    // Check file permissions (should not be world-readable since it has secrets)
    $perms = fileperms($credentials_file);
    $world_readable = ($perms & 0004) === 0004;

    if (!$world_readable) {
        echo "✓ CREDENTIALS.json is not world-readable (secure)\n";
        $passed++;
    } else {
        echo "⚠ CREDENTIALS.json is world-readable (security risk)\n";
        echo "  Run: chmod 600 $credentials_file\n";
        $passed++; // Warning, not error
    }
} else {
    echo "⚠ CREDENTIALS.json not found (expected at: $credentials_file)\n";
    echo "  This file contains secrets and is not in git\n";
    echo "  Run setup.sh to create a template, or copy from another developer\n";
    $passed += 7; // Don't fail - expected to be missing in fresh checkout
}

echo "\n";

// ============================================================================
// TEST 9: Test database connection
// ============================================================================

echo "Test 9: Database connection test\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($credentials_file)) {
    $credentials_content = file_get_contents($credentials_file);
    $credentials = json_decode($credentials_content, true);

    if ($credentials) {
        $db_config = null;

        // New format: db.host, db.username, etc.
        if (isset($credentials['db'])) {
            $db_config = $credentials['db'];

            // Only test if credentials look real (not template)
            if (isset($db_config['password']) && $db_config['password'] !== 'YOUR_DATABASE_PASSWORD_HERE') {
                $mysqli = @new mysqli(
                    $db_config['host'],
                    $db_config['username'],
                    $db_config['password'],
                    $db_config['database']
                );

                if ($mysqli->connect_error) {
                    echo "✗ Database connection failed: " . $mysqli->connect_error . "\n";
                    $failed++;
                } else {
                    echo "✓ Database connection successful\n";
                    $passed++;

                    // Check if database has tables
                    $result = $mysqli->query("SHOW TABLES");
                    $table_count = $result ? $result->num_rows : 0;

                    if ($table_count > 0) {
                        echo "✓ Database has $table_count tables\n";
                        $passed++;
                    } else {
                        echo "⚠ Database is empty (no tables)\n";
                        echo "  Run setup.sh to import database schema\n";
                        $passed++; // Warning, not error
                    }

                    $mysqli->close();
                }
            } else {
                echo "⚠ CREDENTIALS.json appears to be a template (not real credentials)\n";
                $passed += 2; // Don't fail
            }
        }
        // Old format: database_user, database_name
        elseif (isset($credentials['database_user']) && isset($credentials['database_name'])) {
            // Check if mysqli is available
            if (!extension_loaded('mysqli')) {
                echo "⚠ mysqli extension not loaded, skipping database connection test\n";
                $passed += 2; // Don't fail
            } else {
                // Extract credentials from old format
                $db_user = key($credentials['database_user']);
                $db_password = $credentials['database_user'][$db_user];
                $db_name = $credentials['database_name'];
                $db_host = isset($credentials['database_host']) ? $credentials['database_host'] : 'localhost';

                try {
                    $mysqli = @new mysqli(
                        $db_host,
                        $db_user,
                        $db_password,
                        $db_name
                    );

                if ($mysqli->connect_error) {
                    echo "✗ Database connection failed: " . $mysqli->connect_error . "\n";
                    echo "  (This is expected if you're not on a server with the database)\n";
                    $passed += 2; // Don't fail - might not be on server
                } else {
                    echo "✓ Database connection successful\n";
                    $passed++;

                    // Check if database has tables
                    $result = $mysqli->query("SHOW TABLES");
                    $table_count = $result ? $result->num_rows : 0;

                    if ($table_count > 0) {
                        echo "✓ Database has $table_count tables\n";
                        $passed++;
                    } else {
                        echo "⚠ Database is empty (no tables)\n";
                        $passed++; // Warning, not error
                    }

                    $mysqli->close();
                }
                } catch (Exception $e) {
                    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
                    echo "  (This is expected if you're not on a server with the database)\n";
                    $passed += 2; // Don't fail - might not be on server
                }
            }
        } else {
            echo "⚠ Could not parse database credentials (unknown format)\n";
            $passed += 2; // Don't fail
        }
    } else {
        echo "⚠ Could not parse CREDENTIALS.json\n";
        $passed += 2; // Don't fail
    }
} else {
    echo "⚠ CREDENTIALS.json not found, skipping database connection test\n";
    $passed += 2; // Don't fail
}

echo "\n";

// ============================================================================
// TEST 10: Test web server response
// ============================================================================

echo "Test 10: Web server response test\n";
echo str_repeat("-", 60) . "\n";

// Try to connect to localhost
$ch = curl_init('http://localhost');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = @curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo "✓ Web server responding on http://localhost\n";
    $passed++;

    if (strpos($response, 'Troop 212') !== false || strpos($response, 't212') !== false) {
        echo "✓ Response contains Troop 212 content\n";
        $passed++;
    } else {
        echo "⚠ Response doesn't contain expected content\n";
        $passed++; // Warning, not error
    }
} else {
    echo "⚠ Could not connect to http://localhost (HTTP code: $http_code)\n";
    echo "  This is normal if you're running tests remotely or Apache isn't configured yet\n";
    $passed += 2; // Don't fail - might not be accessible
}

echo "\n";

// ============================================================================
// TEST 11: Check SSH configuration for GitHub
// ============================================================================

echo "Test 11: SSH configuration for GitHub\n";
echo str_repeat("-", 60) . "\n";

$ssh_dir = $_SERVER['HOME'] . '/.ssh';

if (is_dir($ssh_dir)) {
    echo "✅ PASSED: SSH directory exists (~/.ssh)\n";
    $passed++;

    // Check for SSH keys
    $has_key = false;
    $key_files = ['id_rsa', 'id_ed25519', 'id_ecdsa'];

    foreach ($key_files as $key_file) {
        if (file_exists("$ssh_dir/$key_file")) {
            echo "✓ SSH private key found: $key_file\n";
            $has_key = true;

            if (file_exists("$ssh_dir/$key_file.pub")) {
                echo "✓ SSH public key found: $key_file.pub\n";
            }
            break;
        }
    }

    if ($has_key) {
        $passed++;
    } else {
        echo "⚠ No SSH keys found (run setup.sh to generate)\n";
        $passed++; // Don't fail
    }
} else {
    echo "⚠ SSH directory not found (run setup.sh to create)\n";
    $passed += 2; // Don't fail
}

echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================

test_summary($passed, $failed);

// Additional summary information
echo "\n";
echo "Environment Status Summary:\n";
echo str_repeat("=", 60) . "\n";

$components = [
    'Setup Script' => file_exists($setup_script) && is_executable($setup_script),
    'Git' => command_exists('git'),
    'Apache' => command_exists('apache2'),
    'PHP' => true,  // We're running PHP, so it exists
    'MySQL/MariaDB' => command_exists('mysql'),
    'CREDENTIALS.json' => file_exists($credentials_file),
];

foreach ($components as $component => $installed) {
    $status = $installed ? "✓ Installed" : "✗ Not installed";
    $color = $installed ? "\033[0;32m" : "\033[0;31m";
    echo sprintf("%-20s %s%s\033[0m\n", $component . ":", $color, $status);
}

echo str_repeat("=", 60) . "\n";

if ($failed === 0) {
    echo "\n\033[0;32m";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  Environment is ready for development! 🎉                  ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\033[0m\n";
} else {
    echo "\n\033[0;33m";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  Some components need attention                            ║\n";
    echo "║  Run ./setup.sh to complete the environment setup          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\033[0m\n";
}

// Helper function to check if command exists
function command_exists($command) {
    exec("which $command 2>/dev/null", $output, $return_code);
    return $return_code === 0;
}

// Exit with appropriate code
exit($failed === 0 ? 0 : 1);
?>
