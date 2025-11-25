<?php
/**
 * Test Bootstrap Configuration
 *
 * This file sets up the testing environment for the t212site project.
 * It defines paths, constants, and helper functions needed for tests.
 */

// Define the root directory of the project
define('PROJECT_ROOT', dirname(__DIR__));

// Define the public_html directory
define('PUBLIC_HTML_DIR', PROJECT_ROOT . '/public_html');

// Define test directories
define('TEST_ROOT', __DIR__);
define('TEST_UNIT_DIR', TEST_ROOT . '/unit');
define('TEST_INTEGRATION_DIR', TEST_ROOT . '/integration');

// Define CREDENTIALS.json path
define('CREDENTIALS_FILE', PUBLIC_HTML_DIR . '/CREDENTIALS.json');

// Test configuration
define('TEST_MODE', true);

/**
 * Simple test assertion helper
 *
 * @param bool $condition The condition to assert
 * @param string $message The message to display if assertion fails
 * @return bool True if assertion passed
 */
function assert_true($condition, $message = '') {
    if (!$condition) {
        echo "❌ FAILED: " . $message . "\n";
        return false;
    }
    echo "✅ PASSED: " . $message . "\n";
    return true;
}

/**
 * Assert that two values are equal
 *
 * @param mixed $expected Expected value
 * @param mixed $actual Actual value
 * @param string $message Test description
 * @return bool True if assertion passed
 */
function assert_equals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        echo "❌ FAILED: " . $message . "\n";
        echo "   Expected: " . var_export($expected, true) . "\n";
        echo "   Actual: " . var_export($actual, true) . "\n";
        return false;
    }
    echo "✅ PASSED: " . $message . "\n";
    return true;
}

/**
 * Assert that a file exists
 *
 * @param string $filepath Path to file
 * @param string $message Test description
 * @return bool True if assertion passed
 */
function assert_file_exists($filepath, $message = '') {
    if (!file_exists($filepath)) {
        echo "❌ FAILED: " . $message . "\n";
        echo "   File not found: " . $filepath . "\n";
        return false;
    }
    echo "✅ PASSED: " . $message . "\n";
    return true;
}

/**
 * Assert that a condition is false
 *
 * @param bool $condition The condition to assert
 * @param string $message The message to display if assertion fails
 * @return bool True if assertion passed
 */
function assert_false($condition, $message = '') {
    if ($condition) {
        echo "❌ FAILED: " . $message . "\n";
        return false;
    }
    echo "✅ PASSED: " . $message . "\n";
    return true;
}

/**
 * Start a test suite
 *
 * @param string $name Name of the test suite
 */
function test_suite($name) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST SUITE: " . $name . "\n";
    echo str_repeat("=", 60) . "\n";
}

/**
 * Print test summary
 *
 * @param int $passed Number of passed tests
 * @param int $failed Number of failed tests
 */
function test_summary($passed, $failed) {
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "SUMMARY: ";
    if ($failed === 0) {
        echo "✅ All tests passed! ";
    } else {
        echo "❌ Some tests failed! ";
    }
    echo "(" . $passed . " passed, " . $failed . " failed)\n";
    echo str_repeat("-", 60) . "\n";
}

// Print bootstrap loaded message
if (!defined('BOOTSTRAP_SILENT')) {
    echo "Bootstrap loaded successfully.\n";
    echo "Project Root: " . PROJECT_ROOT . "\n";
    echo "Public HTML: " . PUBLIC_HTML_DIR . "\n";
    echo "Credentials File: " . CREDENTIALS_FILE . "\n\n";
}
