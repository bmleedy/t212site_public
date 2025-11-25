<?php
// show potential errors / feedback (from login object)
if (isset($login)) {
    if ($login->errors) {
				echo "<h4>Errors</h4><p>";
        foreach ($login->errors as $error) {
            echo $error;
        }
				echo "</p>";
    }
    if ($login->messages) {
				echo "<p>";
        foreach ($login->messages as $message) {
            echo $message;
        }
				echo "</p>";
    }
}
?>

<?php
// show potential errors / feedback (from registration object)
if (isset($registration)) {
    if ($registration->errors) {
				echo "<h4>Errors</h4><p>";
        foreach ($registration->errors as $error) {
            echo $error;
        }
				echo "</p>";
    }
    if ($registration->messages) {
				echo "<p>";
				foreach ($registration->messages as $message) {
            echo $message;
        }
				echo "</p>";
    }
}
?>
