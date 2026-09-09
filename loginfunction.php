<?php

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/validation.php';

$result = validateLoginInput($_POST);

if (!empty($result['errors'])) {
    $message = implode(' ', $result['errors']);

    header('Location: login.php?status=error&message=' . urlencode($message));
    exit;
}

$username = $result['data']['username'];
$password = $result['data']['password'];

try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare(
        'SELECT id, username, password FROM users WHERE username = ?'
    );

    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?status=error&message=' . urlencode('Invalid username or password.'));
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    header('Location: index.php');
    exit;
} catch (Throwable $e) {
    header('Location: login.php?status=error&message=' . urlencode('Database error: ' . $e->getMessage()));
    exit;
}