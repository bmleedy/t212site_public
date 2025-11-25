<?php
/**
 * PHP Syntax Validation Test
 *
 * This test validates that all PHP files in the project have correct syntax.
 * Uses `php -l` (lint) to check each file.
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

test_suite("PHP Syntax Validation");

/**
 * Find all PHP files in a directory recursively
 *
 * @param string $directory Directory to search
 * @param array $excludeDirs Directories to exclude
 * @return array List of PHP file paths
 */
function find_php_files($directory, $excludeDirs = []) {
    $phpFiles = [];
    $defaultExclude = ['vendor', 'node_modules', '.git', 'tests'];
    $excludeDirs = array_merge($defaultExclude, $excludeDirs);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        // Skip if in excluded directory
        $skip = false;
        foreach ($excludeDirs as $excludeDir) {
            if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . $excludeDir . DIRECTORY_SEPARATOR) !== false) {
                $skip = true;
                break;
            }
        }

        if ($skip) {
            continue;
        }

        // Add PHP files
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }

    return $phpFiles;
}

/**
 * Check PHP file syntax using php -l
 *
 * @param string $filepath Path to PHP file
 * @return array ['success' => bool, 'output' => string]
 */
function check_php_syntax($filepath) {
    $output = [];
    $returnCode = 0;

    exec('php -l ' . escapeshellarg($filepath) . ' 2>&1', $output, $returnCode);

    return [
        'success' => ($returnCode === 0),
        'output' => implode("\n", $output),
        'file' => $filepath
    ];
}

// Main test execution
echo "Finding PHP files in project...\n";
$phpFiles = find_php_files(PUBLIC_HTML_DIR);
echo "Found " . count($phpFiles) . " PHP files to check.\n\n";

$passed = 0;
$failed = 0;
$errors = [];

echo "Running syntax checks...\n";
echo str_repeat("-", 60) . "\n";

foreach ($phpFiles as $file) {
    $result = check_php_syntax($file);

    // Get relative path for cleaner output
    $relativePath = str_replace(PROJECT_ROOT . '/', '', $file);

    if ($result['success']) {
        echo "✅ " . $relativePath . "\n";
        $passed++;
    } else {
        echo "❌ " . $relativePath . "\n";
        echo "   Error: " . $result['output'] . "\n";
        $failed++;
        $errors[] = [
            'file' => $relativePath,
            'error' => $result['output']
        ];
    }
}

// Print summary
test_summary($passed, $failed);

// Print detailed error report if there are failures
if ($failed > 0) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "DETAILED ERROR REPORT\n";
    echo str_repeat("=", 60) . "\n";

    foreach ($errors as $error) {
        echo "\nFile: " . $error['file'] . "\n";
        echo "Error: " . $error['error'] . "\n";
        echo str_repeat("-", 60) . "\n";
    }

    exit(1); // Exit with error code
}

echo "\n✅ All PHP files have valid syntax!\n";
exit(0); // Exit with success code
