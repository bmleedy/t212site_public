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
    'getpatrolmembers.php'  // existing file for attendance (lowercase on Linux)
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
// Test 15: Event Attendee Phones API exists
// =============================================================================
echo "\n--- Test 15: Event Attendee Phones API ---\n";

$event_api_path = PUBLIC_HTML_DIR . '/api/getEventAttendeePhones.php';
if (assert_file_exists($event_api_path, "getEventAttendeePhones.php exists")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 16: getEventAttendeePhones.php requires authentication
// =============================================================================
echo "\n--- Test 16: getEventAttendeePhones.php Security ---\n";

$event_content = file_get_contents($event_api_path);

$has_auth = strpos($event_content, 'require_authentication') !== false;
if (assert_true($has_auth, "getEventAttendeePhones.php requires authentication")) {
    $passed++;
} else {
    $failed++;
}

$has_ajax = strpos($event_content, 'require_ajax') !== false;
if (assert_true($has_ajax, "getEventAttendeePhones.php requires AJAX")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 17: getEventAttendeePhones.php returns required data
// =============================================================================
echo "\n--- Test 17: getEventAttendeePhones.php Returns Phone Lists ---\n";

$returns_all_phones = strpos($event_content, "'all_phones'") !== false;
if (assert_true($returns_all_phones, "getEventAttendeePhones.php returns all_phones")) {
    $passed++;
} else {
    $failed++;
}

$returns_adult_phones = strpos($event_content, "'adult_phones'") !== false;
if (assert_true($returns_adult_phones, "getEventAttendeePhones.php returns adult_phones")) {
    $passed++;
} else {
    $failed++;
}

$returns_event_name = strpos($event_content, "'event_name'") !== false;
if (assert_true($returns_event_name, "getEventAttendeePhones.php returns event_name")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 18: getEventAttendeePhones.php uses prepared statements
// =============================================================================
echo "\n--- Test 18: getEventAttendeePhones.php Uses Prepared Statements ---\n";

$uses_prepared = strpos($event_content, '->prepare(') !== false;
if (assert_true($uses_prepared, "getEventAttendeePhones.php uses prepared statements")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 19: getEventAttendeePhones.php queries required tables
// =============================================================================
echo "\n--- Test 19: getEventAttendeePhones.php Queries Required Tables ---\n";

$queries_registration = strpos($event_content, 'registration') !== false;
if (assert_true($queries_registration, "getEventAttendeePhones.php queries registration table")) {
    $passed++;
} else {
    $failed++;
}

$queries_users = strpos($event_content, 'users') !== false;
if (assert_true($queries_users, "getEventAttendeePhones.php queries users table")) {
    $passed++;
} else {
    $failed++;
}

$queries_phone = strpos($event_content, 'phone') !== false;
if (assert_true($queries_phone, "getEventAttendeePhones.php queries phone table")) {
    $passed++;
} else {
    $failed++;
}

$queries_events = strpos($event_content, 'events') !== false;
if (assert_true($queries_events, "getEventAttendeePhones.php queries events table")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 20: getEventAttendeePhones.php sanitizes phone numbers
// =============================================================================
echo "\n--- Test 20: getEventAttendeePhones.php Phone Sanitization ---\n";

$cleans_phone = strpos($event_content, 'preg_replace') !== false;
if (assert_true($cleans_phone, "getEventAttendeePhones.php sanitizes phone numbers")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 21: Event.html has SMS button functionality
// =============================================================================
echo "\n--- Test 21: Event.html Has SMS Button Functions ---\n";

$event_template_path = PUBLIC_HTML_DIR . '/templates/Event.html';
$event_template_content = file_get_contents($event_template_path);

$has_load_sms = strpos($event_template_content, 'loadEventSMSButtons') !== false;
if (assert_true($has_load_sms, "Event.html has loadEventSMSButtons function")) {
    $passed++;
} else {
    $failed++;
}

$has_sms_div = strpos($event_template_content, 'eventSMSButtons') !== false;
if (assert_true($has_sms_div, "Event.html has eventSMSButtons div")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 22: Event.html SMS buttons have iOS/Android detection
// =============================================================================
echo "\n--- Test 22: Event.html Platform Detection ---\n";

$has_ios_detection = strpos($event_template_content, 'iPad|iPhone|iPod') !== false;
if (assert_true($has_ios_detection, "Event.html detects iOS devices")) {
    $passed++;
} else {
    $failed++;
}

$has_sms_link = strpos($event_template_content, 'sms:') !== false;
if (assert_true($has_sms_link, "Event.html creates sms links")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 23: Event.html SMS buttons text matches requirements
// =============================================================================
echo "\n--- Test 23: Event.html Button Labels ---\n";

$has_all_attendees = strpos($event_template_content, 'Text All Attendees') !== false;
if (assert_true($has_all_attendees, "Event.html has 'Text All Attendees' button")) {
    $passed++;
} else {
    $failed++;
}

$has_adult_attendees = strpos($event_template_content, 'Text Adult Attendees') !== false;
if (assert_true($has_adult_attendees, "Event.html has 'Text Adult Attendees' button")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 24: User.html SMS has iOS/Android detection
// =============================================================================
echo "\n--- Test 24: User.html Platform Detection ---\n";

$user_has_ios_detection = strpos($template_content, 'iPad|iPhone|iPod') !== false;
if (assert_true($user_has_ios_detection, "User.html detects iOS devices")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 25: APIs filter active scouts only
// =============================================================================
echo "\n--- Test 25: APIs Filter Active Scouts ---\n";

$emails_filters_active = strpos($emails_content, "user_active = 1") !== false;
if (assert_true($emails_filters_active, "getPatrolEmails.php filters active users")) {
    $passed++;
} else {
    $failed++;
}

$emails_filters_scout = strpos($emails_content, "user_type = 'Scout'") !== false;
if (assert_true($emails_filters_scout, "getPatrolEmails.php filters scouts by user_type = 'Scout'")) {
    $passed++;
} else {
    $failed++;
}

$members_filters_active = strpos($members_content, "user_active = 1") !== false;
if (assert_true($members_filters_active, "GetPatrolMembersForUser.php filters active users")) {
    $passed++;
} else {
    $failed++;
}

$members_filters_scout = strpos($members_content, "user_type = 'Scout'") !== false;
if (assert_true($members_filters_scout, "GetPatrolMembersForUser.php filters Scout type")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Test 26: Regression - getPatrolEmails.php only includes parents (not alumni)
// Bug fix: Previously used "user_type != 'Scout'" which included alumni and
// other family members. Should only include parents/guardians (Mom, Dad, Other)
// for family email/phone collection.
// =============================================================================
echo "\n--- Test 26: Regression - Only Parents Included (Not Alumni) ---\n";

// Must use user_type IN ('Mom', 'Dad', 'Other') to filter family members
$uses_parent_filter = strpos($emails_content, "user_type IN ('Mom', 'Dad', 'Other')") !== false;
if (assert_true($uses_parent_filter, "getPatrolEmails.php uses user_type IN ('Mom', 'Dad', 'Other') for family filter")) {
    $passed++;
} else {
    $failed++;
}

// Must NOT use user_type != 'Scout' as that would include alumni
$uses_bad_filter = strpos($emails_content, "user_type != 'Scout'") !== false;
if (assert_false($uses_bad_filter, "getPatrolEmails.php does NOT use user_type != 'Scout' (would include alumni)")) {
    $passed++;
} else {
    $failed++;
}

// =============================================================================
// Summary
// =============================================================================
test_summary($passed, $failed);

exit($failed > 0 ? 1 : 0);
