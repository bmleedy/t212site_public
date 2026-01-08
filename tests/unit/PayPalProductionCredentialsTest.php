<?php
/**
 * PayPal Production Credentials Test
 *
 * Tests that PayPal production credentials are properly loaded from CREDENTIALS.json
 * and that PayPal configuration files use these credentials correctly.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("PayPal Production Credentials Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: PayPal production credentials load from CREDENTIALS.json
// ============================================================================

echo "Test 1: PayPal production credentials load from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();

    $ppUser = $creds->getPayPalProductionUsername();
    $ppPass = $creds->getPayPalProductionPassword();
    $ppSig = $creds->getPayPalProductionSignature();
    $ppAppId = $creds->getPayPalProductionAppId();

    if (assert_true(!empty($ppUser), "PayPal production username loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppPass), "PayPal production password loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppSig), "PayPal production signature loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($ppAppId), "PayPal production App ID loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load PayPal production credentials: " . $e->getMessage());
    $failed += 4;
}

echo "\n";

// ============================================================================
// TEST 2: PayPal production credentials format validation
// ============================================================================

echo "Test 2: PayPal production credentials format validation\n";
echo str_repeat("-", 60) . "\n";

if (isset($ppUser, $ppPass, $ppSig, $ppAppId)) {
    // Username should be in API format (contains _api1.)
    if (assert_true(strpos($ppUser, '_api1.') !== false, "PayPal username is in API format (_api1.)")) {
        $passed++;
    } else {
        $failed++;
    }

    // Password should have minimum length
    if (assert_true(strlen($ppPass) >= 8, "PayPal password has minimum length (8+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // Signature should have minimum length (PayPal signatures are typically 56 chars)
    if (assert_true(strlen($ppSig) >= 40, "PayPal signature has minimum length (40+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // App ID should start with "APP-"
    if (assert_true(strpos($ppAppId, 'APP-') === 0, "PayPal App ID starts with 'APP-'")) {
        $passed++;
    } else {
        $failed++;
    }

    // App ID should have reasonable length (typically around 21-25 chars)
    if (assert_true(strlen($ppAppId) >= 15, "PayPal App ID has reasonable length")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - PayPal credentials not loaded\n";
    $failed += 5;
}

echo "\n";

// ============================================================================
// TEST 3: Checkout/paypal_config.php constants are set correctly
// ============================================================================

echo "Test 3: Checkout/paypal_config.php constants are set correctly\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the paypal_config.php file
    require_once PUBLIC_HTML_DIR . '/Checkout/paypal_config.php';

    // Check that constants are defined
    if (assert_true(defined('PP_USER'), "PP_USER constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('PP_PASSWORD'), "PP_PASSWORD constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(defined('PP_SIGNATURE'), "PP_SIGNATURE constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that constants have values
    if (assert_true(!empty(PP_USER), "PP_USER is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(PP_PASSWORD), "PP_PASSWORD is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(PP_SIGNATURE), "PP_SIGNATURE is not empty")) {
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
// TEST 4: Constants match credentials
// ============================================================================

echo "Test 4: Constants match credentials from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

if (defined('PP_USER') && defined('PP_PASSWORD') && defined('PP_SIGNATURE') && isset($ppUser, $ppPass, $ppSig)) {
    if (assert_equals($ppUser, PP_USER, "PP_USER matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppPass, PP_PASSWORD, "PP_PASSWORD matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppSig, PP_SIGNATURE, "PP_SIGNATURE matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - PayPal constants or credentials not available\n";
    $failed += 3;
}

echo "\n";

// ============================================================================
// TEST 5: PayPal/Configuration.php returns correct credentials
// ============================================================================

echo "Test 5: PayPal/Configuration.php returns correct credentials\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the Configuration class
    require_once PUBLIC_HTML_DIR . '/PayPal/Configuration.php';

    $config = Configuration::getAcctAndConfig();

    // Check that credentials are in the config array
    if (assert_true(isset($config['acct1.UserName']), "acct1.UserName is set in config")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(isset($config['acct1.Password']), "acct1.Password is set in config")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(isset($config['acct1.Signature']), "acct1.Signature is set in config")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(isset($config['acct1.AppId']), "acct1.AppId is set in config")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that values are not empty
    if (isset($config['acct1.UserName'])) {
        if (assert_true(!empty($config['acct1.UserName']), "acct1.UserName is not empty")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        $failed++;
    }

    if (isset($config['acct1.Password'])) {
        if (assert_true(!empty($config['acct1.Password']), "acct1.Password is not empty")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        $failed++;
    }

    if (isset($config['acct1.Signature'])) {
        if (assert_true(!empty($config['acct1.Signature']), "acct1.Signature is not empty")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        $failed++;
    }

    if (isset($config['acct1.AppId'])) {
        if (assert_true(!empty($config['acct1.AppId']), "acct1.AppId is not empty")) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Exception loading Configuration.php: " . $e->getMessage());
    $failed += 8;
}

echo "\n";

// ============================================================================
// TEST 6: Configuration values match credentials
// ============================================================================

echo "Test 6: Configuration values match credentials from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

if (isset($config, $ppUser, $ppPass, $ppSig, $ppAppId)) {
    if (assert_equals($ppUser, $config['acct1.UserName'], "acct1.UserName matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppPass, $config['acct1.Password'], "acct1.Password matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppSig, $config['acct1.Signature'], "acct1.Signature matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($ppAppId, $config['acct1.AppId'], "acct1.AppId matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - Configuration or credentials not available\n";
    $failed += 4;
}

echo "\n";

// ============================================================================
// TEST 7: PayPal Client ID credentials
// ============================================================================

echo "Test 7: PayPal Client ID credentials\n";
echo str_repeat("-", 60) . "\n";

try {
    $clientId = $creds->getPayPalClientId();

    if (assert_true(!empty($clientId), "PayPal Client ID loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    // PayPal Client IDs are typically around 80 characters
    if (assert_true(strlen($clientId) >= 40, "PayPal Client ID has reasonable length (40+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

    // Client ID should start with 'A' (typical for PayPal Client IDs)
    if (assert_true(strpos($clientId, 'A') === 0, "PayPal Client ID starts with 'A'")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load PayPal Client ID: " . $e->getMessage());
    $failed += 3;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All PayPal production credentials tests passed!\n";
    echo "PayPal production credentials and Client ID are properly loaded and configured.\n";
    exit(0);
} else {
    echo "\n❌ Some PayPal production credentials tests failed!\n";
    echo "Please check:\n";
    echo "  1. CREDENTIALS.json has correct PayPal production credentials\n";
    echo "  2. Checkout/paypal_config.php is loading credentials properly\n";
    echo "  3. PayPal/Configuration.php is loading credentials properly\n";
    exit(1);
}
