<?php
/**
 * SMTP Credentials Test
 *
 * Tests that SMTP credentials are properly loaded from CREDENTIALS.json
 * and that SMTP configuration constants are set correctly.
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Load the Credentials class
require_once PUBLIC_HTML_DIR . '/includes/credentials.php';

test_suite("SMTP Credentials Tests");

$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: SMTP credentials load from CREDENTIALS.json
// ============================================================================

echo "Test 1: SMTP credentials load from CREDENTIALS.json\n";
echo str_repeat("-", 60) . "\n";

try {
    $creds = Credentials::getInstance();

    $smtpUser = $creds->getSMTPUsername();
    $smtpPass = $creds->getSMTPPassword();

    if (assert_true(!empty($smtpUser), "SMTP username loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty($smtpPass), "SMTP password loaded successfully")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify email format
    if (assert_true(strpos($smtpUser, '@') !== false, "SMTP username is in email format")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify password has minimum length
    if (assert_true(strlen($smtpPass) >= 6, "SMTP password has minimum length (6+ chars)")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Failed to load SMTP credentials: " . $e->getMessage());
    $failed += 4;
}

echo "\n";

// ============================================================================
// TEST 2: SMTP configuration constants are set correctly
// ============================================================================

echo "Test 2: SMTP configuration constants are set correctly\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load the config file (this should define all the SMTP constants)
    require_once PUBLIC_HTML_DIR . '/login/config/config.php';

    // Check that EMAIL_SMTP_USERNAME constant is defined
    if (assert_true(defined('EMAIL_SMTP_USERNAME'), "EMAIL_SMTP_USERNAME constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that EMAIL_SMTP_PASSWORD constant is defined
    if (assert_true(defined('EMAIL_SMTP_PASSWORD'), "EMAIL_SMTP_PASSWORD constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that EMAIL_SMTP_HOST constant is defined
    if (assert_true(defined('EMAIL_SMTP_HOST'), "EMAIL_SMTP_HOST constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that EMAIL_SMTP_AUTH constant is defined
    if (assert_true(defined('EMAIL_SMTP_AUTH'), "EMAIL_SMTP_AUTH constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that EMAIL_SMTP_PORT constant is defined
    if (assert_true(defined('EMAIL_SMTP_PORT'), "EMAIL_SMTP_PORT constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that EMAIL_SMTP_ENCRYPTION constant is defined
    if (assert_true(defined('EMAIL_SMTP_ENCRYPTION'), "EMAIL_SMTP_ENCRYPTION constant is defined")) {
        $passed++;
    } else {
        $failed++;
    }

} catch (Exception $e) {
    assert_false(true, "Exception loading config.php: " . $e->getMessage());
    $failed += 6;
}

echo "\n";

// ============================================================================
// TEST 3: SMTP constants match credentials
// ============================================================================

echo "Test 3: SMTP constants match credentials\n";
echo str_repeat("-", 60) . "\n";

if (defined('EMAIL_SMTP_USERNAME') && defined('EMAIL_SMTP_PASSWORD')) {
    if (assert_equals($smtpUser, EMAIL_SMTP_USERNAME, "EMAIL_SMTP_USERNAME matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_equals($smtpPass, EMAIL_SMTP_PASSWORD, "EMAIL_SMTP_PASSWORD matches credentials")) {
        $passed++;
    } else {
        $failed++;
    }

    // Verify expected values for other SMTP settings
    if (assert_true(EMAIL_SMTP_PORT == 465 || EMAIL_SMTP_PORT == 587, "EMAIL_SMTP_PORT is valid (465 or 587)")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(EMAIL_SMTP_AUTH === true, "EMAIL_SMTP_AUTH is enabled")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(EMAIL_SMTP_HOST), "EMAIL_SMTP_HOST is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

    if (assert_true(!empty(EMAIL_SMTP_ENCRYPTION), "EMAIL_SMTP_ENCRYPTION is not empty")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - SMTP constants not defined\n";
    $failed += 6;
}

echo "\n";

// ============================================================================
// TEST 4: SMTP server hostname validation (optional)
// ============================================================================

echo "Test 4: SMTP server hostname validation\n";
echo str_repeat("-", 60) . "\n";

if (defined('EMAIL_SMTP_HOST')) {
    $smtpHost = EMAIL_SMTP_HOST;

    // Remove protocol prefix if present
    $hostWithoutProtocol = str_replace(['ssl://', 'tls://'], '', $smtpHost);

    if (assert_true(!empty($hostWithoutProtocol), "SMTP host has a hostname")) {
        $passed++;
    } else {
        $failed++;
    }

    // Check that hostname looks valid (has a dot for domain)
    if (assert_true(strpos($hostWithoutProtocol, '.') !== false, "SMTP host looks like a valid domain")) {
        $passed++;
    } else {
        $failed++;
    }

} else {
    echo "⚠️  Skipping - EMAIL_SMTP_HOST not defined\n";
    $failed += 2;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
    echo "\n✅ All SMTP credentials tests passed!\n";
    echo "SMTP credentials are properly loaded and configured.\n";
    exit(0);
} else {
    echo "\n❌ Some SMTP credentials tests failed!\n";
    echo "Please check:\n";
    echo "  1. CREDENTIALS.json has correct SMTP credentials\n";
    echo "  2. login/config/config.php is loading credentials properly\n";
    exit(1);
}
