<?php
// ============================================================
// AeroGlide — database/validation.php
// Robust Input Validation & Sanitization Module
// Follows Professor Bensing's validation structure
// ============================================================

if (!function_exists('val_sanitize_string')) {
    function val_sanitize_string(string $input): string
    {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('validateRequired')) {
    function validateRequired(mixed $val, string $fieldName): ?string
    {
        if (empty(trim((string)($val ?? '')))) {
            return "{$fieldName} is required.";
        }
        return null;
    }
}

if (!function_exists('val_email')) {
    function val_email(string $email): ?string
    {
        $email = trim($email);
        if (empty($email)) {
            return "Email address is required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Please enter a valid email address (e.g., user@example.com).";
        }
        return null;
    }
}

if (!function_exists('val_username')) {
    function val_username(string $username): ?string
    {
        $username = trim($username);
        if (empty($username)) {
            return "Username is required.";
        }
        if (strlen($username) < 3 || strlen($username) > 30) {
            return "Username must be between 3 and 30 characters long.";
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return "Username can only contain letters, numbers, and underscores.";
        }
        return null;
    }
}

if (!function_exists('val_password')) {
    function val_password(string $password): ?string
    {
        if (empty($password)) {
            return "Password is required.";
        }
        if (strlen($password) < 6) {
            return "Password must be at least 6 characters long.";
        }
        return null;
    }
}

/**
 * Professor Bensing's exact Login validation structure returning ['errors', 'data']
 */
if (!function_exists('validateLoginInput')) {
    function validateLoginInput(array $post): array
    {
        $username = trim($post['username'] ?? '');
        $password = $post['password'] ?? '';

        $errors = array_filter([
            validateRequired($username, 'Username or Email'),
            validateRequired($password, 'Password'),
        ]);
        $errors = array_values($errors);

        return [
            'errors' => $errors,
            'data'   => ['username' => $username, 'password' => $password],
        ];
    }
}

/**
 * Professor Bensing's exact Registration validation structure returning ['errors', 'data']
 */
if (!function_exists('validateSignupInput')) {
    function validateSignupInput(array $post): array
    {
        $name     = trim($post['name'] ?? '');
        $email    = trim($post['email'] ?? '');
        $username = trim($post['username'] ?? '');
        $password = $post['password'] ?? '';

        $errors = array_filter([
            validateRequired($name, 'Full Name'),
            val_email($email),
            val_username($username),
            val_password($password),
        ]);
        $errors = array_values($errors);

        return [
            'errors' => $errors,
            'data'   => [
                'name'     => $name,
                'email'    => $email,
                'username' => $username,
                'password' => $password,
            ],
        ];
    }
}

if (!function_exists('val_phone')) {
    function val_phone(string $phone): ?string
    {
        $phone = trim($phone);
        if (empty($phone)) {
            return null;
        }
        $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!preg_match('/^(09|\+639)\d{9}$/', $clean));
        return null;
    }
}

if (!function_exists('val_guest_count')) {
    function val_guest_count(int $count): ?string
    {
        if ($count < 1) {
            return "Guest count must be at least 1 person.";
        }
        if ($count > 20) {
            return "For bookings exceeding 20 guests, please contact group sales support.";
        }
        return null;
    }
}

if (!function_exists('val_coupon_eligibility')) {
    function val_coupon_eligibility(string $code, int $guestCount, ?string $userUsedCoupon = null): array
    {
        $code = strtoupper(trim($code));

        if (empty($code)) {
            return ['valid' => false, 'message' => 'No promo code entered.', 'discount' => 0];
        }

        if (!empty($userUsedCoupon)) {
            return [
                'valid' => false,
                'message' => "You have already claimed a promo code ('{$userUsedCoupon}') on your account. Maximum 1 coupon per account.",
                'discount' => 0
            ];
        }

        $coupons = [
            '5PEOPLE2026' => [
                'type' => 'fixed',
                'amount' => 2000,
                'label' => '5PEOPLE2026 Group Deal (₱2,000 OFF)',
                'min_guests' => 5
            ],
            'AERO500' => [
                'type' => 'fixed',
                'amount' => 500,
                'label' => 'AeroGlide Welcome Deal (₱500 OFF)',
                'min_guests' => 1
            ],
            'FLIGHT10' => [
                'type' => 'percent',
                'amount' => 0.10,
                'label' => 'Aero10 Exclusive Deal (10% OFF)',
                'min_guests' => 1
            ],
        ];

        if (!isset($coupons[$code])) {
            return ['valid' => false, 'message' => "Invalid coupon code '{$code}'. Please check for typos.", 'discount' => 0];
        }

        $c = $coupons[$code];

        if ($c['min_guests'] > 1 && $guestCount < $c['min_guests']) {
            return [
                'valid' => false,
                'message' => "Promo code '{$code}' requires a minimum of {$c['min_guests']} guests. You currently selected {$guestCount} guest(s).",
                'discount' => 0
            ];
        }

        return [
            'valid' => true,
            'code' => $code,
            'label' => $c['label'],
            'type' => $c['type'],
            'amount' => $c['amount'],
            'message' => "Coupon '{$code}' applied successfully!"
        ];
    }
}
