<?php
/**
 * Donate Page Unit Tests
 *
 * Static analysis tests for the donation feature:
 * - File existence
 * - Donate button presence in headers
 * - PayPal SDK security (htmlspecialchars on client ID)
 * - No debug code
 * - Proper page structure
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Donate Page Unit Tests");

// ============================================================================
// Test 1: Donate page files exist
// ============================================================================

echo "\n--- Test 1: Donate Page Files Existence ---\n";

$donate_files = [
    'Donate.php',
    'templates/Donate.html',
];

foreach ($donate_files as $file) {
    $path = PUBLIC_HTML_DIR . '/' . $file;
    if (assert_file_exists($path, "File exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// ============================================================================
// Test 2: Donate.php uses public header (not authHeader)
// ============================================================================

echo "\n--- Test 2: Donate.php Uses Public Header ---\n";

$donatePage = file_get_contents(PUBLIC_HTML_DIR . '/Donate.php');

$usesPublicHeader = (strpos($donatePage, 'header.html') !== false);
if (assert_true($usesPublicHeader, "Donate.php includes header.html")) {
    $passed++;
} else {
    $failed++;
}

$usesAuthHeader = (strpos($donatePage, 'authHeader.php') !== false);
if (assert_false($usesAuthHeader, "Donate.php does not use authHeader.php (public page)")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 3: Donate.php loads PayPal credentials
// ============================================================================

echo "\n--- Test 3: Donate.php Loads Credentials ---\n";

$loadsCreds = (strpos($donatePage, 'credentials.php') !== false);
if (assert_true($loadsCreds, "Donate.php loads credentials.php")) {
    $passed++;
} else {
    $failed++;
}

$getsPayPalId = (strpos($donatePage, 'getPayPalClientId') !== false);
if (assert_true($getsPayPalId, "Donate.php calls getPayPalClientId()")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 4: Template escapes PayPal client ID
// ============================================================================

echo "\n--- Test 4: PayPal Client ID Escaping ---\n";

$donateTemplate = file_get_contents(PUBLIC_HTML_DIR . '/templates/Donate.html');

$escapesClientId = (strpos($donateTemplate, 'htmlspecialchars($paypal_client_id)') !== false);
if (assert_true($escapesClientId, "Template escapes PayPal client ID with htmlspecialchars")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 5: PayPal SDK loaded with correct parameters
// ============================================================================

echo "\n--- Test 5: PayPal SDK Configuration ---\n";

$hasPayPalSdk = preg_match('/paypal\.com\/sdk\/js/', $donateTemplate);
if (assert_true($hasPayPalSdk === 1, "Template loads PayPal JavaScript SDK")) {
    $passed++;
} else {
    $failed++;
}

$hasVenmo = (strpos($donateTemplate, 'enable-funding=venmo') !== false);
if (assert_true($hasVenmo, "PayPal SDK enables Venmo funding")) {
    $passed++;
} else {
    $failed++;
}

$hasUsd = (strpos($donateTemplate, 'currency=USD') !== false);
if (assert_true($hasUsd, "PayPal SDK uses USD currency")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 6: Donate button in headers
// ============================================================================

echo "\n--- Test 6: Donate Button in Headers ---\n";

$publicHeader = file_get_contents(PUBLIC_HTML_DIR . '/includes/header.html');
$headerHasDonate = (strpos($publicHeader, 'Donate.php') !== false);
if (assert_true($headerHasDonate, "Public header contains Donate link")) {
    $passed++;
} else {
    $failed++;
}

$authHeader = file_get_contents(PUBLIC_HTML_DIR . '/includes/authHeader.php');
$authHeaderHasDonate = (strpos($authHeader, 'Donate.php') !== false);
if (assert_true($authHeaderHasDonate, "Auth header contains Donate link")) {
    $passed++;
} else {
    $failed++;
}

$mobileMenu = file_get_contents(PUBLIC_HTML_DIR . '/includes/mobile_menu.html');
$mobileHasDonate = (strpos($mobileMenu, 'Donate.php') !== false);
if (assert_true($mobileHasDonate, "Mobile menu contains Donate link")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 7: Donate button has success class (green styling)
// ============================================================================

echo "\n--- Test 7: Donate Button Styling ---\n";

$headerButtonStyled = preg_match('/button\s+success/', $publicHeader);
if (assert_true($headerButtonStyled === 1, "Donate button uses 'button success' classes in public header")) {
    $passed++;
} else {
    $failed++;
}

$authButtonStyled = preg_match('/button\s+success/', $authHeader);
if (assert_true($authButtonStyled === 1, "Donate button uses 'button success' classes in auth header")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 8: No debug code in donate files
// ============================================================================

echo "\n--- Test 8: No Debug Code ---\n";

$debugPatterns = [
    '/\bvar_dump\s*\(/',
    '/\bprint_r\s*\(/',
    '/\berror_reporting\s*\(\s*E_ALL\s*\)/',
];

foreach (['Donate.php', 'templates/Donate.html'] as $file) {
    $content = file_get_contents(PUBLIC_HTML_DIR . '/' . $file);
    $hasDebug = false;
    foreach ($debugPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $hasDebug = true;
            break;
        }
    }
    if (assert_false($hasDebug, "$file has no debug code")) {
        $passed++;
    } else {
        $failed++;
    }
}

// ============================================================================
// Test 9: Minimum amount validation
// ============================================================================

echo "\n--- Test 9: Minimum Amount Validation ---\n";

$hasMinValidation = (strpos($donateTemplate, '1.00') !== false);
if (assert_true($hasMinValidation, "Template enforces minimum donation amount")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 10: Thank-you message
// ============================================================================

echo "\n--- Test 10: Thank You Message ---\n";

$hasThankYou = (strpos($donateTemplate, 'thankYouMessage') !== false);
if (assert_true($hasThankYou, "Template has thank-you message element")) {
    $passed++;
} else {
    $failed++;
}

$hasFormHide = (strpos($donateTemplate, "donationForm').hide()") !== false);
if (assert_true($hasFormHide, "Template hides form after successful donation")) {
    $passed++;
} else {
    $failed++;
}

// ============================================================================
// Test 11: Page structure
// ============================================================================

echo "\n--- Test 11: Page Structure ---\n";

$hasFooter = (strpos($donatePage, 'footer.html') !== false);
if (assert_true($hasFooter, "Donate.php includes footer.html")) {
    $passed++;
} else {
    $failed++;
}

$hasSidebar = (strpos($donatePage, 'sidebar.html') !== false);
if (assert_true($hasSidebar, "Donate.php includes sidebar.html")) {
    $passed++;
} else {
    $failed++;
}

$hasTemplate = (strpos($donatePage, 'templates/Donate.html') !== false);
if (assert_true($hasTemplate, "Donate.php includes templates/Donate.html")) {
    $passed++;
} else {
    $failed++;
}

// Print summary
test_summary($passed, $failed);
exit($failed === 0 ? 0 : 1);
