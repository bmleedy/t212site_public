<?php
/**
 * Authentication Helper for API Files
 *
 * Provides centralized authentication and authorization checks for all API endpoints.
 */

/**
 * Verify that the request is an AJAX request
 *
 * @return void Dies if not an AJAX request
 */
function require_ajax() {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid request method']);
        die();
    }
}

/**
 * Verify that user is logged in and return user ID
 *
 * @return int The logged-in user's ID
 */
function require_authentication() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_access'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        die();
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Check if user has specific access permission
 *
 * @param string $permission The permission code to check (e.g., 'ue', 'oe', 'sa', 'wm')
 * @return bool True if user has the permission
 */
function has_permission($permission) {
    if (!isset($_SESSION['user_access'])) {
        return false;
    }
    $access = explode('.', $_SESSION['user_access']);
    return in_array($permission, $access);
}

/**
 * Require specific permission or die
 *
 * @param string|array $permissions Permission code(s) required (e.g., 'ue' or ['ue', 'sa'])
 * @return void Dies if user doesn't have permission
 */
function require_permission($permissions) {
    $permissions = (array)$permissions;

    foreach ($permissions as $permission) {
        if (has_permission($permission)) {
            return; // User has at least one required permission
        }
    }

    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    die();
}

/**
 * Check if user can access another user's data
 *
 * @param int $requested_user_id The user ID being requested
 * @param int $current_user_id The logged-in user's ID
 * @return bool True if access is allowed
 */
function can_access_user_data($requested_user_id, $current_user_id) {
    // Users can always access their own data
    if ($requested_user_id == $current_user_id) {
        return true;
    }

    // Users with admin permissions can access any data
    if (has_permission('ue') || has_permission('sa') || has_permission('wm')) {
        return true;
    }

    return false;
}

/**
 * Require that user can access specific user's data or die
 *
 * @param int $requested_user_id The user ID being requested
 * @param int $current_user_id The logged-in user's ID
 * @return void Dies if access not allowed
 */
function require_user_access($requested_user_id, $current_user_id) {
    if (!can_access_user_data($requested_user_id, $current_user_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot access this user\'s data']);
        die();
    }
}
?>
