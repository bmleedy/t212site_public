<!DOCTYPE html>
<?php
require_once(__DIR__ . '/api/connect.php');
require "includes/header.html";
?>
<br />

<div class='row'>
  <?php require "includes/sidebar.html"; ?>
  <div class="large-9 columns">
    <div class="panel">

<h3>Troop Events and Outings</h3>
<p>As an active troop, we do at least one adventure/outing per month and usually quite a lot more. Here are some of the most recent outings our troop has done.</p>

<table>
  <thead>
    <tr>
      <th>Event</th>
      <th>Location</th>
      <th>Start</th>
      <th>End</th>
    </tr>
  </thead>
  <tbody>
<?php
// Get events from the past 2 months and all future events
// Using prepared statement for consistency with codebase standards
$stmt = $mysqli->prepare("SELECT name, location, startdate, enddate
          FROM events
          WHERE startdate >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
          ORDER BY startdate ASC");

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $name = htmlspecialchars($row['name']);
            $location = htmlspecialchars($row['location']);
            $startdate = date('D, M j, Y', strtotime($row['startdate']));
            $enddate = date('D, M j, Y', strtotime($row['enddate']));
            echo "    <tr>\n";
            echo "      <td>{$name}</td>\n";
            echo "      <td>{$location}</td>\n";
            echo "      <td>{$startdate}</td>\n";
            echo "      <td>{$enddate}</td>\n";
            echo "    </tr>\n";
        }
    } else {
        echo "    <tr><td colspan='4'>No upcoming events at this time.</td></tr>\n";
    }
    $stmt->close();
} else {
    echo "    <tr><td colspan='4'>Unable to load events. Please try again later.</td></tr>\n";
}
?>
  </tbody>
</table>

<p><em>If you wish to sign up for an outing, please log in!</em></p>

    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
