# Manual Tests

This directory contains manually-run test scripts for testing specific functionality that requires user interaction or external services.

## Available Tests

### Email Test (`test_email.php`)

Tests the SMTP email functionality by sending a test email using credentials from CREDENTIALS.json.

**Usage:**
```bash
# Send a test email
php tests/manual/test_email.php your.email@example.com

# Send with verbose SMTP debugging
php tests/manual/test_email.php -v your.email@example.com

# Show help
php tests/manual/test_email.php --help
```

**What it tests:**
- Loads SMTP credentials from CREDENTIALS.json
- Configures PHPMailer with SMTP settings
- Sends an actual test email
- Verifies email configuration is correct

**Notes:**
- This test sends a real email, so use a valid email address you have access to
- Check your spam folder if you don't receive the email
- Use `-v` flag to see detailed SMTP debug output if troubleshooting

## Why Manual Tests?

These tests are kept separate from automated tests because they:
1. Require external services (like email servers)
2. Need user-provided input (like email addresses)
3. Have side effects (actually send emails)
4. May incur costs or rate limits

Manual tests should be run on-demand when you need to verify specific functionality works correctly in your environment.
