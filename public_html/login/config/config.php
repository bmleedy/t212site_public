<?php

// Load credentials utility
try {
    require_once(__DIR__ . '/../../includes/credentials.php');
} catch (Exception $e) {
    die("Error loading credentials utility in config.php: " . $e->getMessage());
}

/**
 * Configuration for: Database Connection
 * Database credentials are now loaded from CREDENTIALS.json via the Credentials utility
 *
 * For more info about constants please @see http://php.net/manual/en/function.define.php
 * If you want to know why we use "define" instead of "const" @see http://stackoverflow.com/q/2447791/1114320
 *
 * DB_HOST: database host, usually it's "127.0.0.1" or "localhost", some servers also need port info
 * DB_NAME: name of the database. please note: database and database table are not the same thing
 * DB_USER: user for your database. the user needs to have rights for SELECT, UPDATE, DELETE and INSERT.
 *          by the way, it's bad style to use "root", but for development it will work.
 * DB_PASS: the password of the above user
 */
try {
    $creds = Credentials::getInstance();
    define("DB_HOST", $creds->getDatabaseHost());
    define("DB_NAME", $creds->getDatabaseName());
    define("DB_USER", $creds->getDatabaseUser());
    define("DB_PASS", $creds->getDatabasePassword());
} catch (Exception $e) {
    die("Error loading database credentials in config.php: " . $e->getMessage());
}

/**
 * Configuration for: Cookies
 * Cookie secret key is now loaded from CREDENTIALS.json via the Credentials utility
 *
 * Please note: The COOKIE_DOMAIN needs the domain where your app is,
 * in a format like this: .mydomain.com
 * Note the . in front of the domain. No www, no http, no slash here!
 * For local development .127.0.0.1 or .localhost is fine, but when deploying you should
 * change this to your real domain, like '.mydomain.com' ! The leading dot makes the cookie available for
 * sub-domains too.
 * @see http://stackoverflow.com/q/9618217/1114320
 * @see http://www.php.net/manual/en/function.setcookie.php
 *
 * COOKIE_RUNTIME: How long should a cookie be valid ? 1209600 seconds = 2 weeks
 * COOKIE_DOMAIN: The domain where the cookie is valid for, like '.mydomain.com'
 * COOKIE_SECRET_KEY: Put a random value here to make your app more secure. When changed, all cookies are reset.
 */
define("COOKIE_RUNTIME", 1209600);
//define("COOKIE_DOMAIN", "127.0.0.1");
define("COOKIE_DOMAIN", ".t212.org");  //todo: I think this is not used
define("COOKIE_SECRET_KEY", $creds->getCookieSecretKey());

/**
 * Configuration for: Email server credentials
 *
 * Here you can define how you want to send emails.
 * If you have successfully set up a mail server on your linux server and you know
 * what you do, then you can skip this section. Otherwise please set EMAIL_USE_SMTP to true
 * and fill in your SMTP provider account data.
 *
 * An example setup for using gmail.com [Google Mail] as email sending service,
 * works perfectly in August 2013. Change the "xxx" to your needs.
 * Please note that there are several issues with gmail, like gmail will block your server
 * for "spam" reasons or you'll have a daily sending limit. See the readme.md for more info.
 *
 * define("EMAIL_USE_SMTP", true);
 * define("EMAIL_SMTP_HOST", "ssl://smtp.gmail.com");
 * define("EMAIL_SMTP_AUTH", true);
 * define("EMAIL_SMTP_USERNAME", "xxxxxxxxxx@gmail.com");
 * define("EMAIL_SMTP_PASSWORD", "xxxxxxxxxxxxxxxxxxxx");
 * define("EMAIL_SMTP_PORT", 465);
 * define("EMAIL_SMTP_ENCRYPTION", "ssl");
 *
 * It's really recommended to use SMTP!
 *
 */
//todo: test whether all of these workflows still exist.  Also reach out and find out what the passwords
// and set this up to be a troop-owned thing
define("EMAIL_USE_SMTP", false);
define("EMAIL_SMTP_HOST", "ssl://relay-hosting.secureserver.net");
define("EMAIL_SMTP_AUTH", true);
define("EMAIL_SMTP_USERNAME", $creds->getSMTPUsername());
define("EMAIL_SMTP_PASSWORD", $creds->getSMTPPassword());
define("EMAIL_SMTP_PORT", 465);
define("EMAIL_SMTP_ENCRYPTION", "ssl");

/**
 * Configuration for: password reset email data
 * Set the absolute URL to password_reset.php, necessary for email password reset links
 */
define("EMAIL_PASSWORDRESET_URL", "http://www.t212.org/password_reset.php");
define("EMAIL_PASSWORDRESET_FROM", "no-reply@t212.org");
define("EMAIL_PASSWORDRESET_FROM_NAME", "Troop 212 Website");
define("EMAIL_PASSWORDRESET_SUBJECT", "Password reset for Troop 212 Website");
define("EMAIL_PASSWORDRESET_CONTENT", "Please click on this link to reset your password:");

/**
 * Configuration for: verification email data
 * Set the absolute URL to register.php, necessary for email verification links
 */
define("EMAIL_VERIFICATION_URL", "http://www.t212.org/register.php");
define("EMAIL_VERIFICATION_FROM", "no-reply@t212.org");
define("EMAIL_VERIFICATION_FROM_NAME", "Troop 212 Website");
define("EMAIL_VERIFICATION_SUBJECT", "Account activation for Troop 212 Website");
define("EMAIL_VERIFICATION_CONTENT", "Please click on this link to activate the account:");

define("EMAIL_ACTIVATED_SUBJECT", "Account activation for Troop 212 Website");
define("EMAIL_ACTIVATED_CONTENT", "Your account has been actived for Troop 212's website! Please click the following link to login and complete your user profile.");

/**
 * Configuration for: Hashing strength
 * This is the place where you define the strength of your password hashing/salting
 *
 * To make password encryption very safe and future-proof, the PHP 5.5 hashing/salting functions
 * come with a clever so called COST FACTOR. This number defines the base-2 logarithm of the rounds of hashing,
 * something like 2^12 if your cost factor is 12. By the way, 2^12 would be 4096 rounds of hashing, doubling the
 * round with each increase of the cost factor and therefore doubling the CPU power it needs.
 * Currently, in 2013, the developers of this functions have chosen a cost factor of 10, which fits most standard
 * server setups. When time goes by and server power becomes much more powerful, it might be useful to increase
 * the cost factor, to make the password hashing one step more secure. Have a look here
 * (@see https://github.com/panique/php-login/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)
 * in the BLOWFISH benchmark table to get an idea how this factor behaves. For most people this is irrelevant,
 * but after some years this might be very very useful to keep the encryption of your database up to date.
 *
 * Remember: Every time a user registers or tries to log in (!) this calculation will be done.
 * Don't change this if you don't know what you do.
 *
 * To get more information about the best cost factor please have a look here
 * @see http://stackoverflow.com/q/4443476/1114320
 *
 * This constant will be used in the login and the registration class.
 */
define("HASH_COST_FACTOR", "10");
