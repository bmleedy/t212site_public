<?php
/**
 * Input Validation Helper for API Files
 *
 * Provides centralized input validation and sanitization functions.
 */

/**
 * Validate and return an integer from POST data
 *
 * @param string $key The POST key to validate
 * @param bool $required Whether the field is required
 * @param int|null $default Default value if not provided and not required
 * @return int|null The validated integer or null if not required and not provided
 */
function validate_int_post($key, $required = true, $default = null) {
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);

    if ($value === false || $value === null) {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid or missing required field: {$key}"]);
            die();
        }
        return $default;
    }

    return $value;
}

/**
 * Validate and return an integer from GET data
 *
 * @param string $key The GET key to validate
 * @param bool $required Whether the field is required
 * @param int|null $default Default value if not provided and not required
 * @return int|null The validated integer or null if not required and not provided
 */
function validate_int_get($key, $required = true, $default = null) {
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

    if ($value === false || $value === null) {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid or missing required field: {$key}"]);
            die();
        }
        return $default;
    }

    return $value;
}

/**
 * Validate and sanitize string from POST data
 *
 * @param string $key The POST key to validate
 * @param bool $required Whether the field is required
 * @param string|null $default Default value if not provided and not required
 * @param int $max_length Maximum allowed length (0 = no limit)
 * @return string|null The sanitized string or null if not required and not provided
 */
function validate_string_post($key, $required = true, $default = null, $max_length = 0) {
    $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);

    if ($value === false || $value === null || $value === '') {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid or missing required field: {$key}"]);
            die();
        }
        return $default;
    }

    if ($max_length > 0 && strlen($value) > $max_length) {
        http_response_code(400);
        echo json_encode(['error' => "Field {$key} exceeds maximum length of {$max_length}"]);
        die();
    }

    return $value;
}

/**
 * Validate email from POST data
 *
 * @param string $key The POST key to validate
 * @param bool $required Whether the field is required
 * @return string|null The validated email or null if not required and not provided
 */
function validate_email_post($key, $required = true) {
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_EMAIL);

    if ($value === false || $value === null) {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid or missing email: {$key}"]);
            die();
        }
        return null;
    }

    return $value;
}

/**
 * Validate date from POST data (YYYY-MM-DD format)
 *
 * @param string $key The POST key to validate
 * @param bool $required Whether the field is required
 * @return string|null The validated date or null if not required and not provided
 */
function validate_date_post($key, $required = true) {
    $value = $_POST[$key] ?? null;

    if ($value === null || $value === '') {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required date field: {$key}"]);
            die();
        }
        return null;
    }

    // Validate date format
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid date format for {$key}. Expected YYYY-MM-DD"]);
        die();
    }

    return $value;
}

/**
 * Validate datetime from POST data (YYYY-MM-DD HH:MM:SS format)
 *
 * @param string $key The POST key to validate
 * @param bool $required Whether the field is required
 * @return string|null The validated datetime or null if not required and not provided
 */
function validate_datetime_post($key, $required = true) {
    $value = $_POST[$key] ?? null;

    if ($value === null || $value === '') {
        if ($required) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required datetime field: {$key}"]);
            die();
        }
        return null;
    }

    // Validate datetime format
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$date || $date->format('Y-m-d H:i:s') !== $value) {
        // Also try datetime-local format (without seconds)
        $date = DateTime::createFromFormat('Y-m-d H:i', $value);
        if (!$date || $date->format('Y-m-d H:i') !== $value) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid datetime format for {$key}"]);
            die();
        }
        return $date->format('Y-m-d H:i:s');
    }

    return $value;
}

/**
 * Escape HTML output to prevent XSS
 *
 * @param string $text The text to escape
 * @return string The escaped text
 */
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate boolean from POST data
 *
 * @param string $key The POST key to validate
 * @param bool $default Default value if not provided
 * @return bool The boolean value
 */
function validate_bool_post($key, $default = false) {
    if (!isset($_POST[$key])) {
        return $default;
    }

    $value = $_POST[$key];

    // Handle various boolean representations
    if ($value === true || $value === 1 || $value === '1' || strtolower($value) === 'true' || strtolower($value) === 'on') {
        return true;
    }

    return false;
}

/**
 * Sanitize a value for safe database storage using mysqli
 *
 * @param mysqli $mysqli The database connection
 * @param mixed $value The value to sanitize
 * @return string The sanitized value
 */
function sanitize_for_db($mysqli, $value) {
    return $mysqli->real_escape_string($value);
}
?>
