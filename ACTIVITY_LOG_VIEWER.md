# Activity Log Viewer Documentation

## Overview
The Activity Log Viewer provides a web-based interface to view and filter system activity logs. This page is only accessible to webmasters.

## Access

**URL:** `http://yoursite.com/ActivityLog.php`

**Permission Required:** Webmaster or SuperAdmin access level

## Features

### Default View
- **Shows:** Current day's activity logs
- **Order:** Most recent first
- **Limit:** Maximum 1000 entries per query

### Date/Time Filters
- **Start Date/Time:** Select beginning of date range
- **End Date/Time:** Select end of date range
- Default: Today 00:00:00 to 23:59:59

### Column Filters
All filters support partial matching (case-insensitive):

1. **Action** - Filter by action type (e.g., "send_email", "update_user")
2. **Source File** - Filter by originating file (e.g., "sendmail.php")
3. **User ID** - Filter by specific user ID
4. **Success** - Filter by success/failure status
   - All: Show both successes and failures
   - Success Only: Show only successful operations
   - Failures Only: Show only failed operations
5. **Free Text** - Search within description field

### Display Columns

| Column | Description |
|--------|-------------|
| **Timestamp** | When the action occurred (formatted as local time) |
| **Source File** | PHP file that logged the action |
| **Action** | Action type (e.g., send_email, update_user) |
| **Values (JSON)** | Formatted JSON data with relevant details |
| **Success** | ✅ Yes (green) or ❌ No (red) |
| **Description** | Human-readable description of the action |
| **User ID** | ID of user who performed the action (N/A if system) |

### Visual Indicators

- **Green rows**: Successful actions (success = 1)
- **Red rows**: Failed actions (success = 0)
- **Hover effect**: Rows highlight on mouse hover for easier reading

## Files Created

### 1. `/public_html/ActivityLog.php`
Main page file that:
- Requires webmaster/superadmin access
- Loads the ActivityLog.html template
- Shows access denied for non-authorized users

### 2. `/public_html/templates/ActivityLog.html`
HTML/JavaScript template that:
- Renders the filter interface
- Makes AJAX calls to fetch data
- Formats and displays results in a table
- Handles date/time formatting
- Provides clear/apply filter functions

### 3. `/public_html/api/getactivitylog.php`
API endpoint that:
- Accepts filter parameters via JSON POST
- Builds dynamic SQL query with filters
- Returns activity log data as JSON
- Limits results to 1000 entries
- Orders by timestamp DESC (most recent first)

## Usage Examples

### View All Today's Activity
1. Navigate to ActivityLog.php
2. Click "Apply Filters" (default shows today)

### Find All Failed Emails
1. Set Action filter to: `send`
2. Set Success filter to: `Failures Only`
3. Click "Apply Filters"

### View Specific User's Activity
1. Enter user ID in User ID filter
2. Set date range if needed
3. Click "Apply Filters"

### Find All Actions from Specific File
1. Enter filename in Source File filter (e.g., `register.php`)
2. Click "Apply Filters"

### Search for Specific Event
1. Enter event ID or keywords in Free Text filter
2. Click "Apply Filters"

## API Parameters

The `getactivitylog.php` API accepts these JSON parameters:

```json
{
  "startDate": "2026-01-07T00:00",
  "endDate": "2026-01-07T23:59",
  "action": "send_email",
  "sourceFile": "sendmail.php",
  "user": "123",
  "success": "1",
  "freetext": "event"
}
```

All parameters are optional. Empty/null parameters are ignored.

## Response Format

```json
[
  {
    "timestamp": "2026-01-07 14:30:25.123456",
    "source_file": "sendmail.php",
    "action": "send_email",
    "values_json": "{\"to\":[\"user@example.com\"],\"subject\":\"Test\"}",
    "success": 1,
    "freetext": "Email sent successfully",
    "user": 123
  }
]
```

## Performance Considerations

- **Result Limit:** Maximum 1000 entries per query
- **Indexing:** Ensure `activity_log` table has indexes on:
  - `timestamp` (for date range queries)
  - `action` (for action filtering)
  - `user` (for user filtering)
- **Date Range:** Narrow date ranges for faster queries

## Recommended Indexes

Add these indexes to improve query performance:

```sql
ALTER TABLE activity_log ADD INDEX idx_timestamp (timestamp);
ALTER TABLE activity_log ADD INDEX idx_action (action);
ALTER TABLE activity_log ADD INDEX idx_user (user);
ALTER TABLE activity_log ADD INDEX idx_success (success);
ALTER TABLE activity_log ADD INDEX idx_source_file (source_file(50));
```

## Common Queries via UI

### Daily Email Report
- Action: `send_`
- Date: Today
- Success: All

### Failed Operations Last Week
- Success: Failures Only
- Start Date: 7 days ago
- End Date: Today

### User Activity Audit
- User ID: [specific user]
- Date range: As needed

### System Errors
- Success: Failures Only
- Action: [leave empty for all failures]

## Security

- **Access Control:** Only webmaster/superadmin can access
- **AJAX Only:** API endpoint only responds to AJAX requests
- **Read-Only:** No modification of logs possible through UI
- **SQL Injection Protection:** All queries use prepared statements
- **Result Limiting:** Prevents denial of service via large queries

## Troubleshooting

### "Access Denied" message
- Verify user has webmaster or superadmin access level
- Check that user is logged in
- Review `$access` array in session

### No data showing
- Check date range (default is today only)
- Verify activity_log table has data
- Check browser console for JavaScript errors
- Verify API endpoint is accessible

### API errors
- Check PHP error logs
- Verify database connection
- Ensure activity_log table exists
- Check API endpoint permissions

### Slow loading
- Narrow date range
- Use specific filters
- Check database indexes
- Consider archiving old logs

## Maintenance

### Regular Tasks
- Monitor query performance
- Archive logs older than 90 days (via cron)
- Review failed actions periodically
- Adjust filters based on common queries

### Future Enhancements
- Export to CSV
- Email reports
- Real-time updates
- Graphical visualizations
- Action statistics

## Related Documentation

- `/EMAIL_LOGGING.md` - Email notification logging details
- `/tests/ACTIVITY_LOGGING_TESTS.md` - Testing documentation
- `/db_copy/cleanup_activity_log.php` - Log cleanup script

## Support

For issues or questions:
- Email: t212webmaster@gmail.com
- Check activity_log table directly for data verification
- Review browser console for JavaScript errors
- Check PHP error logs for API issues
