<?php
// Debug version of updateattendance.php with error logging
// Replace the original temporarily to see what's failing

// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/updateattendance_errors.log');
error_reporting(E_ALL);

// Log the request
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
file_put_contents(__DIR__ . '/updateattendance_debug.log',
    date('Y-m-d H:i:s') . " - Request received\n" .
    "POST data: " . print_r($_POST, true) . "\n" .
    "Headers: " . print_r($headers, true) . "\n\n",
    FILE_APPEND
);

// Check AJAX header
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - AJAX header OK\n",
        FILE_APPEND
    );
} else {
    echo "Not sure what you are after, but it ain't here.";
    die();
}

header('Content-Type: application/json');

// Try to connect
file_put_contents(__DIR__ . '/updateattendance_debug.log',
    date('Y-m-d H:i:s') . " - About to require connect.php\n",
    FILE_APPEND
);

try {
    require 'connect.php';
    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - connect.php loaded successfully\n",
        FILE_APPEND
    );
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - ERROR in connect.php: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    echo json_encode(['status' => 'Error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    die();
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$was_present = isset($_POST['was_present']) ? $_POST['was_present'] : null;
$date = isset($_POST['date']) ? $_POST['date'] : null;

file_put_contents(__DIR__ . '/updateattendance_debug.log',
    date('Y-m-d H:i:s') . " - Parsed: user_id=$user_id, was_present=$was_present, date=$date\n",
    FILE_APPEND
);

// Validate required parameters
if (!$user_id || $was_present === null) {
    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - Validation failed\n",
        FILE_APPEND
    );
    echo json_encode([
        'status' => 'Error',
        'message' => 'user_id and was_present are required'
    ]);
    die();
}

// Convert was_present to boolean
$was_present = ($was_present === 'true' || $was_present === '1' || $was_present === 1) ? 1 : 0;

// If no date provided, use current date in Pacific Time Zone
if (!$date) {
    $timezone = new DateTimeZone('America/Los_Angeles');
    $datetime = new DateTime('now', $timezone);
    $date = $datetime->format('Y-m-d');
}

// Check if attendance record already exists for this user and date
$query = "SELECT id FROM attendance_daily
          WHERE user_id = " . $user_id . "
          AND date = '" . $mysqli->real_escape_string($date) . "'";

file_put_contents(__DIR__ . '/updateattendance_debug.log',
    date('Y-m-d H:i:s') . " - Executing query: $query\n",
    FILE_APPEND
);

$results = $mysqli->query($query);

if ($results && $results->num_rows > 0) {
    // Record exists - UPDATE it
    $row = $results->fetch_assoc();
    $record_id = $row['id'];

    $updateQuery = "UPDATE attendance_daily
                    SET was_present = " . $was_present . "
                    WHERE id = " . $record_id;

    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - Updating: $updateQuery\n",
        FILE_APPEND
    );

    if ($mysqli->query($updateQuery)) {
        file_put_contents(__DIR__ . '/updateattendance_debug.log',
            date('Y-m-d H:i:s') . " - Update successful\n",
            FILE_APPEND
        );
        echo json_encode([
            'status' => 'Success',
            'message' => 'Attendance updated',
            'action' => 'updated',
            'user_id' => $user_id,
            'date' => $date,
            'was_present' => (bool)$was_present
        ]);
    } else {
        file_put_contents(__DIR__ . '/updateattendance_debug.log',
            date('Y-m-d H:i:s') . " - Update failed: " . $mysqli->error . "\n",
            FILE_APPEND
        );
        echo json_encode([
            'status' => 'Error',
            'message' => 'Failed to update attendance: ' . $mysqli->error
        ]);
    }
} else {
    // Record doesn't exist - INSERT it
    $insertQuery = "INSERT INTO attendance_daily (user_id, date, was_present)
                    VALUES (" . $user_id . ", '" . $mysqli->real_escape_string($date) . "', " . $was_present . ")";

    file_put_contents(__DIR__ . '/updateattendance_debug.log',
        date('Y-m-d H:i:s') . " - Inserting: $insertQuery\n",
        FILE_APPEND
    );

    if ($mysqli->query($insertQuery)) {
        file_put_contents(__DIR__ . '/updateattendance_debug.log',
            date('Y-m-d H:i:s') . " - Insert successful\n",
            FILE_APPEND
        );
        echo json_encode([
            'status' => 'Success',
            'message' => 'Attendance recorded',
            'action' => 'created',
            'user_id' => $user_id,
            'date' => $date,
            'was_present' => (bool)$was_present
        ]);
    } else {
        file_put_contents(__DIR__ . '/updateattendance_debug.log',
            date('Y-m-d H:i:s') . " - Insert failed: " . $mysqli->error . "\n",
            FILE_APPEND
        );
        echo json_encode([
            'status' => 'Error',
            'message' => 'Failed to record attendance: ' . $mysqli->error
        ]);
    }
}

die();
?>
