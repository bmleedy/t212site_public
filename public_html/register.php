<!DOCTYPE html>

<?php require "includes/header.html"; ?>
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
include("login/views/register.php");
?>

    </div>
  </div>
</div>

<?php require "includes/footer.html"; ?>
