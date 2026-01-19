<?php
/**
 * Page Counter Utility
 *
 * Handles asynchronous page view counting via AJAX.
 * Should be called via AJAX after the main page has loaded.
 */

// Start session if needed for error logging context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the page URL from the request
$pageUrl = isset($_POST['page_url']) ? trim($_POST['page_url']) : '';

// Validate the page URL
if (empty($pageUrl)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Missing page_url parameter']);
    exit;
}

// Limit URL length to prevent abuse
if (strlen($pageUrl) > 255) {
    $pageUrl = substr($pageUrl, 0, 255);
}

// Load credentials and connect using mysqli (consistent with rest of codebase)
try {
    require_once(__DIR__ . '/credentials.php');
    $creds = Credentials::getInstance();

    $mysqli = new mysqli(
        $creds->getDatabaseHost(),
        $creds->getDatabaseUser(),
        $creds->getDatabasePassword(),
        $creds->getDatabaseName()
    );

    if ($mysqli->connect_error) {
        throw new Exception('Connection failed');
    }

    // Update the counter using INSERT ... ON DUPLICATE KEY UPDATE
    // Assumes page_counters table exists (created during setup, not on every request)
    $query = "INSERT INTO page_counters (page_url, count, first_visit, last_visit)
              VALUES (?, 1, NOW(), NOW())
              ON DUPLICATE KEY UPDATE
                  count = count + 1,
                  last_visit = NOW()";

    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
        throw new Exception('Prepare failed');
    }

    $stmt->bind_param('s', $pageUrl);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed');
    }

    $stmt->close();
    $mysqli->close();

    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Log the actual error for debugging
    error_log('Page counter error: ' . $e->getMessage());

    // Return generic error to client (don't leak details)
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update page counter']);
}
