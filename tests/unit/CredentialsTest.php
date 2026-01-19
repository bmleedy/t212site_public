<?php
/**
 * Credentials Utility Unit Test
 *
 * Tests the Credentials utility class to ensure it properly loads
 * and provides access to credentials from CREDENTIALS.json.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Credentials Utility Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Verify CREDENTIALS.json file exists
// ============================================================================

echo "Test 1: CREDENTIALS.json file existence\n";
echo str_repeat("-", 60) . "\n";

if (assert_file_exists(CREDENTIALS_FILE, "CREDENTIALS.json file exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Verify Credentials class can be instantiated
// ============================================================================

echo "Test 2: Credentials class instantiation\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();
    assert_true(true, "Credentials class instantiated successfully");
    $passed++;
} catch (Exception $e) {
    assert_false(true, "Failed to instantiate Credentials class: " . $e->getMessage());
    $failed++;
    echo "\n🚨 Cannot continue tests without Credentials instance.\n";
    test_summary($passed, $failed);
    exit(1);
}

echo "\n";

// ============================================================================
// TEST 3: Verify credentials file path is correct
// ============================================================================

echo "Test 3: Credentials file path\n";
echo str_repeat("-", 60) . "\n";

$credPath = $creds->getCredentialsFilePath();
// Normalize paths for comparison
$normalizedCredFile = realpath(CREDENTIALS_FILE);
$normalizedCredPath = realpath($credPath);

if (assert_equals($normalizedCredFile, $normalizedCredPath, "Credentials file path is correct")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Database credentials
// ============================================================================

echo "Test 4: Database credentials\n";
echo str_repeat("-", 60) . "\n";

$dbUser = $creds->getDatabaseUser();
$dbPass = $creds->getDatabasePassword();
$dbName = $creds->getDatabaseName();
$dbHost = $creds->getDatabaseHost();

if (assert_true(!empty($dbUser), "Database username is not empty")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(!empty($dbPass), "Database password is not empty")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(!empty($dbName), "Database name is not empty")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(!empty($dbHost), "Database host is not empty")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 5: SMTP email credentials
// ============================================================================

echo "Test 5: SMTP email credentials\n";
echo str_repeat("-", 60) . "\n";

$smtpUser = $creds->getSMTPUsername();
$smtpPass = $creds->getSMTPPassword();

if (assert_true(!empty($smtpUser), "SMTP username is not empty")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(!empty($smtpPass), "SMTP password is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Validate email format (simple check for @ symbol)
if (assert_true(strpos($smtpUser, '@') !== false, "SMTP username contains @ symbol (email format)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Cookie secret key
// ============================================================================

echo "Test 6: Cookie secret key\n";
echo str_repeat("-", 60) . "\n";

$cookieSecret = $creds->getCookieSecretKey();

if (assert_true(!empty($cookieSecret), "Cookie secret key is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Ensure it's at least 10 characters long for security
if (assert_true(strlen($cookieSecret) >= 10, "Cookie secret key is at least 10 characters")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: PayPal JavaScript SDK credentials
// ============================================================================

echo "Test 7: PayPal JavaScript SDK credentials\n";
echo str_repeat("-", 60) . "\n";

// Test getPayPalClientId() - returns production or sandbox based on environment
$ppClientId = $creds->getPayPalClientId();

if (assert_true(!empty($ppClientId), "PayPal Client ID is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Validate Client ID is sufficiently long (typically 80 chars)
if (assert_true(strlen($ppClientId) >= 50, "PayPal Client ID has valid length")) {
    $passed++;
} else {
    $failed++;
}

// Test getPayPalProductionClientId()
$ppProdClientId = $creds->getPayPalProductionClientId();

if (assert_true(!empty($ppProdClientId), "PayPal production Client ID is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Test getPayPalSandboxClientId()
$ppSandboxClientId = $creds->getPayPalSandboxClientId();

if (assert_true(!empty($ppSandboxClientId), "PayPal sandbox Client ID is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Test getPayPalEnvironment() returns valid value
$ppEnvironment = $creds->getPayPalEnvironment();

if (assert_true(in_array($ppEnvironment, ['production', 'sandbox']), "PayPal environment is 'production' or 'sandbox'")) {
    $passed++;
} else {
    $failed++;
}

// Test isPayPalSandbox() returns boolean
$isSandbox = $creds->isPayPalSandbox();

if (assert_true(is_bool($isSandbox), "isPayPalSandbox() returns boolean")) {
    $passed++;
} else {
    $failed++;
}

// Verify isPayPalSandbox() matches environment
$expectedSandbox = ($ppEnvironment === 'sandbox');
if (assert_equals($expectedSandbox, $isSandbox, "isPayPalSandbox() matches environment setting")) {
    $passed++;
} else {
    $failed++;
}

// Verify getPayPalClientId() returns correct ID based on environment
if ($ppEnvironment === 'sandbox') {
    $expectedClientId = $ppSandboxClientId;
} else {
    $expectedClientId = $ppProdClientId;
}

if (assert_equals($expectedClientId, $ppClientId, "getPayPalClientId() returns correct ID for environment")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Google credentials
// ============================================================================

echo "Test 8: Google credentials\n";
echo str_repeat("-", 60) . "\n";

$googleEmail = $creds->getGoogleEmail();
$googlePass = $creds->getGooglePassword();

if (assert_true(!empty($googleEmail), "Google email is not empty")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(!empty($googlePass), "Google password is not empty")) {
    $passed++;
} else {
    $failed++;
}

// Validate email format (simple check for @ symbol)
if (assert_true(strpos($googleEmail, '@') !== false, "Google email contains @ symbol (email format)")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: Singleton pattern (same instance returned)
// ============================================================================

echo "Test 9: Singleton pattern\n";
echo str_repeat("-", 60) . "\n";

$creds2 = Credentials::getInstance();

if (assert_true($creds === $creds2, "Singleton pattern returns same instance")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All credentials utility tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some credentials utility tests failed!\n";
    echo "Please check CREDENTIALS.json file structure and content.\n";
    exit(1);
}
