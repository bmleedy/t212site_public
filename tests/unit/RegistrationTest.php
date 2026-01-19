<?php
/**
 * Registration Class Unit Tests
 *
 * Tests the Registration class functionality including:
 * - Security improvements (cryptographic token generation)
 * - Code structure validation
 * - Pattern verification
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Registration Class Unit Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify Registration.php file exists
// ============================================================================

echo "Test 1: Registration.php file existence\n";
echo str_repeat("-", 60) . "\n";

$registrationFile = PUBLIC_HTML_DIR . '/login/classes/Registration.php';

if (assert_file_exists($registrationFile, "Registration.php file exists")) {
    $passed++;
} else {
    $failed++;
    echo "\n🚨 Cannot continue tests without Registration.php file.\n";
    test_summary($passed, $failed);
    exit(1);
}

echo "\n";

// ============================================================================
// TEST 2: Verify strict_types declaration
// ============================================================================

echo "Test 2: strict_types declaration\n";
echo str_repeat("-", 60) . "\n";

$content = file_get_contents($registrationFile);

if (assert_true(strpos($content, 'declare(strict_types=1)') !== false, "File uses declare(strict_types=1)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify cryptographically secure token generation
// ============================================================================

echo "Test 3: Cryptographically secure token generation\n";
echo str_repeat("-", 60) . "\n";

// Check that random_bytes is used instead of sha1(uniqid(mt_rand()))
if (assert_true(strpos($content, 'random_bytes') !== false, "Uses random_bytes() for token generation")) {
    $passed++;
} else {
    $failed++;
}

// Check that the old insecure pattern is NOT present
if (assert_true(strpos($content, 'sha1(uniqid(mt_rand()') === false, "Does NOT use insecure sha1(uniqid(mt_rand())) pattern")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify hardcoded email removed
// ============================================================================

echo "Test 4: Hardcoded email address removed\n";
echo str_repeat("-", 60) . "\n";

// The hardcoded debug email should not be present
if (assert_true(strpos($content, 'mscdaryl@gmail.com') === false, "Hardcoded debug email address removed")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify error message security
// ============================================================================

echo "Test 5: Error message security (no database error leakage)\n";
echo str_repeat("-", 60) . "\n";

// Check that database errors use error_log instead of exposing to users
if (assert_true(strpos($content, 'error_log(') !== false, "Uses error_log() for error logging")) {
    $passed++;
} else {
    $failed++;
}

// Check that we don't expose getMessage() to users in errors array
// The pattern we want to avoid: $this->errors[] = ... $e->getMessage()
preg_match_all('/\$this->errors\[\].*\$e->getMessage\(\)/', $content, $matches);
if (assert_true(count($matches[0]) === 0, "Does NOT expose PDO exception messages to users")) {
    $passed++;
} else {
    $failed++;
    echo "   Found " . count($matches[0]) . " instances of error leakage\n";
}

echo "\n";

// ============================================================================
// TEST 6: Verify helper methods exist
// ============================================================================

echo "Test 6: Helper methods for code reuse\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(strpos($content, 'private function configureMailer') !== false, "configureMailer() helper method exists")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(strpos($content, 'private function logEmailActivity') !== false, "logEmailActivity() helper method exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify early return pattern
// ============================================================================

echo "Test 7: Early return validation pattern\n";
echo str_repeat("-", 60) . "\n";

// Count the number of early returns in registerNewUser (should have multiple)
preg_match_all('/\$this->errors\[\].*;\s*return;/', $content, $earlyReturns);
$earlyReturnCount = count($earlyReturns[0]);

if (assert_true($earlyReturnCount >= 8, "registerNewUser uses early return pattern ($earlyReturnCount early returns found)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify dead code removed
// ============================================================================

echo "Test 8: Dead code removed\n";
echo str_repeat("-", 60) . "\n";

// Check for commented-out code blocks (/* */ blocks that are NOT PHPDoc)
// PHPDoc blocks start with /** while dead code starts with just /*
preg_match_all('/\/\*[^*][\s\S]{50,}?\*\//', $content, $largeComments);
$largeCommentCount = count($largeComments[0]);

if (assert_true($largeCommentCount === 0, "No large commented-out code blocks ($largeCommentCount found)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify password hashing uses proper options
// ============================================================================

echo "Test 9: Password hashing configuration\n";
echo str_repeat("-", 60) . "\n";

// Check that password_hash uses options array properly
if (assert_true(strpos($content, "password_hash(\$user_password, PASSWORD_DEFAULT, \$options)") !== false, "Uses password_hash with options variable")) {
    $passed++;
} else {
    $failed++;
}

// Check that cost factor is cast to int
if (assert_true(strpos($content, "(int)HASH_COST_FACTOR") !== false, "Casts HASH_COST_FACTOR to int")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: Verify return type declarations
// ============================================================================

echo "Test 10: Return type declarations\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(strpos($content, '): bool') !== false, "Uses bool return type declarations")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(strpos($content, '): void') !== false, "Uses void return type declarations")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: Verify PHPDoc comments present
// ============================================================================

echo "Test 11: PHPDoc documentation\n";
echo str_repeat("-", 60) . "\n";

preg_match_all('/\/\*\*[\s\S]*?\*\//', $content, $phpDocBlocks);
$phpDocCount = count($phpDocBlocks[0]);

if (assert_true($phpDocCount >= 8, "Has sufficient PHPDoc documentation blocks ($phpDocCount found)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 12: Test random_bytes output format
// ============================================================================

echo "Test 12: Activation hash format (hex encoded)\n";
echo str_repeat("-", 60) . "\n";

// Verify the pattern bin2hex(random_bytes(20)) which produces 40-char hex string
if (assert_true(strpos($content, 'bin2hex(random_bytes(20))') !== false, "Uses bin2hex(random_bytes(20)) for 40-char hex hash")) {
    $passed++;
} else {
    $failed++;
}

// Actually test that random_bytes produces expected output
$testHash = bin2hex(random_bytes(20));
if (assert_true(strlen($testHash) === 40, "Generated hash is 40 characters")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(ctype_xdigit($testHash), "Generated hash is valid hexadecimal")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All Registration class tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some Registration class tests failed!\n";
    exit(1);
}
