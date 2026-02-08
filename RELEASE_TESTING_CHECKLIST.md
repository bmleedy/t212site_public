# Troop 212 Website - Pre-Release Manual Testing Checklist

**Date:** _______________

**Tester:** _______________

**Release Version/Branch:** _______________


**Test Environment:** Production Site
**Estimated Time:** 10-15 minutes

---

## What's Automatically Tested

The following are verified by GitHub Actions on every push/PR and do **not** need manual testing:

- **Unit tests** (PHP): validation, activity logging, credentials, auth helpers
- **Static analysis**: PHPStan
- **E2E tests** (Playwright): Page loads, navigation, sidebar menus, login/logout, mobile responsiveness, browser console errors, visual snapshot regression, and basic UI element presence for all pages

See `.github/workflows/php-tests.yml` for full details.

---

## Prerequisites

- [ ] SA user account credentials ready
- [ ] Test user account credentials ready (separate from SA account)

---

## SECTION 1: Interactive Workflows (as SA User)

These test form submissions and data interactions that the automated tests do not exercise.

### 1.1 Profile Editing (User.php)
- [ ] Profile fields are editable (Name, Email, Phone, etc.)
- [ ] Save changes works (make a minor change, save, verify it persisted)
- [ ] Revert any test changes

### 1.2 Event Details (Event.php)
- [ ] Click an event from ListEvents.php → Event.php loads with details
- [ ] Attending Scouts table displays (if applicable)
- [ ] Attending Adults table displays (if applicable)
- [ ] "Download CSV" button works (downloads a file)
- [ ] Events with closed registration appear greyed out on ListEvents.php

### 1.3 Attendance Report (AttendanceReport.php)
- [ ] Date range selector works
- [ ] Report data displays after selecting a range

### 1.4 Attendance Tracker (Attendance.php)
- [ ] Scout attendance checkboxes toggle correctly
- [ ] DO NOT save changes that affect real data

### 1.5 Manage Committee (ManageCommittee.php)
- [ ] Add form displays (Role Name, User dropdown, Sort Order)
- [ ] User dropdown excludes scouts and alumni
- [ ] DO NOT add/delete roles (database impact)

### 1.6 Manage Patrols (Patrols.php)
- [ ] Add new patrol form displays
- [ ] Existing patrols are editable
- [ ] DO NOT add/delete patrols (database impact)

### 1.7 Activity Log (ActivityLog.php)
- [ ] Recent activity entries display
- [ ] Pagination/filtering works (if applicable)

### 1.8 Permissions (Permissions.php)
- [ ] Permission checkboxes display for each user

---

## SECTION 2: T-Shirt Store Interactions

### 2.1 Public Order Page (TShirtOrder.php)
- [ ] Size selection dropdowns appear (XS through XXL)
- [ ] Selecting quantities updates total automatically
- [ ] Form validation prevents empty submissions

### 2.2 Order Management (ManageTShirtOrders.php)
- [ ] Statistics panel shows: Total, Unfulfilled, Fulfilled, Revenue
- [ ] Filter dropdown works (All/Unfulfilled/Fulfilled)
- [ ] Clicking order number opens details modal
- [ ] Export CSV button downloads file
- [ ] DO NOT mark orders fulfilled on production unless intended

### 2.3 Item Price Management (ManageItemPrices.php)
- [ ] Price edit functionality works
- [ ] DO NOT change prices unless intended

### 2.4 Notification Preferences (User Profile)
- [ ] Treasurer users see "Notification Preferences" section
- [ ] T-shirt order notification toggle works
- [ ] Preference saves correctly

---

## SECTION 3: Permission-Based Access Testing

The automated tests verify menu visibility for SA users but cannot test permission toggling workflows.

Use your Test User account for these tests. Modify permissions via Permissions.php as SA user.

### 3.1 Remove All Permissions from Test User
- [ ] As SA: Remove all permissions from Test User
- [ ] Login as Test User
- [ ] Verify: No "Leader Tools" menu appears
- [ ] Verify: No "Admin" menu appears
- [ ] Verify: No "[Treasurer]" link appears
- [ ] Verify: No "[Patrol Leader]" links appear

### 3.2 Test Patrol Leader (pl) Permission
- [ ] As SA: Grant only 'pl' permission to Test User
- [ ] Login as Test User
- [ ] Verify: Attendance Tracker link appears
- [ ] Verify: Patrol Agenda link appears
- [ ] Verify: Leader Tools menu does NOT appear (pl only doesn't grant this)

### 3.3 Test Outing Editor (oe) Permission
- [ ] As SA: Grant only 'oe' permission to Test User
- [ ] Login as Test User
- [ ] Verify: Leader Tools menu appears
- [ ] Verify: Event Signups appears in Leader Tools
- [ ] Verify: Attendance Report appears in Leader Tools

### 3.4 Restore Test User Permissions
- [ ] As SA: Restore Test User's original permissions

---

## SECTION 4: User Impersonation Workflow (SA Only)

The automated tests verify access control (SA can access, non-SA gets denied) but do not test the actual impersonation workflow.

### 4.1 Start Impersonation
- [ ] As SA: Go to Impersonate.php
- [ ] Use search filter to find a test user (non-SA)
- [ ] Click "Impersonate" button → confirm dialog
- [ ] Verify: Redirected to home page
- [ ] Verify: Blue banner appears with impersonated user's name
- [ ] Verify: "Exit Impersonation" link is visible in banner

### 4.2 Verify Impersonation Behavior
- [ ] Navigate to "My Profile" → see impersonated user's profile
- [ ] Navigate to ListEvents.php → site behaves as that user
- [ ] Blue banner persists on all pages

### 4.3 Activity Log During Impersonation
- [ ] Perform an action that logs to activity log (e.g., update profile)
- [ ] Exit impersonation
- [ ] Navigate to Admin > Activity Log
- [ ] Verify: Entry contains "impersonated_by" with your admin username

### 4.4 Exit Impersonation
- [ ] Click "Exit Impersonation" link in blue banner
- [ ] Verify: Redirected to Impersonate.php, banner gone
- [ ] Verify: Welcome message shows your admin name
- [ ] Navigate to "My Profile" → see your admin profile

### 4.5 Logout During Impersonation
- [ ] Start impersonating a user
- [ ] Click "Logoff"
- [ ] Verify: Impersonation ends (NOT logged out)
- [ ] Verify: Redirected to Impersonate.php as admin

---

## SECTION 5: Event Registration Flow

### 5.1 View-Only Event Test
- [ ] Navigate to an existing event
- [ ] If registered: "I can't go" button appears
- [ ] If not registered: "Sign Me Up!" button appears
- [ ] DO NOT click registration buttons on real events

---

## SECTION 6: External Dependency Checks

### 6.1 PayPal/Payment Integration
- [ ] Donate page: PayPal/Venmo/Card buttons load correctly
- [ ] T-Shirt order page: PayPal buttons render and are interactive

### 6.2 Google Calendar
- [ ] Calendar.php: Google calendar iframe loads with actual calendar content

---

## Final Cleanup

- [ ] Restore any Test User permission changes
- [ ] Log out of all accounts
- [ ] Clear any test data created (if applicable)

---

## Test Summary

| Section                        | Pass | Fail | Notes |
|--------------------------------|------|------|-------|
| 1. Interactive Workflows       |      |      |       |
|                                |      |      |       |
| 2. T-Shirt Store Interactions  |      |      |       |
|                                |      |      |       |
| 3. Permissions                 |      |      |       |
|                                |      |      |       |
| 4. Impersonation               |      |      |       |
|                                |      |      |       |
| 5. Event Registration          |      |      |       |
|                                |      |      |       |
| 6. External Dependencies       |      |      |       |

**Overall Result:** [ ] PASS  [ ] FAIL

**Critical Issues Found:**
_____________________________________________
_____________________________________________
_____________________________________________

**Signed Off By:** _______________  **Date:** _______________

---

## Assumptions & Exceptions

The following features/scenarios CANNOT be fully tested with this method:

### Cannot Test (Would Impact Production Data)
1. **User Registration Flow** - Creating new users would add clutter to database
2. **Event Creation** - Creating test events visible to all users
3. **Event Sign-up/Cancel** - Would affect real event rosters and send emails
4. **Payment Processing** - Cannot test marking payments without affecting real data
5. **Approval Workflow** - Would require pending registrations from real scouts
6. **Delete User** - Cannot safely test user deletion
7. **Email Notifications** - Would send real emails to users
8. **Patrol/Committee Create/Delete** - Would affect production reference data
9. **T-Shirt Order Payment** - Would create real orders in production database
10. **T-Shirt Order Fulfillment** - Would affect real order status
11. **Item Price Changes** - Would affect live pricing for customers

### Cannot Test (External Dependencies)
1. **PayPal Integration** - Requires real payment flow (use sandbox for testing)
2. **Password Reset Email** - Would require testing email delivery
3. **T-Shirt Order Emails** - Sends real emails to customers and treasurers

### Cannot Test (Requires Specific Conditions)
1. **Registration Conflict Detection** - Requires overlapping events
2. **Court of Honor Display** - Time-based content
3. **Past Event Warning Banner** - Would need past event with open registration
4. **Scout Parent Email Notification** - Requires scout user with linked parents

### Requires Separate Testing Environment
1. **Database Migrations** - Should test on staging first
2. **Bulk Operations** - Risky on production
3. **Error Handling Edge Cases** - May cause visible errors

---

*Document Version: 2.0*
*Last Updated: February 2026*
*Revised to remove items covered by automated CI/CD (GitHub Actions e2e + unit tests)*
