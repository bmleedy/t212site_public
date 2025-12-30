<?php
// Diagnostic test for getattendancedata.php issues
// Access this directly in browser: http://yoursite/api/test_attendance_debug.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Attendance Data API Diagnostics</h2>";

// Test 1: Check if credentials.php exists and loads
echo "<h3>Test 1: Loading credentials.php</h3>";
try {
    require_once(__DIR__ . '/../includes/credentials.php');
    echo "✓ credentials.php loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ ERROR loading credentials.php: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Check if Credentials class can be instantiated
echo "<h3>Test 2: Creating Credentials instance</h3>";
try {
    $creds = Credentials::getInstance();
    echo "✓ Credentials instance created<br>";
} catch (Exception $e) {
    echo "✗ ERROR creating Credentials instance: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check if credentials can be retrieved
echo "<h3>Test 3: Reading database credentials</h3>";
try {
    $user = $creds->getDatabaseUser();
    $password = $creds->getDatabasePassword();
    $database = $creds->getDatabaseName();
    $host = $creds->getDatabaseHost();

    echo "✓ Database credentials retrieved:<br>";
    echo "&nbsp;&nbsp;Host: " . htmlspecialchars($host) . "<br>";
    echo "&nbsp;&nbsp;User: " . htmlspecialchars($user) . "<br>";
    echo "&nbsp;&nbsp;Database: " . htmlspecialchars($database) . "<br>";
    echo "&nbsp;&nbsp;Password: " . (empty($password) ? "EMPTY/MISSING" : "[" . strlen($password) . " characters]") . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR reading credentials: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Test database connection
echo "<h3>Test 4: Testing database connection</h3>";
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    echo "✗ DATABASE CONNECTION FAILED<br>";
    echo "&nbsp;&nbsp;Error Code: " . $mysqli->connect_errno . "<br>";
    echo "&nbsp;&nbsp;Error Message: " . htmlspecialchars($mysqli->connect_error) . "<br>";
    die();
} else {
    echo "✓ Database connection successful<br>";
    echo "&nbsp;&nbsp;MySQL Version: " . $mysqli->server_info . "<br>";
}

// Test 5: Check if attendance_daily table exists
echo "<h3>Test 5: Checking attendance_daily table</h3>";
$result = $mysqli->query("SHOW TABLES LIKE 'attendance_daily'");
if ($result && $result->num_rows > 0) {
    echo "✓ attendance_daily table exists<br>";
} else {
    echo "✗ attendance_daily table NOT FOUND<br>";
    echo "&nbsp;&nbsp;This table is required for the API to work<br>";
}

// Test 6: Try a sample query
echo "<h3>Test 6: Testing sample query</h3>";
$test_start = '2024-01-01';
$test_end = '2024-12-31';

$query = "SELECT user_id, date, was_present
          FROM attendance_daily
          WHERE date >= '" . $mysqli->real_escape_string($test_start) . "'
          AND date <= '" . $mysqli->real_escape_string($test_end) . "'
          LIMIT 5";

$results = $mysqli->query($query);

if ($results) {
    echo "✓ Sample query executed successfully<br>";
    echo "&nbsp;&nbsp;Found " . $results->num_rows . " sample records<br>";

    if ($results->num_rows > 0) {
        echo "&nbsp;&nbsp;Sample data:<br>";
        while ($row = $results->fetch_assoc()) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;User: " . htmlspecialchars($row['user_id']) .
                 ", Date: " . htmlspecialchars($row['date']) .
                 ", Present: " . ($row['was_present'] ? 'Yes' : 'No') . "<br>";
        }
    }
} else {
    echo "✗ QUERY FAILED<br>";
    echo "&nbsp;&nbsp;Error: " . htmlspecialchars($mysqli->error) . "<br>";
}

// Test 7: Simulate the actual API call
echo "<h3>Test 7: Simulating actual API request</h3>";
$_POST['start_date'] = '2024-01-01';
$_POST['end_date'] = '2024-12-31';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

if (!$start_date || !$end_date) {
    echo "✗ Missing date parameters<br>";
} else {
    echo "✓ Date parameters present<br>";
    echo "&nbsp;&nbsp;Start: " . htmlspecialchars($start_date) . "<br>";
    echo "&nbsp;&nbsp;End: " . htmlspecialchars($end_date) . "<br>";

    $attendance = array();

    $query = "SELECT user_id, date, was_present
              FROM attendance_daily
              WHERE date >= '" . $mysqli->real_escape_string($start_date) . "'
              AND date <= '" . $mysqli->real_escape_string($end_date) . "'";

    $results = $mysqli->query($query);

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $key = $row['user_id'] . '-' . $row['date'];
            $attendance[$key] = (bool)$row['was_present'];
        }

        $returnMsg = array(
            'status' => 'Success',
            'attendance' => $attendance,
            'record_count' => count($attendance)
        );

        echo "✓ API simulation successful<br>";
        echo "&nbsp;&nbsp;JSON Response:<br>";
        echo "<pre>" . json_encode($returnMsg, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "✗ API simulation failed<br>";
        echo "&nbsp;&nbsp;Error: " . htmlspecialchars($mysqli->error) . "<br>";
    }
}

echo "<h3>All Tests Complete</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, the issue may be with the AJAX request headers or session</li>";
echo "<li>If any test fails, that indicates the root cause of the 500 error</li>";
echo "<li>Check your browser's Network tab to see the actual error response from getattendancedata.php</li>";
echo "</ul>";

$mysqli->close();
?>
