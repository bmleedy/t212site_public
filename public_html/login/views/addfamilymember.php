<?php include('_header.php'); 

?>

<!-- show registration form, but only if we didn't submit already -->
<?php if (!$registration->registration_successful && !$registration->verification_successful) { ?>
<form method="post" action="addfamilymember.php" name="registerform">
	<?php echo csrf_input(); ?>
	<p>Hi <?php echo htmlspecialchars($user_first, ENT_QUOTES, 'UTF-8'); ?>, you can use this form to register Scouts or Adults into your family. Any adult registered in your family can approve & pay for scouts in your family. If the new user is not part of your family, please have the appropriate person register them.</p>
	<p>Site access is reserved for Scouts and Adults affiliated with Troop 212.</p>

	<div class="row">
		<div class="large-12 columns">
			<input type="radio" name="user_type" id="user_type_scout" value="Scout" required ><label for="user_type_scout">Scout</label>
			<input type="radio" name="user_type" id="user_type_dad" value="Dad" required ><label for="user_type_dad">Dad</label>
			<input type="radio" name="user_type" id="user_type_mom" value="Mom" required ><label for="user_type_mom">Mom</label>
			<input type="radio" name="user_type" id="user_type_other" value="Other" required ><label for="user_type_other">Other Adult</label>
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
			<!-- Note: HTML5 pattern attributes are for UX only, not security. Server validates all input. -->
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
			<!-- Note: HTML5 pattern attributes are for UX only, not security. Server validates all input. -->
			<input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />
		</div>
		<div class="large-6 columns">
			<label for="user_password_repeat"><?php echo WORDING_REGISTRATION_PASSWORD_REPEAT; ?></label>
			<input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />
		</div>
	</div>
  
	<div class="row">
		<div class="medium-9 columns">
			<input type="submit" name="register" value="<?php echo WORDING_REGISTER; ?>" />
		</div>
		<div class="medium-3 columns">
			<label for="family_id">Family ID (Official Use)</label>
			<input type="text" readonly="readonly" id="family_id" name="family_id" value="<?php echo htmlspecialchars($family_id, ENT_QUOTES, 'UTF-8'); ?>">
		</div>
	</div>

</form>
<?php } ?>
