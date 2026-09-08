<?php
// ============================================================
// AeroGlide — auth_helper.php
// User Authentication Helper: Session, Asset Lookup & DB
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Path to local user database JSON file.
 */
function auth_db_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'users_db.json';
}

/**
 * Load database array from JSON storage or initialize default accounts.
 */
function auth_load_db(): array
{
    $file = auth_db_path();
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            if (!isset($data['users'])) {
                // Migrate legacy user structure
                $data = ['users' => $data, 'tokens' => []];
            }
            return $data;
        }
    }

    // Default system data
    $defaultData = [
        'users' => [
            'demo' => [
                'id'       => 1,
                'name'     => 'Demo Traveler',
                'email'    => 'demo@aeroglide.com',
                'username' => 'demo',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ],
            'juan' => [
                'id'       => 2,
                'name'     => 'Juan Dela Cruz',
                'email'    => 'juan@example.com',
                'username' => 'juan',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ],
        ],
        'tokens' => []
    ];

    auth_save_db($defaultData);
    return $defaultData;
}

/**
 * Save database array to JSON storage.
 */
function auth_save_db(array $db): void
{
    file_put_contents(auth_db_path(), json_encode($db, JSON_PRETTY_PRINT));
}

/**
 * Load users list.
 */
function auth_load_users(): array
{
    $db = auth_load_db();
    return $db['users'] ?? [];
}

/**
 * Check if visitor is logged in.
 */
function auth_is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details.
 */
function auth_get_user(): ?array
{
    if (!auth_is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? 'User',
        'name'     => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Traveler',
        'email'    => $_SESSION['user_email'] ?? '',
    ];
}

/**
 * Get user record from database by user ID or username.
 */
function auth_find_user_record(mixed $userId): ?array
{
    $db = auth_load_db();
    $users = $db['users'] ?? [];
    foreach ($users as $key => $u) {
        if ((string)($u['id'] ?? '') === (string)$userId || (string)($u['username'] ?? '') === (string)$userId) {
            $u['_key'] = $key;
            return $u;
        }
    }
    return null;
}

/**
 * Get all booked trips for a specific user ID.
 */
function auth_get_user_bookings(mixed $userId): array
{
    $user = auth_find_user_record($userId);
    return $user['bookings'] ?? [];
}

/**
 * Add a completed trip booking to user history in DB.
 */
function auth_add_user_booking(mixed $userId, array $booking): void
{
    $db = auth_load_db();
    $user = auth_find_user_record($userId);
    if (!$user || !isset($user['_key'])) {
        return;
    }
    $key = $user['_key'];
    if (!isset($db['users'][$key]['bookings'])) {
        $db['users'][$key]['bookings'] = [];
    }
    array_unshift($db['users'][$key]['bookings'], $booking);
    auth_save_db($db);
}

/**
 * Check if the user has already claimed their 1 coupon limit.
 */
function auth_user_has_claimed_coupon(mixed $userId): ?string
{
    $user = auth_find_user_record($userId);
    if (!$user) {
        return null;
    }
    $used = $user['used_coupons'] ?? [];
    if (is_array($used) && !empty($used)) {
        return end($used);
    }
    if (is_string($used) && $used !== '') {
        return $used;
    }
    return null;
}

/**
 * Record a claimed coupon code for a user account in DB.
 */
function auth_record_user_coupon(mixed $userId, string $couponCode): void
{
    $db = auth_load_db();
    $user = auth_find_user_record($userId);
    if (!$user || !isset($user['_key'])) {
        return;
    }
    $key = $user['_key'];
    if (!isset($db['users'][$key]['used_coupons'])) {
        $db['users'][$key]['used_coupons'] = [];
    }
    if (is_string($db['users'][$key]['used_coupons'])) {
        $db['users'][$key]['used_coupons'] = [$db['users'][$key]['used_coupons']];
    }
    if (!in_array(strtoupper($couponCode), $db['users'][$key]['used_coupons'], true)) {
        $db['users'][$key]['used_coupons'][] = strtoupper($couponCode);
    }
    auth_save_db($db);
}

/**
 * Attempt user login.
 */
function auth_login(string $username, string $password): array
{
    $usernameKey = strtolower(trim($username));
    $users = auth_load_users();

    if (!isset($users[$usernameKey])) {
        // Also allow login by email
        $found = null;
        foreach ($users as $u) {
            if (isset($u['email']) && strtolower($u['email']) === $usernameKey) {
                $found = $u;
                break;
            }
        }
        if (!$found) {
            return ['success' => false, 'message' => 'Account not found. Please check your credentials or sign up.'];
        }
        $user = $found;
    } else {
        $user = $users[$usernameKey];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    return ['success' => true, 'user' => $user];
}

/**
 * Register a new user account.
 */
function auth_register(string $name, string $email, string $username, string $password): array
{
    $name     = trim($name);
    $email    = trim($email);
    $username = trim($username);
    $key      = strtolower($username);

    if ($name === '') {
        return ['success' => false, 'message' => 'Full name is required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Valid email address is required.'];
    }
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    $db = auth_load_db();
    $users = $db['users'] ?? [];

    if (isset($users[$key])) {
        return ['success' => false, 'message' => 'Username already taken. Please choose another username.'];
    }

    foreach ($users as $u) {
        if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
            return ['success' => false, 'message' => 'Email address already registered. Please log in instead.'];
        }
    }

    $newUser = [
        'id'       => time(),
        'name'     => $name,
        'email'    => $email,
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ];

    $db['users'][$key] = $newUser;
    auth_save_db($db);

    // Auto log in after registration
    session_regenerate_id(true);
    $_SESSION['user_id']    = $newUser['id'];
    $_SESSION['username']   = $newUser['username'];
    $_SESSION['user_name']  = $newUser['name'];
    $_SESSION['user_email'] = $newUser['email'];

    return ['success' => true, 'user' => $newUser];
}

/**
 * Request password reset (Forgot Password).
 */
function auth_forgot_password(string $identity): array
{
    $identity = strtolower(trim($identity));
    if ($identity === '') {
        return ['success' => false, 'message' => 'Please enter your email address or username.'];
    }

    $db = auth_load_db();
    $users = $db['users'] ?? [];
    $targetUserKey = null;

    if (isset($users[$identity])) {
        $targetUserKey = $identity;
    } else {
        foreach ($users as $key => $u) {
            if (isset($u['email']) && strtolower($u['email']) === $identity) {
                $targetUserKey = $key;
                break;
            }
        }
    }

    if (!$targetUserKey) {
        return ['success' => false, 'message' => 'No account found matching that email or username.'];
    }

    // Generate token
    $token = bin2hex(random_bytes(16));
    $db['tokens'][$token] = [
        'user_key' => $targetUserKey,
        'expires'  => time() + (3600 * 2), // 2 hours
    ];

    auth_save_db($db);

    return [
        'success'  => true,
        'token'    => $token,
        'username' => $users[$targetUserKey]['username'],
        'email'    => $users[$targetUserKey]['email'],
        'message'  => 'Password reset request generated successfully.'
    ];
}

/**
 * Reset password using token.
 */
function auth_reset_password(string $token, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $db = auth_load_db();
    $tokens = $db['tokens'] ?? [];

    if (!isset($tokens[$token])) {
        return ['success' => false, 'message' => 'Invalid or expired password reset code.'];
    }

    $tokenData = $tokens[$token];
    if (time() > $tokenData['expires']) {
        unset($db['tokens'][$token]);
        auth_save_db($db);
        return ['success' => false, 'message' => 'Reset link has expired. Please request a new one.'];
    }

    $userKey = $tokenData['user_key'];
    if (!isset($db['users'][$userKey])) {
        return ['success' => false, 'message' => 'Associated user account not found.'];
    }

    // Update password
    $db['users'][$userKey]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Invalidate used token
    unset($db['tokens'][$token]);
    auth_save_db($db);

    return ['success' => true, 'message' => 'Password updated successfully. You can now log in.'];
}

/**
 * Log out current user.
 */
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

// ============================================================
// ASSET FINDER HELPERS (GLOBAL FALLBACK)
// ============================================================

if (!function_exists('asset_index')) {
    function asset_index(): array
    {
        static $files = null;
        if ($files !== null) {
            return $files;
        }

        $files = [];
        $collect = function (string $dir, string $relPrefix) use (&$collect, &$files): void {
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $dir . DIRECTORY_SEPARATOR . $entry;
                $rel  = $relPrefix === '' ? $entry : $relPrefix . '/' . $entry;

                if (is_dir($full)) {
                    $collect($full, $rel);
                    continue;
                }
                if (!is_file($full)) {
                    continue;
                }

                $stem = pathinfo($entry, PATHINFO_FILENAME);
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $stem));
                $files[] = [
                    'rel'  => $rel,
                    'name' => $entry,
                    'norm' => ' ' . trim($norm) . ' ',
                ];
            }
        };

        foreach (['asset', 'assets'] as $folder) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . $folder;
            if (is_dir($dir)) {
                $collect($dir, $folder);
            }
        }
        return $files;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(array $file): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $file['rel'])));
    }
}

if (!function_exists('asset_placeholder')) {
    function asset_placeholder(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs><rect width="900" height="600" fill="url(#g)"/></svg>';
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }
}

if (!function_exists('asset_find')) {
    function asset_find(array $keywords): string
    {
        $files = asset_index();
        foreach ($files as $f) {
            $ok = true;
            foreach ($keywords as $k) {
                if (strpos($f['norm'], strtolower($k)) === false) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return asset_url($f);
            }
        }
        return asset_placeholder();
    }
}

