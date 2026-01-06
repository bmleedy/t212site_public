# Event Email Preference Implementation

## Overview
Successfully implemented the 'evnt' (Event) notification preference to control whether parents and adults receive event-related emails from organizers via the mailto link on Event.php.

## How It Works

### Flow
1. User views an event on Event.php
2. Event organizer (or authorized user) sees "Send Email to Attending Scouts & Parents" mailto link
3. `api/getevent.php` builds the mailto link with email addresses of:
   - All registered scouts (always included)
   - Parents of registered scouts (only if opted in to Event Emails)
   - Adult attendees (only if opted in to Event Emails)
4. Mailto link opens user's email client with all opted-in recipients

### Code Changes

**File: `public_html/api/getevent.php`**

**Change 1: Parents of Scouts (Lines 194-213)**
Modified the query to include notification preferences:
```php
// Before:
$query3 = "SELECT user_email FROM users WHERE user_type !='Scout' AND family_id=" . $family_id;

// After:
$query3 = "SELECT user_email, notif_preferences FROM users WHERE user_type !='Scout' AND family_id=" . $family_id;
```

Added preference checking logic for parents:
```php
while ($row3 = $results3->fetch_assoc()) {
    // Check if this parent wants event emails
    $include_email = true;  // Default: include (opted in)

    if ($row3['notif_preferences']) {
        $prefs = json_decode($row3['notif_preferences'], true);
        // Check 'evnt' (Event) preference
        if (isset($prefs['evnt']) && $prefs['evnt'] === false) {
            $include_email = false;
        }
    }

    if ($include_email && strpos($mailto, $row3['user_email'])===false) {
        $mailto = $mailto . $sep . $row3['user_email'];
    }
}
```

**Change 2: Adult Attendees (Lines 216-245)**
Modified the query to include notification preferences:
```php
// Before:
$query = "SELECT reg.user_id, ..., user_email, reg.id as register_id FROM registration...";

// After:
$query = "SELECT reg.user_id, ..., user_email, notif_preferences, reg.id as register_id FROM registration...";
```

Added preference checking logic for adults:
```php
while ($row = $results->fetch_assoc()) {
    // Build attendingAdults array...

    // Check if this adult wants event emails
    $include_email = true;  // Default: include (opted in)

    if ($row['notif_preferences']) {
        $prefs = json_decode($row['notif_preferences'], true);
        // Check 'evnt' (Event) preference
        if (isset($prefs['evnt']) && $prefs['evnt'] === false) {
            $include_email = false;
        }
    }

    if ($include_email && strpos($mailto, $row['user_email'])===false) {
        $mailto = $mailto . $sep . $row['user_email'];
    }
}
```

## Default Behavior
- **Opted IN by default**: Parents and adults receive emails unless they explicitly opt out
- Scout emails are ALWAYS included (not filtered by preferences)
- If `notif_preferences` is NULL → Include email ✅
- If `evnt` key doesn't exist → Include email ✅
- If `evnt` is `true` → Include email ✅
- If `evnt` is `false` → Don't include email ❌

## Key Features
- ✅ Scout emails always included (scouts can't opt out of event communications)
- ✅ Parent emails filtered based on Event Email preference
- ✅ Adult attendee emails filtered based on Event Email preference
- ✅ Mailto link only includes opted-in recipients
- ✅ Default opt-in ensures important event communications reach families

## Testing

### Automated Tests
Created comprehensive test suite: `tests/unit/EventEmailPreferenceTest.php`
- ✅ All 21 tests passing
- Tests cover: file existence, preference checking for both parents and adults, default behavior, NULL handling, and end-to-end flow

### Manual Testing Steps

**Setup:**
1. Create an event with at least one scout and one adult registered
2. Note the parent and adult email addresses

**Test 1: Default Opted IN**
1. Ensure parent/adult have Event Emails CHECKED (or not set)
2. Go to Event.php for the event as an authorized user
3. Click "Send Email to Attending Scouts & Parents" link
4. Verify parent and adult emails are in the "To:" field ✅

**Test 2: Parent Opts OUT**
1. Go to parent's User.php?id=PARENT_ID&edit=1
2. UNCHECK "Event Emails"
3. Click Submit
4. Go back to Event.php
5. Click mailto link
6. Parent's email should NOT be in the "To:" field ❌
7. Scout's email and adult's email should still be there ✅

**Test 3: Adult Opts OUT**
1. Go to adult's User.php?id=ADULT_ID&edit=1
2. UNCHECK "Event Emails"
3. Click Submit
4. Go back to Event.php
5. Click mailto link
6. Adult's email should NOT be in the "To:" field ❌
7. Scout's email should still be there ✅

**Test 4: Re-opt IN**
1. Re-check "Event Emails" for parent and adult
2. Click Submit
3. Go back to Event.php
4. Click mailto link
5. All emails should be included again ✅

## Important Notes

### Scout Emails Never Filtered
Scout emails are deliberately ALWAYS included in the mailto link, regardless of any preferences. This ensures:
- Scouts receive event communications from organizers
- Important updates reach scouts directly
- Safety and logistics information gets to participants

### Mailto Link vs Direct Email
- This implementation uses a **mailto link** (opens user's email client)
- It does NOT use the sendmail.php API
- The filtering happens when building the mailto link
- User's email client will show all recipients

### Privacy Consideration
When an organizer clicks the mailto link, all recipients can see each other's email addresses (standard mailto behavior). This is intentional for event communication but users should be aware.

## Use Cases

**Event Organizer Scenarios:**
1. **Last-minute changes**: "Weather update - event moved indoors"
2. **Reminders**: "Don't forget to bring your sleeping bag!"
3. **Follow-up**: "Thanks for attending! Here are photos from the event"
4. **Logistics**: "Carpool assignments for tomorrow's outing"

**Why Parents Might Opt Out:**
1. They prefer to get info from their scout
2. They're receiving too many emails
3. Another parent in the family is handling event communications
4. They're no longer actively involved

## Files Modified
- ✅ `public_html/api/getevent.php` - Added preference checking for parents and adults

## Files Created
- ✅ `tests/unit/EventEmailPreferenceTest.php` - Comprehensive test suite

## Security & Data Safety
- ✅ JSON decode used for safe data handling
- ✅ Default opt-in prevents missing important event communications
- ✅ Strict comparison (`=== false`) ensures only explicit opt-outs are honored
- ✅ Proper NULL checking prevents errors

## Implementation Complete ✅
The Event Email preference is now fully functional. Parents and adults can control whether they receive event-related emails from organizers, while scouts always receive event communications for safety and participation reasons.
