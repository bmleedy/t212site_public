<?php
declare(strict_types=1);

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

final class Credentials {

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
     * Prevent cloning of singleton instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton instance
     *
     * @throws Exception
     */
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Get singleton instance
     *
     * @return Credentials
     */
    public static function getInstance(): self {
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
    private function loadCredentials(): void {
        if (!file_exists($this->credentialsFile)) {
            throw new Exception(
                "CREDENTIALS.json file not found. " .
                "Please ensure CREDENTIALS.json exists in the public_html directory."
            );
        }

        $jsonContent = file_get_contents($this->credentialsFile);

        if ($jsonContent === false) {
            throw new Exception("Unable to read CREDENTIALS.json file.");
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
     * Extract username or password from a credential pair stored as associative array
     *
     * The JSON format stores credentials as {"username": "password"}.
     * This helper extracts either the key (username) or value (password).
     *
     * @param string $key The credential key in the JSON (e.g., 'database_user', 'smtp_email')
     * @param bool $returnKey True to return the key (username), false for value (password)
     * @return string The extracted credential or empty string if not found
     */
    private function getCredentialPair(string $key, bool $returnKey = true): string {
        if (!isset($this->credentials[$key]) || !is_array($this->credentials[$key])) {
            return '';
        }
        $pairs = $this->credentials[$key];
        return $returnKey ? (array_keys($pairs)[0] ?? '') : (reset($pairs) ?: '');
    }

    /**
     * Get all credentials (use with caution)
     *
     * WARNING: This exposes all credentials. Only use for debugging
     * or migration purposes. Never expose to end users.
     *
     * @return array
     */
    public function getAll(): array {
        return $this->credentials ?? [];
    }

    // ========================================================================
    // DATABASE CREDENTIALS
    // ========================================================================

    /**
     * Get database username
     *
     * @return string
     */
    public function getDatabaseUser(): string {
        return $this->getCredentialPair('database_user', true);
    }

    /**
     * Get database password
     *
     * @return string
     */
    public function getDatabasePassword(): string {
        return $this->getCredentialPair('database_user', false);
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDatabaseName(): string {
        // First check if database_name is explicitly defined
        if (isset($this->credentials['database_name'])) {
            return $this->credentials['database_name'];
        }

        // Fallback: extract from username (legacy behavior for hostinger)
        // e.g., u321706752_t212db -> u321706752_t212
        $user = $this->getDatabaseUser();
        return str_replace('db', '', $user);
    }

    /**
     * Get database host (defaults to localhost)
     *
     * @return string
     */
    public function getDatabaseHost(): string {
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
    public function getSMTPUsername(): string {
        return $this->getCredentialPair('smtp_email', true);
    }

    /**
     * Get SMTP email password
     *
     * @return string
     */
    public function getSMTPPassword(): string {
        return $this->getCredentialPair('smtp_email', false);
    }

    // ========================================================================
    // COOKIE SECRET KEY
    // ========================================================================

    /**
     * Get cookie secret key
     *
     * @return string
     */
    public function getCookieSecretKey(): string {
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
    public function getPayPalClientId(): string {
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
    public function getPayPalProductionClientId(): string {
        return $this->credentials['paypal_client_id'] ?? '';
    }

    /**
     * Get PayPal Sandbox Client ID for JavaScript SDK (for testing)
     *
     * @return string
     */
    public function getPayPalSandboxClientId(): string {
        return $this->credentials['paypal_sandbox_client_id'] ?? '';
    }

    /**
     * Get PayPal environment setting ('production' or 'sandbox')
     * Defaults to 'production' if not set
     *
     * @return string
     */
    public function getPayPalEnvironment(): string {
        return $this->credentials['paypal_environment'] ?? 'production';
    }

    /**
     * Check if PayPal is in sandbox mode
     *
     * @return bool
     */
    public function isPayPalSandbox(): bool {
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
    public function getGoogleEmail(): string {
        return $this->getCredentialPair('google', true);
    }

    /**
     * Get Google account password
     *
     * @return string
     */
    public function getGooglePassword(): string {
        return $this->getCredentialPair('google', false);
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Check if credentials file exists
     *
     * @return bool
     */
    public function credentialsFileExists(): bool {
        return file_exists($this->credentialsFile);
    }

    /**
     * Get the path to the credentials file
     *
     * WARNING: This exposes the file location. Only use for debugging
     * or configuration validation. Never expose to end users.
     *
     * @return string
     */
    public function getCredentialsFilePath(): string {
        return $this->credentialsFile;
    }
}
