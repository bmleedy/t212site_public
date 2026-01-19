<?php include('_header.php'); ?>

<?php
// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" name="loginform">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

    <label for="user_name"><?php echo WORDING_USERNAME; ?></label>
    <input id="user_name" type="text" name="user_name" autocomplete="username" required>

    <label for="user_password"><?php echo WORDING_PASSWORD; ?></label>
    <input id="user_password" type="password" name="user_password" autocomplete="current-password" required>

    <input type="checkbox" id="user_rememberme" name="user_rememberme" value="1">
    <label for="user_rememberme"><?php echo WORDING_REMEMBER_ME; ?></label>

    <input type="submit" name="login" value="<?php echo WORDING_LOGIN; ?>">
</form>

<p>
    <a href="register.php"><?php echo WORDING_REGISTER_NEW_ACCOUNT; ?></a>
    |
    <a href="password_reset.php"><?php echo WORDING_FORGOT_MY_PASSWORD; ?></a>
</p>

<?php include('_footer.php'); ?>
