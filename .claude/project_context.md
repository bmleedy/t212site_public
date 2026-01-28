# T212 Site Context

## Project
- **Stack:** PHP 8.3, MySQL, jQuery, Foundation CSS
- **Purpose:** Scout troop event/payment/user management
- **Root:** `/Users/bmleedy/t212site_public/`
- **Webmaster:** t212webmaster@gmail.com

## Key Patterns

### Authentication & Authorization
```php
require 'auth_helper.php';
require_ajax();
$user_id = require_authentication();
require_permission(['wm', 'sa']);  // Optional - restrict to specific roles
```

### Permission Codes
`sa` (Super Admin), `wm` (Webmaster), `ue` (User Edit), `oe` (Outing Edit), `pl` (Patrol Leader), `trs` (Treasurer)

Session access: `$_SESSION['user_access']` - dot-separated string (e.g., `'ue.oe.sa'`)

### Activity Logging (Required for ALL write operations)
```php
require_once(__DIR__ . '/../includes/activity_logger.php');
log_activity($mysqli, $action, $values_array, $success_bool, $freetext, $user_id);
```
- Log both success AND failure cases
- `$values` must be an array, not JSON string
- `$success` must be boolean true/false

### Input Validation
```php
require 'validation_helper.php';
$id = validate_int_post('id');
$name = validate_string_post('name');
```

### Database Credentials
```php
require_once(__DIR__ . '/includes/credentials.php');
$creds = Credentials::getInstance();
```
Credentials stored in `CREDENTIALS.json` (git-ignored, .htaccess protected).

## File Structure
```
public_html/
├── *.php                      # Main pages (see Approve.php for template)
├── api/                       # Backend APIs (see approve.php for canonical pattern)
│   ├── auth_helper.php        # Authentication utilities
│   ├── validation_helper.php  # Input validation
│   └── connect.php            # Database connection
├── includes/
│   ├── authHeader.php         # Page authentication
│   ├── credentials.php        # Credentials class
│   ├── activity_logger.php    # Activity logging
│   ├── m_sidebar.html         # Logged-in menu
│   ├── mobile_menu.html       # Mobile menu
│   └── sidebar.html           # Logged-out menu
├── templates/                 # HTML/JavaScript templates
tests/
├── unit/                      # Unit tests
├── integration/               # Integration tests
└── test_runner.php            # Run all tests
```

## Testing Rules (MANDATORY)

1. **New features:** Create corresponding unit/integration tests
2. **Bug fixes:** Create regression test that catches the bug
3. **All changes:** Run `php tests/test_runner.php` - not complete until tests pass
4. **New features:** Update `RELEASE_TESTING_CHECKLIST.md` with manual testing steps

### Test Structure
```php
require_once dirname(__DIR__) . '/bootstrap.php';
test_suite("Test Suite Name");
$passed = 0; $failed = 0;
// Tests here using assert_true(), assert_equals(), etc.
test_summary($passed, $failed);
exit($failed === 0 ? 0 : 1);
```

## Common Gotchas

1. **Menus:** Update BOTH `m_sidebar.html` AND `mobile_menu.html`
2. **Session:** Use `$_SESSION['user_access']`, NOT `$_SESSION['access']`
3. **Logging:** Log both success AND failure cases
4. **Position-Permission Sync:** Patrol Leader (position_id=1) auto-syncs 'pl' permission via `syncPositionPermissions()` in `api/updateuser.php`
5. **Git:** Only commit when user explicitly requests

## Reference Files

| Purpose | File |
|---------|------|
| Canonical API pattern | `api/approve.php` |
| Page template pattern | `Approve.php` |
| DB schema | `db_copy/u104214272_t212.sql` |
| Stored procedures | `db_copy/create_procedures.sql` |
| Activity log tests | `tests/ACTIVITY_LOGGING_TESTS.md` |

## Recent Decisions

- **Position-Permission Sync:** Auto 'pl' permission with Patrol Leader position (Jan 2026)
- **Recharter System:** Removed - deprecated PayPal Adaptive Payments (Jan 2026)
- **ClassBOrder:** T-shirt system pending removal (uses deprecated PayPal SDK)
