<?php
// Test file to diagnose updateattendance.php AJAX issues
// This simulates the exact AJAX call and shows detailed debugging info

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Update Attendance AJAX Diagnostics</h2>";

// Test 1: Check AJAX header requirement
echo "<h3>Test 1: AJAX Header Check</h3>";
echo "HTTP_X_REQUESTED_WITH header: ";
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo "'" . htmlspecialchars($_SERVER['HTTP_X_REQUESTED_WITH']) . "'<br>";
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo "✓ AJAX header is correct<br>";
    } else {
        echo "✗ AJAX header value is wrong (should be 'XMLHttpRequest')<br>";
    }
} else {
    echo "<strong>NOT SET</strong><br>";
    echo "⚠ This is likely why direct browser access fails<br>";
    echo "⚠ But AJAX calls from JavaScript should set this automatically<br>";
}

// Test 2: Check if this is a POST request
echo "<h3>Test 2: Request Method</h3>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✓ Using POST method<br>";
} else {
    echo "⚠ Not a POST request (AJAX calls use POST)<br>";
}

// Test 3: Check POST parameters
echo "<h3>Test 3: POST Parameters</h3>";
echo "POST data received:<br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

if (empty($_POST)) {
    echo "⚠ No POST data - setting test values...<br>";
    $_POST['user_id'] = 1;
    $_POST['was_present'] = 1;
    $_POST['date'] = '2024-12-30';
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$was_present = isset($_POST['was_present']) ? $_POST['was_present'] : null;
$date = isset($_POST['date']) ? $_POST['date'] : null;

echo "Parsed parameters:<br>";
echo "&nbsp;&nbsp;user_id: " . ($user_id ? $user_id : "NULL/MISSING") . "<br>";
echo "&nbsp;&nbsp;was_present: " . ($was_present !== null ? htmlspecialchars($was_present) : "NULL/MISSING") . "<br>";
echo "&nbsp;&nbsp;date: " . ($date ? htmlspecialchars($date) : "NULL/MISSING") . "<br>";

// Test 4: Check parameter validation (same as updateattendance.php)
echo "<h3>Test 4: Parameter Validation</h3>";
if (!$user_id || $was_present === null) {
    echo "✗ VALIDATION FAILED - This is what updateattendance.php would return:<br>";
    echo "<pre>" . json_encode([
        'status' => 'Error',
        'message' => 'user_id and was_present are required'
    ], JSON_PRETTY_PRINT) . "</pre>";
    echo "<strong>This is likely your issue!</strong><br>";
    echo "Check that the AJAX call is sending both user_id and was_present.<br>";
} else {
    echo "✓ All required parameters present<br>";
}

// Test 5: Check was_present conversion
echo "<h3>Test 5: was_present Conversion</h3>";
$was_present_original = $was_present;
$was_present = ($was_present === 'true' || $was_present === '1' || $was_present === 1) ? 1 : 0;
echo "Original value: " . htmlspecialchars(var_export($was_present_original, true)) . "<br>";
echo "Converted value: " . $was_present . " (type: " . gettype($was_present) . ")<br>";

// Test 6: Test database connection
echo "<h3>Test 6: Database Connection</h3>";
try {
    require_once(__DIR__ . '/../includes/credentials.php');
    $creds = Credentials::getInstance();
    $user = $creds->getDatabaseUser();
    $password = $creds->getDatabasePassword();
    $database = $creds->getDatabaseName();
    $host = $creds->getDatabaseHost();

    $mysqli = new mysqli($host, $user, $password, $database);

    if ($mysqli->connect_error) {
        echo "✗ Connection failed: " . htmlspecialchars($mysqli->connect_error) . "<br>";
        die();
    } else {
        echo "✓ Database connected<br>";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . htmlspecialchars($e->getMessage()) . "<br>";
    die();
}

// Test 7: Test the actual query logic
if ($user_id && $was_present !== null && $date) {
    echo "<h3>Test 7: Testing Actual Query Logic</h3>";

    // Check if record exists
    $query = "SELECT id FROM attendance_daily
              WHERE user_id = " . $user_id . "
              AND date = '" . $mysqli->real_escape_string($date) . "'";

    echo "Check query: <pre>" . htmlspecialchars($query) . "</pre>";

    $results = $mysqli->query($query);

    if ($results === false) {
        echo "✗ Query failed: " . htmlspecialchars($mysqli->error) . "<br>";
    } elseif ($results->num_rows > 0) {
        echo "✓ Record exists - would UPDATE<br>";
        $row = $results->fetch_assoc();
        $record_id = $row['id'];

        $updateQuery = "UPDATE attendance_daily
                        SET was_present = " . $was_present . "
                        WHERE id = " . $record_id;

        echo "Update query: <pre>" . htmlspecialchars($updateQuery) . "</pre>";

        if ($mysqli->query($updateQuery)) {
            echo "✓ UPDATE successful<br>";
        } else {
            echo "✗ UPDATE failed: " . htmlspecialchars($mysqli->error) . "<br>";
        }
    } else {
        echo "✓ Record doesn't exist - would INSERT<br>";

        $insertQuery = "INSERT INTO attendance_daily (user_id, date, was_present)
                        VALUES (" . $user_id . ", '" . $mysqli->real_escape_string($date) . "', " . $was_present . ")";

        echo "Insert query: <pre>" . htmlspecialchars($insertQuery) . "</pre>";

        if ($mysqli->query($insertQuery)) {
            echo "✓ INSERT successful<br>";
            echo "&nbsp;&nbsp;New record ID: " . $mysqli->insert_id . "<br>";
        } else {
            echo "✗ INSERT failed: " . htmlspecialchars($mysqli->error) . "<br>";
        }
    }
}

echo "<h3>Summary</h3>";
echo "<p><strong>Common causes of 500 errors:</strong></p>";
echo "<ul>";
echo "<li>Missing HTTP_X_REQUESTED_WITH header (check browser console)</li>";
echo "<li>Missing user_id or was_present in POST data</li>";
echo "<li>Invalid user_id (not a number or zero)</li>";
echo "<li>Database table structure mismatch</li>";
echo "</ul>";

echo "<h3>Next Step: Test with Real AJAX</h3>";
echo "<button id='testAjax'>Click to Test Real AJAX Call</button>";
echo "<div id='ajaxResult' style='margin-top: 20px; padding: 10px; background: #f0f0f0;'></div>";

echo "<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>";
echo "<script>
$('#testAjax').on('click', function() {
    $('#ajaxResult').html('Sending AJAX request...');

    $.ajax({
        url: 'updateattendance.php',
        type: 'post',
        data: {
            user_id: 1,
            was_present: 1,
            date: '2024-12-30'
        },
        dataType: 'json',
        success: function(response) {
            $('#ajaxResult').html('<strong>Success!</strong><br><pre>' + JSON.stringify(response, null, 2) + '</pre>');
        },
        error: function(xhr, status, error) {
            $('#ajaxResult').html('<strong>Error!</strong><br>' +
                'Status: ' + xhr.status + '<br>' +
                'Response: <pre>' + xhr.responseText + '</pre>');
        }
    });
});
</script>";
?>
