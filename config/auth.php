<?php
/**
 * Authentication & Session Management
 * Graduate Predicting Analysis System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get currently authenticated user details
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'] ?? 'User',
        'email'    => $_SESSION['user_email'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'student',
        'student_id' => $_SESSION['student_record_id'] ?? null,
        'student_code' => $_SESSION['student_code'] ?? null,
        'faculty_id' => $_SESSION['faculty_record_id'] ?? null
    ];
}

/**
 * Enforce that a user must be logged in
 */
function requireLogin(string $redirect = '/graduate_predictor/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header("Location: $redirect");
        exit;
    }
}

/**
 * Enforce role-based access control
 */
function requireRole($roles, string $redirect = '/graduate_predictor/login.php'): void {
    requireLogin($redirect);
    $userRole = $_SESSION['user_role'] ?? '';
    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array($userRole, $allowed, true)) {
        $_SESSION['flash_error'] = 'Access denied. You do not have permission for this section.';
        // Redirect according to their current role
        if ($userRole === 'admin') {
            header("Location: /graduate_predictor/admin/dashboard.php");
        } elseif ($userRole === 'faculty') {
            header("Location: /graduate_predictor/faculty/dashboard.php");
        } else {
            header("Location: /graduate_predictor/student/dashboard.php");
        }
        exit;
    }
}

/**
 * Log in a user and populate session variables
 */
function loginUser(array $user, ?array $extra = null): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    if ($user['role'] === 'student' && !empty($extra)) {
        $_SESSION['student_record_id'] = $extra['id'];
        $_SESSION['student_code']      = $extra['student_id'];
    } elseif ($user['role'] === 'faculty' && !empty($extra)) {
        $_SESSION['faculty_record_id'] = $extra['id'];
        $_SESSION['faculty_code']      = $extra['faculty_id'];
    }
}

/**
 * Log out and destroy session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generate CSRF Token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash alert message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Fetch and clear flash messages
 */
function getFlashes(): array {
    $flashes = [];
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (!empty($_SESSION['flash_' . $type])) {
            $flashes[$type] = $_SESSION['flash_' . $type];
            unset($_SESSION['flash_' . $type]);
        }
    }
    return $flashes;
}
