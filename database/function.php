<?php
// global helper functions

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// base url builder
if (!function_exists('ag_base_url')) {
    function ag_base_url(string $path = ''): string
    {
        static $base = null;
        if ($base === null) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $dir = str_replace('\\', '/', dirname($script));
            $dir = preg_replace('#/(Login|User|user|destinations|place|admin|database|api)$#i', '', $dir);
            $base = rtrim($dir, '/\\');
            if ($base === '.' || $base === '/') {
                $base = '';
            }
        }
        $path = ltrim($path, '/\\');
        return ($base !== '' ? $base : '') . '/' . $path;
    }
}


// get logged in user info
if (!function_exists('auth_get_user')) {
    function auth_get_user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        
        if (!empty($_SESSION['user_data'])) {
            return $_SESSION['user_data'];
        }

        try {
            $db = ag_db();
            $stmt = $db->prepare("SELECT id, username, name, email, role, used_coupon, created_at FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_data'] = $user;
                return $user;
            }
        } catch (Exception $e) {
            // fallback
        }

        return [
            'id'       => $_SESSION['user_id']    ?? null,
            'username' => $_SESSION['username']   ?? 'User',
            'name'     => $_SESSION['user_name']  ?? $_SESSION['username'] ?? 'Traveler',
            'email'    => $_SESSION['user_email'] ?? '',
            'role'     => $_SESSION['user_role']  ?? 'user',
        ];
    }
}

// check if user logged in
if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

// check admin role
if (!function_exists('auth_is_admin')) {
    function auth_is_admin(): bool
    {
        $u = auth_get_user();
        return ($u !== null && ($u['role'] ?? '') === 'admin');
    }
}

// login user and store session
if (!function_exists('auth_login_user')) {
    function auth_login_user(array $user): void
    {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user_data'] = $user;

        try {
            $db = ag_db();
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
        } catch (Exception $e) {
            // ignore timestamp failure
        }
    }
}

// logout and clear session
if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
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
}

// log admin action
if (!function_exists('admin_log')) {
    function admin_log(string $action, ?string $target = null): void
    {
        $user = auth_get_user();
        if (!$user || ($user['role'] ?? '') !== 'admin') return;

        try {
            $db = ag_db();
            $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target) VALUES (:aid, :act, :tgt)");
            $stmt->execute([
                ':aid' => $user['id'],
                ':act' => $action,
                ':tgt' => $target,
            ]);
        } catch (Exception $e) {
            // ignore failure
        }
    }
}

// asset path helper
if (!function_exists('asset_find')) {
    function asset_find(array $terms, ?array $fallbackTerms = null): string
    {
        if (function_exists('asset_index')) {
            $files = asset_index();
            foreach ($terms as $kw) {
                $kwNorm = strtolower(trim((string)$kw));
                if ($kwNorm === '') continue;
                foreach ($files as $f) {
                    if (strpos($f['norm'], ' ' . $kwNorm . ' ') !== false || strpos($f['norm'], $kwNorm) !== false || strpos($f['squash'], $kwNorm) !== false) {
                        return asset_url($f);
                    }
                }
            }
            if ($fallbackTerms) {
                foreach ($fallbackTerms as $kw) {
                    $kwNorm = strtolower(trim((string)$kw));
                    if ($kwNorm === '') continue;
                    foreach ($files as $f) {
                        if (strpos($f['norm'], $kwNorm) !== false || strpos($f['squash'], $kwNorm) !== false) {
                            return asset_url($f);
                        }
                    }
                }
            }
        }
        return ag_base_url('asset/logo.png');
    }
}


// format peso amount
if (!function_exists('format_currency')) {
    function format_currency(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }
}

// format date display
if (!function_exists('format_date')) {
    function format_date(string $datetime): string
    {
        return date('M j, Y — g:i A', strtotime($datetime));
    }
}
