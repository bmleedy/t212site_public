<?php
/**
 * Centralized Session Configuration
 *
 * Include this file before any session access. Ensures consistent
 * session cookie parameters across all pages and API endpoints.
 *
 * This is the ONLY place session cookie params should be configured.
 * Do NOT call session_set_cookie_params() or session_start() elsewhere.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
