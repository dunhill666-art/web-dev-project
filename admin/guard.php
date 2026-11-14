<?php
// admin access check
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
}
require_once dirname(__DIR__) . '/user/auth_helper.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('Pragma: no-cache', true);
header('Expires: 0', true);

// 1. Base access guard: user must be logged in with admin or staff role
if (!auth_is_logged_in() || (!auth_is_admin() && !auth_is_staff())) {
    header('Location: ' . ag_base_url('user/login.php?error=' . urlencode('Authorized staff or administrator access only. Please log in.')));
    exit;
}

// 1b. Revoke access immediately if the account was deactivated or removed.
try {
    $guardUser = auth_find_user_by_id((int)($_SESSION['user_id'] ?? 0));
    if (!$guardUser || (($guardUser['status'] ?? 'active') !== 'active')) {
        auth_logout();
        header('Location: ' . ag_base_url('user/login.php?error=' . urlencode('This account is no longer active. Please contact an administrator.')));
        exit;
    }
} catch (Throwable $e) {
    // Never lock out administrators because of a transient database error.
    auth_log_exception($e, 'admin_guard_status');
}

// 2. Strict Administrator-Only Gatekeeper
if (!function_exists('admin_require_full_admin')) {
    function admin_require_full_admin(string $deniedMessage = 'Access Denied: Full administrator privileges required.'): void
    {
        if (!auth_is_admin()) {
            $target = ag_base_url('admin/bookings.php?err=denied&msg=' . urlencode($deniedMessage));
            header('Location: ' . $target);
            exit;
        }
    }
}

if (!function_exists('admin_log')) {
    function admin_log(string $action, string $target = ''): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        try {
            ag_db()->prepare("INSERT INTO admin_logs (admin_id,action,target) VALUES (:a,:ac,:t)")
                   ->execute([':a' => $userId, ':ac' => $action, ':t' => $target ?: null]);
        } catch (Exception) {}
    }
}
