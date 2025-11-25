<?php
/**
 * Cookie Secret Key Test
 *
 * Tests that the cookie secret key is properly loaded from CREDENTIALS.json
 * and that the COOKIE_SECRET_KEY constant is set correctly.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("Cookie Secret Key Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: Cookie secret key loads from CREDENTIALS.json
// ============================================================================

echo "Test 1: Cookie secret key loads from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();

    $cookieSecret = $creds->getCookieSecretKey();

    if (assert_true(!empty($cookieSecret), "Cookie secret key loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify cookie secret has minimum length (should be at least 10 chars for security)
    if (assert_true(strlen($cookieSecret) >= 10, "Cookie secret key has minimum length (10+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify cookie secret contains a mix of characters (not just numbers or letters)
    $hasLetters = preg_match('/[a-zA-Z]/', $cookieSecret);
    $hasNumbers = preg_match('/[0-9]/', $cookieSecret);

    if (assert_true($hasLetters && $hasNumbers, "Cookie secret key has mixed character types")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load cookie secret key: " . $e->getMessage());
    $failed += 3;
}

echo "\n";

// ============================================================================
// TEST 2: COOKIE_SECRET_KEY constant is set correctly
// ============================================================================

echo "Test 2: COOKIE_SECRET_KEY constant is set correctly\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the config file (this should define COOKIE_SECRET_KEY constant)
    require_once PUBLIC_HTML_DIR . '/login/config/config.php';

    // Check that COOKIE_SECRET_KEY constant is defined
    if (assert_true(defined('COOKIE_SECRET_KEY'), "COOKIE_SECRET_KEY constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that COOKIE_SECRET_KEY is not empty
    if (assert_true(!empty(COOKIE_SECRET_KEY), "COOKIE_SECRET_KEY is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that COOKIE_SECRET_KEY has minimum length
    if (assert_true(strlen(COOKIE_SECRET_KEY) >= 10, "COOKIE_SECRET_KEY has minimum length")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Exception loading config.php: " . $e->getMessage());
    $failed += 3;
}

echo "\n";

// ============================================================================
// TEST 3: COOKIE_SECRET_KEY constant matches credentials
// ============================================================================

echo "Test 3: COOKIE_SECRET_KEY constant matches credentials\n";
echo str_repeat("-", 60) . "\n";

if (defined('COOKIE_SECRET_KEY') && isset($cookieSecret)) {
    if (assert_equals($cookieSecret, COOKIE_SECRET_KEY, "COOKIE_SECRET_KEY matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "⚠️  Skipping - COOKIE_SECRET_KEY not defined or credentials not loaded\n";
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 4: Other cookie constants are set correctly
// ============================================================================

echo "Test 4: Other cookie constants are set correctly\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(defined('COOKIE_RUNTIME'), "COOKIE_RUNTIME constant is defined")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(defined('COOKIE_DOMAIN'), "COOKIE_DOMAIN constant is defined")) {
    $passed++;
} else {
    $failed++;
}

// COOKIE_RUNTIME should be a positive integer
if (defined('COOKIE_RUNTIME')) {
    if (assert_true(is_numeric(COOKIE_RUNTIME) && COOKIE_RUNTIME > 0, "COOKIE_RUNTIME is a positive number")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "⚠️  Skipping - COOKIE_RUNTIME not defined\n";
    $failed++;
}

// COOKIE_DOMAIN should be a string (can be empty or have a domain)
if (defined('COOKIE_DOMAIN')) {
    if (assert_true(is_string(COOKIE_DOMAIN), "COOKIE_DOMAIN is a string")) {
        $passed++;
    } else {
        $failed++;
    }
} else {
    echo "⚠️  Skipping - COOKIE_DOMAIN not defined\n";
    $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All cookie secret key tests passed!\n";
    echo "Cookie secret key is properly loaded and configured.\n";
    exit(0);
} else {
    echo "\n❌ Some cookie secret key tests failed!\n";
    echo "Please check:\n";
    echo "  1. CREDENTIALS.json has a valid cookie_secret_key\n";
    echo "  2. login/config/config.php is loading the secret properly\n";
    exit(1);
}
