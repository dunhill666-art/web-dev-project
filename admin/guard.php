<?php
// admin access check

require_once dirname(__DIR__) . '/user/auth_helper.php';

if (!auth_is_logged_in() || !auth_is_admin()) {
    header('Location: ' . ag_base_url('user/login.php?error=' . urlencode('Admin access only. Please log in with an administrator account.')));
    exit;
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
