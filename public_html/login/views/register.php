<?php
include('_header.php');

// Generate CSRF token if not exists (session already started by Registration class)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Generate a hidden input field with the CSRF token
 * @return string HTML hidden input element
 */
function csrf_input_register() {
    $token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>

<!-- show registration form, but only if we didn't submit already -->
<?php if (!$registration->registration_successful && !$registration->verification_successful) { ?>
<form method="post" action="register.php" name="registerform">
    <?php echo csrf_input_register(); ?>
    <input type="hidden" name="family_id" value="0" />

			<p>Please fill out this form completely and click "Register". A Site Administrator will be notified of your application
			and will verify your request before approving. You will be notified when your appliation has been processed.</p>
			<p>Site access is reserved for Scouts and Adults affiliated with Troop 212.</p>

	<div class="row">
    <div class="large-12 columns">
      <input type="radio" name="user_type" value="Scout" required ><label>Scout</label>
      <input type="radio" name="user_type" value="Dad" required ><label>Dad</label>
			<input type="radio" name="user_type" value="Mom" required ><label>Mom</label>
			<input type="radio" name="user_type" value="Other" required ><label>Other Adult</label>
    </div>
  </div>
  
	<div class="row">
    <div class="large-6 columns">
      <label>First Name
        <input type="text" id="user_first" name="user_first" required />
      </label>
    </div>
    <div class="large-6 columns">
      <label>Last Name
        <input type="text" id="user_last" name="user_last" required />
      </label>
    </div>
  </div>
  
	<div class="row">
    <div class="large-6 columns">
			<label for="user_name"><?php echo WORDING_REGISTRATION_USERNAME; ?></label>
			<input id="user_name" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />
    </div>
		<div class="large-6 columns">
      <label for="user_email"><br>Email</label>
			<input id="user_email" type="email" name="user_email" required />
    </div>
  </div>
  

	<div class="row">
    <div class="large-6 columns">
      <label for="user_password_new"><?php echo WORDING_REGISTRATION_PASSWORD; ?></label>
			<input id="user_password_new" type="password" name="user_password_new" pattern=".{8,}" required autocomplete="off" title="Password must be at least 8 characters" />
    </div>
    <div class="large-6 columns">
			<label for="user_password_repeat"><?php echo WORDING_REGISTRATION_PASSWORD_REPEAT; ?></label>
			<input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{8,}" required autocomplete="off" title="Password must be at least 8 characters" />
		</div>
	</div>
  
	<img src="login/tools/showCaptcha.php" alt="captcha" />
  <label><?php echo WORDING_REGISTRATION_CAPTCHA; ?></label>
  <input type="text" name="captcha" required />
  <input type="submit" name="register" value="<?php echo WORDING_REGISTER; ?>" />

</form>
<?php } ?>
