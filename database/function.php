<?php
// ============================================================
// AeroGlide — database/function.php
// Global application helper functions and state management
// ============================================================

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Compute the project base web URL path dynamically.
 * Works regardless of execution directory (root, /Login, /User, /admin, etc.)
 */
if (!function_exists('ag_base_url')) {
    function ag_base_url(string $path = ''): string
    {
        static $base = null;
        if ($base === null) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/project/index.php');
            $dir = str_replace('\\', '/', dirname($script));
            $dir = preg_replace('#/(Login|User|admin|place|database)$#i', '', $dir);
            $base = rtrim($dir, '/');
            if ($base === '') {
                $base = '/project';
            }
        }
        $path = ltrim($path, '/');
        return $base . ($path !== '' ? '/' . $path : '');
    }
}

/**
 * Get current logged in user array or null
 */
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
            // Fallback
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

/**
 * Check if user is logged in
 */
if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

/**
 * Check if current user is an administrator
 */
if (!function_exists('auth_is_admin')) {
    function auth_is_admin(): bool
    {
        $u = auth_get_user();
        return ($u !== null && ($u['role'] ?? '') === 'admin');
    }
}

/**
 * Log in a user into session
 */
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
            // Ignore timestamp failure
        }
    }
}

/**
 * Log out current user
 */
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

/**
 * Record an admin action in admin_logs table
 */
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
            // Non-blocking log failure
        }
    }
}

if (!function_exists('asset_find')) {
    function asset_find(array $terms, ?array $fallbackTerms = null): string
    {
        static $files = null;
        if ($files === null) {
            $files = [];
            $collect = function (string $dir, string $relPrefix) use (&$collect, &$files): void {
                $entries = @scandir($dir);
                if (!$entries) return;
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') continue;
                    $full = $dir . '/' . $entry;
                    $rel  = $relPrefix === '' ? $entry : $relPrefix . '/' . $entry;
                    if (is_dir($full)) {
                        $collect($full, $rel);
                    } else if (is_file($full)) {
                        $stem = pathinfo($entry, PATHINFO_FILENAME);
                        $norm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $stem));
                        $files[] = [
                            'rel'  => $rel,
                            'name' => $entry,
                            'norm' => ' ' . trim($norm) . ' ',
                        ];
                    }
                }
            };
            $rootDir = dirname(__DIR__);
            foreach (['asset', 'assets'] as $folder) {
                if (is_dir($rootDir . '/' . $folder)) {
                    $collect($rootDir . '/' . $folder, $folder);
                }
            }
        }

        $search = function (array $kwList) use ($files): ?string {
            foreach ($kwList as $kw) {
                $kwNorm = strtolower(trim((string)$kw));
                if ($kwNorm === '') continue;
                foreach ($files as $f) {
                    if (str_contains($f['norm'], $kwNorm) || str_contains(strtolower($f['name']), $kwNorm)) {
                        $encodedRel = implode('/', array_map('rawurlencode', explode('/', $f['rel'])));
                        return ag_base_url($encodedRel);
                    }
                }
            }
            return null;
        };

        if ($found = $search($terms)) {
            return $found;
        }
        if ($fallbackTerms && ($found = $search($fallbackTerms))) {
            return $found;
        }

        return ag_base_url('asset/logo.png');
    }
}

/**
 * Format monetary amount in Philippine Pesos
 */
if (!function_exists('format_currency')) {
    function format_currency(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }
}

/**
 * Format datetime display string
 */
if (!function_exists('format_date')) {
    function format_date(string $datetime): string
    {
        return date('M j, Y — g:i A', strtotime($datetime));
    }
}
