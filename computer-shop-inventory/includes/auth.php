<?php
// ==========================================================
// Authentication & Session Guard: Computer Shop Inventory
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access the system.');
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'staff',
    ];
}

function has_role($allowed_roles): bool {
    if (!is_logged_in()) return false;
    $role = $_SESSION['role'] ?? '';
    if (is_array($allowed_roles)) {
        return in_array($role, $allowed_roles, true);
    }
    return $role === $allowed_roles;
}

function require_role($allowed_roles) {
    require_login();
    if (!has_role($allowed_roles)) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="container py-5 text-center">
                <div class="card bg-dark border-danger p-5 max-w-md mx-auto" style="max-width: 500px;">
                    <i class="bi bi-shield-lock-fill text-danger display-1 mb-3"></i>
                    <h3 class="text-white">Access Denied</h3>
                    <p class="text-secondary">Your account role (<strong>' . htmlspecialchars($_SESSION['role'] ?? 'Guest') . '</strong>) does not have permission to access this module.</p>
                    <a href="index.php" class="btn btn-primary mt-3"><i class="bi bi-house me-1"></i> Return to Dashboard</a>
                </div>
              </div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

function set_flash(string $type, string $message) {
    $_SESSION['flash'] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message,
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
