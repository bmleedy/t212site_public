<?php
/**
 * Patrol Features Unit Tests
 *
 * Tests for the patrol-related API endpoints:
 * - getPatrolEmails.php (Email Patrol + Send Patrol Text)
 * - GetPatrolMembersForUser.php (Patrol Members table)
 *
 * These are static analysis tests that verify file structure,
 * security patterns, and code quality without database access.
 */

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

test_suite("Patrol Features Unit Tests");

// =============================================================================
// Test 1: Patrol API files exist
// =============================================================================
echo "\n--- Test 1: Patrol API Files Existence ---\n";

$api_files = [
    'getPatrolEmails.php',
    'GetPatrolMembersForUser.php',
    'getPatrolMembers.php'  // existing file for attendance
];

foreach ($api_files as $file) {
    $path = PUBLIC_HTML_DIR . '/api/' . $file;
    if (assert_file_exists($path, "API file exists: $file")) {
        $passed++;
    } else {
        $failed++;
    }
}

// =============================================================================
// Test 2: getPatrolEmails.php requires authentication
// =============================================================================
echo "\n--- Test 2: getPatrolEmails.php Security ---\n";

$path = PUBLIC_HTML_DIR . '/api/getPatrolEmails.php';
$content = file_get_contents($path);

$has_auth = strpos($content, 'require_authentication') !== false;
if (assert_true($has_auth, "getPatrolEmails.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

$has_ajax = strpos($content, 'require_ajax') !== false;
if (assert_true($has_ajax, "getPatrolEmails.php requires AJAX")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 3: getPatrolEmails.php returns emails and phones
// =============================================================================
echo "\n--- Test 3: getPatrolEmails.php Returns Emails and Phones ---\n";

$returns_emails = strpos($content, "'emails'") !== false;
if (assert_true($returns_emails, "getPatrolEmails.php returns emails")) {
    $passed++;
} else {
    $failed++;
}

$returns_phones = strpos($content, "'phones'") !== false;
if (assert_true($returns_phones, "getPatrolEmails.php returns phones")) {
    $passed++;
} else {
    $failed++;
}

$returns_patrol_name = strpos($content, "'patrol_name'") !== false;
if (assert_true($returns_patrol_name, "getPatrolEmails.php returns patrol_name")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 4: getPatrolEmails.php uses prepared statements
// =============================================================================
echo "\n--- Test 4: getPatrolEmails.php Uses Prepared Statements ---\n";

$uses_prepared = strpos($content, '->prepare(') !== false;
if (assert_true($uses_prepared, "getPatrolEmails.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 5: getPatrolEmails.php queries required tables
// =============================================================================
echo "\n--- Test 5: getPatrolEmails.php Queries Required Tables ---\n";

$queries_scout_info = strpos($content, 'scout_info') !== false;
if (assert_true($queries_scout_info, "getPatrolEmails.php queries scout_info table")) {
    $passed++;
} else {
    $failed++;
}

$queries_patrols = strpos($content, 'patrols') !== false;
if (assert_true($queries_patrols, "getPatrolEmails.php queries patrols table")) {
    $passed++;
} else {
    $failed++;
}

$queries_phone = strpos($content, 'phone') !== false;
if (assert_true($queries_phone, "getPatrolEmails.php queries phone table")) {
    $passed++;
} else {
    $failed++;
}

$queries_committee = strpos($content, 'committee') !== false;
if (assert_true($queries_committee, "getPatrolEmails.php queries committee table for scoutmaster")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 6: GetPatrolMembersForUser.php requires authentication
// =============================================================================
echo "\n--- Test 6: GetPatrolMembersForUser.php Security ---\n";

$path = PUBLIC_HTML_DIR . '/api/GetPatrolMembersForUser.php';
$content = file_get_contents($path);

$has_auth = strpos($content, 'require_authentication') !== false;
if (assert_true($has_auth, "GetPatrolMembersForUser.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

$has_ajax = strpos($content, 'require_ajax') !== false;
if (assert_true($has_ajax, "GetPatrolMembersForUser.php requires AJAX")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 7: GetPatrolMembersForUser.php returns required fields
// =============================================================================
echo "\n--- Test 7: GetPatrolMembersForUser.php Returns Required Fields ---\n";

$returns_members = strpos($content, "'members'") !== false;
if (assert_true($returns_members, "GetPatrolMembersForUser.php returns members")) {
    $passed++;
} else {
    $failed++;
}

$returns_patrol_name = strpos($content, "'patrol_name'") !== false;
if (assert_true($returns_patrol_name, "GetPatrolMembersForUser.php returns patrol_name")) {
    $passed++;
} else {
    $failed++;
}

$returns_rank = strpos($content, "'rank'") !== false || strpos($content, "rank_name") !== false;
if (assert_true($returns_rank, "GetPatrolMembersForUser.php returns rank info")) {
    $passed++;
} else {
    $failed++;
}

$returns_position = strpos($content, "'position'") !== false || strpos($content, "position_name") !== false;
if (assert_true($returns_position, "GetPatrolMembersForUser.php returns position info")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 8: GetPatrolMembersForUser.php uses prepared statements
// =============================================================================
echo "\n--- Test 8: GetPatrolMembersForUser.php Uses Prepared Statements ---\n";

$uses_prepared = strpos($content, '->prepare(') !== false;
if (assert_true($uses_prepared, "GetPatrolMembersForUser.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 9: GetPatrolMembersForUser.php joins required tables
// =============================================================================
echo "\n--- Test 9: GetPatrolMembersForUser.php Joins Required Tables ---\n";

$joins_ranks = strpos($content, 'ranks') !== false;
if (assert_true($joins_ranks, "GetPatrolMembersForUser.php joins ranks table")) {
    $passed++;
} else {
    $failed++;
}

$joins_leadership = strpos($content, 'leadership') !== false;
if (assert_true($joins_leadership, "GetPatrolMembersForUser.php joins leadership table")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 10: User.html has patrol member functions
// =============================================================================
echo "\n--- Test 10: User.html Has Patrol Functions ---\n";

$template_path = PUBLIC_HTML_DIR . '/templates/User.html';
$template_content = file_get_contents($template_path);

$has_load_patrol_members = strpos($template_content, 'loadPatrolMembers') !== false;
if (assert_true($has_load_patrol_members, "User.html has loadPatrolMembers function")) {
    $passed++;
} else {
    $failed++;
}

$has_load_patrol_email = strpos($template_content, 'loadPatrolEmailButton') !== false;
if (assert_true($has_load_patrol_email, "User.html has loadPatrolEmailButton function")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 11: User.html has patrol email button
// =============================================================================
echo "\n--- Test 11: User.html Has Email/SMS Buttons ---\n";

$has_mailto = strpos($template_content, 'mailto:') !== false;
if (assert_true($has_mailto, "User.html creates mailto link")) {
    $passed++;
} else {
    $failed++;
}

$has_sms = strpos($template_content, 'sms:') !== false;
if (assert_true($has_sms, "User.html creates sms link")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 12: User.html has patrol members accordion
// =============================================================================
echo "\n--- Test 12: User.html Has Patrol Members Section ---\n";

$has_patrol_div = strpos($template_content, 'divPatrolMembers') !== false;
if (assert_true($has_patrol_div, "User.html has divPatrolMembers section")) {
    $passed++;
} else {
    $failed++;
}

$has_patrol_content = strpos($template_content, 'patrol_members_content') !== false;
if (assert_true($has_patrol_content, "User.html has patrol_members_content div")) {
    $passed++;
} else {
    $failed++;
}

$has_patrol_header = strpos($template_content, 'patrolMembersHeader') !== false;
if (assert_true($has_patrol_header, "User.html has patrolMembersHeader element")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 13: APIs handle NoPatrol case
// =============================================================================
echo "\n--- Test 13: APIs Handle NoPatrol Case ---\n";

$emails_path = PUBLIC_HTML_DIR . '/api/getPatrolEmails.php';
$emails_content = file_get_contents($emails_path);
$handles_no_patrol = strpos($emails_content, 'NoPatrol') !== false ||
                     strpos($emails_content, 'not in a patrol') !== false;
if (assert_true($handles_no_patrol, "getPatrolEmails.php handles no patrol case")) {
    $passed++;
} else {
    $failed++;
}

$members_path = PUBLIC_HTML_DIR . '/api/GetPatrolMembersForUser.php';
$members_content = file_get_contents($members_path);
$handles_no_patrol = strpos($members_content, 'NoPatrol') !== false ||
                     strpos($members_content, 'not in a patrol') !== false;
if (assert_true($handles_no_patrol, "GetPatrolMembersForUser.php handles no patrol case")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 14: Phone numbers are cleaned
// =============================================================================
echo "\n--- Test 14: Phone Number Sanitization ---\n";

$cleans_phone = strpos($emails_content, 'preg_replace') !== false;
if (assert_true($cleans_phone, "getPatrolEmails.php sanitizes phone numbers")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
