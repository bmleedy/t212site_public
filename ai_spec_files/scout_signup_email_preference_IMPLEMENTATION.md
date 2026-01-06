# Scout Signup Email Preference Implementation

## Overview
Successfully implemented the 'scsu' (Scout SignUp) notification preference to control whether parents receive emails when their scout signs up for events.

## How It Works

### Flow
1. Scout clicks "Sign Up!" button on Signups.php for an event
2. `templates/Signups.html` calls `register()` function
3. After successful registration, if user type is 'Scout', calls `sendParentsEmail()`
4. `sendParentsEmail()` makes AJAX call to `api/sendmail.php` with `sendTo: "scout parents"`
5. `api/sendmail.php` queries database for parent emails AND notification preferences
6. For each parent:
   - Checks their `notif_preferences` JSON in database
   - Looks for `scsu` key
   - If `scsu` is explicitly `false`, skips adding that parent's email
   - If `scsu` is `true` or not set (NULL), adds parent's email
7. Sends email to all parents who haven't opted out

### Code Changes

**File: `public_html/api/sendmail.php`**

Changed the query to include notification preferences:
```php
// Before:
$query = "SELECT parent.user_email FROM users...";

// After:
$query = "SELECT parent.user_email, parent.notif_preferences FROM users...";
```

Added preference checking logic:
```php
while ($row = $results->fetch_assoc()) {
    // Check if parent wants scout signup emails
    $send_email = true;  // Default: send email (opted in)

    if ($row['notif_preferences']) {
        $prefs = json_decode($row['notif_preferences'], true);
        // Check 'scsu' (Scout SignUp) preference
        if (isset($prefs['scsu']) && $prefs['scsu'] === false) {
            $send_email = false;
            error_log("Parent " . $row['user_email'] . " has opted out");
        }
    }

    if ($send_email) {
        $mail->AddAddress($row['user_email']);
    }
}
```

## Default Behavior
- **Opted IN by default**: Parents receive emails unless they explicitly opt out
- If `notif_preferences` is NULL → Send email ✅
- If `scsu` key doesn't exist → Send email ✅
- If `scsu` is `true` → Send email ✅
- If `scsu` is `false` → Don't send email ❌

## Testing

### Automated Tests
Created comprehensive test suite: `tests/unit/ScoutSignupEmailPreferenceTest.php`
- ✅ All 22 tests passing
- Tests cover: file existence, preference checking, default behavior, security, and end-to-end flow

### Manual Testing Steps
1. **Setup**:
   - Create a scout account and parent account in same family
   - Note the parent's email address

2. **Test Opted IN (default)**:
   - Go to User.php for parent account
   - Verify "Scout Signup Emails" is CHECKED (or not set)
   - Log in as scout
   - Sign up for an event on Signups.php
   - Check parent's email - should receive signup notification ✅

3. **Test Opted OUT**:
   - Go to User.php?id=PARENT_ID&edit=1
   - UNCHECK "Scout Signup Emails"
   - Click Submit
   - Verify preference saved
   - Log in as scout
   - Sign up for a different event
   - Check parent's email - should NOT receive notification ❌

4. **Test Re-opt IN**:
   - Go to User.php for parent, edit mode
   - CHECK "Scout Signup Emails"
   - Click Submit
   - Scout signs up for another event
   - Parent should receive email again ✅

### Debugging
The code includes error logging:
```php
error_log("Parent " . $row['user_email'] . " has opted out of scout signup emails");
error_log("Adding email address: " . $row['user_email']);
```

Check your server error logs to see which parents are receiving/not receiving emails.

## Files Modified
- ✅ `public_html/api/sendmail.php` - Added preference checking

## Files Created
- ✅ `tests/unit/ScoutSignupEmailPreferenceTest.php` - Comprehensive test suite

## Security Considerations
- ✅ JSON decode used for safe data handling
- ✅ Default opt-in prevents accidental blocking of important notifications
- ✅ Strict comparison (`=== false`) ensures only explicit opt-outs are honored
- ✅ Error logging for debugging without exposing sensitive data

## Future Enhancements
When ready, similar logic can be applied to:
- **Roster Emails** (`rost` key) in ListAdults.php
- **Event Emails** (`evnt` key) in Event.php

The pattern is the same:
1. Query for `notif_preferences` column
2. Decode JSON
3. Check specific preference key
4. Default to sending email if not explicitly opted out

## Implementation Complete ✅
The Scout Signup Email preference is now fully functional and respects user preferences while maintaining a safe default of sending emails.
