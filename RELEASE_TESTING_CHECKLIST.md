# Troop 212 Website - Pre-Release Testing Checklist

**Date:** _______________

**Tester:** _______________

**Release Version/Branch:** _______________


**Test Environment:** Production Site
**Estimated Time:** 15-30 minutes

---

## Prerequisites

- [ ] SA user account credentials ready
- [ ] Test user account credentials ready (separate from SA account)
- [ ] Browser dev tools available (for checking console errors)

---

## SECTION 1: Public Pages (Logged Out)

Log out completely before starting this section.

### 1.1 Home Page & Navigation
- [ ] Home page (index.php) loads without errors
- [ ] Gig Harbor image displays correctly
- [ ] Four chip links at bottom work: Calendar, Troop Photos, Members, Recent Events
- [ ] Login form appears and is functional

### 1.2 Public Sidebar Navigation
- [ ] Recent Events link works → OutingsPublic.php
- [ ] Troop Calendar link works → Calendar.php
- [ ] Troop Photos link opens Facebook
- [ ] Members link works → Members.php
- [ ] Scoutmaster link works → Scoutmaster.php

### 1.3 Public Content Pages
- [ ] OutingsPublic.php: Table displays recent events (past 2 months + future)
- [ ] OutingsPublic.php: "Please log in" message appears at bottom
- [ ] Calendar.php: Google calendar iframe loads
- [ ] CurrentInfo.php: All sections display (Charter, Meetings, Committee table)

---

## SECTION 2: Authentication

### 2.1 Login/Logout
- [ ] Login with SA account succeeds
- [ ] Sidebar changes to logged-in menu
- [ ] "Logoff" link works and returns to public view
- [ ] Login with Test User account succeeds

---

## SECTION 3: Core User Features (as SA User)

### 3.1 My Profile
- [ ] User.php loads with your profile data
- [ ] Profile fields are editable (Name, Email, Phone, etc.)
- [ ] Save changes works (make a minor change, save, verify)
- [ ] Revert any test changes

### 3.2 Pay or Approve Outings
- [ ] EventPay.php loads without errors
- [ ] Displays pending payments/approvals (if any exist)

### 3.3 Troop Events & Outings
- [ ] ListEvents.php: Event list loads
- [ ] Events with closed registration appear greyed out
- [ ] Click an event → Event.php loads with details
- [ ] "New Entry" button appears (SA permission)
- [ ] "View ALL Events" link works → ListEventsAll.php

### 3.4 Event Details (Event.php)
- [ ] Event details display correctly
- [ ] Attending Scouts table displays (if applicable)
- [ ] Attending Adults table displays (if applicable)
- [ ] "Download CSV" button works

### 3.5 Directories
- [ ] Scout Directory (ListScouts.php): Lists scouts
- [ ] Adult Directory (ListAdults.php): Lists adults
- [ ] Merit Badge Counselors (MB_Counselors.php): List loads

### 3.6 Calendar
- [ ] Calendar.php: Google calendar displays correctly

---

## SECTION 4: Leader Tools Menu (Expand & Test)

### 4.1 Menu Functionality
- [ ] "Leader Tools" expandable menu appears in sidebar
- [ ] Click expands/collapses the submenu
- [ ] Arrow icon toggles direction

### 4.2 New User (registernew.php)
- [ ] Page loads without errors
- [ ] Form fields display correctly
- [ ] DO NOT submit (would create database clutter)

### 4.3 Deleted Users (ListDeletes.php)
- [ ] Page loads and displays deleted users (if any)

### 4.4 Event Signups (EventSignups.php)
- [ ] Page loads and displays signup summary

### 4.5 Attendance Report (AttendanceReport.php)
- [ ] Page loads without errors
- [ ] Date range selector works
- [ ] Report data displays

### 4.6 Manage Committee (ManageCommittee.php)
- [ ] Page loads with committee roles table
- [ ] Add form displays (Role Name, User dropdown, Sort Order)
- [ ] User dropdown excludes scouts and alumni
- [ ] DO NOT add/delete roles (database impact)

---

## SECTION 5: Attendance Features

### 5.1 Attendance Tracker (Attendance.php)
- [ ] Page loads without errors
- [ ] Patrol tabs display
- [ ] Scout attendance checkboxes work
- [ ] DO NOT save changes that affect real data

### 5.2 Patrol Agenda (PatrolAgenda.php)
- [ ] Page loads without errors
- [ ] Patrol information displays

---

## SECTION 6: Treasurer Features

### 6.1 Payment Report (TreasurerReport.php)
- [ ] Page loads without errors
- [ ] Payment data displays in table format

---

## SECTION 6A: T-Shirt Store Features

### 6A.1 Public Order Page (TShirtOrder.php) - Logged Out
- [ ] Page loads without errors (no login required)
- [ ] T-shirt image displays
- [ ] Size selection dropdowns appear (XS through XXL)
- [ ] Customer information form displays (Name, Email, Phone, Address)
- [ ] PayPal buttons load
- [ ] Selecting quantities updates total automatically
- [ ] Form validation prevents empty submissions

### 6A.2 Order Management (ManageTShirtOrders.php) - Requires trs/wm/sa
- [ ] Page loads with order list
- [ ] Statistics panel shows: Total, Unfulfilled, Fulfilled, Revenue
- [ ] Filter dropdown works (All/Unfulfilled/Fulfilled)
- [ ] Clicking order number opens details modal
- [ ] Export CSV button downloads file
- [ ] DO NOT mark orders fulfilled on production unless intended

### 6A.3 Item Price Management (ManageItemPrices.php) - Requires wm/sa ONLY
- [ ] "Item Prices" link appears in Admin menu (for wm/sa users)
- [ ] Page loads with T-shirt sizes and prices
- [ ] Price edit functionality works
- [ ] DO NOT change prices unless intended

### 6A.4 Notification Preferences (User Profile)
- [ ] Treasurer users see "Notification Preferences" section
- [ ] T-shirt order notification toggle works
- [ ] Preference saves correctly

---

## SECTION 7: Admin Menu (Expand & Test)

### 7.1 Menu Functionality
- [ ] "Admin" expandable menu appears in sidebar
- [ ] Click expands/collapses the submenu
- [ ] Arrow icon toggles direction

### 7.2 Manage Patrols (Patrols.php)
- [ ] Page loads with patrols table
- [ ] Add new patrol form displays
- [ ] Existing patrols are editable
- [ ] DO NOT add/delete patrols (database impact)

### 7.3 Activity Log (ActivityLog.php)
- [ ] Page loads without errors
- [ ] Recent activity entries display
- [ ] Pagination/filtering works (if applicable)

### 7.4 Permissions (Permissions.php)
- [ ] Page loads with users list
- [ ] Permission checkboxes display for each user
- [ ] Test User's permissions are visible

---

## SECTION 8: Permission-Based Access Testing

Use your Test User account for these tests. Modify permissions via Permissions.php as SA user.

### 8.1 Remove All Permissions from Test User
- [ ] As SA: Remove all permissions from Test User
- [ ] Login as Test User
- [ ] Verify: No "Leader Tools" menu appears
- [ ] Verify: No "Admin" menu appears
- [ ] Verify: No "[Treasurer]" link appears
- [ ] Verify: No "[Patrol Leader]" links appear

### 8.2 Test Patrol Leader (pl) Permission
- [ ] As SA: Grant only 'pl' permission to Test User
- [ ] Login as Test User
- [ ] Verify: Attendance Tracker link appears
- [ ] Verify: Patrol Agenda link appears
- [ ] Verify: Leader Tools menu does NOT appear (pl only doesn't grant this)

### 8.3 Test Outing Editor (oe) Permission
- [ ] As SA: Grant only 'oe' permission to Test User
- [ ] Login as Test User
- [ ] Verify: Leader Tools menu appears
- [ ] Verify: Event Signups appears in Leader Tools
- [ ] Verify: Attendance Report appears in Leader Tools

### 8.4 Restore Test User Permissions
- [ ] As SA: Restore Test User's original permissions

---

## SECTION 9: Event Registration Flow (Non-Destructive)

### 9.1 View-Only Event Test
- [ ] Navigate to an existing event
- [ ] If registered: "I can't go" button appears
- [ ] If not registered: "Sign Me Up!" button appears
- [ ] DO NOT click registration buttons on real events

---

## SECTION 10: Browser Compatibility Check

Open browser developer tools (F12) and check console.

- [ ] No JavaScript errors on home page
- [ ] No JavaScript errors on ListEvents.php
- [ ] No JavaScript errors on Event.php
- [ ] No JavaScript errors on sidebar menu toggle

---

## SECTION 11: Mobile Responsiveness

Resize browser or use mobile emulation.

- [ ] Home page displays correctly on mobile
- [ ] Sidebar collapses appropriately
- [ ] Tables are readable/scrollable

---

## Final Cleanup

- [ ] Restore any Test User permission changes
- [ ] Log out of all accounts
- [ ] Clear any test data created (if applicable)

---

## Test Summary

| Section                     | Pass | Fail | Notes |
|-----------------------------|------|------|-------|
| 1. Public Pages             |      |      |       |
|                             |      |      |       |
| 2. Authentication           |      |      |       |
|                             |      |      |       |
| 3. Core Features            |      |      |       |
|                             |      |      |       |
| 4. Leader Tools             |      |      |       |
|                             |      |      |       |
| 5. Attendance               |      |      |       |
|                             |      |      |       |
| 6. Treasurer                |      |      |       |
|                             |      |      |       |
| 6A. T-Shirt Store           |      |      |       |
|                             |      |      |       |
| 7. Admin                    |      |      |       |
|                             |      |      |       |
| 8. Permissions              |      |      |       |
|                             |      |      |       |
| 9. Event Registration       |      |      |       |
|                             |      |      |       |
| 10. Browser Compat          |      |      |       |
|                             |      |      |       |
| 11. Mobile                  |      |      |       |

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
1. **PayPal Integration** - Requires real payment flow (use sandbox for T-shirt order testing)
2. **Google Calendar** - Depends on external Google API
3. **Facebook Links** - External site, can only verify link works
4. **Password Reset Email** - Would require testing email delivery
5. **T-Shirt Order Emails** - Sends real emails to customers and treasurers

### Cannot Test (Requires Specific Conditions)
1. **Registration Conflict Detection** - Requires overlapping events
2. **Court of Honor Display** - Time-based content
3. **Past Event Warning Banner** - Would need past event with open registration
4. **Scout Parent Email Notification** - Requires scout user with linked parents

### Requires Separate Testing Environment
1. **Database Migrations** - Should test on staging first
2. **Bulk Operations** - Risky on production
3. **Error Handling Edge Cases** - May cause visible errors

### Tested Implicitly
1. **Activity Logging** - Verified by checking Activity Log after operations
2. **Session Management** - Tested during login/logout
3. **AJAX Endpoints** - Tested when pages load data successfully

---

*Document Version: 1.1*
*Last Updated: January 2026*
*Added: T-Shirt Store feature tests (Section 6A)*
