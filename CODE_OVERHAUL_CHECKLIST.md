# Troop 212 Website - Comprehensive Code Overhaul Checklist

**Purpose:** Systematic review of all files for simplicity, readability, modernity, efficiency, security, and performance.

**Review Criteria for Each File:**
- [ ] **Simplicity:** Remove dead code, simplify logic, reduce nesting
- [ ] **Readability:** Consistent naming, clear comments where needed, proper formatting
- [ ] **Modernity:** Update deprecated functions, use modern PHP/JS patterns
- [ ] **Efficiency:** Eliminate redundant DB queries, optimize loops, cache where appropriate
- [ ] **Security:** Prepared statements, input validation, XSS prevention, proper auth checks
- [ ] **Performance:** Minimize DB calls, lazy loading, reduce file size

---

## SECTION 1: Core Infrastructure

### 1.1 Authentication & Session Management

| File | Purpose | Review Status |
|------|---------|---------------|
| `includes/authHeader.php` | Session init, auth check | [x] |
| `includes/credentials.php` | Credentials management | [x] |
| `login/classes/Login.php` | Login logic | [x] |
| `login/classes/Registration.php` | User registration | [x] |
| `login/config/config.php` | Login configuration | [x] |
| `login/inc_login.php` | Login include | [x] |
| `login/index.php` | Login entry point | [x] |
| `login/views/user_login.php` | Login form view | [x] |
| `login/views/logged_in.php` | Logged in view | [x] |
| `login/views/not_logged_in.php` | Not logged in view | [x] |
| `password_reset.php` | Password reset page | [x] |
| `login/views/password_reset.php` | Password reset view | [x] |

**Priority Issues to Check:**
- [x] Password hashing uses `password_hash()` with `PASSWORD_DEFAULT`
- [x] Session regeneration on login/logout
- [x] CSRF protection on forms
- [ ] Rate limiting on login attempts (currently only per-user lockout: 3 attempts/30 sec)
- [x] Secure cookie flags (HttpOnly, Secure, SameSite)

### 1.2 API Helpers & Utilities

| File | Purpose | Review Status |
|------|---------|---------------|
| `api/auth_helper.php` | API authentication utilities | [x] |
| `api/validation_helper.php` | Input validation functions | [x] |
| `api/connect.php` | Database connection | [x] |
| `includes/activity_logger.php` | Activity logging utility | [x] |
| `includes/page_counter.php` | Page view tracking | [x] |
| `includes/notification_types.php` | Notification type constants | [x] |

**Priority Issues to Check:**
- [x] All validation functions handle edge cases
- [x] Database connection uses SSL in production (SSL comment added, ready for config)
- [x] Error messages don't leak sensitive info

### 1.3 Navigation & Layout

| File | Purpose | Review Status |
|------|---------|---------------|
| `includes/header.html` | Page header | [x] |
| `includes/footer.html` | Page footer | [x] |
| `includes/m_sidebar.html` | Logged-in menu | [x] |
| `includes/sidebar.html` | Public menu | [x] |
| `includes/mobile_menu.html` | Mobile navigation | [x] |

**Priority Issues to Check:**
- [x] Menu items have consistent permission checks
- [x] All three menus (m_sidebar, sidebar, mobile_menu) are in sync
- [x] No hardcoded URLs that should be relative

---

## SECTION 2: User Management

### 2.1 User Profile & Family

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `User.php` | `templates/User.html` | `api/getuser.php`, `api/getOtherUserInfo.php`, `api/updateuser.php` | [x] |
| `Family.php` | `templates/Family.html` | `api/getfamily.php` | [x] |
| `addfamilymember.php` | `login/views/addfamilymember.php` | (uses registration) | [x] |

**Priority Issues to Check:**
- [x] Users can only edit their own profile (unless admin) - Added require_user_access() in updateuser.php
- [x] Phone/email validation - Proper prepared statements and validation helpers
- [ ] Profile picture upload security - Not addressed in this section
- [x] Family relationship integrity - Added validation in addfamilymember.php

### 2.2 User Registration

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `register.php` | `login/views/register.php` | `login/classes/Registration.php` | [x] |
| `registernew.php` | `login/views/registernew.php` | (admin registration) | [x] |
| ~~`NewUser.php`~~ | ~~`templates/NewUser.html`~~ | (REMOVED - dead code) | [x] |

**Priority Issues to Check:**
- [x] Email verification flow - Existing implementation verified working
- [x] Duplicate email prevention - PDO prepared statements in Registration.php
- [x] Strong password requirements - Increased minimum from 6 to 8 characters
- [x] Admin-only access for registernew.php - Added sa/wm permission check

**Additional Security Fixes Applied (Jan 2026):**
- [x] Added CSRF token to public registration form (login/views/register.php)
- [x] Added CSRF token to admin registration form (login/views/registernew.php)
- [x] Added CAPTCHA validation in Registration.php (was displayed but never validated)
- [x] Added user_type whitelist validation in Registration.php
- [x] Added server-side CSRF validation in Registration.php constructor
- [x] Fixed XSS vulnerability in family_id output (login/views/registernew.php)
- [x] Removed dead code: NewUser.php and templates/NewUser.html (broken, incomplete)

### 2.3 User Directories

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `ListScouts.php` | `templates/ListScouts.html` | `api/getscouts.php` | [x] |
| `ListAdults.php` | `templates/ListAdults.html` | `api/getadults.php` | [x] |
| `ListDeletes.php` | `templates/ListDeletes.html` | `api/getdeletes.php` | [x] |
| `Members.php` | `templates/Members.html` | (directory landing) | [x] |
| `TroopRoster.php` | `templates/TroopRoster.html` | (roster view) | [x] |

**Priority Issues to Check:**
- [x] Login required for member directories
- [x] Deleted users not visible to non-admins (getdeletes.php requires admin permission)
- [x] No PII exposed to unauthorized users (getscouts.php and getadults.php now require permissions)

### 2.4 Permissions & Admin

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `Permissions.php` | `templates/Permissions.html` | `api/getpermissions.php`, `api/updatepermissions.php` | [x] |
| `DELUser.php` | (inline) | (user deletion) | [x] DEAD CODE - recommend deletion |
| `ActivityLog.php` | `templates/ActivityLog.html` | `api/getactivitylog.php` | [x] |

**Priority Issues to Check:**
- [x] Only sa/wm can access Permissions - Verified sa check in Permissions.php, wm/sa check in ActivityLog.php
- [x] Permission changes logged - Verified comprehensive logging in updatepermissions.php
- [x] User deletion is soft-delete with audit trail - Uses user_type='Delete' pattern via updateuser.php

**Changes Made (Jan 2026):**
- Added `require_csrf()` to `api/auth_helper.php` for CSRF validation
- Added CSRF validation to `getpermissions.php`, `updatepermissions.php`, `getactivitylog.php`
- Added `require_permission(['wm', 'sa'])` to `getactivitylog.php`
- Added XSS escaping to `Permissions.html` (escapeHtml function)
- Added XSS escaping to `ActivityLog.html` (escapeHtml function)
- Modernized session handling in `ActivityLog.php` with secure cookie options
- Fixed DELUser.php XSS vulnerabilities (marked as dead code - references non-existent User2.html)

---

## SECTION 3: Events & Outings

### 3.1 Event Listing

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `ListEvents.php` | `templates/ListEvents.html` | `api/getevents.php` | [x] |
| `ListEventsAll.php` | `templates/ListEventsAll.html` | `api/geteventsall.php` | [x] |
| `OutingsPublic.php` | (inline) | (public event view) | [x] |

**Priority Issues to Check:**
- [x] Future events hidden appropriately (getevents.php filters by date)
- [x] Past events accessible for history (ListEventsAll shows all events)
- [x] Public page doesn't leak member info (OutingsPublic only shows event name/location/dates)

**Security Fixes Applied (Jan 2026):**
- [x] Added authentication to `geteventsall.php` (require_authentication, require_csrf)
- [x] Converted `geteventsall.php` to use prepared statements
- [x] Added output escaping to `geteventsall.php` (escape_html)
- [x] Added XSS protection to `ListEvents.html` and `ListEventsAll.html` (escapeHtml function)
- [x] Added ID validation in JavaScript templates to prevent injection
- [x] Converted `OutingsPublic.php` to use prepared statements
- [x] Added error handling to `OutingsPublic.php`
- [x] Fixed HTML syntax error in `ListEventsAll.html`
- [x] Added user-friendly error messages in templates

### 3.2 Event Details & Management

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `Event.php` | `templates/Event.html` | `api/getevent.php`, `api/amd_event.php` | [x] |
| `Signups.php` | `templates/Signups.html` | `api/getsignups.php` | [x] |
| `EventSignups.php` | `templates/EventSignups.html` | (signup management) | [x] |

**Priority Issues to Check:**
- [x] Event editing restricted to oe/sa
- [x] Date validation (start before end)
- [x] Registration deadlines enforced

**Security Fixes Applied (Jan 2026):**
- [x] **CRITICAL:** Added authentication/authorization to `amd_event.php` (require_authentication, require_permission, require_csrf)
- [x] **CRITICAL:** Added authentication/authorization to `approve.php` (require_authentication, require_permission, require_csrf)
- [x] Added permission check to `getapprove.php` and `getsignups.php`
- [x] Replaced raw `$_POST` access with validation helpers in `amd_event.php` and `approve.php`
- [x] Added date validation (start < end) to `amd_event.php`
- [x] Fixed bind_param types (integers for user IDs) in `amd_event.php` and `approve.php`
- [x] Fixed XSS in `Event.php`, `Signups.php` hidden fields (htmlspecialchars)
- [x] Fixed XSS in `Event.html`, `Signups.html`, `EventSignups.html` (replaced inline onclick with data attributes + jQuery delegation)
- [x] Added escapeHtml functions to JavaScript templates
- [x] Converted raw queries to prepared statements in `getsignups.php`
- [x] Optimized N+1 query pattern to JOIN in `getsignups.php`
- [x] Removed dead code from `EventSignups.php`
- [x] Added activity logging with user ID to `amd_event.php`
- [x] Fixed typo: `json_endode()` → `json_encode()` in `amd_event.php`

### 3.3 Event Registration & Payment

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `EventPay.php` | `templates/EventPay.html` | `api/geteventpay.php`, `api/pay.php` | [x] |
| `Approve.php` | `templates/Approve.html` | `api/getapprove.php`, `api/approve.php` | [x] |
| `PPReturnPage2.php` | `templates/PPReturnPage2.html` | `api/ppupdate2.php` | [x] |

**Priority Issues to Check:**
- [x] Payment verification from PayPal - Documented limitation, follow-up task created for webhook integration
- [x] No double-payment issues - Added paid status check before update in ppupdate2.php
- [x] Approval restricted to appropriate users - Verified: oe/sa permission required, documented authorization model
- [x] Payment amounts validated server-side - Costs retrieved from database, not from client

**Security Fixes Applied (Jan 2026):**
- [x] **CRITICAL:** Fixed XSS in `PPReturnPage2.php` (escaped reg_ids GET parameter with htmlspecialchars)
- [x] **HIGH:** Added `require_csrf()` to `pay.php` and `ppupdate2.php`
- [x] Added transaction safety to `approve.php` (checks execute() result, logs failure with success=false)
- [x] Added double-payment prevention to `ppupdate2.php` (checks paid status before update, logs already_paid)
- [x] Added escapeHtml function to `EventPay.html` and `PPReturnPage2.html` (defense-in-depth XSS protection)
- [x] Updated writeCell functions to use escapeHtml for safe DOM insertion
- [x] Documented authorization model differences between `getapprove.php` and `approve.php`
- [x] Added PayPal verification limitation documentation to `ppupdate2.php` with TODO for webhook

**Known Limitations:**
- PayPal payment verification not implemented - payments marked as paid based on client-side redirect
- Recommend implementing PayPal webhook/IPN verification in future (tracked as TODO in ppupdate2.php)

### 3.4 Event Rosters

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `EventRosterSI.php` | `templates/EventRosterSI.html` | `api/geteventrostersi.php` | [x] |
| `EventRoster.php` | `templates/EventRoster.html` | `api/geteventroster.php` | [x] |

**Priority Issues to Check:**
- [x] Roster access restricted to logged-in users
- [x] Phone/email visible only to leaders (er/sa permission required)
- [x] CSV export sanitized (N/A - no CSV export functionality exists)

**Security Fixes Applied (Jan 2026):**
- [x] **CRITICAL:** Added `require_permission(['er', 'sa'])` to `geteventrostersi.php` and `geteventroster.php`
- [x] **CRITICAL:** Fixed XSS in `EventRosterSI.html` and `EventRoster.html` (added escapeHtml function, fixed unsafe onclick handlers)
- [x] Added activity logging to both roster APIs (`view_event_roster` and `view_event_roster_si` actions)
- [x] Added input validation to `EventRosterSI.php` and `EventRoster.php` (intval for event ID)
- [x] Fixed unescaped output in hidden inputs (htmlspecialchars)
- [x] Fixed short PHP tag in `EventRoster.php` (`<?` → `<?php`)
- [x] Added session variable null checks to prevent undefined index errors

---

## SECTION 4: Attendance

### 4.1 Attendance Tracking

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `Attendance.php` | `templates/Attendance.html` | `api/getattendancedata.php`, `api/updateattendance.php`, `api/getscoutsforattendance.php` | [x] |
| `AttendanceReport.php` | `templates/AttendanceReport.html` | `api/getattendanceevents.php` | [x] |

**Priority Issues to Check:**
- [x] Attendance updates restricted to pl/oe/sa
- [x] Date range validation on reports
- [x] Efficient queries for large date ranges

**Security Fixes Applied (Jan 2026):**
- [x] **CRITICAL:** Added `require_authentication()`, `require_permission()`, `require_csrf()` to `updateattendance.php`
- [x] **CRITICAL:** Added `require_authentication()`, `require_permission()`, `require_csrf()` to `getscoutsforattendance.php`
- [x] **CRITICAL:** Converted `updateattendance.php` from string concatenation to prepared statements
- [x] Added CSRF and permission checks to `getattendancedata.php` and `getattendanceevents.php`
- [x] Added date range validation (start_date <= end_date) to both date-range APIs
- [x] Fixed XSS in `Attendance.php` and `AttendanceReport.php` hidden inputs (htmlspecialchars)
- [x] Modernized session cookie handling with secure flags (httponly, samesite)
- [x] Added escapeHtml() function to `Attendance.html` and `AttendanceReport.html`
- [x] Escaped all dynamic content in JavaScript templates (patrol names, scout names, error messages)
- [x] Added event_id validation with parseInt() before use in hrefs
- [x] Removed debug console.log statements that exposed sensitive data
- [x] Added `escape_html()` to API outputs for defense-in-depth
- [x] Activity logging uses correct actor ID (current_user_id, not target user_id)

### 4.2 Related APIs

| API File | Purpose | Review Status |
|----------|---------|---------------|
| `api/getattendance.php` | Get attendance records | [x] Already reviewed - uses auth_helper, prepared statements |
| `api/updateattendance_debug.php` | Debug version (REMOVE?) | [x] REMOVED - Already deleted per earlier cleanup |

---

## SECTION 5: Patrols

### 5.1 Patrol Management

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `Patrols.php` | `templates/Patrols.html` | `api/getpatrols.php`, `api/getallpatrols.php`, `api/createpatrol.php`, `api/updatepatrol.php`, `api/deletepatrol.php` | [x] |

**Priority Issues to Check:**
- [x] Patrol CRUD restricted to wm/sa (verified - require_permission(['wm', 'sa']) on all CRUD APIs)
- [x] Deleting patrol handles scouts in that patrol (verified - deletepatrol.php checks before deletion and prevents if scouts assigned)
- [x] Position-permission sync for Patrol Leader (documented - Patrol Leaders get 'pl' permission through position_id in scout_info table; permission checks use has_permission('pl'))

### 5.2 Patrol Features

| API File | Purpose | Review Status |
|----------|---------|---------------|
| `api/getpatrol.php` | Get single patrol | [x] |
| `api/getpatrolmembers.php` | Get patrol members | [x] |
| `api/GetPatrolMembersForUser.php` | Get patrol for a user | [x] |
| `api/getPatrolEmails.php` | Get patrol email list | [x] |
| `api/getuserpatrol.php` | Get user's patrol | [x] |

**Security Fixes Applied (Jan 2026):**
- [x] **Patrols.php**: Added wm/sa permission check before displaying page content
- [x] **Patrols.html**: Added escapeHtml() XSS prevention helper, client-side validation for patrol names
- [x] **getpatrols.php**: Added require_authentication(), require_csrf(), proper prepared statements, filters only active scouts
- [x] **getallpatrols.php**: Added require_permission(['wm', 'sa']) for admin-only access, require_csrf()
- [x] **createpatrol.php**: Added require_permission(['wm', 'sa']), require_csrf(), prepared statements, patrol name format validation, duplicate name/sort checks, activity logging
- [x] **updatepatrol.php**: Added require_permission(['wm', 'sa']), require_csrf(), prepared statements, patrol name format validation, duplicate sort check, activity logging
- [x] **deletepatrol.php**: Added require_permission(['wm', 'sa']), require_csrf(), prepared statements, scout-in-patrol check before deletion, activity logging
- [x] **getpatrol.php**: Added authorization check (user in patrol OR pl/er/wm/sa), require_csrf(), prepared statements, escape_html() for output, table whitelist for getLabel(), activity logging
- [x] **getpatrolmembers.php**: Added authorization check (user in patrol OR pl/er/wm/sa), require_csrf(), prepared statements, filters active scouts only, attendance integration, activity logging
- [x] **GetPatrolMembersForUser.php**: Added authorization check (own data, same patrol, OR pl/er/wm/sa), require_csrf(), prepared statements, filters active scouts only, NoPatrol handling, activity logging
- [x] **getPatrolEmails.php**: Added authorization check (own data, same patrol, OR pl/er/wm/sa), require_csrf(), prepared statements, filters parents only (not alumni), phone sanitization, activity logging
- [x] **getuserpatrol.php**: Added authorization check (own data OR wm/sa), require_csrf(), prepared statements, escape_html() for output, activity logging

**Authorization Model:**
- **Read patrols list (getpatrols.php)**: Any authenticated user (patrol names not sensitive)
- **Manage patrols (getallpatrols, create, update, delete)**: wm/sa only
- **View patrol details with contact info (getpatrol.php, getpatrolmembers.php)**: User in patrol OR pl/er/wm/sa
- **View patrol members for user profile (GetPatrolMembersForUser.php, getPatrolEmails.php)**: Own data, same patrol, OR pl/er/wm/sa
- **Get user's patrol ID (getuserpatrol.php)**: Own data OR wm/sa

---

## SECTION 6: T-Shirt Store

### 6.1 Public Store

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `TShirtOrder.php` | `templates/TShirtOrder.html` | `api/order_create.php`, `api/order_getconfig.php` | [x] |
| `TShirtOrderComplete.php` | `templates/TShirtOrderComplete.html` | (order confirmation) | [x] |

**Priority Issues to Check:**
- [x] PayPal integration secure - PayPal order ID stored; server-side verification TODO documented (see Known Limitations)
- [x] Order validation server-side - All inputs validated with validation_helper.php, prepared statements
- [x] Email confirmation sent - send_order_confirmation() called after order creation
- [x] Store can be disabled via config - store_config table with store_enabled flag

### 6.2 Store Management

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `ManageTShirtOrders.php` | `templates/ManageTShirtOrders.html` | `api/order_getall.php`, `api/order_fulfill.php` | [x] |
| `ManageItemPrices.php` | `templates/ManageItemPrices.html` | `api/itemprices_getall.php`, `api/itemprices_update.php` | [x] |

**Priority Issues to Check:**
- [x] Order management restricted to trs/wm/sa - require_permission(['trs', 'wm', 'sa']) in order_getall.php and order_fulfill.php
- [x] Price changes restricted to wm/sa - require_permission(['wm', 'sa']) in itemprices_update.php
- [x] Fulfillment status tracked with audit - order_fulfill.php logs to activity_log with fulfilled_by user ID

### 6.3 Store Email

| File | Purpose | Review Status |
|------|---------|---------------|
| `includes/tshirt_email.php` | T-shirt order email (backward compat wrapper) | [x] |
| `includes/store_email.php` | Generic store notification email | [x] |

**Security Fixes Applied (Jan 2026):**
- [x] **REFACTORED:** Renamed APIs to generic names (order_create.php, order_getconfig.php, order_getall.php, order_fulfill.php)
- [x] **order_create.php:** Added validation_helper.php for all inputs, prepared statements, database transactions, rate limiting (10 orders/hour per IP)
- [x] **order_getconfig.php:** Public read-only endpoint with prepared statements for category filter
- [x] **order_getall.php:** Added require_authentication(), require_permission(['trs', 'wm', 'sa']), require_csrf()
- [x] **order_fulfill.php:** Added require_authentication(), require_permission(['trs', 'wm', 'sa']), require_csrf(), double-fulfillment prevention
- [x] **itemprices_getall.php:** Added require_permission(['wm', 'sa']), require_csrf()
- [x] **itemprices_update.php:** Added require_permission(['wm', 'sa']), require_csrf(), price validation (0-9999.99), activity logging with old/new values
- [x] **ManageTShirtOrders.php:** Added server-side trs/wm/sa permission check before displaying template
- [x] **ManageItemPrices.php:** Added server-side wm/sa permission check before displaying template
- [x] **store_email.php:** Complete rewrite with prepared statements, PHPMailer, notification preferences support, activity logging
- [x] **tshirt_email.php:** Converted to backward-compatibility wrapper for store_email.php

**Authorization Model:**
- **Public store (order_getconfig.php):** No authentication - read-only price/availability info
- **Create orders (order_create.php):** No authentication - public can place orders (with rate limiting)
- **View orders (order_getall.php):** trs/wm/sa permission required
- **Fulfill orders (order_fulfill.php):** trs/wm/sa permission required
- **View item prices admin (itemprices_getall.php):** wm/sa permission required
- **Update item prices (itemprices_update.php):** wm/sa permission required

**Known Limitations:**
- PayPal payment verification stores order ID but does not verify with PayPal servers
- Order marked as paid based on PayPal client-side redirect, not server-side verification
- TODO documented in order_create.php: Implement PayPal webhook/IPN verification for production security
- Recommend implementing PayPal webhook or server-to-server API verification in future

---

## SECTION 7: Financial

### 7.1 Treasurer Features

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `TreasurerReport.php` | `templates/TreasurerReport.html` | `api/gettreasurerreport.php` | [x] |

**Priority Issues to Check:**
- [x] Treasurer access restricted to trs/wm/sa (verified in TreasurerReport.php and gettreasurerreport.php)
- [x] Financial data not exposed in logs (activity logging uses filtered sensitive data)
- [x] Payment history accurate (prepared statements, proper escaping)

### 7.2 Payment APIs

| API File | Purpose | Review Status |
|----------|---------|---------------|
| `api/pay.php` | Process payment | [x] |
| `api/getpasteventpay.php` | Get past payments | [x] |
| `api/order_create.php` | Create order | [x] (Section 6) |
| `api/order_fulfill.php` | Fulfill order | [x] (Section 6) |
| `api/order_getall.php` | Get all orders | [x] (Section 6) |
| `api/order_getconfig.php` | Get order config | [x] (Section 6) |

**Security Fixes Applied (Jan 2026):**
- [x] **CRITICAL:** Added `require_csrf()` to `gettreasurerreport.php` (was missing CSRF protection)
- [x] **CRITICAL:** Added `require_csrf()` to `getpasteventpay.php` (was missing CSRF protection)
- [x] Added activity logging to `gettreasurerreport.php` (`view_treasurer_report` action with filter params)
- [x] Added activity logging to `getpasteventpay.php` (`view_past_event_payments` action)
- [x] Fixed NULL check in `getpasteventpay.php` for user not found case (lines 54-55)
- [x] Fixed XSS vulnerability in `TreasurerReport.html` error message display (line 113)
- [x] Verified `pay.php` already secure (CSRF, auth, trs/sa permission, activity logging)
- [x] Verified `TreasurerReport.php` already secure (auth, trs/wm/sa permission check)

**Authorization Model:**
- **Treasurer Report (gettreasurerreport.php):** trs/wm/sa permission required
- **Past Event Payments (getpasteventpay.php):** Own data or family member data only
- **Update Payment Status (pay.php):** trs/sa permission only (more restrictive - excludes wm)

**Test Files Created:**
- `tests/unit/TreasurerReportAccessControlTest.php` - 31 tests
- `tests/unit/PastEventPayAccessControlTest.php` - 24 tests
- `tests/unit/PaymentAPIAccessControlTest.php` - 28 tests

---

## SECTION 8: Committee & Leadership

### 8.1 Committee Management

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `ManageCommittee.php` | `templates/ManageCommittee.html` | `api/getallcommittee.php`, `api/createcommittee.php`, `api/updatecommittee.php`, `api/deletecommittee.php` | [x] |

**Priority Issues to Check:**
- [x] Committee management restricted to wm/sa (actually sa-only, more restrictive)
- [x] Sort order enforced (UNIQUE constraint on sort_order column)
- [x] No orphaned committee entries (application-level check + FK migration created)

**Security Fixes Applied (Jan 2026):**
- [x] **ManageCommittee.php:** Already had sa permission check before displaying template
- [x] **ManageCommittee.html:** Already had escapeHtml() XSS prevention, client-side validation
- [x] **getallcommittee.php:** Already had require_authentication(), require_permission(['sa']), require_ajax()
- [x] **createcommittee.php:** Added require_csrf(), sanitized error messages (no $mysqli->error exposure)
- [x] **updatecommittee.php:** Added require_csrf(), sanitized error messages (no $mysqli->error exposure)
- [x] **deletecommittee.php:** Added require_csrf(), sanitized error messages (no $mysqli->error exposure)
- [x] All write APIs use prepared statements (no SQL injection)
- [x] All write APIs have comprehensive activity logging (success and failure cases)
- [x] FK migration created: `db_copy/migrations/add_committee_user_fk.sql`

**Authorization Model:**
- **All committee operations:** Super Admin (sa) only - more restrictive than wm/sa

**Test Files Created:**
- `tests/unit/CommitteeApiAuthorizationTest.php` - 67 tests
- `tests/unit/CommitteeApiInputValidationTest.php` - 78 tests
- `tests/unit/CommitteeActivityLoggingTest.php` - 20 tests

---

## SECTION 9: Merit Badges

### 9.1 Merit Badge Counselors

| Page File | Template File | API Files | Review Status |
|-----------|---------------|-----------|---------------|
| `MB_Counselors.php` | `templates/MB_Counselors.html` | `api/getMBcounselors.php` | [ ] |

**Priority Issues to Check:**
- [ ] Counselor list login-protected
- [ ] Contact info visible only to logged-in users

---

## SECTION 10: Public/Info Pages

### 10.1 Public Content

| Page File | Template File | Review Status |
|-----------|---------------|---------------|
| `index.php` | (home page) | [ ] |
| `Calendar.php` | (Google Calendar embed) | [ ] |
| `CurrentInfo.php` | (troop info) | [ ] |
| `Scoutmaster.php` | (scoutmaster info) | [ ] |
| `OurHistory.php` | (troop history) | [ ] |
| `EagleScouts.php` | (eagle scouts) | [ ] |
| `Handbook.php` | (handbook info) | [ ] |
| `FAQ.php` | `templates/FAQ.html` | [ ] |
| `Links.php` | (external links) | [ ] |
| `NewScoutInfo.php` | (new scout info) | [ ] |
| `ParentInfo.php` | (parent info) | [ ] |

**Priority Issues to Check:**
- [ ] No member PII on public pages
- [ ] Google Calendar iframe secure
- [ ] External links use rel="noopener"

---

## SECTION 11: Notifications

### 11.1 Notification Preferences

| API File | Purpose | Review Status |
|----------|---------|---------------|
| `api/notifications_getprefs.php` | Get user notification prefs | [ ] |
| `api/notifications_updatepref.php` | Update notification prefs | [ ] |

### 11.2 Email System

| File | Purpose | Review Status |
|------|---------|---------------|
| `api/sendmail.php` | Send email | [ ] |
| `api/sendtest.php` | Test email (REMOVE?) | [ ] |
| `login/libraries/PHPMailer.php` | PHPMailer library | [ ] |
| `login/libraries/class.smtp.php` | SMTP library | [ ] |
| `scripts/reminders.php` | Cron reminder script | [ ] |

**Priority Issues to Check:**
- [ ] Email content escaped for HTML
- [ ] Rate limiting on email sends
- [ ] Unsubscribe option available
- [ ] PHPMailer version is current

---

## SECTION 12: Miscellaneous

### 12.1 Utility Pages

| Page File | Purpose | Review Status |
|-----------|---------|---------------|
| `Mobile.php` | Mobile redirect | [ ] |
| `edit.php` | Generic edit page | [ ] |
| `info.php` | PHP info (REMOVE!) | [ ] |
| `ListGear.php` | Gear list | [ ] |

### 12.2 Profile Pictures

| File | Purpose | Review Status |
|------|---------|---------------|
| `profile_pics/create_thumbnails.php` | Thumbnail generator | [ ] |

**Priority Issues to Check:**
- [ ] Image upload validates file type
- [ ] Image size limits enforced
- [ ] No path traversal in filenames

### 12.3 Potential Dead Code/Debug Files

| File | Concern | Action |
|------|---------|--------|
| `api/updateattendance_debug.php` | Debug version | [x] REMOVED - Fixed AttendanceReport.html to use production API |
| `api/sendtest.php` | Test utility | [x] REMOVED |
| `api/fixscouts.php` | One-time fix? | [x] REMOVED - Migration script, no references |
| `api/checkinfo.php` | Unknown purpose | [x] KEEP - Actively used by authHeader.php for Scout profile |
| `info.php` | Exposes PHP info | [x] REMOVED - Security risk |
| `login/edit.php` | Unclear purpose | [x] REMOVED - Orphaned template |
| `login/views/edit.php` | Part of edit.php | [x] REMOVED - Orphaned template |
| `includes/Members.php` | Duplicate of root? | [x] REMOVED - Duplicate file |
| `test_manual/` | Manual test directory | [x] REMOVED - Not needed |

---

## SECTION 13: JavaScript Files

### 13.1 Custom JavaScript

| File | Purpose | Review Status |
|------|---------|---------------|
| `js/sortable-table.js` | Table sorting | [ ] |
| `js/modernizr-shim.js` | Modernizr compatibility | [ ] |
| `js/jquery.datetimepicker.js` | Date picker | [ ] |

**Priority Issues to Check:**
- [ ] No eval() or innerHTML with user data
- [ ] Event handlers properly namespaced
- [ ] No global variable pollution

### 13.2 Vendor Libraries

| File | Purpose | Review Status |
|------|---------|---------------|
| `js/jquery.js` | jQuery (CHECK VERSION) | [ ] |
| `js/vendor/jquery.js` | jQuery vendor copy | [ ] |
| `js/vendor/modernizr.js` | Modernizr | [ ] |
| `js/vendor/fastclick.js` | FastClick | [ ] |
| `js/vendor/jquery.cookie.js` | Cookie plugin | [ ] |
| `js/vendor/placeholder.js` | Placeholder polyfill | [ ] |
| `js/foundation.min.js` | Foundation framework | [ ] |
| `js/foundation/*.js` | Foundation components | [ ] |

**Priority Issues to Check:**
- [ ] jQuery version is current (3.x+)
- [ ] No known vulnerabilities in libraries
- [ ] Unused Foundation components can be removed

---

## SECTION 14: Tests

### 14.1 Test Infrastructure

| File | Purpose | Review Status |
|------|---------|---------------|
| `tests/bootstrap.php` | Test setup | [ ] |
| `tests/test_runner.php` | Test runner | [ ] |
| `tests/SyntaxTest.php` | PHP syntax validation | [ ] |

### 14.2 Unit Tests

| File | Feature Covered | Review Status |
|------|-----------------|---------------|
| `tests/unit/CredentialsTest.php` | Credentials | [ ] |
| `tests/unit/DatabaseCredentialsTest.php` | DB credentials | [ ] |
| `tests/unit/SMTPCredentialsTest.php` | SMTP credentials | [ ] |
| `tests/unit/CookieSecretTest.php` | Cookie secret | [ ] |
| `tests/unit/ActivityLoggerTest.php` | Activity logger | [ ] |
| `tests/unit/ActivityLoggingStaticTest.php` | Activity logging | [ ] |
| `tests/unit/AttendanceAccessControlTest.php` | Attendance auth | [ ] |
| `tests/unit/AttendanceUpdatesTest.php` | Attendance updates | [ ] |
| `tests/unit/AttendanceReportAccessControlTest.php` | Report auth | [ ] |
| `tests/unit/AttendanceReportAPITest.php` | Report API | [ ] |
| `tests/unit/AttendanceReportFrontendTest.php` | Report frontend | [ ] |
| `tests/unit/AttendanceReportIntegrationTest.php` | Report integration | [ ] |
| `tests/unit/DateSelectorTest.php` | Date selector | [ ] |
| `tests/unit/AddressUpdateTest.php` | Address updates | [ ] |
| `tests/unit/CancellationNotificationTest.php` | Cancellation emails | [ ] |
| `tests/unit/EventEmailPreferenceTest.php` | Event email prefs | [ ] |
| `tests/unit/GetUserTest.php` | Get user API | [ ] |
| `tests/unit/NotificationPreferencesTest.php` | Notification prefs | [ ] |
| `tests/unit/PatrolFeaturesTest.php` | Patrol features | [ ] |
| `tests/unit/PatrolMembersTest.php` | Patrol members | [ ] |
| `tests/unit/RosterEmailPreferenceTest.php` | Roster email prefs | [ ] |
| `tests/unit/ScoutSignupEmailPreferenceTest.php` | Signup email prefs | [ ] |
| `tests/unit/SetupScriptTest.php` | Setup script | [ ] |
| `tests/unit/SiteWideAccessControlTest.php` | Site-wide auth | [ ] |
| `tests/unit/StoreAPITest.php` | Store API | [ ] |

### 14.3 Integration Tests

| File | Feature Covered | Review Status |
|------|-----------------|---------------|
| `tests/integration/SecurityTest.php` | Security checks | [ ] |
| `tests/integration/ActivityLoggingIntegrationTest.php` | Activity logging | [ ] |
| `tests/integration/PositionPermissionSyncTest.php` | Position-permission sync | [ ] |
| `tests/integration/StoreOrderFlowTest.php` | Store order flow | [ ] |

---

## SECTION 15: Cross-Cutting Concerns

### 15.1 Security Audit Checklist

- [ ] All API endpoints use `require_ajax()`
- [ ] All API endpoints use `require_authentication()` where needed
- [ ] All API endpoints validate permissions appropriately
- [ ] All database queries use prepared statements
- [ ] All user output is escaped (XSS prevention)
- [ ] All file uploads validate type and size
- [ ] CSRF tokens on all state-changing forms
- [ ] No hardcoded credentials in codebase
- [ ] Error messages don't leak sensitive info
- [ ] Session cookies have proper flags

### 15.2 Performance Audit Checklist

- [ ] Database queries are optimized with indexes
- [ ] No N+1 query patterns
- [ ] Large lists use pagination
- [ ] Static assets have cache headers
- [ ] JavaScript is minified in production
- [ ] Images are optimized
- [ ] No blocking synchronous operations

### 15.3 Code Quality Checklist

- [ ] Consistent indentation (tabs vs spaces)
- [ ] Consistent naming conventions
- [ ] No duplicate code (DRY)
- [ ] Functions are single-purpose
- [ ] Magic numbers replaced with constants
- [ ] Dead code removed
- [ ] Debug code removed
- [ ] Comments explain "why" not "what"

---

## Summary Statistics

| Category | File Count | Reviewed |
|----------|------------|----------|
| Main Pages (.php) | ~45 | 0 |
| Templates (.html) | ~35 | 0 |
| API Endpoints | ~55 | 0 |
| Include Files | ~15 | 0 |
| JavaScript Files | ~20 | 0 |
| Test Files | ~30 | 0 |
| **TOTAL** | **~200** | **0** |

---

## Recommended Review Order

1. **Security-Critical First:**
   - `api/auth_helper.php`
   - `api/validation_helper.php`
   - `login/classes/Login.php`
   - `api/updatepermissions.php`

2. **High-Traffic APIs:**
   - `api/getevents.php`
   - `api/getevent.php`
   - `api/getuser.php`
   - `api/updateuser.php`

3. **Financial/Payment:**
   - `api/pay.php`
   - T-shirt store APIs
   - `TreasurerReport.php`

4. **Remaining by feature section order**

---

*Document Created: January 2026*
*Last Updated: January 29, 2026*

---

## Section 6 Overhaul Summary (January 29, 2026)

### Files Modified

**API Files:**
- `/Users/bmleedy/t212site_public/public_html/api/order_create.php` - Order creation with validation, transactions, rate limiting
- `/Users/bmleedy/t212site_public/public_html/api/order_getconfig.php` - Public store configuration endpoint
- `/Users/bmleedy/t212site_public/public_html/api/order_getall.php` - Admin order listing with permission checks
- `/Users/bmleedy/t212site_public/public_html/api/order_fulfill.php` - Order fulfillment with audit trail
- `/Users/bmleedy/t212site_public/public_html/api/itemprices_getall.php` - Item price listing for admins
- `/Users/bmleedy/t212site_public/public_html/api/itemprices_update.php` - Item price updates with validation

**Page Files:**
- `/Users/bmleedy/t212site_public/public_html/TShirtOrder.php` - Public order page
- `/Users/bmleedy/t212site_public/public_html/TShirtOrderComplete.php` - Order confirmation page
- `/Users/bmleedy/t212site_public/public_html/ManageTShirtOrders.php` - Admin order management
- `/Users/bmleedy/t212site_public/public_html/ManageItemPrices.php` - Admin price management

**Template Files:**
- `/Users/bmleedy/t212site_public/public_html/templates/TShirtOrder.html`
- `/Users/bmleedy/t212site_public/public_html/templates/TShirtOrderComplete.html`
- `/Users/bmleedy/t212site_public/public_html/templates/ManageTShirtOrders.html`
- `/Users/bmleedy/t212site_public/public_html/templates/ManageItemPrices.html`

**Include Files:**
- `/Users/bmleedy/t212site_public/public_html/includes/store_email.php` - Generic order email functions
- `/Users/bmleedy/t212site_public/public_html/includes/tshirt_email.php` - Backward compatibility wrapper

**Test Files:**
- `/Users/bmleedy/t212site_public/tests/unit/StoreAPITest.php` - 58 unit tests (all passing)
- `/Users/bmleedy/t212site_public/tests/integration/StoreOrderFlowTest.php` - Integration tests (requires mysqli)

### Security Fixes Applied

1. **Authentication & Authorization:**
   - All admin APIs require authentication via `require_authentication()`
   - Order management requires trs/wm/sa permission
   - Price management requires wm/sa permission (more restrictive)
   - Server-side permission checks in PHP pages before displaying templates

2. **Input Validation:**
   - All user inputs validated via `validation_helper.php`
   - String length limits enforced (name: 100, phone: 20, address: 500)
   - Price validation with min/max bounds (0 to 9999.99)
   - JSON items array validation with type checking

3. **SQL Injection Prevention:**
   - All database queries use prepared statements
   - No string concatenation in SQL queries

4. **CSRF Protection:**
   - All state-changing APIs require `require_csrf()` validation

5. **Rate Limiting:**
   - Order creation limited to 10 orders per IP per hour
   - Prevents abuse of public ordering endpoint

6. **Transaction Safety:**
   - Order creation uses database transactions
   - Rollback on failure with error logging

7. **Activity Logging:**
   - All order operations logged with success/failure status
   - Fulfillment logs include user ID of fulfiller
   - Price changes log old and new values

### Test Results

```
TEST SUITE: Store API Unit Tests
============================================================
SUMMARY: All tests passed! (58 passed, 0 failed)

TEST SUITE: T212 SITE TEST RUNNER
============================================================
Total Test Suites: 34
Passed: 34
Failed: 0
Skipped: 4 (mysqli not available)
```

Note: `StoreOrderFlowTest.php` requires mysqli extension which is not available in the local test environment. These integration tests will run in environments with database connectivity.
