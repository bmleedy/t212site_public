<?php
/**
 * Test Runner
 *
 * Executes all test files in the test suite and reports results.
 * Usage: php tests/test_runner.php
 */

// Silence the bootstrap output for cleaner test runner output
define('BOOTSTRAP_SILENT', true);
require_once __DIR__ . '/bootstrap.php';

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "                    T212 SITE TEST RUNNER                          \n";
echo str_repeat("=", 70) . "\n";
echo "Running all tests...\n\n";

/**
 * Find all test files
 *
 * @param string $directory Directory to search for test files
 * @return array List of test file paths
 */
function find_test_files($directory) {
    $testFiles = [];

    // Get all PHP files in unit and integration directories
    $directories = [
        $directory . '/unit',
        $directory . '/integration'
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            continue;
        }

        $files = glob($dir . '/*Test.php');
        if ($files) {
            $testFiles = array_merge($testFiles, $files);
        }
    }

    return $testFiles;
}

/**
 * Run a test file
 *
 * @param string $testFile Path to test file
 * @return array ['success' => bool, 'output' => string, 'exitCode' => int]
 */
function run_test_file($testFile) {
    $output = [];
    $exitCode = 0;

    // Execute the test file
    exec('php ' . escapeshellarg($testFile) . ' 2>&1', $output, $exitCode);

    return [
        'success' => ($exitCode === 0),
        'output' => implode("\n", $output),
        'exitCode' => $exitCode,
        'file' => $testFile
    ];
}

// Run syntax test first (it's in the root tests directory)
$syntaxTestFile = TEST_ROOT . '/SyntaxTest.php';
$allTestsPassed = true;
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

if (file_exists($syntaxTestFile)) {
    echo "Running Syntax Tests...\n";
    echo str_repeat("-", 70) . "\n";

    $result = run_test_file($syntaxTestFile);
    echo $result['output'] . "\n";

    $totalTests++;
    if ($result['success']) {
        $passedTests++;
        echo "\n✅ Syntax tests PASSED\n\n";
    } else {
        $failedTests++;
        $allTestsPassed = false;
        echo "\n❌ Syntax tests FAILED\n\n";
    }
}

// Find and run all other test files
$testFiles = find_test_files(TEST_ROOT);

if (count($testFiles) > 0) {
    echo "Running Unit and Integration Tests...\n";
    echo str_repeat("-", 70) . "\n";

    foreach ($testFiles as $testFile) {
        $testName = basename($testFile);
        echo "\nRunning: " . $testName . "\n";
        echo str_repeat("-", 70) . "\n";

        $result = run_test_file($testFile);
        echo $result['output'] . "\n";

        $totalTests++;
        if ($result['success']) {
            $passedTests++;
            echo "\n✅ " . $testName . " PASSED\n";
        } else {
            $failedTests++;
            $allTestsPassed = false;
            echo "\n❌ " . $testName . " FAILED\n";
        }
    }
} else {
    echo "\nNo unit or integration tests found yet.\n";
    echo "(Tests will be added as we refactor each phase)\n";
}

// Print final summary
echo "\n";
echo str_repeat("=", 70) . "\n";
echo "                         FINAL SUMMARY                              \n";
echo str_repeat("=", 70) . "\n";
echo "Total Test Suites: " . $totalTests . "\n";
echo "Passed: " . $passedTests . "\n";
echo "Failed: " . $failedTests . "\n";
echo "\n";

if ($allTestsPassed) {
    echo "🎉 ✅ ALL TESTS PASSED! 🎉\n";
    echo str_repeat("=", 70) . "\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED - Please review the errors above.\n";
    echo str_repeat("=", 70) . "\n";
    exit(1);
}
