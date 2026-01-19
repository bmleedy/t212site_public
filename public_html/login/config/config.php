<?php

/**
 * Configuration for: Database Connection
 * Database credentials are loaded from CREDENTIALS.json via the Credentials utility.
 *
 * DB_HOST: database host, usually "127.0.0.1" or "localhost", some servers also need port info
 * DB_NAME: name of the database
 * DB_USER: database user with SELECT, UPDATE, DELETE and INSERT privileges
 * DB_PASS: the password of the above user
 */
try {
    require_once(__DIR__ . '/../../includes/credentials.php');
    $creds = Credentials::getInstance();
    define("DB_HOST", $creds->getDatabaseHost());
    define("DB_NAME", $creds->getDatabaseName());
    define("DB_USER", $creds->getDatabaseUser());
    define("DB_PASS", $creds->getDatabasePassword());
} catch (Exception $e) {
    die("Configuration error: Unable to load credentials.");
}

/**
 * Configuration for: Cookies
 * Cookie secret key is loaded from CREDENTIALS.json via the Credentials utility.
 *
 * COOKIE_RUNTIME: How long should a cookie be valid? 1209600 seconds = 2 weeks
 * COOKIE_DOMAIN: The domain where the cookie is valid. Leading dot enables sub-domains.
 * COOKIE_SECRET_KEY: Random value for app security. When changed, all cookies are reset.
 */
define("COOKIE_RUNTIME", 1209600);
define("COOKIE_DOMAIN", ".t212.org");
define("COOKIE_SECRET_KEY", $creds->getCookieSecretKey());

/**
 * Configuration for: Email server credentials
 * SMTP credentials are loaded from CREDENTIALS.json via the Credentials utility.
 * Set EMAIL_USE_SMTP to true to enable SMTP email delivery (recommended).
 */
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
define("EMAIL_PASSWORDRESET_URL", "https://www.t212.org/password_reset.php");
define("EMAIL_PASSWORDRESET_FROM", "no-reply@t212.org");
define("EMAIL_PASSWORDRESET_FROM_NAME", "Troop 212 Website");
define("EMAIL_PASSWORDRESET_SUBJECT", "Password reset for Troop 212 Website");
define("EMAIL_PASSWORDRESET_CONTENT", "Please click on this link to reset your password:");

/**
 * Configuration for: verification email data
 * Set the absolute URL to register.php, necessary for email verification links
 */
define("EMAIL_VERIFICATION_URL", "https://www.t212.org/register.php");
define("EMAIL_VERIFICATION_FROM", "no-reply@t212.org");
define("EMAIL_VERIFICATION_FROM_NAME", "Troop 212 Website");
define("EMAIL_VERIFICATION_SUBJECT", "Account activation for Troop 212 Website");
define("EMAIL_VERIFICATION_CONTENT", "Please click on this link to activate the account:");

define("EMAIL_ACTIVATED_SUBJECT", "Account activation for Troop 212 Website");
define("EMAIL_ACTIVATED_CONTENT", "Your account has been activated for Troop 212's website! Please click the following link to login and complete your user profile.");

/**
 * Configuration for: Hashing strength
 * Defines the cost factor for password_hash() using bcrypt.
 *
 * The cost factor is the base-2 logarithm of hashing rounds (2^12 = 4096 rounds).
 * Higher values are more secure but require more CPU time per login/registration.
 * Current recommendation: 12 (increase as server hardware improves).
 *
 * @see https://www.php.net/manual/en/function.password-hash.php
 */
define("HASH_COST_FACTOR", 12);
