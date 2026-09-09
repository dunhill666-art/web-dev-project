<?php

/* ---------- Small reusable validators ---------- */

function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "{$label} is required." : null;
}

function validateEmailFormat(string $email): ?string
{
    return filter_var($email, FILTER_VALIDATE_EMAIL)
        ? null
        : 'Please enter a valid email address.';
}

function validateIntRange($value, string $label, int $min, int $max): ?string
{
    if (!is_numeric($value) || (int) $value < $min || (int) $value > $max) {
        return "{$label} must be a number between {$min} and {$max}.";
    }
    return null;
}

function validatePasswordStrength(string $password): ?string
{
    return strlen($password) < 8
        ? 'Password must be at least 8 characters long.'
        : null;
}

/* ---------- Student registration form ---------- */

function validateStudentInput(array $post): array
{
    $username = trim($post['username'] ?? '');
    $email    = trim($post['email'] ?? '');
    $age      = $post['age'] ?? '';
    $password = $post['password'] ?? '';

    $errors = array_filter([
        validateRequired($username, 'Username'),
        validateRequired($email, 'Email'),
        validateEmailFormat($email),
        validateIntRange($age, 'Age', 1, 120),
        validatePasswordStrength($password),
    ]);
    $errors = array_values($errors);

    if (empty($errors)) {
        $username = htmlspecialchars($username);
        $age      = (int) $age;
    }

    return [
        'errors' => $errors,
        'data'   => [
            'username' => $username,
            'email'    => $email,
            'age'      => $age,
            'password' => $password,
        ],
    ];
}

/* ---------- Login form ---------- */

function validateLoginInput(array $post): array
{
    $username = trim($post['username'] ?? '');
    $password = $post['password'] ?? '';

    $errors = array_filter([
        validateRequired($username, 'Username'),
        validateRequired($password, 'Password'),
    ]);
    $errors = array_values($errors);

    return [
        'errors' => $errors,
        'data'   => ['username' => $username, 'password' => $password],
    ];
}