<?php
/**
 * Login Security Unit Tests
 *
 * Tests the Login class for security best practices including:
 * - Secure token generation (using random_bytes)
 * - Session regeneration on login
 * - Secure cookie flags
 * - Password length requirements
 * - HTTPS for external URLs
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Login Security Tests");

$passed = 0;
$failed = 0;

// Read the Login.php file content for static analysis
$loginFilePath = PUBLIC_HTML_DIR . '/login/classes/Login.php';
$loginFileContent = file_get_contents($loginFilePath);

// ============================================================================
// TEST 1: Verify Login.php file exists
// ============================================================================

echo "Test 1: Login.php file existence\n";
echo str_repeat("-", 60) . "\n";

if (assert_file_exists($loginFilePath, "Login.php file exists")) {
    $passed++;
} else {
    $failed++;
    echo "\n! Cannot continue tests without Login.php file.\n";
    test_summary($passed, $failed);
    exit(1);
}

echo "\n";

// ============================================================================
// TEST 2: Verify secure token generation (random_bytes)
// ============================================================================

echo "Test 2: Secure token generation\n";
echo str_repeat("-", 60) . "\n";

// Check that random_bytes is used for token generation
$usesRandomBytes = preg_match('/random_bytes\s*\(\s*32\s*\)/', $loginFileContent);

if (assert_true($usesRandomBytes === 1, "Login.php uses random_bytes(32) for secure token generation")) {
    $passed++;
} else {
    $failed++;
}

// Check that mt_rand is NOT used for token generation
$usesMtRand = preg_match('/mt_rand\s*\(\s*\)/', $loginFileContent);

if (assert_true($usesMtRand === 0, "Login.php does not use insecure mt_rand() for tokens")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 3: Verify session regeneration on login
// ============================================================================

echo "Test 3: Session regeneration on login\n";
echo str_repeat("-", 60) . "\n";

// Check that session_regenerate_id is called
$usesSessionRegenerate = preg_match('/session_regenerate_id\s*\(\s*true\s*\)/', $loginFileContent);

if (assert_true($usesSessionRegenerate === 1, "Login.php calls session_regenerate_id(true) on login")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Verify secure cookie flags
// ============================================================================

echo "Test 4: Secure cookie flags for remember-me cookies\n";
echo str_repeat("-", 60) . "\n";

// Check for httponly flag in setcookie
$hasHttpOnly = preg_match("/'httponly'\s*=>\s*true/", $loginFileContent);

if (assert_true($hasHttpOnly === 1, "Remember-me cookies use httponly flag")) {
    $passed++;
} else {
    $failed++;
}

// Check for secure flag in setcookie
$hasSecure = preg_match("/'secure'\s*=>/", $loginFileContent);

if (assert_true($hasSecure === 1, "Remember-me cookies use secure flag")) {
    $passed++;
} else {
    $failed++;
}

// Check for samesite flag in setcookie
$hasSameSite = preg_match("/'samesite'\s*=>\s*'Lax'/", $loginFileContent);

if (assert_true($hasSameSite === 1, "Remember-me cookies use samesite=Lax flag")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: Verify minimum password length
// ============================================================================

echo "Test 5: Minimum password length requirement\n";
echo str_repeat("-", 60) . "\n";

// Check that password length check uses 8 (not 6)
$uses8CharMinimum = preg_match('/strlen\s*\(\s*\$user_password_new\s*\)\s*<\s*8/', $loginFileContent);

if (assert_true($uses8CharMinimum >= 1, "Login.php requires minimum 8-character passwords")) {
    $passed++;
} else {
    $failed++;
}

// Check that password length check does NOT use 6
$uses6CharMinimum = preg_match('/strlen\s*\(\s*\$user_password_new\s*\)\s*<\s*6/', $loginFileContent);

if (assert_true($uses6CharMinimum === 0, "Login.php does not use weak 6-character password minimum")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify HTTPS for Gravatar URL
// ============================================================================

echo "Test 6: HTTPS for external URLs\n";
echo str_repeat("-", 60) . "\n";

// Check that Gravatar URL uses HTTPS
$usesHttpsGravatar = preg_match("/https:\/\/www\.gravatar\.com\/avatar/", $loginFileContent);

if (assert_true($usesHttpsGravatar === 1, "Gravatar URL uses HTTPS")) {
    $passed++;
} else {
    $failed++;
}

// Check that Gravatar URL does NOT use HTTP
$usesHttpGravatar = preg_match("/[^s]http:\/\/www\.gravatar\.com\/avatar/", $loginFileContent);

if (assert_true($usesHttpGravatar === 0, "Gravatar URL does not use insecure HTTP")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Verify no insecure password reset token generation
// ============================================================================

echo "Test 7: Secure password reset token generation\n";
echo str_repeat("-", 60) . "\n";

// Check that sha1(uniqid(mt_rand)) pattern is NOT used
$usesInsecurePattern = preg_match('/sha1\s*\(\s*uniqid\s*\(\s*mt_rand/', $loginFileContent);

if (assert_true($usesInsecurePattern === 0, "Login.php does not use insecure sha1(uniqid(mt_rand)) pattern")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Verify prepared statements are used
// ============================================================================

echo "Test 8: Prepared statements usage\n";
echo str_repeat("-", 60) . "\n";

// Check that prepared statements are used
$usesPrepare = preg_match_all('/->prepare\s*\(/', $loginFileContent, $matches);

if (assert_true($usesPrepare > 0, "Login.php uses prepared statements (found $usesPrepare occurrences)")) {
    $passed++;
} else {
    $failed++;
}

// Check for bindValue or bindParam usage
$usesBindValue = preg_match_all('/->bindValue\s*\(/', $loginFileContent, $matches);

if (assert_true($usesBindValue > 0, "Login.php uses bindValue for parameters (found $usesBindValue occurrences)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Verify password_hash and password_verify are used
// ============================================================================

echo "Test 9: Secure password hashing\n";
echo str_repeat("-", 60) . "\n";

// Check that password_hash is used
$usesPasswordHash = preg_match('/password_hash\s*\(/', $loginFileContent);

if (assert_true($usesPasswordHash === 1, "Login.php uses password_hash() for hashing passwords")) {
    $passed++;
} else {
    $failed++;
}

// Check that password_verify is used
$usesPasswordVerify = preg_match('/password_verify\s*\(/', $loginFileContent);

if (assert_true($usesPasswordVerify === 1, "Login.php uses password_verify() for checking passwords")) {
    $passed++;
} else {
    $failed++;
}

// Check that PASSWORD_DEFAULT is used
$usesPasswordDefault = preg_match('/PASSWORD_DEFAULT/', $loginFileContent);

if (assert_true($usesPasswordDefault === 1, "Login.php uses PASSWORD_DEFAULT for bcrypt algorithm")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n All login security tests passed!\n";
    exit(0);
} else {
    echo "\n Some login security tests failed!\n";
    echo "Please review the Login.php file for security issues.\n";
    exit(1);
}
