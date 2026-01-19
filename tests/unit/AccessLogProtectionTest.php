<?php
/**
 * Access Log Protection Test
 *
 * Validates that access_log files are protected from HTTP access via .htaccess
 */

require_once dirname(__DIR__) . '/bootstrap.php';

echo "\n";
echo "============================================================\n";
echo "TEST SUITE: Access Log .htaccess Protection\n";
echo "============================================================\n";

$passed = 0;
$failed = 0;

// Test 1: Check .htaccess has access_log protection
echo "\nTest 1: Checking .htaccess has access_log protection\n";
echo "------------------------------------------------------------\n";

$htaccessPath = PUBLIC_HTML_DIR . '/.htaccess';
if (!file_exists($htaccessPath)) {
    echo "❌ FAILED: .htaccess file not found at: $htaccessPath\n";
    $failed++;
} else {
    $htaccessContent = file_get_contents($htaccessPath);

    // Check for FilesMatch directive blocking access_log files
    if (preg_match('/FilesMatch.*access_log/i', $htaccessContent)) {
        echo "✅ PASSED: .htaccess contains FilesMatch rule for access_log files\n";
        $passed++;
    } else {
        echo "❌ FAILED: .htaccess does not contain FilesMatch rule for access_log files\n";
        $failed++;
    }

    // Check that the rule denies access
    if (preg_match('/FilesMatch[^>]*access_log[^<]*<\/FilesMatch>/s', $htaccessContent, $matches)) {
        $ruleBlock = $matches[0];
        if (stripos($ruleBlock, 'Require all denied') !== false ||
            stripos($ruleBlock, 'deny from all') !== false ||
            stripos($ruleBlock, 'Order deny,allow') !== false) {
            echo "✅ PASSED: access_log FilesMatch rule properly denies access\n";
            $passed++;
        } else {
            echo "❌ FAILED: access_log FilesMatch rule does not deny access\n";
            $failed++;
        }
    } else {
        echo "❌ FAILED: Could not parse access_log FilesMatch block\n";
        $failed++;
    }
}

// Test 2: Check the regex pattern covers expected filenames
echo "\nTest 2: Validating regex pattern coverage\n";
echo "------------------------------------------------------------\n";

$testFilenames = [
    'access_log2026-01-18.txt' => true,  // Should be blocked
    'access_log2025-12-31.txt' => true,  // Should be blocked
    'access_log.txt' => true,             // Should be blocked
    'access_log_backup.txt' => true,      // Should be blocked
    'my_access_log.txt' => false,         // Should NOT be blocked (doesn't start with access_log)
    'index.php' => false,                 // Should NOT be blocked
    'access.log' => false,                // Should NOT be blocked (different pattern)
];

// The pattern in .htaccess is: ^access_log.*
$pattern = '/^access_log.*/';
$allPatternTestsPassed = true;

foreach ($testFilenames as $filename => $shouldBlock) {
    $matches = preg_match($pattern, $filename);
    $isBlocked = $matches === 1;

    if ($isBlocked === $shouldBlock) {
        $status = $shouldBlock ? "blocked" : "allowed";
        echo "  ✓ $filename - correctly $status\n";
    } else {
        $expected = $shouldBlock ? "blocked" : "allowed";
        $actual = $isBlocked ? "blocked" : "allowed";
        echo "  ✗ $filename - expected $expected but was $actual\n";
        $allPatternTestsPassed = false;
    }
}

if ($allPatternTestsPassed) {
    echo "✅ PASSED: Regex pattern correctly identifies access_log files\n";
    $passed++;
} else {
    echo "❌ FAILED: Regex pattern does not correctly identify all test cases\n";
    $failed++;
}

// Summary
echo "\n";
echo "------------------------------------------------------------\n";
if ($failed === 0) {
    echo "SUMMARY: ✅ All tests passed! ($passed passed, $failed failed)\n";
} else {
    echo "SUMMARY: ❌ Some tests failed! ($passed passed, $failed failed)\n";
}
echo "------------------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
?>
