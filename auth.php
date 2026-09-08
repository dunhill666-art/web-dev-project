<?php
// ============================================================
// AeroGlide — auth.php
// Central Authentication Controller & Endpoint
// Handles API/Form actions: login, register, forgot, reset, logout
// ============================================================

require_once __DIR__ . '/auth_helper.php';

$action = $_REQUEST['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $redirect = $_POST['redirect'] ?? 'index.php';
        
        $res = auth_login($username, $password);
        if ($res['success']) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: login.php?error=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect));
            exit;
        }

    case 'register':
    case 'signup':
        $name     = $_POST['name'] ?? '';
        $email    = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $redirect = $_POST['redirect'] ?? 'index.php';

        $res = auth_register($name, $email, $username, $password);
        if ($res['success']) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: signup.php?error=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect));
            exit;
        }

    case 'forgot':
        $identity = $_POST['identity'] ?? '';
        $res = auth_forgot_password($identity);
        if ($res['success']) {
            header('Location: reset_password.php?token=' . urlencode($res['token']) . '&msg=' . urlencode('Reset code created for ' . $res['username'] . '. Enter your new password below.'));
            exit;
        } else {
            header('Location: forgot_password.php?error=' . urlencode($res['message']));
            exit;
        }

    case 'reset':
        $token       = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $res = auth_reset_password($token, $newPassword);
        if ($res['success']) {
            header('Location: login.php?msg=' . urlencode('Password updated successfully! Please log in with your new password.'));
            exit;
        } else {
            header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode($res['message']));
            exit;
        }

    case 'logout':
        auth_logout();
        header('Location: index.php?msg=' . urlencode('You have been logged out safely.'));
        exit;

    default:
        // Redirect to homepage or user status
        if (auth_is_logged_in()) {
            header('Location: index.php');
        } else {
            header('Location: login.php');
        }
        exit;
}
