# T212 Site Project Context

This file contains important context about the T212 Scout Troop website project for AI assistants working on this codebase.

## Project Overview

**Project Type:** Scout Troop 212 Website
**Stack:** PHP, MySQL, JavaScript (jQuery), Foundation CSS Framework
**Purpose:** Event management, payment processing, user management, attendance tracking for a Boy Scout troop
**Working Directory:** `/Users/bmleedy/t212site_public/`

---

## Code Style & Conventions

### PHP

- **Authentication Pattern:**
  ```php
  session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
  session_start();
  require "includes/authHeader.php";
  ```

- **Access Control:**
  - Use `require_permission(['wm', 'sa'])` from `api/auth_helper.php`
  - Session access stored in `$_SESSION['user_access']` as dot-separated string (e.g., `'ue.oe.sa'`)
  - Common permissions: `ue` (User Edit), `oe` (Outing Edit), `sa` (Super Admin), `wm` (Webmaster), `pl` (Patrol Leader)

- **API Standards:**
  - Always use `require_ajax()` at the start of API files
  - Always use `require_authentication()` to get authenticated user ID
  - Use prepared statements for ALL database queries
  - Return JSON with `header('Content-Type: application/json')`
  - Use `validation_helper.php` functions: `validate_int_post()`, `validate_string_post()`

- **Activity Logging:**
  - ALL write operations must log to `activity_log` table
  - Use `log_activity($mysqli, $action, $values, $success, $freetext, $user_id)`
  - Log both success AND failure cases
  - Include descriptive freetext messages
  - Values parameter should be an array of relevant data

- **SQL Patterns:**
  ```php
  $query = "SELECT * FROM table WHERE id=?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('i', $id);
  $statement->execute();
  $result = $statement->get_result();
  ```

### File Organization

**Structure:**
```
public_html/
├── *.php                     # Main pages
├── api/                      # Backend API endpoints
│   ├── auth_helper.php       # Authentication utilities
│   ├── validation_helper.php # Input validation
│   └── connect.php           # Database connection
├── includes/
│   ├── authHeader.php        # Page authentication
│   ├── credentials.php       # Credentials class
│   ├── activity_logger.php   # Activity logging utility
│   ├── m_sidebar.html        # Logged-in menu
│   └── sidebar.html          # Logged-out menu
├── templates/                # HTML/JavaScript templates
└── tests/
    ├── unit/                 # Unit tests
    └── integration/          # Integration tests
```

### Database Credentials

**Never hardcode credentials!** Always use:
```php
require_once(__DIR__ . '/includes/credentials.php');
$creds = Credentials::getInstance();
$host = $creds->getDatabaseHost();
```

Credentials are stored in `CREDENTIALS.json` (git-ignored, .htaccess protected).

---

## Testing Standards

### Test Requirements

1. **Always create tests for new features:**
   - Unit tests for isolated functionality
   - Integration tests for database/API operations

2. **Test File Naming:**
   - Unit: `tests/unit/FeatureNameTest.php`
   - Integration: `tests/integration/FeatureNameIntegrationTest.php`

3. **Test Structure:**
   ```php
   require_once dirname(__DIR__) . '/bootstrap.php';
   test_suite("Test Suite Name");

   $passed = 0;
   $failed = 0;

   // Tests here

   test_summary($passed, $failed);
   exit($failed === 0 ? 0 : 1);
   ```

4. **Update Test Documentation:**
   - Add new APIs to test file arrays
   - Update action counts in `tests/ACTIVITY_LOGGING_TESTS.md`

### Running Tests

```bash
# All tests
php tests/test_runner.php

# Specific test
php tests/unit/SpecificTest.php
```

---

## Security Best Practices

### Input Validation

```php
// API endpoints
require_ajax();
$user_id = require_authentication();
require_permission(['wm', 'sa']);

// POST data
$id = validate_int_post('id');
$name = validate_string_post('name');
```

### SQL Injection Prevention

✅ **Always use prepared statements**
❌ **Never concatenate user input into SQL**

```php
// GOOD
$query = "SELECT * FROM users WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);

// BAD - Never do this!
$query = "SELECT * FROM users WHERE id=" . $_POST['id'];
```

### XSS Prevention

```php
// Use escape_html() from validation_helper.php
echo escape_html($user_input);
```

---

## Feature Patterns

### Adding New Menu Items

1. **Determine access level** (who can see it)
2. **Add to both menus:**
   - `includes/m_sidebar.html` (logged-in users)
   - `includes/mobile_menu.html` (mobile)
3. **Use proper access control:**
   ```php
   <?php if ((in_array("wm",$access)) || (in_array("sa",$access))) {
     echo '<a href="Page.php"><p><i class="fi-icon"></i> [Label] Page Name</p></a><hr>';
   } ?>
   ```

### Page Template Pattern

```php
<?php
session_set_cookie_params(0, '/', $_SERVER['SERVER_NAME']);
session_start();
require "includes/authHeader.php";
?>
<br />
<div class='row'>
	<?php
		if ($login->isUserLoggedIn() == true) {
			require "includes/m_sidebar.html";
		} else {
			require "includes/sidebar.html";
		}
	?>
	<div class="large-9 columns">
		<div class="panel">
			<?php
			if ($login->isUserLoggedIn() == true) {
				include("templates/PageTemplate.html");
			} else {
				include("login/views/user_login.php");
			}
			?>
		</div>
	</div>
</div>
<?php require "includes/footer.html"; ?>
```

### API Endpoint Pattern

```php
<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['wm', 'sa']); // Adjust as needed

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Validate inputs
$id = validate_int_post('id');
$name = validate_string_post('name');

// Database operations with prepared statements
$query = "UPDATE table SET name=? WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('si', $name, $id);

if ($stmt->execute()) {
    // Log success
    log_activity(
        $mysqli,
        'action_name',
        array('id' => $id, 'name' => $name),
        true,
        "Descriptive success message",
        $current_user_id
    );
    echo json_encode(['status' => 'Success']);
} else {
    // Log failure
    log_activity(
        $mysqli,
        'action_name',
        array('id' => $id, 'error' => $mysqli->error),
        false,
        "Descriptive failure message",
        $current_user_id
    );
    echo json_encode(['status' => 'Error', 'message' => $mysqli->error]);
}

$stmt->close();
die();
?>
```

---

## Permission System

### Permission Codes

| Code | Name | Description |
|------|------|-------------|
| `sa` | Super Admin | Full access to everything |
| `wm` | Webmaster | Manage site, view logs, manage patrols |
| `ue` | User Edit | Create/edit users |
| `oe` | Outing Edit | Manage events |
| `pl` | Patrol Leader | Access patrol tools |
| `trs` | Treasurer | Financial access |

### Position-Permission Sync

- Patrol Leader position (position_id=1) automatically grants/removes 'pl' permission
- Implemented in `api/updateuser.php` via `syncPositionPermissions()` function
- Changes are logged to activity_log

---

## Common Gotchas

1. **Session Access:** Use `$_SESSION['user_access']`, NOT `$_SESSION['access']`
2. **Menu Files:** Both `m_sidebar.html` and `mobile_menu.html` need updates
3. **Activity Logging:** Don't forget to log BOTH success and failure
4. **Test Updates:** When modifying APIs, update test files too
5. **Git Commits:** Only commit when user explicitly requests it

---

## Dead Code Policy

When identifying potential dead code:
1. Search for all references in codebase
2. Check navigation menus (m_sidebar.html, mobile_menu.html, sidebar.html)
3. Check database for page_counter entries (recent usage)
4. Verify template existence
5. Check if APIs referenced by the code exist
6. Propose deletion plan to user before removing

---

## Documentation

- Activity logging tests: `tests/ACTIVITY_LOGGING_TESTS.md`
- Database schema: `db_copy/u104214272_t212.sql`
- Stored procedures: `db_copy/create_procedures.sql`
- Test runner: `tests/test_runner.php`

---

## Recent Changes & Decisions

- **Position-Permission Sync:** Implemented automatic 'pl' permission sync with Patrol Leader position (Jan 2026)
- **Recharter System:** Removed entirely - deprecated PayPal Adaptive Payments (Jan 2026)
- **Activity Logging:** All write operations now logged (implemented late 2025)
- **ClassBOrder:** T-shirt ordering system, pending removal (uses deprecated PayPal SDK)

---

## Contact & Support

- Webmaster email: t212webmaster@gmail.com (receives activity log failure alerts)
- GitHub: Issues at https://github.com/anthropics/claude-code/issues (for Claude Code tool issues)
