#!/usr/bin/env php
<?php
/**
 * Manual SMTP Email Test Script
 *
 * This script sends a test email using the SMTP credentials from CREDENTIALS.json
 * to verify that email functionality is working correctly.
 *
 * Usage:
 *   php tests/manual/test_email.php recipient@example.com
 *
 * Options:
 *   --help, -h     Show this help message
 *   --verbose, -v  Show detailed SMTP debug output
 */

// Check if this is being run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Parse command line arguments
$options = getopt("hv", ["help", "verbose"]);
$showHelp = isset($options['h']) || isset($options['help']);
$verbose = isset($options['v']) || isset($options['verbose']);

// Get non-option arguments
$args = array_slice($argv, 1);
$args = array_filter($args, function($arg) {
    return !in_array($arg, ['-h', '--help', '-v', '--verbose']);
});
$args = array_values($args);

// Show help message
if ($showHelp || empty($args)) {
    echo <<<HELP

╔══════════════════════════════════════════════════════════════════════════╗
║                       SMTP EMAIL TEST SCRIPT                             ║
╚══════════════════════════════════════════════════════════════════════════╝

This script tests the email functionality by sending a test email using the
SMTP credentials from CREDENTIALS.json.

USAGE:
    php tests/manual/test_email.php [OPTIONS] <recipient_email>

ARGUMENTS:
    recipient_email    Email address to send the test email to

OPTIONS:
    -h, --help        Show this help message
    -v, --verbose     Show detailed SMTP debug output

EXAMPLES:
    # Send a test email to yourself
    php tests/manual/test_email.php your.email@example.com

    # Send with verbose SMTP debugging
    php tests/manual/test_email.php -v your.email@example.com

NOTES:
    - This test uses the SMTP credentials from CREDENTIALS.json
    - The test email subject will be: "Test Email from Troop 212 Website"
    - Check your spam folder if you don't receive the email
    - If EMAIL_USE_SMTP is false in config.php, this will use PHP's mail()

HELP;
    exit(0);
}

// Get recipient email from arguments
$recipientEmail = $args[0];

// Validate email format (simple check for @ and . in domain)
if (strpos($recipientEmail, '@') === false || !preg_match('/^[^@]+@[^@]+\.[^@]+$/', $recipientEmail)) {
    echo "❌ Error: Invalid email address format: $recipientEmail\n";
    echo "Run with --help for usage information.\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════╗\n";
echo "║                       SMTP EMAIL TEST                                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Determine project root
$projectRoot = dirname(dirname(__DIR__));
$publicHtml = $projectRoot . '/public_html';

echo "Step 1: Loading configuration...\n";
echo str_repeat("-", 60) . "\n";

try {
    // Load configuration
    require_once($publicHtml . '/login/config/config.php');
    echo "✅ Configuration loaded successfully\n";
    echo "   SMTP Host: " . EMAIL_SMTP_HOST . "\n";
    echo "   SMTP Username: " . EMAIL_SMTP_USERNAME . "\n";
    echo "   SMTP Port: " . EMAIL_SMTP_PORT . "\n";
    echo "   SMTP Encryption: " . EMAIL_SMTP_ENCRYPTION . "\n";
    echo "   Use SMTP: " . (EMAIL_USE_SMTP ? 'Yes' : 'No (using PHP mail())') . "\n";
} catch (Exception $e) {
    echo "❌ Failed to load configuration: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "Step 2: Loading PHPMailer library...\n";
echo str_repeat("-", 60) . "\n";

try {
    require_once($publicHtml . '/login/libraries/PHPMailer.php');
    echo "✅ PHPMailer library loaded\n";
} catch (Exception $e) {
    echo "❌ Failed to load PHPMailer: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "Step 3: Setting up email...\n";
echo str_repeat("-", 60) . "\n";

try {
    $mail = new PHPMailer;

    // Set debugging level if verbose
    if ($verbose) {
        $mail->SMTPDebug = 2; // Enable verbose debug output
        echo "🔍 Verbose mode enabled - showing SMTP debug output\n";
    }

    if (EMAIL_USE_SMTP) {
        echo "📧 Configuring SMTP...\n";
        $mail->IsSMTP();
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;

        if (defined('EMAIL_SMTP_ENCRYPTION')) {
            $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
        }

        $mail->Host = EMAIL_SMTP_HOST;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->Port = EMAIL_SMTP_PORT;
        echo "✅ SMTP configured\n";
    } else {
        echo "📧 Using PHP mail() function\n";
        $mail->IsMail();
        echo "✅ PHP mail() configured\n";
    }

    // Set email details
    $mail->From = EMAIL_SMTP_USERNAME;
    $mail->FromName = "Troop 212 Website - Test Script";
    $mail->AddAddress($recipientEmail);
    $mail->Subject = "Test Email from Troop 212 Website";

    $body = "This is a test email from the Troop 212 website email system.\n\n";
    $body .= "If you received this email, the SMTP configuration is working correctly!\n\n";
    $body .= "Test Details:\n";
    $body .= "- Sent at: " . date('Y-m-d H:i:s') . "\n";
    $body .= "- SMTP Host: " . EMAIL_SMTP_HOST . "\n";
    $body .= "- SMTP Username: " . EMAIL_SMTP_USERNAME . "\n";
    $body .= "- SMTP Port: " . EMAIL_SMTP_PORT . "\n";
    $body .= "- Encryption: " . EMAIL_SMTP_ENCRYPTION . "\n";
    $body .= "\n";
    $body .= "This test was run from: " . gethostname() . "\n";

    $mail->Body = $body;

    echo "✅ Email details configured\n";
    echo "   To: " . $recipientEmail . "\n";
    echo "   From: " . $mail->From . " (" . $mail->FromName . ")\n";
    echo "   Subject: " . $mail->Subject . "\n";

} catch (Exception $e) {
    echo "❌ Failed to set up email: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "Step 4: Sending email...\n";
echo str_repeat("-", 60) . "\n";

try {
    if ($verbose) {
        echo "\n--- SMTP DEBUG OUTPUT START ---\n";
    }

    $sent = $mail->Send();

    if ($verbose) {
        echo "--- SMTP DEBUG OUTPUT END ---\n\n";
    }

    if (!$sent) {
        echo "❌ Failed to send email\n";
        echo "   Error: " . $mail->ErrorInfo . "\n";
        exit(1);
    }

    echo "✅ Email sent successfully!\n";

} catch (Exception $e) {
    echo "❌ Exception while sending email: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════╗\n";
echo "║                       ✅ TEST COMPLETED SUCCESSFULLY                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📬 Test email has been sent to: " . $recipientEmail . "\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Check the inbox of " . $recipientEmail . "\n";
echo "  2. Check your spam/junk folder if you don't see it\n";
echo "  3. Verify the email content looks correct\n";
echo "\n";
echo "If you didn't receive the email:\n";
echo "  - Verify SMTP credentials in CREDENTIALS.json are correct\n";
echo "  - Check that EMAIL_USE_SMTP is set correctly in config.php\n";
echo "  - Run again with --verbose to see detailed SMTP debug output\n";
echo "  - Check your email provider's security settings\n";
echo "\n";

exit(0);
