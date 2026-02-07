<?php
/**
 * Public Pages Security Unit Tests
 *
 * Tests for security measures on public-facing pages including:
 * - No PII exposure (email addresses, phone numbers, etc.)
 * - External link safety (rel="noopener noreferrer")
 * - Calendar iframe security (HTTPS, sandbox attribute)
 * - XSS prevention (htmlspecialchars on user input)
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

test_suite("Public Pages Security Tests");

$passed = 0;
$failed = 0;

// Define public page files to test
$publicPages = [
  'index.php',
  'Calendar.php',
  'CurrentInfo.php',
  'Scoutmaster.php',
  'OurHistory.php',
  'EagleScouts.php',
  'Handbook.php',
  'FAQ.php',
  'Links.php',
  'NewScoutInfo.php',
  'ParentInfo.php',
  'Donate.php',
];

// ============================================================================
// TEST 1: Verify public page files exist
// ============================================================================

echo "Test 1: Public page files existence\n";
echo str_repeat("-", 60) . "\n";

foreach ($publicPages as $page) {
  $filePath = PUBLIC_HTML_DIR . '/' . $page;
  if (assert_file_exists($filePath, "$page exists")) {
    $passed++;
  } else {
    $failed++;
  }
}

echo "\n";

// ============================================================================
// TEST 2: No email address patterns exposed on public pages
// ============================================================================

echo "Test 2: No PII email patterns exposed\n";
echo str_repeat("-", 60) . "\n";

// Allowed email patterns (public contact emails, not personal)
$allowedEmailPatterns = [
  'Troop212_sm@googlegroups.com',  // Public contact email
];

foreach ($publicPages as $page) {
  $filePath = PUBLIC_HTML_DIR . '/' . $page;
  if (!file_exists($filePath)) continue;

  $content = file_get_contents($filePath);

  // Find all email-like patterns
  preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches);

  $unexpectedEmails = [];
  foreach ($matches[0] as $email) {
    $isAllowed = false;
    foreach ($allowedEmailPatterns as $allowed) {
      if (stripos($email, $allowed) !== false || stripos($allowed, $email) !== false) {
        $isAllowed = true;
        break;
      }
    }
    if (!$isAllowed) {
      $unexpectedEmails[] = $email;
    }
  }

  if (assert_true(count($unexpectedEmails) === 0, "$page has no unexpected email addresses exposed")) {
    $passed++;
  } else {
    $failed++;
    echo "   Found: " . implode(', ', $unexpectedEmails) . "\n";
  }
}

echo "\n";

// ============================================================================
// TEST 3: No phone number patterns exposed
// ============================================================================

echo "Test 3: No PII phone patterns exposed\n";
echo str_repeat("-", 60) . "\n";

// Allowed phone patterns (business numbers)
$allowedPhonePatterns = [
  '(253) 752-7731',  // Scout shop phone number
];

foreach ($publicPages as $page) {
  $filePath = PUBLIC_HTML_DIR . '/' . $page;
  if (!file_exists($filePath)) continue;

  $content = file_get_contents($filePath);

  // Find phone patterns like (xxx) xxx-xxxx or xxx-xxx-xxxx
  preg_match_all('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $matches);

  $unexpectedPhones = [];
  foreach ($matches[0] as $phone) {
    $isAllowed = false;
    foreach ($allowedPhonePatterns as $allowed) {
      if (strpos($phone, str_replace(['(', ')', '-', ' '], '', $allowed)) !== false ||
          strpos(str_replace(['(', ')', '-', ' '], '', $phone), str_replace(['(', ')', '-', ' '], '', $allowed)) !== false) {
        $isAllowed = true;
        break;
      }
    }
    // Also allow if it's just the business number
    $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
    if ($normalizedPhone === '2537527731') {
      $isAllowed = true;
    }
    if (!$isAllowed) {
      $unexpectedPhones[] = $phone;
    }
  }

  if (assert_true(count($unexpectedPhones) === 0, "$page has no unexpected phone numbers exposed")) {
    $passed++;
  } else {
    $failed++;
    echo "   Found: " . implode(', ', $unexpectedPhones) . "\n";
  }
}

echo "\n";

// ============================================================================
// TEST 4: External links have rel="noopener noreferrer"
// ============================================================================

echo "Test 4: External links security (rel=noopener noreferrer)\n";
echo str_repeat("-", 60) . "\n";

foreach ($publicPages as $page) {
  $filePath = PUBLIC_HTML_DIR . '/' . $page;
  if (!file_exists($filePath)) continue;

  $content = file_get_contents($filePath);

  // Find all external links with target="_blank"
  preg_match_all('/<a\s+[^>]*href\s*=\s*["\']https?:\/\/[^"\']+["\'][^>]*target\s*=\s*["\']_blank["\'][^>]*>/i', $content, $matches);

  // Also check reverse order (target before href)
  preg_match_all('/<a\s+[^>]*target\s*=\s*["\']_blank["\'][^>]*href\s*=\s*["\']https?:\/\/[^"\']+["\'][^>]*>/i', $content, $matches2);

  $allExternalLinks = array_merge($matches[0], $matches2[0]);

  $insecureLinks = [];
  foreach ($allExternalLinks as $link) {
    if (stripos($link, 'rel="noopener noreferrer"') === false &&
        stripos($link, "rel='noopener noreferrer'") === false) {
      $insecureLinks[] = $link;
    }
  }

  if (assert_true(count($insecureLinks) === 0, "$page external links have rel=noopener noreferrer")) {
    $passed++;
  } else {
    $failed++;
    foreach ($insecureLinks as $link) {
      echo "   Missing rel attribute: " . substr($link, 0, 80) . "...\n";
    }
  }
}

echo "\n";

// ============================================================================
// TEST 5: Calendar.php iframe security
// ============================================================================

echo "Test 5: Calendar.php iframe security\n";
echo str_repeat("-", 60) . "\n";

$calendarPath = PUBLIC_HTML_DIR . '/Calendar.php';
$calendarContent = file_get_contents($calendarPath);

// Check that iframe uses HTTPS
$usesHttps = preg_match('/<iframe[^>]*src\s*=\s*["\']https:\/\//', $calendarContent);
if (assert_true($usesHttps === 1, "Calendar iframe uses HTTPS")) {
  $passed++;
} else {
  $failed++;
}

// Check that iframe has sandbox attribute
$hasSandbox = preg_match('/<iframe[^>]*sandbox\s*=/', $calendarContent);
if (assert_true($hasSandbox === 1, "Calendar iframe has sandbox attribute")) {
  $passed++;
} else {
  $failed++;
}

// Check that iframe has title attribute for accessibility
$hasTitle = preg_match('/<iframe[^>]*title\s*=/', $calendarContent);
if (assert_true($hasTitle === 1, "Calendar iframe has title attribute for accessibility")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 6: FAQ.php XSS prevention
// ============================================================================

echo "Test 6: FAQ.php XSS prevention\n";
echo str_repeat("-", 60) . "\n";

$faqPath = PUBLIC_HTML_DIR . '/FAQ.php';
$faqContent = file_get_contents($faqPath);

// Check that $_GET values are escaped
$escapesGetId = preg_match('/htmlspecialchars\s*\(\s*\$_GET\s*\[\s*["\']id["\']\s*\]/', $faqContent);
if (assert_true($escapesGetId === 1, "FAQ.php escapes \$_GET['id'] with htmlspecialchars")) {
  $passed++;
} else {
  $failed++;
}

// Check that $_SESSION values are escaped
$escapesSession = preg_match('/htmlspecialchars\s*\(\s*\$_SESSION\s*\[\s*["\']user_id["\']\s*\]/', $faqContent);
if (assert_true($escapesSession === 1, "FAQ.php escapes \$_SESSION['user_id'] with htmlspecialchars")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Links.php uses HTTPS for external links
// ============================================================================

echo "Test 7: Links.php uses HTTPS for external links\n";
echo str_repeat("-", 60) . "\n";

$linksPath = PUBLIC_HTML_DIR . '/Links.php';
$linksContent = file_get_contents($linksPath);

// Check for any http:// links (should all be https://)
preg_match_all('/href\s*=\s*["\']http:\/\/[^"\']+["\']/', $linksContent, $httpLinks);

if (assert_true(count($httpLinks[0]) === 0, "Links.php uses HTTPS for all external links (no HTTP)")) {
  $passed++;
} else {
  $failed++;
  foreach ($httpLinks[0] as $link) {
    echo "   Found HTTP link: $link\n";
  }
}

// Count external links with proper security attributes
preg_match_all('/target="_blank"\s+rel="noopener noreferrer"/', $linksContent, $secureLinks);
$secureCount = count($secureLinks[0]);

if (assert_true($secureCount >= 10, "Links.php has at least 10 secure external links (found $secureCount)")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Handbook.php external link security
// ============================================================================

echo "Test 8: Handbook.php external link security\n";
echo str_repeat("-", 60) . "\n";

$handbookPath = PUBLIC_HTML_DIR . '/Handbook.php';
$handbookContent = file_get_contents($handbookPath);

// Check that Google Docs link has proper security attributes
$hasSecureGoogleLink = preg_match('/href\s*=\s*["\']https:\/\/docs\.google\.com\/[^"\']+["\'][^>]*target\s*=\s*["\']_blank["\'][^>]*rel\s*=\s*["\']noopener noreferrer["\']/', $handbookContent);

if (assert_true($hasSecureGoogleLink === 1, "Handbook.php Google Docs link has security attributes")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 9: CurrentInfo.php uses htmlspecialchars for database output
// ============================================================================

echo "Test 9: CurrentInfo.php database output escaping\n";
echo str_repeat("-", 60) . "\n";

$currentInfoPath = PUBLIC_HTML_DIR . '/CurrentInfo.php';
$currentInfoContent = file_get_contents($currentInfoPath);

// Check for htmlspecialchars usage on database values
$escapesRoleName = preg_match('/htmlspecialchars\s*\(\s*\$row\s*\[\s*["\']role_name["\']\s*\]/', $currentInfoContent);
if (assert_true($escapesRoleName === 1, "CurrentInfo.php escapes role_name with htmlspecialchars")) {
  $passed++;
} else {
  $failed++;
}

$escapesUserName = preg_match('/htmlspecialchars\s*\(.*user_first/', $currentInfoContent);
if (assert_true($escapesUserName === 1, "CurrentInfo.php escapes user names with htmlspecialchars")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 10: index.php Facebook link security
// ============================================================================

echo "Test 10: index.php external link security\n";
echo str_repeat("-", 60) . "\n";

$indexPath = PUBLIC_HTML_DIR . '/index.php';
$indexContent = file_get_contents($indexPath);

// Check that Facebook link has proper security attributes
$hasSecureFacebookLink = preg_match('/href\s*=\s*["\']https:\/\/www\.facebook\.com\/[^"\']+["\'][^>]*target\s*=\s*["\']_blank["\'][^>]*rel\s*=\s*["\']noopener noreferrer["\']/', $indexContent);

if (assert_true($hasSecureFacebookLink === 1, "index.php Facebook link has rel=noopener noreferrer")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// ============================================================================
// TEST 11: No debug code in public pages
// ============================================================================

echo "Test 11: No debug code in public pages\n";
echo str_repeat("-", 60) . "\n";

$debugPatterns = [
  '/\bvar_dump\s*\(/',
  '/\bprint_r\s*\(/',
  '/\bdebug_print_backtrace\s*\(/',
  '/\berror_reporting\s*\(\s*E_ALL\s*\)/',
  '/\bini_set\s*\(\s*["\']display_errors["\']\s*,\s*["\']?1["\']?\s*\)/',
];

foreach ($publicPages as $page) {
  $filePath = PUBLIC_HTML_DIR . '/' . $page;
  if (!file_exists($filePath)) continue;

  $content = file_get_contents($filePath);

  $hasDebugCode = false;
  $foundPatterns = [];
  foreach ($debugPatterns as $pattern) {
    if (preg_match($pattern, $content)) {
      $hasDebugCode = true;
      $foundPatterns[] = $pattern;
    }
  }

  if (assert_true(!$hasDebugCode, "$page has no debug code")) {
    $passed++;
  } else {
    $failed++;
    echo "   Found debug patterns: " . implode(', ', $foundPatterns) . "\n";
  }
}

echo "\n";

// ============================================================================
// TEST 12: EagleScouts.php is static content (no database queries)
// ============================================================================

echo "Test 12: EagleScouts.php is static (no DB queries)\n";
echo str_repeat("-", 60) . "\n";

$eagleScoutsPath = PUBLIC_HTML_DIR . '/EagleScouts.php';
$eagleScoutsContent = file_get_contents($eagleScoutsPath);

$hasDbQuery = preg_match('/\$mysqli->query\s*\(|\$pdo->query\s*\(|->prepare\s*\(/', $eagleScoutsContent);
if (assert_true($hasDbQuery === 0, "EagleScouts.php has no database queries (static content)")) {
  $passed++;
} else {
  $failed++;
}

echo "\n";

// Print summary
test_summary($passed, $failed);

// Exit with appropriate code
if ($failed === 0) {
  echo "\nAll public pages security tests passed!\n";
  exit(0);
} else {
  echo "\nSome public pages security tests failed!\n";
  echo "Please review the public page files for security issues.\n";
  exit(1);
}
