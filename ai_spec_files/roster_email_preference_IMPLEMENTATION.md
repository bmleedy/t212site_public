# Roster Email Preference Implementation

## Overview
Successfully implemented the 'rost' (Roster) notification preference to control whether adults receive broadcast emails from the adult roster page buttons ("Email All Adults" and "Email All Adults & Scouts").

## How It Works

### Flow
1. User visits ListAdults.php to view the adult roster
2. Page calls `api/getadults.php` to get list of adults
3. `getadults.php` checks each adult's `rost` preference:
   - If opted IN (default): Returns email address
   - If opted OUT: Returns empty string for email
4. ListAdults.html builds mailto buttons with BCC to all adults who have email addresses
5. Adults who opted out have empty email, so they're automatically excluded from the mailto link

### Code Changes

**File: `public_html/api/getadults.php`**

Modified the query to include notification preferences:
```php
// Before:
$query="SELECT * FROM users WHERE user_type not in ('Scout', ...)...";

// After:
$query="SELECT user_id, user_first, user_last, user_email, user_type, notif_preferences FROM users WHERE user_type not in ('Scout', ...)...";
```

Added preference checking logic:
```php
while ($row = $results->fetch_object()) {
    $id = $row->user_id;

    // Check if this adult wants roster emails
    $include_in_roster_emails = true;  // Default: include (opted in)

    if ($row->notif_preferences) {
        $prefs = json_decode($row->notif_preferences, true);
        // Check 'rost' (Roster) preference
        if (isset($prefs['rost']) && $prefs['rost'] === false) {
            $include_in_roster_emails = false;
        }
    }

    // ... fetch phone numbers ...

    $adults[] = [
        'first' => $row->user_first,
        'last' => $row->user_last,
        'email'=> $include_in_roster_emails ? $row->user_email : '',  // Only if opted in
        'id'=>$id,
        'phone'=>$phones,
        'user_type'=>$row->user_type
    ];
}
```

## Default Behavior
- **Opted IN by default**: Adults receive emails unless they explicitly opt out
- If `notif_preferences` is NULL → Include email ✅
- If `rost` key doesn't exist → Include email ✅
- If `rost` is `true` → Include email ✅
- If `rost` is `false` → Return empty string (excluded from mailto) ❌

## Key Features
- ✅ Adult info (name, phone, profile pic) still displayed on roster
- ✅ Only email addresses are filtered based on preference
- ✅ Empty emails automatically excluded from mailto links by JavaScript
- ✅ Works for both "Email All Adults" and "Email All Adults & Scouts" buttons
- ✅ Default opt-in ensures important communications reach adults

## How ListAdults.html Works

The JavaScript in ListAdults.html:
1. Receives adult data from API (email will be empty string if opted out)
2. Builds email list, checking `if (element["email"])` before adding
3. Empty emails are skipped, so opted-out adults don't appear in mailto
4. Creates BCC mailto links with only opted-in adults

```javascript
// Collect all adult email addresses
var adultEmailList = [];
data.forEach(function (element) {
    if (element["email"]) {  // Skips empty emails (opted out)
        adultEmailList.push(element["email"]);
    }
});

// Create email adults link
var emailAdultsLink = '<a href="mailto:?bcc=' + adultEmailList.join(',') +
    '" class="button small round">Email All Adults</a>';
```

## Testing

### Automated Tests
Created comprehensive test suite: `tests/unit/RosterEmailPreferenceTest.php`
- ✅ All 24 tests passing
- Tests cover: file existence, preference checking, email filtering, default behavior, NULL handling, and end-to-end flow

### Manual Testing Steps

**Setup:**
1. Have at least 2 adult users in the system
2. Note their email addresses

**Test 1: Default Opted IN**
1. Ensure adults have Roster Emails CHECKED (or not set)
2. Go to ListAdults.php
3. Click "Email All Adults" button
4. Both adults' emails should be in BCC field ✅

**Test 2: One Adult Opts OUT**
1. Go to User.php?id=ADULT1_ID&edit=1
2. UNCHECK "Roster Emails"
3. Click Submit
4. Go back to ListAdults.php
5. Click "Email All Adults" button
6. Only ADULT2's email should be in BCC field ✅
7. ADULT1 should NOT be in BCC field ❌

**Test 3: All Adults Opt OUT**
1. Have all adults uncheck "Roster Emails"
2. Go to ListAdults.php
3. Click "Email All Adults" button
4. BCC field should be empty (or only have your own email if browser adds it)

**Test 4: Re-opt IN**
1. Re-check "Roster Emails" for adults
2. Go back to ListAdults.php
3. Click "Email All Adults" button
4. All adults' emails should be in BCC again ✅

**Test 5: Verify Roster Still Shows All Adults**
1. Even with adults opted out of emails
2. Their names, phones, and photos still appear on the roster ✅
3. Only the email buttons exclude them

## Use Cases

**Why Broadcast Email Buttons Exist:**
1. **Quick communications**: "Meeting tonight at 7pm"
2. **Urgent updates**: "Event cancelled due to weather"
3. **Information sharing**: "New policy document attached"
4. **Coordination**: "Need volunteers for this weekend"

**Why Adults Might Opt Out:**
1. They prefer direct communication methods
2. They're receiving too many broadcast emails
3. They're not actively involved in troop activities
4. Another adult in their family handles communications
5. They only want event-specific emails, not general broadcasts

## Important Notes

### Roster Display vs Email Access
- **Roster display**: Adults always shown with name, phone, photo (regardless of preference)
- **Email buttons**: Only include adults who haven't opted out
- This allows adults to opt out of broadcast emails while remaining visible and contactable via phone

### BCC Privacy
- Emails use BCC (blind carbon copy)
- Recipients can't see each other's email addresses
- This is intentional for privacy

### Email All Adults & Scouts Button
- This button gets scouts from `api/getscouts.php` (separate API)
- Scout emails are ALWAYS included (no filtering)
- Only adult emails are filtered by preference

## Files Modified
- ✅ `public_html/api/getadults.php` - Added preference checking

## Files Created
- ✅ `tests/unit/RosterEmailPreferenceTest.php` - Comprehensive test suite

## Security & Data Safety
- ✅ JSON decode used for safe data handling
- ✅ Default opt-in prevents missing important communications
- ✅ Strict comparison (`=== false`) ensures only explicit opt-outs are honored
- ✅ Proper NULL checking prevents errors
- ✅ Email field returns empty string (not NULL) for clean JavaScript handling

## Query Optimization
Changed from `SELECT *` to selecting specific fields:
- `user_id, user_first, user_last, user_email, user_type, notif_preferences`
- More efficient database query
- Only retrieves needed data

## Implementation Complete ✅
The Roster Email preference is now fully functional. Adults can control whether they receive broadcast emails from the roster page buttons while still remaining visible and contactable on the roster itself.
