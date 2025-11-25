<?php
// This file handles asynchronous page view counting
// It should be called via AJAX after the main page has loaded

// Include database configuration
require_once(dirname(__DIR__) . '/login/config/config.php');

// Get the page URL from the request
$pageUrl = isset($_POST['page_url']) ? $_POST['page_url'] : '';

// Validate the page URL (basic validation)
if (empty($pageUrl)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing page_url parameter']);
    exit;
}

// Connect to the database and update the counter
try {
    $db = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, check if the page_counters table exists, if not create it
    $tableCheck = $db->query("SHOW TABLES LIKE 'page_counters'");
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTable = $db->prepare("
            CREATE TABLE page_counters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_url VARCHAR(255) NOT NULL UNIQUE,
                count INT NOT NULL DEFAULT 1,
                first_visit DATETIME NOT NULL,
                last_visit DATETIME NOT NULL
            )
        ");
        $createTable->execute();
    }

    // Try to update the counter for this page
    $updateCounter = $db->prepare("
        INSERT INTO page_counters (page_url, count, first_visit, last_visit)
        VALUES (:page_url, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            count = count + 1,
            last_visit = NOW()
    ");
    $updateCounter->bindParam(':page_url', $pageUrl, PDO::PARAM_STR);
    $updateCounter->execute();

    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Return error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error on page counter: ' . $e->getMessage()]);

    // Log the error (optional)
    error_log('Page counter error: ' . $e->getMessage());
}
