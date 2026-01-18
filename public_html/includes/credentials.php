<?php
/**
 * Credentials Utility Class
 *
 * This class provides centralized access to credentials stored in CREDENTIALS.json.
 * It loads credentials once and caches them in memory for performance.
 *
 * Usage:
 *   require_once(__DIR__ . '/includes/credentials.php');
 *   $creds = Credentials::getInstance();
 *   $dbPassword = $creds->getDatabasePassword();
 *
 * Security:
 * - CREDENTIALS.json must be in .gitignore
 * - CREDENTIALS.json must be blocked by .htaccess
 * - Never echo or print credential values in production
 */

class Credentials {

    /**
     * Singleton instance
     * @var Credentials|null
     */
    private static $instance = null;

    /**
     * Cached credentials data
     * @var array|null
     */
    private $credentials = null;

    /**
     * Path to CREDENTIALS.json file
     * @var string
     */
    private $credentialsFile;

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        // Determine the path to CREDENTIALS.json
        // Assumes this file is in public_html/includes/
        $this->credentialsFile = __DIR__ . '/../CREDENTIALS.json';

        // Load credentials immediately
        $this->loadCredentials();
    }

    /**
     * Get singleton instance
     *
     * @return Credentials
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Credentials();
        }
        return self::$instance;
    }

    /**
     * Load credentials from CREDENTIALS.json file
     *
     * @throws Exception if file doesn't exist or JSON is invalid
     */
    private function loadCredentials() {
        if (!file_exists($this->credentialsFile)) {
            throw new Exception(
                "CREDENTIALS.json file not found at: " . $this->credentialsFile . "\n" .
                "Please ensure CREDENTIALS.json exists in the public_html directory."
            );
        }

        $jsonContent = file_get_contents($this->credentialsFile);

        if ($jsonContent === false) {
            throw new Exception("Unable to read CREDENTIALS.json file");
        }

        $this->credentials = json_decode($jsonContent, true);

        if ($this->credentials === null) {
            throw new Exception(
                "CREDENTIALS.json contains invalid JSON. " .
                "Error: " . json_last_error_msg()
            );
        }
    }

    /**
     * Get all credentials (use with caution)
     *
     * @return array
     */
    public function getAll() {
        return $this->credentials;
    }

    // ========================================================================
    // DATABASE CREDENTIALS
    // ========================================================================

    /**
     * Get database username
     *
     * @return string
     */
    public function getDatabaseUser() {
        if (isset($this->credentials['database_user'])) {
            $users = $this->credentials['database_user'];
            if (is_array($users)) {
                // Return the first username key
                $keys = array_keys($users);
                return $keys[0] ?? '';
            }
        }
        return '';
    }

    /**
     * Get database password
     *
     * @return string
     */
    public function getDatabasePassword() {
        if (isset($this->credentials['database_user'])) {
            $users = $this->credentials['database_user'];
            if (is_array($users)) {
                // Return the first password value
                return reset($users) ?: '';
            }
        }
        return '';
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDatabaseName() {
        // First check if database_name is explicitly defined
        if (isset($this->credentials['database_name'])) {
            return $this->credentials['database_name'];
        }

        // Fallback: extract from username (legacy behavior)
        $user = $this->getDatabaseUser();
        // Database name is typically the username without the 'db' suffix
        // e.g., u321706752_t212db -> u321706752_t212
        return str_replace('db', '', $user);
    }

    /**
     * Get database host (defaults to localhost)
     *
     * @return string
     */
    public function getDatabaseHost() {
        return $this->credentials['database_host'] ?? '127.0.0.1';
    }

    // ========================================================================
    // SMTP EMAIL CREDENTIALS
    // ========================================================================

    /**
     * Get SMTP email username
     *
     * @return string
     */
    public function getSMTPUsername() {
        if (isset($this->credentials['smtp_email'])) {
            $emails = $this->credentials['smtp_email'];
            if (is_array($emails)) {
                $keys = array_keys($emails);
                return $keys[0] ?? '';
            }
        }
        return '';
    }

    /**
     * Get SMTP email password
     *
     * @return string
     */
    public function getSMTPPassword() {
        if (isset($this->credentials['smtp_email'])) {
            $emails = $this->credentials['smtp_email'];
            if (is_array($emails)) {
                return reset($emails) ?: '';
            }
        }
        return '';
    }

    // ========================================================================
    // COOKIE SECRET KEY
    // ========================================================================

    /**
     * Get cookie secret key
     *
     * @return string
     */
    public function getCookieSecretKey() {
        return $this->credentials['cookie_secret_key'] ?? '';
    }

    // ========================================================================
    // PAYPAL CREDENTIALS (JavaScript SDK)
    // ========================================================================

    /**
     * Get PayPal Client ID based on environment setting
     * Returns production client ID by default, or sandbox if environment is set to 'sandbox'
     *
     * @return string
     */
    public function getPayPalClientId() {
        $environment = $this->getPayPalEnvironment();
        if ($environment === 'sandbox') {
            return $this->credentials['paypal_sandbox_client_id'] ?? '';
        }
        return $this->credentials['paypal_client_id'] ?? '';
    }

    /**
     * Get PayPal Production Client ID for JavaScript SDK
     *
     * @return string
     */
    public function getPayPalProductionClientId() {
        return $this->credentials['paypal_client_id'] ?? '';
    }

    /**
     * Get PayPal Sandbox Client ID for JavaScript SDK (for testing)
     *
     * @return string
     */
    public function getPayPalSandboxClientId() {
        return $this->credentials['paypal_sandbox_client_id'] ?? '';
    }

    /**
     * Get PayPal environment setting ('production' or 'sandbox')
     * Defaults to 'production' if not set
     *
     * @return string
     */
    public function getPayPalEnvironment() {
        return $this->credentials['paypal_environment'] ?? 'production';
    }

    /**
     * Check if PayPal is in sandbox mode
     *
     * @return bool
     */
    public function isPayPalSandbox() {
        return $this->getPayPalEnvironment() === 'sandbox';
    }

    // ========================================================================
    // GOOGLE CREDENTIALS
    // ========================================================================

    /**
     * Get Google account email
     *
     * @return string
     */
    public function getGoogleEmail() {
        if (isset($this->credentials['google'])) {
            $accounts = $this->credentials['google'];
            if (is_array($accounts)) {
                $keys = array_keys($accounts);
                return $keys[0] ?? '';
            }
        }
        return '';
    }

    /**
     * Get Google account password
     *
     * @return string
     */
    public function getGooglePassword() {
        if (isset($this->credentials['google'])) {
            $accounts = $this->credentials['google'];
            if (is_array($accounts)) {
                return reset($accounts) ?: '';
            }
        }
        return '';
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Check if credentials file exists
     *
     * @return bool
     */
    public function credentialsFileExists() {
        return file_exists($this->credentialsFile);
    }

    /**
     * Get the path to the credentials file
     *
     * @return string
     */
    public function getCredentialsFilePath() {
        return $this->credentialsFile;
    }
}
