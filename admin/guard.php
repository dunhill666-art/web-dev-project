<?php
// ============================================================
// AeroGlide — admin/guard.php
// Include at the top of every admin page.
// Redirects non-admins back to login.
// ============================================================
require_once dirname(__DIR__) . '/auth_helper.php';

if (!auth_is_logged_in() || !auth_is_admin()) {
    header('Location: ../login.php?error=' . urlencode('Admin access only. Please log in with an administrator account.'));
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
