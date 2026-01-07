# Email Notification Logging Implementation

## Overview
Email notification logging has been added to the activity_log system. All emails sent by the website are now tracked with recipient addresses and subject lines.

## Files Modified

### API Email Endpoints
1. **`/public_html/api/sendmail.php`**
   - General email sending API
   - Actions logged: `send_email`, `send_email_failed`
   - Captures: recipients (including parent emails with preferences), subject, from address

2. **`/public_html/api/sendtest.php`**
   - Test email sending API
   - Actions logged: `send_test_email`, `send_test_email_failed`
   - Captures: recipients, subject, from address

3. **`/public_html/api/register.php`** (sendCancellationNotification function)
   - Event cancellation notifications
   - Actions logged: `send_cancellation_email`, `send_cancellation_email_failed`
   - Captures: recipients (SIC/AIC based on preferences), subject, event_id, cancelled_by_user_id

### Login/Registration Classes
4. **`/public_html/login/classes/Registration.php`** (sendVerificationEmail method)
   - New user verification emails
   - Actions logged: `send_verification_email`, `send_verification_email_failed`
   - Captures: recipient, subject, user_email, user_type

5. **`/public_html/login/classes/Login.php`** (sendPasswordResetMail method)
   - Password reset emails
   - Actions logged: `send_password_reset_email`, `send_password_reset_email_failed`
   - Captures: recipient, subject, user_name

## Email Actions Logged

| Action | Type | Captured Data |
|--------|------|---------------|
| `send_email` | General | to[], subject, from, recipient_count |
| `send_email_failed` | General | to[], subject, from, error |
| `send_test_email` | Test | to[], subject, from, recipient_count |
| `send_test_email_failed` | Test | to[], subject, from, error |
| `send_cancellation_email` | Event | to[], subject, event_id, cancelled_by_user_id |
| `send_cancellation_email_failed` | Event | to[], subject, event_id, error |
| `send_verification_email` | Registration | to[], subject, user_email, user_type |
| `send_verification_email_failed` | Registration | to[], subject, user_email, error |
| `send_password_reset_email` | Login | to[], subject, user_name |
| `send_password_reset_email_failed` | Login | to[], subject, user_name, error |

## Data Captured

### Common Fields (All Email Logs)
- **to**: Array of recipient email addresses
- **subject**: Email subject line
- **timestamp**: When the email was sent (or attempted)
- **source_file**: Which file sent the email
- **success**: Whether the email was sent successfully (1) or failed (0)

### Email-Specific Fields
- **from**: Sender email address (API emails)
- **recipient_count**: Number of recipients (calculated)
- **event_id**: Event ID (cancellation emails)
- **cancelled_by_user_id**: User who cancelled (cancellation emails)
- **user_email**: Registering user's email (verification emails)
- **user_type**: Scout or Adult (verification emails)
- **user_name**: Username (password reset emails)
- **error**: PHPMailer error message (failed sends)

## Implementation Details

### API Files (sendmail.php, sendtest.php)
- Added `require_once` for activity_logger.php
- Collect all recipient addresses using `$mail->getAllRecipientAddresses()`
- Log after `$mail->Send()` attempt
- Log both success and failure cases
- Include error details for failures

### Cancellation Notifications (register.php)
- Already had activity_logger included
- Extract recipient emails from $recipients array
- Log with event context (event_id, user_id)
- Respects user notification preferences before logging

### Login/Registration Classes
- Use conditional file_exists() check for compatibility
- Create separate mysqli connection for logging
- Silently fail logging if error (don't break email flow)
- Close logging connection after use
- Use try/catch to prevent exceptions from breaking workflow

## Query Examples

### View all emails sent today
```sql
SELECT
    timestamp,
    action,
    values_json->>'$.to' as recipients,
    values_json->>'$.subject' as subject,
    success
FROM activity_log
WHERE action LIKE 'send_%'
  AND DATE(timestamp) = CURDATE()
ORDER BY timestamp DESC;
```

### Count emails by type
```sql
SELECT
    action,
    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed,
    COUNT(*) as total
FROM activity_log
WHERE action LIKE 'send_%'
GROUP BY action;
```

### Find failed emails
```sql
SELECT
    timestamp,
    action,
    values_json->>'$.to' as recipients,
    values_json->>'$.subject' as subject,
    values_json->>'$.error' as error_message
FROM activity_log
WHERE action LIKE '%_failed'
  AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY timestamp DESC;
```

### Emails sent to specific address
```sql
SELECT
    timestamp,
    action,
    values_json->>'$.subject' as subject,
    success
FROM activity_log
WHERE action LIKE 'send_%'
  AND JSON_SEARCH(values_json->'$.to', 'one', 'user@example.com') IS NOT NULL
ORDER BY timestamp DESC;
```

### Daily email volume
```sql
SELECT
    DATE(timestamp) as date,
    COUNT(*) as emails_sent,
    SUM(JSON_LENGTH(values_json->'$.to')) as total_recipients
FROM activity_log
WHERE action LIKE 'send_%'
  AND success = 1
GROUP BY DATE(timestamp)
ORDER BY date DESC
LIMIT 30;
```

## Notification Preference Handling

The logging respects user notification preferences:

1. **Cancellation Emails** (register.php):
   - Checks `notif_preferences.canc` for SIC/AIC
   - Only logs emails actually sent (opted-in users)
   - Logs which users opted out in error_log

2. **Scout Signup Emails** (sendmail.php):
   - Checks `notif_preferences.scsu` for parents
   - Only adds opted-in parents to recipient list
   - Logs final recipient count

## Error Handling

### API Files
- Log failures with `success=false`
- Include `error` field with PHPMailer error message
- Continue execution after logging failure
- Return error to API caller

### Login/Registration Classes
- Use try/catch blocks around logging
- Silently fail if logging errors occur
- Don't break email sending flow
- Use separate mysqli connection (mysqli_log)

## Testing

### Manual Testing
1. Send test email via API: `http://yoursite.com/api/sendtest.php`
2. Register new user: triggers verification email
3. Request password reset: triggers reset email
4. Cancel event registration: triggers cancellation email
5. Check activity_log table for entries

### Verification Query
```sql
SELECT
    action,
    COUNT(*) as count
FROM activity_log
WHERE action LIKE 'send_%'
GROUP BY action;
```

Expected actions:
- send_email
- send_email_failed
- send_test_email
- send_test_email_failed
- send_cancellation_email
- send_cancellation_email_failed
- send_verification_email
- send_verification_email_failed
- send_password_reset_email
- send_password_reset_email_failed

## Monitoring & Alerts

### Email Delivery Monitoring
Monitor failed emails:
```sql
SELECT COUNT(*) as failed_emails_today
FROM activity_log
WHERE action LIKE '%_failed'
  AND DATE(timestamp) = CURDATE();
```

### High Volume Alert
Check for unusual email volume:
```sql
SELECT COUNT(*) as emails_last_hour
FROM activity_log
WHERE action LIKE 'send_%'
  AND success = 1
  AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

## Privacy & Data Retention

- Email addresses are stored in `values_json` column
- Subject lines are stored (may contain user names)
- 90-day retention via cleanup_activity_log.php cron
- Data automatically purged after 90 days

## Maintenance

### Adding New Email Types
To log a new email type:

1. Include activity_logger.php:
```php
require_once(__DIR__ . '/../includes/activity_logger.php');
```

2. Collect recipients:
```php
$recipients = array();
foreach ($mail->getAllRecipientAddresses() as $email => $name) {
    $recipients[] = $email;
}
```

3. Log after sending:
```php
if(!$mail->Send()) {
    log_activity(
        $mysqli,
        'send_YOUR_EMAIL_TYPE_failed',
        array(
            'to' => $recipients,
            'subject' => $mail->Subject,
            'error' => $mail->ErrorInfo
        ),
        false,
        "Failed to send YOUR_EMAIL_TYPE",
        $user_id
    );
} else {
    log_activity(
        $mysqli,
        'send_YOUR_EMAIL_TYPE',
        array(
            'to' => $recipients,
            'subject' => $mail->Subject,
            'recipient_count' => count($recipients)
        ),
        true,
        "YOUR_EMAIL_TYPE sent successfully",
        $user_id
    );
}
```

## Files Summary

**Modified:** 5 files
- /public_html/api/sendmail.php
- /public_html/api/sendtest.php
- /public_html/api/register.php
- /public_html/login/classes/Registration.php
- /public_html/login/classes/Login.php

**Email Actions:** 10 actions (5 success + 5 failure pairs)

**Database Impact:** Minimal - emails typically <10 per day
