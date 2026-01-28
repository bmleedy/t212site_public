<?php
/**
 * Impersonation Helper
 *
 * Functions to support admin user impersonation feature.
 * Only Super Admins (sa) can impersonate other users.
 */

/**
 * Check if the current session is impersonating another user
 *
 * @return bool True if currently impersonating
 */
function is_impersonating(): bool {
    return isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true;
}

/**
 * Get the original admin's user ID (when impersonating)
 *
 * @return int|null The original user ID or null if not impersonating
 */
function get_impersonator_id(): ?int {
    if (!is_impersonating()) {
        return null;
    }
    return isset($_SESSION['original_user_id']) ? (int)$_SESSION['original_user_id'] : null;
}

/**
 * Get the original admin's username (when impersonating)
 *
 * @return string|null The original username or null if not impersonating
 */
function get_impersonator_name(): ?string {
    if (!is_impersonating()) {
        return null;
    }
    return $_SESSION['original_user_name'] ?? null;
}

/**
 * Get the original admin's first name (when impersonating)
 *
 * @return string|null The original first name or null if not impersonating
 */
function get_impersonator_first_name(): ?string {
    if (!is_impersonating()) {
        return null;
    }
    return $_SESSION['original_user_first'] ?? null;
}

/**
 * Start impersonating a user
 *
 * @param mysqli $mysqli Database connection object
 * @param int $target_user_id The user ID to impersonate
 * @return array Result with 'success' and 'message' keys
 */
function start_impersonation($mysqli, int $target_user_id): array {
    // Must be logged in
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Not authenticated'];
    }

    // Must be super admin
    $access = explode('.', $_SESSION['user_access'] ?? '');
    if (!in_array('sa', $access)) {
        return ['success' => false, 'message' => 'Only Super Admins can impersonate'];
    }

    // Cannot impersonate while already impersonating
    if (is_impersonating()) {
        return ['success' => false, 'message' => 'Already impersonating. Exit first.'];
    }

    // Cannot impersonate self
    if ($target_user_id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot impersonate yourself'];
    }

    // Fetch target user data
    $stmt = $mysqli->prepare("SELECT user_id, family_id, user_name, user_first, user_last, user_email,
                                     user_access, user_type
                              FROM users WHERE user_id = ? AND user_active = 1 AND user_type != 'Delete'");
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Database error'];
    }

    $stmt->bind_param('i', $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $target_user = $result->fetch_assoc();
    $stmt->close();

    if (!$target_user) {
        return ['success' => false, 'message' => 'User not found or inactive'];
    }

    // Store original admin session data
    $_SESSION['original_user_id'] = $_SESSION['user_id'];
    $_SESSION['original_family_id'] = $_SESSION['family_id'];
    $_SESSION['original_user_name'] = $_SESSION['user_name'];
    $_SESSION['original_user_first'] = $_SESSION['user_first'];
    $_SESSION['original_user_email'] = $_SESSION['user_email'];
    $_SESSION['original_user_access'] = $_SESSION['user_access'];
    $_SESSION['original_user_type'] = $_SESSION['user_type'];

    // Replace session with target user's data
    $_SESSION['user_id'] = $target_user['user_id'];
    $_SESSION['family_id'] = $target_user['family_id'];
    $_SESSION['user_name'] = $target_user['user_name'];
    $_SESSION['user_first'] = $target_user['user_first'];
    $_SESSION['user_email'] = $target_user['user_email'];
    $_SESSION['user_access'] = $target_user['user_access'];
    $_SESSION['user_type'] = $target_user['user_type'];

    // Set impersonation flag
    $_SESSION['is_impersonating'] = true;

    $full_name = $target_user['user_first'] . ' ' . $target_user['user_last'];
    return [
        'success' => true,
        'message' => 'Now impersonating ' . $full_name
    ];
}

/**
 * Stop impersonating and restore original session
 *
 * @return array Result with 'success' and 'message' keys
 */
function stop_impersonation(): array {
    if (!is_impersonating()) {
        return ['success' => false, 'message' => 'Not currently impersonating'];
    }

    // Restore original session data
    $_SESSION['user_id'] = $_SESSION['original_user_id'];
    $_SESSION['family_id'] = $_SESSION['original_family_id'];
    $_SESSION['user_name'] = $_SESSION['original_user_name'];
    $_SESSION['user_first'] = $_SESSION['original_user_first'];
    $_SESSION['user_email'] = $_SESSION['original_user_email'];
    $_SESSION['user_access'] = $_SESSION['original_user_access'];
    $_SESSION['user_type'] = $_SESSION['original_user_type'];

    // Clear impersonation variables
    unset($_SESSION['is_impersonating']);
    unset($_SESSION['original_user_id']);
    unset($_SESSION['original_family_id']);
    unset($_SESSION['original_user_name']);
    unset($_SESSION['original_user_first']);
    unset($_SESSION['original_user_email']);
    unset($_SESSION['original_user_access']);
    unset($_SESSION['original_user_type']);

    return ['success' => true, 'message' => 'Impersonation ended'];
}
