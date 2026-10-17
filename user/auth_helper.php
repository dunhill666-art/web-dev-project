<?php
// user auth and booking helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../database/config.php')) {
    require_once __DIR__ . '/../database/config.php';
    require_once __DIR__ . '/../database/function.php';
    require_once __DIR__ . '/../database/validation.php';
} elseif (file_exists(__DIR__ . '/database/config.php')) {
    require_once __DIR__ . '/database/config.php';
    require_once __DIR__ . '/database/function.php';
    require_once __DIR__ . '/database/validation.php';
} else {
    require_once dirname(__DIR__) . '/database/config.php';
    require_once dirname(__DIR__) . '/database/function.php';
    require_once dirname(__DIR__) . '/database/validation.php';
}

// session helpers
if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('auth_get_user')) {
    function auth_get_user(): ?array
    {
        if (!auth_is_logged_in()) return null;
        
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
            // session fallback
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

if (!function_exists('auth_is_admin')) {
    function auth_is_admin(): bool
    {
        $u = auth_get_user();
        return ($u !== null && ($u['role'] ?? '') === 'admin');
    }
}

// user lookups
if (!function_exists('auth_find_user_by_id')) {
    function auth_find_user_by_id(int $id): ?array
    {
        $stmt = ag_db()->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('auth_find_user_by_username')) {
    function auth_find_user_by_username(string $username): ?array
    {
        $stmt = ag_db()->prepare("SELECT * FROM users WHERE username=:u LIMIT 1");
        $stmt->execute([':u' => strtolower(trim($username))]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('auth_find_user_by_email')) {
    function auth_find_user_by_email(string $email): ?array
    {
        $stmt = ag_db()->prepare("SELECT * FROM users WHERE email=:e LIMIT 1");
        $stmt->execute([':e' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }
}

// booking functions
if (!function_exists('auth_get_user_bookings')) {
    function auth_get_user_bookings(mixed $userId): array
    {
        $stmt = ag_db()->prepare("SELECT * FROM bookings WHERE user_id=:uid ORDER BY booking_date DESC");
        $stmt->execute([':uid' => (int)$userId]);
        $rows = $stmt->fetchAll();

        // format booking keys
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'                      => $r['id'],
                'bookingRef'              => $r['booking_ref'],
                'bookingDate'             => $r['booking_date'],
                'travelerName'            => $r['traveler_name'],
                'travelerEmail'           => $r['traveler_email'],
                'travelerPhone'           => $r['traveler_phone'],
                'city'                    => $r['city'],
                'mode'                    => $r['mode'],
                'adults'                  => (int)$r['adults'],
                'cabin'                   => $r['cabin'],
                'fareName'                => $r['fare_name'],
                'fareDesc'                => $r['fare_desc'],
                'flightSchedule'          => $r['flight_schedule'],
                'hotelName'               => $r['hotel_name'],
                'hotelPrice'              => (float)$r['hotel_price'],
                'nights'                  => (int)$r['nights'],
                'carName'                 => $r['car_name'],
                'carPrice'                => (float)$r['car_price'],
                'days'                    => (int)$r['days'],
                'subTotal'                => (float)$r['subtotal'],
                'discountNum'             => (float)$r['discount_num'],
                'discountLabel'           => $r['discount_label'],
                'grandTotal'              => (float)$r['grand_total'],
                'cancellationFee'         => (float)($r['cancellation_fee'] ?? 0),
                'refundAmount'            => (float)($r['refund_amount'] ?? 0),
                'cancelledAt'             => $r['cancelled_at'] ?? null,
                'cancellationReason'      => $r['cancellation_reason'] ?? '',
                'adminNotes'              => $r['admin_notes'] ?? '',
                'cancellationRequestedAt' => $r['cancellation_requested_at'] ?? null,
                'promoCode'               => $r['promo_code'],
                'paymentMethod'           => $r['payment_method'],
                'status'                  => $r['status'],
            ];
        }
        return $out;
    }
}

// User Requests Cancellation -> Sets status to PENDING_CANCELLATION
if (!function_exists('auth_request_cancel_user_booking')) {
    function auth_request_cancel_user_booking(mixed $userId, string $bookingRef, ?string $reason = null): array
    {
        $db = ag_db();
        $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id = :uid AND booking_ref = :ref LIMIT 1");
        $stmt->execute([':uid' => (int)$userId, ':ref' => trim($bookingRef)]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking reference not found in your account history.'];
        }

        $currentStatus = strtolower($booking['status'] ?? 'confirmed');
        if ($currentStatus === 'cancelled') {
            return ['success' => false, 'message' => 'This reservation has already been cancelled.'];
        }
        if ($currentStatus === 'pending_cancellation') {
            return ['success' => false, 'message' => 'A cancellation request for this trip is already pending admin review.'];
        }

        $grandTotal = (float)($booking['grand_total'] ?? 0);
        $cancellationFee = min(500.00, $grandTotal);
        $refundAmount    = max(0.00, $grandTotal - $cancellationFee);

        $reqReason = !empty($reason) ? trim($reason) : 'Customer requested trip cancellation via dashboard.';

        $up = $db->prepare("
            UPDATE bookings 
            SET status = 'pending_cancellation', 
                cancellation_reason = :reason, 
                cancellation_requested_at = NOW()
            WHERE id = :bid
        ");
        $up->execute([
            ':reason' => $reqReason,
            ':bid'    => $booking['id']
        ]);

        if (function_exists('admin_log')) {
            admin_log("User #{$userId} requested cancellation for booking #{$bookingRef} (Pending Admin Approval)", "booking:{$booking['id']}");
        }

        return [
            'success'     => true,
            'message'     => 'Cancellation request submitted. Your request is now pending admin approval.',
            'bookingRef'  => $bookingRef,
            'fee'         => $cancellationFee,
            'refund'      => $refundAmount,
            'status'      => 'pending_cancellation'
        ];
    }
}

// Admin Approves Cancellation -> Status CANCELLED + Automated Refund Calculation
if (!function_exists('auth_admin_approve_cancellation')) {
    function auth_admin_approve_cancellation(int $adminId, int|string $bookingIdentifier, ?string $adminNotes = null): array
    {
        $db = ag_db();
        if (is_numeric($bookingIdentifier)) {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$bookingIdentifier]);
        } else {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_ref = :ref LIMIT 1");
            $stmt->execute([':ref' => trim($bookingIdentifier)]);
        }
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        $grandTotal      = (float)($booking['grand_total'] ?? 0);
        $cancellationFee = min(500.00, $grandTotal);
        $refundAmount    = max(0.00, $grandTotal - $cancellationFee);
        $cancelledAt     = date('Y-m-d H:i:s');
        $notes           = !empty($adminNotes) ? trim($adminNotes) : 'Cancellation approved by Admin. Refund processed.';

        $up = $db->prepare("
            UPDATE bookings 
            SET status = 'cancelled', 
                cancellation_fee = :fee, 
                refund_amount = :refund, 
                cancelled_at = :cat,
                admin_notes = :notes
            WHERE id = :bid
        ");
        $up->execute([
            ':fee'    => $cancellationFee,
            ':refund' => $refundAmount,
            ':cat'    => $cancelledAt,
            ':notes'  => $notes,
            ':bid'    => $booking['id']
        ]);

        if (function_exists('admin_log')) {
            admin_log("Admin #{$adminId} approved cancellation for booking #{$booking['booking_ref']}. Fee: P{$cancellationFee}, Refund: P{$refundAmount}", "booking:{$booking['id']}");
        }

        return [
            'success'     => true,
            'message'     => 'Cancellation successfully approved. Refund has been recorded.',
            'bookingRef'  => $booking['booking_ref'],
            'fee'         => $cancellationFee,
            'refund'      => $refundAmount,
            'cancelledAt' => $cancelledAt
        ];
    }
}

// Admin Rejects / Resolves Issue -> Status reverts to CONFIRMED with admin note
if (!function_exists('auth_admin_reject_cancellation')) {
    function auth_admin_reject_cancellation(int $adminId, int|string $bookingIdentifier, ?string $adminNotes = null): array
    {
        $db = ag_db();
        if (is_numeric($bookingIdentifier)) {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$bookingIdentifier]);
        } else {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_ref = :ref LIMIT 1");
            $stmt->execute([':ref' => trim($bookingIdentifier)]);
        }
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        $notes = !empty($adminNotes) ? trim($adminNotes) : 'Cancellation request rejected by Admin.';

        $up = $db->prepare("
            UPDATE bookings 
            SET status = 'confirmed', 
                admin_notes = :notes
            WHERE id = :bid
        ");
        $up->execute([
            ':notes'  => $notes,
            ':bid'    => $booking['id']
        ]);

        if (function_exists('admin_log')) {
            admin_log("Admin #{$adminId} rejected cancellation request for booking #{$booking['booking_ref']}. Reason: {$notes}", "booking:{$booking['id']}");
        }

        return [
            'success'     => true,
            'message'     => 'Cancellation request rejected. Booking status has been restored to Confirmed.',
            'bookingRef'  => $booking['booking_ref']
        ];
    }
}

// Legacy fallback alias for backward compatibility
if (!function_exists('auth_cancel_user_booking')) {
    function auth_cancel_user_booking(mixed $userId, string $bookingRef): array
    {
        return auth_request_cancel_user_booking($userId, $bookingRef);
    }
}

if (!function_exists('auth_add_user_booking')) {
    function auth_add_user_booking(mixed $userId, array $b): void
    {
        $stmt = ag_db()->prepare("
            INSERT INTO bookings
            (user_id,booking_ref,booking_date,traveler_name,traveler_email,traveler_phone,
             city,mode,adults,cabin,fare_name,fare_desc,flight_schedule,
             hotel_name,hotel_price,nights,car_name,car_price,days,
             subtotal,discount_num,discount_label,grand_total,promo_code,payment_method,status)
            VALUES
            (:uid,:ref,:dt,:tn,:te,:tp,
             :city,:mode,:adults,:cabin,:fn,:fd,:fs,
             :hn,:hp,:ni,:cn,:cp,:da,
             :sub,:disc,:dlbl,:gt,:promo,:pay,:status)
        ");
        $stmt->execute([
            ':uid'    => (int)$userId,
            ':ref'    => $b['bookingRef']     ?? ('AG-' . strtoupper(bin2hex(random_bytes(4)))),
            ':dt'     => $b['bookingDate']    ?? date('Y-m-d H:i:s'),
            ':tn'     => $b['travelerName']   ?? '',
            ':te'     => $b['travelerEmail']  ?? '',
            ':tp'     => $b['travelerPhone']  ?? null,
            ':city'   => $b['city']           ?? '',
            ':mode'   => $b['mode']           ?? 'flights',
            ':adults' => $b['adults']         ?? 1,
            ':cabin'  => $b['cabin']          ?? 'Economy',
            ':fn'     => $b['fareName']       ?? null,
            ':fd'     => $b['fareDesc']       ?? null,
            ':fs'     => $b['flightSchedule'] ?? null,
            ':hn'     => $b['hotelName']      ?? null,
            ':hp'     => $b['hotelPrice']     ?? 0,
            ':ni'     => $b['nights']         ?? 0,
            ':cn'     => $b['carName']        ?? null,
            ':cp'     => $b['carPrice']       ?? 0,
            ':da'     => $b['days']           ?? 0,
            ':sub'    => $b['subTotal']       ?? 0,
            ':disc'   => $b['discountNum']    ?? 0,
            ':dlbl'   => $b['discountLabel']  ?? null,
            ':gt'     => $b['grandTotal']     ?? 0,
            ':promo'  => $b['promoCode']      ?? null,
            ':pay'    => $b['paymentMethod']  ?? null,
            ':status' => $b['status']         ?? 'confirmed',
        ]);
    }
}

// coupon helpers
if (!function_exists('auth_user_has_claimed_coupon')) {
    function auth_user_has_claimed_coupon(mixed $userId): ?string
    {
        $stmt = ag_db()->prepare("SELECT used_coupon FROM users WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => (int)$userId]);
        $row = $stmt->fetch();
        return ($row && $row['used_coupon'] !== null && $row['used_coupon'] !== '') ? $row['used_coupon'] : null;
    }
}

if (!function_exists('auth_record_user_coupon')) {
    function auth_record_user_coupon(mixed $userId, string $couponCode): void
    {
        $stmt = ag_db()->prepare("UPDATE users SET used_coupon=:c WHERE id=:id");
        $stmt->execute([':c' => strtoupper($couponCode), ':id' => (int)$userId]);
    }
}

// auth actions
if (!function_exists('auth_login')) {
    function auth_login(string $username, string $password): array
    {
        $username = trim($username);
        $user = auth_find_user_by_username($username) ?? auth_find_user_by_email($username);

        if (!$user) {
            return ['success' => false, 'message' => 'Account not found. Please check your credentials or sign up.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
        }

        // update timestamp
        ag_db()->prepare("UPDATE users SET last_login=NOW() WHERE id=:id")->execute([':id' => $user['id']]);

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_data']  = $user;

        return ['success' => true, 'user' => $user];
    }
}

if (!function_exists('auth_register')) {
    function auth_register(string $name, string $email, string $username, string $password): array
    {
        $name     = trim($name);
        $email    = trim($email);
        $username = strtolower(trim($username));

        if ($name === '')    return ['success' => false, 'message' => 'Full name is required.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Valid email address is required.'];
        if (strlen($username) < 3) return ['success' => false, 'message' => 'Username must be at least 3 characters.'];
        if (strlen($password) < 6) return ['success' => false, 'message' => 'Password must be at least 6 characters.'];

        if (auth_find_user_by_username($username)) {
            return ['success' => false, 'message' => 'Username already taken. Please choose another username.'];
        }
        if (auth_find_user_by_email($email)) {
            return ['success' => false, 'message' => 'Email address already registered. Please log in instead.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = ag_db()->prepare("INSERT INTO users (username,name,email,password,role) VALUES (:u,:n,:e,:p,'user')");
        $stmt->execute([':u' => $username, ':n' => $name, ':e' => $email, ':p' => $hash]);
        $newId = (int)ag_db()->lastInsertId();

        $userData = ['id' => $newId, 'username' => $username, 'name' => $name, 'email' => $email, 'role' => 'user'];

        session_regenerate_id(true);
        $_SESSION['user_id']    = $newId;
        $_SESSION['username']   = $username;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'user';
        $_SESSION['user_data']  = $userData;

        return ['success' => true, 'user' => $userData];
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

// password reset
if (!function_exists('auth_forgot_password')) {
    function auth_forgot_password(string $identity): array
    {
        $identity = strtolower(trim($identity));
        if ($identity === '') {
            return ['success' => false, 'message' => 'Please enter your email address or username.'];
        }

        $user = auth_find_user_by_username($identity) ?? auth_find_user_by_email($identity);
        if (!$user) {
            return ['success' => false, 'message' => 'No account found matching that email or username.'];
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 7200);

        $stmt = ag_db()->prepare("INSERT INTO password_tokens (user_id,token,expires_at) VALUES (:uid,:tok,:exp)");
        $stmt->execute([':uid' => $user['id'], ':tok' => $token, ':exp' => $expires]);

        return [
            'success'  => true,
            'token'    => $token,
            'username' => $user['username'],
            'email'    => $user['email'],
            'message'  => 'Password reset request generated successfully.',
        ];
    }
}

if (!function_exists('auth_reset_password')) {
    function auth_reset_password(string $token, string $newPassword): array
    {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        $stmt = ag_db()->prepare("SELECT * FROM password_tokens WHERE token=:tok AND used=0 LIMIT 1");
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'message' => 'Invalid or expired password reset code.'];
        }
        if (strtotime($row['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Reset link has expired. Please request a new one.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        ag_db()->prepare("UPDATE users SET password=:p WHERE id=:id")->execute([':p' => $hash, ':id' => $row['user_id']]);
        ag_db()->prepare("UPDATE password_tokens SET used=1 WHERE id=:id")->execute([':id' => $row['id']]);

        return ['success' => true, 'message' => 'Password updated successfully. You can now log in.'];
    }
}

// asset finder helpers
// asset finder helpers
if (!function_exists('asset_index')) {
    function asset_index(): array
    {
        static $files = null;
        if ($files !== null) return $files;

        $files = [];
        $collect = function (string $dir, string $relPrefix) use (&$collect, &$files): void {
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $full = $dir . DIRECTORY_SEPARATOR . $entry;
                $rel  = $relPrefix === '' ? $entry : $relPrefix . '/' . $entry;
                if (is_dir($full)) { $collect($full, $rel); continue; }
                if (!is_file($full)) continue;
                $stem = pathinfo($entry, PATHINFO_FILENAME);
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $stem));
                $squash = strtolower(preg_replace('/[^a-z0-9]+/i', '', $stem));
                $files[] = [
                    'rel'    => $rel,
                    'name'   => $entry,
                    'norm'   => ' ' . trim($norm) . ' ',
                    'squash' => $squash
                ];
            }
        };

        $rootDir = dirname(__DIR__);
        if (!is_dir($rootDir . DIRECTORY_SEPARATOR . 'asset') && is_dir(__DIR__ . '/../asset')) {
            $rootDir = dirname(__DIR__);
        }
        foreach (['asset', 'assets'] as $folder) {
            $dir = $rootDir . DIRECTORY_SEPARATOR . $folder;
            if (is_dir($dir)) $collect($dir, $folder);
        }

        return $files;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(array|string $file): string
    {
        $rel = is_array($file) ? ($file['rel'] ?? '') : $file;
        $clean = str_replace('\\', '/', $rel);
        $clean = ltrim($clean, '/');
        $parts = explode('/', $clean);
        $encoded = implode('/', array_map('rawurlencode', $parts));
        return ag_base_url($encoded);
    }
}

if (!function_exists('asset_placeholder')) {
    function asset_placeholder(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs><rect width="900" height="600" fill="url(#g)"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="28" font-weight="700" fill="#0284c7">AeroGlide</text></svg>';
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }
}

if (!function_exists('asset_find')) {
    function asset_find(array $keywords, ?array $fallbackTerms = null): string
    {
        $files = asset_index();
        foreach ($keywords as $kw) {
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
        return ag_base_url('asset/logo.png');
    }
}

