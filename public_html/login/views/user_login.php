<?php
/**
 * Login form view
 * Displays the login form with error/success messages
 */

// Helper function to display feedback messages with proper escaping
function displayFeedback($object): void {
    if (!isset($object)) {
        return;
    }

    if (!empty($object->errors)) {
        foreach ($object->errors as $error) {
            echo '<div class="alert alert-error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    if (!empty($object->messages)) {
        foreach ($object->messages as $message) {
            echo '<div class="alert alert-success">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}

// Display feedback from login and registration objects
displayFeedback($login ?? null);
displayFeedback($registration ?? null);

// Generate CSRF token if not exists (session should already be started by inc_login.php)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Build form action URL safely - use only PHP_SELF with proper escaping
// Note: We preserve query string for redirect-after-login functionality
$formAction = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
    $formAction .= '?' . $_SERVER['QUERY_STRING'];
}
?>

<form method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" name="loginform">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <h5>Please login</h5>
    <label for="user_name"><?php echo WORDING_USERNAME; ?></label>
    <input id="user_name" type="text" name="user_name" required />
    <label for="user_password"><?php echo WORDING_PASSWORD; ?></label>
    <input id="user_password" type="password" name="user_password" autocomplete="current-password" required />
    <input type="submit" name="login" value="<?php echo WORDING_LOGIN; ?>" />
</form>

<p><a href="password_reset.php"><?php echo WORDING_FORGOT_MY_PASSWORD; ?></a></p>

<div class="login-info">
    <p>This section is for Scouts and Adults of Troop 212 only. If you are a Scout or Adult of Troop 212
    and do not have access, you can gain access one of two ways:</p>
    <ol>
        <li>If a family member is already a member of T212.org, please have them login, click on My Profile, then click the Add Family Member button.</li>
        <li>If you are the first in your family to register, please send an email to Jessica or attend the next Scout Meeting to get setup.</li>
    </ol>
</div>
