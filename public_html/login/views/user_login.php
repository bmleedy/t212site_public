<?php
// show potential errors / feedback (from login object)
if (isset($login)) {
    if ($login->errors) {
        foreach ($login->errors as $error) {
            echo $error;
        }
    }
    if ($login->messages) {
        foreach ($login->messages as $message) {
            echo $message;
        }
    }
}
?>

<?php
// show potential errors / feedback (from registration object)
if (isset($registration)) {
    if ($registration->errors) {
        foreach ($registration->errors as $error) {
            echo $error;
        }
    }
    if ($registration->messages) {
        foreach ($registration->messages as $message) {
            echo $message;
        }
    }
}

if ($_SERVER['QUERY_STRING']=='') {
	$tempURL = $_SERVER['PHP_SELF'];
} else {
	$tempURL = $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
}
?>

<form method="post" action="<?php echo $tempURL; ?>" name="loginform">
<h5>Please login</h5>
    <label for="user_name"><?php echo WORDING_USERNAME; ?></label>
    <input id="user_name" type="text" name="user_name" required />
    <label for="user_password"><?php echo WORDING_PASSWORD; ?></label>
    <input id="user_password" type="password" name="user_password" autocomplete="off" required />
    <input type="submit" name="login" value="<?php echo WORDING_LOGIN; ?>" />
</form>
<p>
<a href="password_reset.php"><?php echo WORDING_FORGOT_MY_PASSWORD; ?></a>
</p>
<p>This section is for Scouts and Adults of Troop 212 only. If you are a Scout or Adult of Troop 212 
and do not have access, you can gain access one of two ways:
<ol>
<li>If a family member is already a member of T212.org, please have them login, click on My Profile, then click the Add Family Member button.</li>
<li>If you are the first in your family to register, please send an email to Jessica or attend the next Scout Meeting to get setup.</li>
</ol>
</p>
