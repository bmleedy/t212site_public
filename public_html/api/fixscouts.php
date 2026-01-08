<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication and require admin permissions
$current_user_id = require_authentication();
require_permission(['wm', 'sa']); // Only webmaster or scoutmaster can run this

header('Content-Type: application/json');
require 'connect.php';

$query = "SELECT id, position FROM scout_info WHERE position<>''";
$results = $mysqli->query($query);

$updated_count = 0;

while ($row = $results->fetch_object()) {
    $id = $row->id;
    $label = $row->position;

    if ($label) {
        // Use prepared statement for lookup
        $query2 = "SELECT id FROM leadership WHERE label=?";
        $stmt2 = $mysqli->prepare($query2);
        $stmt2->bind_param("s", $label);
        $stmt2->execute();
        $results2 = $stmt2->get_result();
        $row2 = $results2->fetch_object();
        $stmt2->close();

        if ($row2) {
            $key_id = $row2->id;

            // Update with prepared statement
            $query3 = "UPDATE scout_info SET position_id=? WHERE id=?";
            $stmt = $mysqli->prepare($query3);
            $stmt->bind_param("ii", $key_id, $id);
            $stmt->execute();
            $stmt->close();

            $updated_count++;
        }
    }
}

echo json_encode([
    'success' => true,
    'updated_count' => $updated_count,
    'message' => "Updated {$updated_count} scout records"
]);
?>
