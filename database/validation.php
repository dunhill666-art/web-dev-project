<?php
// input validation and sanitization

// sanitize text string
if (!function_exists('val_sanitize_string')) {
    function val_sanitize_string(string $input): string
    {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// check required field
if (!function_exists('validateRequired')) {
    function validateRequired(mixed $val, string $fieldName): ?string
    {
        if (empty(trim((string)($val ?? '')))) {
            return "{$fieldName} is required.";
        }
        return null;
    }
}

// validate email format
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

// validate username format
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

// validate password length
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

// validate login input structure
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

// validate signup input structure
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

// normalize a phone number for validation (does not modify stored/entered value)
if (!function_exists('val_normalize_phone')) {
    function val_normalize_phone(string $phone): string
    {
        return preg_replace('/[\s\-\(\)\.]/', '', trim($phone));
    }
}

// validate phone number (Philippine mobile formats: 09XXXXXXXXX or +639XXXXXXXXX)
if (!function_exists('val_phone')) {
    function val_phone(string $phone, bool $required = false): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return $required ? 'Phone number is required.' : null;
        }
        $clean = val_normalize_phone($phone);
        if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $clean)) {
            return 'Please enter a valid phone number (e.g. 09XXXXXXXXX or +639XXXXXXXXX).';
        }
        return null;
    }
}

// Luhn checksum used by payment card numbers
if (!function_exists('val_luhn')) {
    function val_luhn(string $digits): bool
    {
        $sum = 0;
        $double = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int)$digits[$i];
            if ($double) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $double = !$double;
        }
        return $digits !== '' && $sum % 10 === 0;
    }
}

// detect supported card network from the digits only
if (!function_exists('val_card_network')) {
    function val_card_network(string $digits): ?string
    {
        $len = strlen($digits);
        if ($len < 13 || $len > 19 || !ctype_digit($digits)) {
            return null;
        }

        // Visa: 13, 16 or 19 digits starting with 4
        if (preg_match('/^4\d{12}(\d{3})?(\d{3})?$/', $digits)) {
            return 'visa';
        }

        if ($len === 16) {
            $first2 = (int)substr($digits, 0, 2);
            $first4 = (int)substr($digits, 0, 4);
            $first6 = (int)substr($digits, 0, 6);

            // Mastercard: 51-55 or 2221-2720
            if (($first2 >= 51 && $first2 <= 55) || ($first4 >= 2221 && $first4 <= 2720)) {
                return 'mastercard';
            }

            // Discover: 6011, 65, 644-649, 622126-622925
            if (
                str_starts_with($digits, '6011')
                || str_starts_with($digits, '65')
                || ($first4 >= 6440 && $first4 <= 6499)
                || ($first6 >= 622126 && $first6 <= 622925)
            ) {
                return 'discover';
            }

            // JCB: 3528-3589
            if ($first4 >= 3528 && $first4 <= 3589) {
                return 'jcb';
            }
        }

        // American Express: 15 digits starting with 34 or 37
        if ($len === 15 && preg_match('/^3[47]\d{13}$/', $digits)) {
            return 'amex';
        }

        return null;
    }
}

// validate card number: allowed characters, supported format, Luhn checksum
if (!function_exists('val_card_number')) {
    function val_card_number(string $number): ?string
    {
        $number = trim($number);
        if ($number === '') {
            return 'Card number is required.';
        }
        if (!preg_match('/^[0-9 \-]+$/', $number)) {
            return 'Card number may only contain digits, spaces, or hyphens.';
        }

        $digits = preg_replace('/[^0-9]/', '', $number);
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return 'Please enter a valid card number (13-19 digits).';
        }
        if (val_card_network($digits) === null) {
            return 'Unsupported or invalid card number. Use a valid Visa, Mastercard, American Express, Discover, or JCB card.';
        }
        if (!val_luhn($digits)) {
            return 'Please enter a valid card number. The card number failed the checksum test.';
        }
        return null;
    }
}

// validate expiry date: strict MM/YY and must not be in the past
if (!function_exists('val_card_expiry')) {
    function val_card_expiry(string $expiry, ?DateTimeInterface $now = null): ?string
    {
        $expiry = trim($expiry);
        if ($expiry === '') {
            return 'Expiry date is required.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
            return 'Please enter a valid expiry date in MM/YY format (e.g. 08/29).';
        }

        $month = (int)$m[1];
        $year  = 2000 + (int)$m[2];
        $now   = $now ?? new DateTimeImmutable('now');
        $currentYear  = (int)$now->format('Y');
        $currentMonth = (int)$now->format('n');

        if ($year < $currentYear || ($year === $currentYear && $month < $currentMonth)) {
            return 'This card has expired. Please use a card with a future expiry date.';
        }
        if ($year > $currentYear + 20) {
            return 'Please enter a valid expiry year.';
        }
        return null;
    }
}

// validate CVV: 3 digits, or 4 digits for American Express
if (!function_exists('val_cvv')) {
    function val_cvv(string $cvv, string $cardNumber = ''): ?string
    {
        $cvv = trim($cvv);
        $cardDigits = preg_replace('/[^0-9]/', '', $cardNumber);
        $expected = (strlen($cardDigits) === 15 && preg_match('/^3[47]/', $cardDigits)) ? 4 : 3;

        if ($cvv === '') {
            return 'CVV is required.';
        }
        if (!preg_match('/^\d{' . $expected . '}$/', $cvv)) {
            return 'Please enter the ' . $expected . '-digit CVV security code.';
        }
        return null;
    }
}

// validate guest count
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

// check promo code validity
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
