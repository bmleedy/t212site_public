<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require "includes/authHeader.php";
$user_id = $_SESSION['user_id'];
$family_id = isset($_GET['familyID']) ? (int)$_GET['familyID'] : null;
$user_first = $_SESSION['user_first'];

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

// Validate family relationship - user can only add members to their own family
if ($family_id !== null) {
    require_once('includes/credentials.php');
    $creds = Credentials::getInstance();
    $mysqli = new mysqli(
        $creds->getDatabaseHost(),
        $creds->getDatabaseUser(),
        $creds->getDatabasePassword(),
        $creds->getDatabaseName()
    );

    if ($mysqli->connect_error) {
        die('Database connection failed');
    }

    // Check if the logged-in user belongs to the specified family
    $stmt = $mysqli->prepare("SELECT family_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_family = $result->fetch_assoc();
    $stmt->close();
    $mysqli->close();

    if (!$user_family || (int)$user_family['family_id'] !== $family_id) {
        die('You do not have permission to add members to this family.');
    }
}
?>

<br />

<div class='row'>
  <?php require "includes/sidebar.html"; ?>
  <div class="large-9 columns">
    <div class="panel">
<?php

// include the config
require_once('login/config/config.php');

// include the to-be-used language, english by default. feel free to translate your project and include something else
require_once('login/translations/en.php');

// include the PHPMailer library
require_once('login/libraries/PHPMailer.php');

// load the registration class
require_once('login/classes/Registration.php');

// create the registration object. when this object is created, it will do all registration stuff automatically
// so this single line handles the entire registration process.
$registration = new Registration();
// showing the register view (with the registration form, and messages/errors)
include("login/views/addfamilymember.php");
?>

    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
