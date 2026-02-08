<?php
/**
 * Seed test users for e2e testing.
 *
 * Reads credentials from environment variables and inserts test users
 * into the database so that Playwright login tests can authenticate.
 *
 * Required env vars:
 *   TEST_SA_USERNAME, TEST_SA_PASSWORD
 *   TEST_USER_USERNAME, TEST_USER_PASSWORD
 */

require_once __DIR__ . '/../../public_html/includes/credentials.php';

$creds = Credentials::getInstance();

$pdo = new PDO(
    'mysql:host=' . $creds->getDatabaseHost() . ';dbname=' . $creds->getDatabaseName() . ';charset=utf8',
    $creds->getDatabaseUser(),
    $creds->getDatabasePassword(),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$saUser   = getenv('TEST_SA_USERNAME');
$saPass   = getenv('TEST_SA_PASSWORD');
$testUser = getenv('TEST_USER_USERNAME');
$testPass = getenv('TEST_USER_PASSWORD');

if (!$saUser || !$saPass) {
    fwrite(STDERR, "ERROR: TEST_SA_USERNAME and TEST_SA_PASSWORD must be set\n");
    exit(1);
}
if (!$testUser || !$testPass) {
    fwrite(STDERR, "ERROR: TEST_USER_USERNAME and TEST_USER_PASSWORD must be set\n");
    exit(1);
}

$hashCost = 12; // matches HASH_COST_FACTOR in login/config/config.php

// Insert families first (users.family_id references families.family_id)
$pdo->exec("INSERT INTO families (family_id, address1, city, state, zip) VALUES (1, '123 Test St', 'Testville', 'TX', '75001')");
$pdo->exec("INSERT INTO families (family_id, address1, city, state, zip) VALUES (2, '456 Test Ave', 'Testville', 'TX', '75002')");

// Insert SA user
$saHash = password_hash($saPass, PASSWORD_DEFAULT, ['cost' => $hashCost]);
$stmt = $pdo->prepare(
    "INSERT INTO users (user_id, family_id, user_name, user_password_hash, user_email, user_active,
                        user_failed_logins, user_registration_datetime, user_registration_ip,
                        user_first, user_last, is_scout, user_type, user_access)
     VALUES (1, 1, :name, :hash, :email, 1, 0, NOW(), '127.0.0.1', 'Test', 'Admin', 0, 'Adult', 'sa')"
);
$stmt->execute([
    ':name'  => $saUser,
    ':hash'  => $saHash,
    ':email' => 'sa_test@example.com',
]);

// Insert regular test user
$testHash = password_hash($testPass, PASSWORD_DEFAULT, ['cost' => $hashCost]);
$stmt = $pdo->prepare(
    "INSERT INTO users (user_id, family_id, user_name, user_password_hash, user_email, user_active,
                        user_failed_logins, user_registration_datetime, user_registration_ip,
                        user_first, user_last, is_scout, user_type, user_access)
     VALUES (2, 2, :name, :hash, :email, 1, 0, NOW(), '127.0.0.1', 'Test', 'User', 0, 'Adult', '')"
);
$stmt->execute([
    ':name'  => $testUser,
    ':hash'  => $testHash,
    ':email' => 'user_test@example.com',
]);

echo "Seeded 2 test users successfully.\n";
