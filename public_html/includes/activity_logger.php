<?php
/**
 * Activity Logger Utility
 *
 * Logs all write operations to the activity_log database table.
 * Records: timestamp, source_file, action, values_json, success, freetext, user
 */

/**
 * Log an activity to the activity_log table
 *
 * @param mysqli $mysqli Database connection object
 * @param string $action Human-readable brief name of what action was requested (e.g., "update_user", "register_event")
 * @param array|null $values Associative array of relevant data to log (will be JSON encoded)
 * @param bool $success True if the action was successful, false otherwise
 * @param string|null $freetext User-friendly explanation/context (optional)
 * @param int|null $post_user_id User ID from POST data (optional, for comparison with session)
 * @return bool True if logged successfully, false if logging failed
 */
function log_activity($mysqli, $action, $values = null, $success = true, $freetext = null, $post_user_id = null) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get user ID from session (preferred) or fall back to POST
    $session_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $user_id = $session_user_id ? $session_user_id : $post_user_id;

    // If no user ID available, use 0 as fallback
    if (!$user_id) {
        $user_id = 0;
    }

    // Check if impersonating and add impersonator info to values
    if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
        if (!is_array($values)) {
            $values = array();
        }
        $values['impersonated_by'] = $_SESSION['original_user_name'] ?? 'unknown_admin';
    }

    // If session and POST user IDs differ, add that to values_json
    if ($session_user_id && $post_user_id && $session_user_id != $post_user_id) {
        if (!is_array($values)) {
            $values = array();
        }
        $values['_user_id_mismatch'] = array(
            'session_user_id' => $session_user_id,
            'post_user_id' => $post_user_id
        );
    }

    // Get the source file (the file that called this function)
    $backtrace = debug_backtrace();
    $source_file = isset($backtrace[0]['file']) ? basename($backtrace[0]['file']) : 'unknown';

    // Convert values array to JSON (max 500 chars as per schema)
    $values_json = null;
    if ($values && is_array($values)) {
        $json_string = json_encode($values);
        // Truncate to 500 characters if needed
        if (strlen($json_string) > 500) {
            $json_string = substr($json_string, 0, 497) . '...';
        }
        $values_json = $json_string;
    }

    // Truncate freetext to 500 characters if needed
    if ($freetext && strlen($freetext) > 500) {
        $freetext = substr($freetext, 0, 497) . '...';
    }

    // Convert success boolean to tinyint (0 or 1)
    $success_int = $success ? 1 : 0;

    // Prepare and execute the INSERT query
    $query = "INSERT INTO activity_log (timestamp, source_file, action, values_json, success, freetext, user)
              VALUES (NOW(6), ?, ?, ?, ?, ?, ?)";
    $statement = $mysqli->prepare($query);

    if ($statement === false) {
        // Failed to prepare statement - send email alert
        send_activity_log_failure_email(
            'Failed to prepare SQL statement',
            $action,
            $values,
            $mysqli->error,
            $source_file
        );
        return false;
    }

    $bind_result = $statement->bind_param('ssssss',
        $source_file,
        $action,
        $values_json,
        $success_int,
        $freetext,
        $user_id
    );

    if ($bind_result === false) {
        // Failed to bind parameters - send email alert
        send_activity_log_failure_email(
            'Failed to bind parameters',
            $action,
            $values,
            $statement->error,
            $source_file
        );
        $statement->close();
        return false;
    }

    $execute_result = $statement->execute();

    if ($execute_result === false) {
        // Failed to execute - send email alert
        send_activity_log_failure_email(
            'Failed to execute INSERT',
            $action,
            $values,
            $statement->error,
            $source_file
        );
        $statement->close();
        return false;
    }

    $statement->close();
    return true;
}

/**
 * Filter sensitive keys from an array before logging/emailing
 *
 * @param array $data The data to filter
 * @return array The filtered data with sensitive values replaced
 */
function filter_sensitive_data($data) {
    $sensitive_keys = array('password', 'user_password', 'pass', 'pwd', 'token', 'csrf_token', 'secret', 'api_key', 'credit_card', 'cc_number');
    $filtered = array();
    foreach ($data as $key => $value) {
        $lower_key = strtolower($key);
        $is_sensitive = false;
        foreach ($sensitive_keys as $sensitive) {
            if (strpos($lower_key, $sensitive) !== false) {
                $is_sensitive = true;
                break;
            }
        }
        if ($is_sensitive) {
            $filtered[$key] = '[REDACTED]';
        } else if (is_array($value)) {
            $filtered[$key] = filter_sensitive_data($value);
        } else {
            $filtered[$key] = $value;
        }
    }
    return $filtered;
}

/**
 * Send email alert when activity logging fails
 *
 * Rate limited to 1 email per 5 minutes to prevent email flooding
 * when there are persistent database issues.
 *
 * @param string $failure_reason Why the logging failed
 * @param string $action The action that was being logged
 * @param array|null $values The values that were being logged
 * @param string $db_error The database error message
 * @param string $source_file The file that attempted to log
 */
function send_activity_log_failure_email($failure_reason, $action, $values, $db_error, $source_file) {
    // Rate limit: only send 1 email per 5 minutes
    $rate_limit_file = sys_get_temp_dir() . '/t212_activity_log_email_' . md5(__DIR__) . '.lock';
    $rate_limit_seconds = 300; // 5 minutes

    if (file_exists($rate_limit_file)) {
        $last_sent = filemtime($rate_limit_file);
        if (time() - $last_sent < $rate_limit_seconds) {
            // Rate limited - log to error_log instead
            error_log("Activity log failure (email rate limited): $failure_reason - $action - $db_error");
            return;
        }
    }

    // Update rate limit file
    touch($rate_limit_file);

    // Load PHPMailer and config
    try {
        require_once(__DIR__ . '/../login/config/config.php');
        require_once(__DIR__ . '/../login/libraries/PHPMailer.php');
    } catch (Exception $e) {
        error_log("Failed to load PHPMailer for activity log failure email: " . $e->getMessage());
        return;
    }

    $mail = new PHPMailer;

    if (EMAIL_USE_SMTP) {
        $mail->IsSMTP();
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;
        if (defined('EMAIL_SMTP_ENCRYPTION')) {
            $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
        }
        $mail->Host = EMAIL_SMTP_HOST;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->Port = EMAIL_SMTP_PORT;
    } else {
        $mail->IsMail();
    }

    $mail->From = "donotreply@t212.org";
    $mail->FromName = "Troop 212 Website - Activity Log Error";
    $mail->AddAddress("t212webmaster@gmail.com");

    $mail->Subject = "ALERT: Activity Log Failure - " . $action;

    // Build detailed email body
    $body = "Activity Logging Failure Alert\n";
    $body .= "=" . str_repeat("=", 50) . "\n\n";
    $body .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $body .= "Failure Reason: " . $failure_reason . "\n";
    $body .= "Database Error: " . $db_error . "\n\n";

    $body .= "ACTIVITY DETAILS\n";
    $body .= "-" . str_repeat("-", 50) . "\n";
    $body .= "Source File: " . $source_file . "\n";
    $body .= "Action: " . $action . "\n";
    $body .= "Values: " . ($values ? json_encode($values, JSON_PRETTY_PRINT) : 'null') . "\n\n";

    $body .= "REQUEST DETAILS\n";
    $body .= "-" . str_repeat("-", 50) . "\n";
    $body .= "Request URI: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A') . "\n";
    $body .= "Request Method: " . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A') . "\n";
    $body .= "Remote IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A') . "\n";
    $body .= "User Agent: " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A') . "\n\n";

    $body .= "POST DATA\n";
    $body .= "-" . str_repeat("-", 50) . "\n";
    $body .= print_r(filter_sensitive_data($_POST), true) . "\n\n";

    $body .= "SESSION DATA\n";
    $body .= "-" . str_repeat("-", 50) . "\n";
    if (session_status() === PHP_SESSION_ACTIVE) {
        $body .= print_r(filter_sensitive_data($_SESSION), true) . "\n";
    } else {
        $body .= "Session not active\n";
    }

    $body .= "\n" . str_repeat("=", 50) . "\n";
    $body .= "This is an automated alert. Please investigate the activity_log table and database connectivity.\n";

    $mail->Body = $body;

    if (!$mail->Send()) {
        error_log("Failed to send activity log failure email: " . $mail->ErrorInfo);
    } else {
        error_log("Activity log failure email sent to t212webmaster@gmail.com");
    }
}
