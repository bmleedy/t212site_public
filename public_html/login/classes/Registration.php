<?php
declare(strict_types=1);

/**
 * Handles the user registration
 * @author Panique
 * @link http://www.php-login.net
 * @link https://github.com/panique/php-login-advanced/
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Registration
{
	/**
	 * @var PDO|null $db_connection The database connection
	 */
	private $db_connection = null;

	/**
	 * @var bool success state of registration
	 */
	public $registration_successful = false;

	/**
	 * @var bool success state of verification
	 */
	public $verification_successful = false;

	/**
	 * @var array collection of error messages
	 */
	public $errors = array();

	/**
	 * @var array collection of success / neutral messages
	 */
	public $messages = array();

	/**
	 * Constructor - automatically starts whenever an object of this class is created
	 */
	public function __construct()
	{
		session_start();

		// if we have such a POST request, call the registerNewUser() method
		if (isset($_POST["register"])) {
			$this->registerNewUser(
				$_POST['user_name'],
				$_POST['user_email'],
				$_POST['user_password_new'],
				$_POST['user_password_repeat'],
				$_POST['user_first'],
				$_POST['user_last'],
				$_POST['user_type'],
				$_POST['family_id']
			);
		// if we have such a GET request, call the verifyNewUser() method
		} else if (isset($_GET["id"]) && isset($_GET["verification_code"])) {
			$this->verifyNewUser($_GET["id"], $_GET["email"], $_GET["verification_code"]);
		}
	}

	/**
	 * Checks if database connection is opened and open it if not
	 * @return bool True if connection is available
	 */
	private function databaseConnection(): bool
	{
		if ($this->db_connection !== null) {
			return true;
		}

		try {
			// Generate a database connection with charset for security
			$this->db_connection = new PDO(
				'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
				DB_USER,
				DB_PASS
			);
			return true;
		} catch (PDOException $e) {
			// Log error but don't expose details to user
			error_log("Registration database connection failed: " . $e->getMessage());
			$this->errors[] = MESSAGE_DATABASE_ERROR;
			return false;
		}
	}

	/**
	 * Configure PHPMailer with SMTP settings from config
	 * @param PHPMailer $mail The mailer instance to configure
	 * @return PHPMailer The configured mailer
	 */
	private function configureMailer($mail)
	{
		if (EMAIL_USE_SMTP) {
			$mail->IsSMTP();
			$mail->SMTPAuth = EMAIL_SMTP_AUTH;
			if (defined('EMAIL_SMTP_ENCRYPTION')) {
				$mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
			}
			$mail->Host = EMAIL_SMTP_HOST;
			$mail->Username = EMAIL_SMTP_USERNAME;
			$mail->Password = EMAIL_SMTP_PASSWORD;
			$mail->Port = EMAIL_SMTP_PORT;
		} else {
			$mail->IsMail();
		}
		return $mail;
	}

	/**
	 * Log email activity (success or failure)
	 * @param string $action The action name for logging
	 * @param array $values The values to log
	 * @param bool $success Whether the action succeeded
	 * @param string $message Human-readable message
	 */
	private function logEmailActivity(string $action, array $values, bool $success, string $message): void
	{
		if (!file_exists(__DIR__ . '/../../includes/activity_logger.php')) {
			return;
		}

		require_once(__DIR__ . '/../../includes/activity_logger.php');
		require_once(__DIR__ . '/../../includes/credentials.php');

		try {
			$creds = Credentials::getInstance();
			$mysqli = new mysqli(
				$creds->getDatabaseHost(),
				$creds->getDatabaseUser(),
				$creds->getDatabasePassword(),
				$creds->getDatabaseName()
			);

			if (!$mysqli->connect_error) {
				log_activity($mysqli, $action, $values, $success, $message, null);
				$mysqli->close();
			}
		} catch (Exception $e) {
			// Silently fail logging - don't break registration flow
		}
	}

	/**
	 * Handles the entire registration process. Checks all error possibilities
	 * and creates a new user in the database if everything is fine.
	 */
	private function registerNewUser($user_name, $user_email, $user_password, $user_password_repeat, $user_first, $user_last, $user_type, $family_id): void
	{
		// Trim inputs
		$user_name = trim($user_name);
		$user_email = trim($user_email);
		$user_first = trim($user_first);
		$user_last = trim($user_last);
		$family_id = trim($family_id);

		if ($family_id === '') {
			$family_id = 0;
		}

		// Validation with early returns
		if (empty($user_name)) {
			$this->errors[] = MESSAGE_USERNAME_EMPTY;
			return;
		}

		if (empty($user_password) || empty($user_password_repeat)) {
			$this->errors[] = MESSAGE_PASSWORD_EMPTY;
			return;
		}

		if ($user_password !== $user_password_repeat) {
			$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
			return;
		}

		if (strlen($user_password) < 6) {
			$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
			return;
		}

		if (strlen($user_name) > 64 || strlen($user_name) < 2) {
			$this->errors[] = MESSAGE_USERNAME_BAD_LENGTH;
			return;
		}

		if (!preg_match('/^[a-z\d]{2,64}$/i', $user_name)) {
			$this->errors[] = MESSAGE_USERNAME_INVALID;
			return;
		}

		if (empty($user_email)) {
			$this->errors[] = MESSAGE_EMAIL_EMPTY;
			return;
		}

		if (strlen($user_email) > 64) {
			$this->errors[] = MESSAGE_EMAIL_TOO_LONG;
			return;
		}

		if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
			$this->errors[] = MESSAGE_EMAIL_INVALID;
			return;
		}

		// All validation passed, proceed with database operations
		if (!$this->databaseConnection()) {
			return;
		}

		// Check if username or email already exists
		try {
			$query_check_user_name = $this->db_connection->prepare(
				'SELECT user_name, user_email FROM users WHERE user_name=:user_name OR user_email=:user_email'
			);
			$query_check_user_name->bindValue(':user_name', $user_name, PDO::PARAM_STR);
			$query_check_user_name->bindValue(':user_email', $user_email, PDO::PARAM_STR);
			$query_check_user_name->execute();
			$result = $query_check_user_name->fetchAll();
		} catch (PDOException $e) {
			error_log("Registration check user query failed: " . $e->getMessage());
			$this->errors[] = MESSAGE_DATABASE_ERROR;
			return;
		}

		// Check for duplicate username or email
		if (count($result) > 0) {
			foreach ($result as $row) {
				if ($row['user_name'] === $user_name) {
					$this->errors[] = MESSAGE_USERNAME_EXISTS;
				} else {
					$this->errors[] = MESSAGE_EMAIL_ALREADY_EXISTS;
				}
			}
			return;
		}

		// Hash password with configured cost factor
		$options = defined('HASH_COST_FACTOR') ? ['cost' => (int)HASH_COST_FACTOR] : [];
		$user_password_hash = password_hash($user_password, PASSWORD_DEFAULT, $options);

		// Generate cryptographically secure activation hash (40 char hex string)
		$user_activation_hash = bin2hex(random_bytes(20));

		// Insert new user into database
		try {
			$query_new_user_insert = $this->db_connection->prepare('INSERT INTO users (
				user_name,
				user_password_hash,
				user_email,
				user_activation_hash,
				user_registration_ip,
				user_first,
				user_last,
				user_type,
				is_scout,
				family_id,
				user_registration_datetime,
				user_access)
			VALUES(:user_name,
				:user_password_hash,
				:user_email,
				:user_activation_hash,
				:user_registration_ip,
				:user_first,
				:user_last,
				:user_type,
				:is_scout,
				:family_id,
				now(),
				:user_access)');

			$query_new_user_insert->bindValue(':user_name', $user_name, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_password_hash', $user_password_hash, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_email', $user_email, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_first', $user_first, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_last', $user_last, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_type', $user_type, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':is_scout', 0, PDO::PARAM_BOOL);
			$query_new_user_insert->bindValue(':family_id', $family_id, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_activation_hash', $user_activation_hash, PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_registration_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
			$query_new_user_insert->bindValue(':user_access', '', PDO::PARAM_STR);
			$query_new_user_insert->execute();
		} catch (PDOException $e) {
			error_log("Registration insert failed: " . $e->getMessage());
			$this->errors[] = MESSAGE_DATABASE_ERROR;
			return;
		}

		if ($query_new_user_insert) {
			$this->messages[] = "User " . $user_name . " registered. ";
			$this->registration_successful = true;
		} else {
			$this->errors[] = MESSAGE_REGISTRATION_FAILED;
		}
	}

	/**
	 * Sends a verification email to the administrator for account approval
	 * @return bool True if mail was sent successfully
	 */
	public function sendVerificationEmail($user_id, $user_email, $user_first, $user_last, $user_type, $user_activation_hash): bool
	{
		$mail = new PHPMailer;
		$this->configureMailer($mail);

		$mail->From = EMAIL_VERIFICATION_FROM;
		$mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
		$mail->AddAddress($user_email);
		$mail->Subject = EMAIL_VERIFICATION_SUBJECT;

		$link = EMAIL_VERIFICATION_URL . '?id=' . urlencode($user_id) . '&email=' . urlencode($user_email) . '&verification_code=' . urlencode($user_activation_hash);
		$newLine = "\r\n";

		$userTypeLabel = ($user_type === 'Scout') ? 'Scout' : 'Adult';
		$userTypePossessive = ($user_type === 'Scout') ? "Scout's" : "Adult's";

		$body = "A {$userTypeLabel}, {$user_first} {$user_last}, has requested access to Troop 212's website.{$newLine}{$newLine}";
		$body .= "The {$userTypePossessive} name is: {$user_first} {$user_last}{$newLine}";
		$body .= "The {$userTypePossessive} email address is: {$user_email}{$newLine}{$newLine}";
		$body .= EMAIL_VERIFICATION_CONTENT . ' ' . $link;

		$mail->Body = $body;

		if (!$mail->Send()) {
			$this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT . $mail->ErrorInfo;

			$this->logEmailActivity(
				'send_verification_email_failed',
				array(
					'to' => array($user_email),
					'subject' => EMAIL_VERIFICATION_SUBJECT,
					'user_email' => $user_email,
					'user_type' => $user_type,
					'error' => $mail->ErrorInfo
				),
				false,
				"Failed to send verification email for $user_email"
			);

			return false;
		}

		$this->logEmailActivity(
			'send_verification_email',
			array(
				'to' => array($user_email),
				'subject' => EMAIL_VERIFICATION_SUBJECT,
				'user_email' => $user_email,
				'user_type' => $user_type
			),
			true,
			"Verification email sent for $user_email"
		);

		return true;
	}

	/**
	 * Sends an email to notify the user their account has been activated
	 * @return bool True if mail was sent successfully
	 */
	public function sendActivatedEmail($user_email, $user_id): bool
	{
		$mail = new PHPMailer;
		$this->configureMailer($mail);

		$newLine = "\r\n";

		$mail->From = EMAIL_VERIFICATION_FROM;
		$mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
		$mail->AddAddress($user_email);
		$mail->Subject = EMAIL_ACTIVATED_SUBJECT;

		$link = "http://www.t212.org/User.php?id=" . urlencode($user_id) . "&edit=1";
		$mail->Body = EMAIL_ACTIVATED_CONTENT . $newLine . $newLine . $link;

		if (!$mail->Send()) {
			$this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT . $mail->ErrorInfo;
			return false;
		}

		return true;
	}

	/**
	 * Checks the id/verification code combination and activates the user account
	 */
	public function verifyNewUser($user_id, $user_email, $user_activation_hash): void
	{
		if (!$this->databaseConnection()) {
			return;
		}

		// Try to update user with specified information
		$query_update_user = $this->db_connection->prepare(
			'UPDATE users SET user_activation_hash = NULL WHERE user_id = :user_id AND user_activation_hash = :user_activation_hash'
		);
		$query_update_user->bindValue(':user_id', intval(trim($user_id)), PDO::PARAM_INT);
		$query_update_user->bindValue(':user_activation_hash', $user_activation_hash, PDO::PARAM_STR);
		$query_update_user->execute();

		if ($query_update_user->rowCount() > 0) {
			$this->verification_successful = true;
			$this->messages[] = MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL;
			$this->sendActivatedEmail($user_email, $user_id);
		} else {
			$this->errors[] = MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL;
		}
	}
}
