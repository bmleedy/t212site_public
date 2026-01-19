<?php include('_header.php'); ?>

<?php if ($login->passwordResetLinkIsValid()) { ?>
<form method="post" action="password_reset.php" name="new_password_form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($_GET['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="user_password_reset_hash" value="<?php echo htmlspecialchars($_GET['verification_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />

    <label for="user_password_new"><?php echo WORDING_NEW_PASSWORD; ?></label>
    <input id="user_password_new" type="password" name="user_password_new" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must be at least 8 characters with uppercase, lowercase, and a number" required autocomplete="new-password" />

    <label for="user_password_repeat"><?php echo WORDING_NEW_PASSWORD_REPEAT; ?></label>
    <input id="user_password_repeat" type="password" name="user_password_repeat" minlength="8" required autocomplete="new-password" />
    <input type="submit" name="submit_new_password" value="<?php echo WORDING_SUBMIT_NEW_PASSWORD; ?>" />
</form>
<!-- no data from a password-reset-mail has been provided, so we simply show the request-a-password-reset form -->
<?php } else { ?>
<form method="post" action="password_reset.php" name="password_reset_form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
    <label for="user_name"><?php echo WORDING_REQUEST_PASSWORD_RESET; ?></label>
    <input id="user_name" type="text" name="user_name" required />
    <div class="alert-box warning">If you do not remember your username, anyone with T212 access can retrieve it for you by logging in to this site and locating your entry in the Directory. Your username is located directly below your email address.</div>
    <input type="submit" name="request_password_reset" value="<?php echo WORDING_RESET_PASSWORD; ?>" />
</form>
<?php } ?>

<a href="Members.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
