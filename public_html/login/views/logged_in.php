<?php include('_header.php'); ?>

<?php
echo WORDING_YOU_ARE_LOGGED_IN_AS . htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') . "<br>";
echo WORDING_PROFILE_PICTURE . '<br>' . $login->user_gravatar_image_tag;
?>

<div class="user-actions">
    <a href="index.php?logout"><?php echo WORDING_LOGOUT; ?></a>
    <a href="User.php?id=<?php echo htmlspecialchars((string)$_SESSION['user_id'], ENT_QUOTES, 'UTF-8'); ?>&amp;edit=1"><?php echo WORDING_EDIT_USER_DATA; ?></a>
</div>

<?php include('_footer.php'); ?>
