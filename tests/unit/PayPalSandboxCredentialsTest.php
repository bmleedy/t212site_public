<?php
/**
 * PayPal Sandbox Credentials Test
 *
 * Tests that PayPal sandbox credentials are properly loaded from CREDENTIALS.json
 * and that PayPal configuration files use these credentials correctly.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("PayPal Sandbox Credentials Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: PayPal sandbox credentials load from CREDENTIALS.json
// ============================================================================

echo "Test 1: PayPal sandbox credentials load from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();

    $ppUser = $creds->getPayPalSandboxUsername();
    $ppPass = $creds->getPayPalSandboxPassword();
    $ppSig = $creds->getPayPalSandboxSignature();
    $ppAppId = $creds->getPayPalSandboxAppId();

    if (assert_true(!empty($ppUser), "PayPal sandbox username loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppPass), "PayPal sandbox password loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppSig), "PayPal sandbox signature loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppAppId), "PayPal sandbox App ID loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load PayPal sandbox credentials: " . $e->getMessage());
    $failed += 4;
}

echo "\n";

// ============================================================================
// TEST 2: PayPal sandbox credentials format validation
// ============================================================================

echo "Test 2: PayPal sandbox credentials format validation\n";
echo str_repeat("-", 60) . "\n";

if (isset($ppUser, $ppPass, $ppSig, $ppAppId)) {
    // Username should be in API format (contains _api1. or @)
    $hasApiFormat = (strpos($ppUser, '_api1.') !== false) || (strpos($ppUser, '@') !== false);
    if (assert_true($hasApiFormat, "PayPal sandbox username is in valid format")) {
        $passed++;
    } else {
        $failed++;
    }

    // Password should have minimum length
    if (assert_true(strlen($ppPass) >= 6, "PayPal sandbox password has minimum length (6+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // Signature should have minimum length (PayPal signatures are typically 56 chars)
    if (assert_true(strlen($ppSig) >= 40, "PayPal sandbox signature has minimum length (40+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // App ID should start with "APP-"
    if (assert_true(strpos($ppAppId, 'APP-') === 0, "PayPal sandbox App ID starts with 'APP-'")) {
        $passed++;
    } else {
        $failed++;
    }

    // App ID should have reasonable length (typically around 21-25 chars)
    if (assert_true(strlen($ppAppId) >= 15, "PayPal sandbox App ID has reasonable length")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - PayPal sandbox credentials not loaded\n";
    $failed += 5;
}

echo "\n";

// ============================================================================
// TEST 3: Checkout/paypal_config.php sandbox constants are set correctly
// ============================================================================

echo "Test 3: Checkout/paypal_config.php sandbox constants are set correctly\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the paypal_config.php file (if not already loaded)
    if (!defined('PP_USER_SANDBOX')) {
        require_once PUBLIC_HTML_DIR . '/Checkout/paypal_config.php';
    }

    // Check that sandbox constants are defined
    if (assert_true(defined('PP_USER_SANDBOX'), "PP_USER_SANDBOX constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('PP_PASSWORD_SANDBOX'), "PP_PASSWORD_SANDBOX constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('PP_SIGNATURE_SANDBOX'), "PP_SIGNATURE_SANDBOX constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that sandbox constants have values
    if (assert_true(!empty(PP_USER_SANDBOX), "PP_USER_SANDBOX is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(PP_PASSWORD_SANDBOX), "PP_PASSWORD_SANDBOX is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(PP_SIGNATURE_SANDBOX), "PP_SIGNATURE_SANDBOX is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Exception loading paypal_config.php: " . $e->getMessage());
    $failed += 6;
}

echo "\n";

// ============================================================================
// TEST 4: Sandbox constants match credentials
// ============================================================================

echo "Test 4: Sandbox constants match credentials from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

if (defined('PP_USER_SANDBOX') && defined('PP_PASSWORD_SANDBOX') && defined('PP_SIGNATURE_SANDBOX') && isset($ppUser, $ppPass, $ppSig)) {
    if (assert_equals($ppUser, PP_USER_SANDBOX, "PP_USER_SANDBOX matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppPass, PP_PASSWORD_SANDBOX, "PP_PASSWORD_SANDBOX matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppSig, PP_SIGNATURE_SANDBOX, "PP_SIGNATURE_SANDBOX matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - PayPal sandbox constants or credentials not available\n";
    $failed += 3;
}

echo "\n";

// ============================================================================
// TEST 5: Verify sandbox App ID differs from production
// ============================================================================

echo "Test 5: Verify sandbox App ID differs from production\n";
echo str_repeat("-", 60) . "\n";

try {
    $prodAppId = $creds->getPayPalProductionAppId();

    // Sandbox and production App IDs should be different
    if (assert_true($ppAppId !== $prodAppId, "Sandbox App ID differs from production")) {
        $passed++;
    } else {
        $failed++;
    }

    // Note: It's OK if sandbox username/password/signature are the same as production.
    // Some users may use the same PayPal account for both environments during testing.
    echo "ℹ️  INFO: Sandbox and production may share the same credentials\n";
    echo "   This is acceptable for testing environments.\n";

} catch (Exception $e) {
    assert_false(true, "Exception comparing sandbox and production App IDs: " . $e->getMessage());
    $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Verify Credentials class has sandbox getter methods
// ============================================================================

echo "Test 6: Verify Credentials class has sandbox getter methods\n";
echo str_repeat("-", 60) . "\n";

if (assert_true(method_exists($creds, 'getPayPalSandboxUsername'), "getPayPalSandboxUsername method exists")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(method_exists($creds, 'getPayPalSandboxPassword'), "getPayPalSandboxPassword method exists")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(method_exists($creds, 'getPayPalSandboxSignature'), "getPayPalSandboxSignature method exists")) {
    $passed++;
} else {
    $failed++;
}

if (assert_true(method_exists($creds, 'getPayPalSandboxAppId'), "getPayPalSandboxAppId method exists")) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All PayPal sandbox credentials tests passed!\n";
    echo "PayPal sandbox credentials are properly loaded and configured.\n";
    exit(0);
} else {
    echo "\n❌ Some PayPal sandbox credentials tests failed!\n";
    echo "Please check:\n";
    echo "  1. CREDENTIALS.json has correct PayPal sandbox credentials\n";
    echo "  2. Checkout/paypal_config.php is loading sandbox credentials properly\n";
    echo "  3. Sandbox credentials differ from production credentials\n";
    exit(1);
}
