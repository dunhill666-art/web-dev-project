<?php
// ============================================================
// AeroGlide — auth.php
// Authentication Request Controller & Form Handler
// ============================================================

require_once __DIR__ . '/database/function.php';
require_once __DIR__ . '/database/validation.php';
require_once __DIR__ . '/auth_helper.php';

$action = $_REQUEST['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $redirect = $_POST['redirect'] ?? ag_base_url('index.php');
        $valResult = validateLoginInput($_POST);

        if (!empty($valResult['errors'])) {
            $msg = implode(' ', $valResult['errors']);
            header('Location: ' . ag_base_url('login.php?status=error&message=' . urlencode($msg) . '&redirect=' . urlencode($redirect)));
            exit;
        }

        $username = $valResult['data']['username'];
        $password = $valResult['data']['password'];

        $res = auth_login($username, $password);
        if ($res['success']) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: ' . ag_base_url('login.php?status=error&message=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect)));
            exit;
        }

    case 'register':
    case 'signup':
        $redirect = $_POST['redirect'] ?? ag_base_url('index.php');
        $valResult = validateSignupInput($_POST);

        if (!empty($valResult['errors'])) {
            $msg = implode(' ', $valResult['errors']);
            header('Location: ' . ag_base_url('signup.php?status=error&message=' . urlencode($msg) . '&redirect=' . urlencode($redirect)));
            exit;
        }

        $name     = $valResult['data']['name'];
        $email    = $valResult['data']['email'];
        $username = $valResult['data']['username'];
        $password = $valResult['data']['password'];

        $res = auth_register($name, $email, $username, $password);
        if ($res['success']) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: ' . ag_base_url('signup.php?status=error&message=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect)));
            exit;
        }

    case 'forgot':
        $identity = trim($_POST['identity'] ?? '');
        if (empty($identity)) {
            header('Location: ' . ag_base_url('forgot_password.php?error=' . urlencode('Please enter your email or username.')));
            exit;
        }

        $res = auth_forgot_password($identity);
        if ($res['success']) {
            header('Location: ' . ag_base_url('reset_password.php?token=' . urlencode($res['token']) . '&msg=' . urlencode('Reset link generated for ' . $res['username'] . '. Enter your new password below.')));
            exit;
        } else {
            header('Location: ' . ag_base_url('forgot_password.php?error=' . urlencode($res['message'])));
            exit;
        }

    case 'reset':
        $token       = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';

        $passErr = val_password($newPassword);
        if ($passErr) {
            header('Location: ' . ag_base_url('reset_password.php?token=' . urlencode($token) . '&error=' . urlencode($passErr)));
            exit;
        }

        $res = auth_reset_password($token, $newPassword);
        if ($res['success']) {
            header('Location: ' . ag_base_url('login.php?msg=' . urlencode('Password reset successful! Please log in with your new password.')));
            exit;
        } else {
            header('Location: ' . ag_base_url('reset_password.php?token=' . urlencode($token) . '&error=' . urlencode($res['message'])));
            exit;
        }

    case 'logout':
        auth_logout();
        header('Location: ' . ag_base_url('index.php?msg=' . urlencode('You have been logged out safely.')));
        exit;

    case 'cancel_booking':
    case 'cancel':
        if (!auth_is_logged_in()) {
            header('Location: ' . ag_base_url('login.php?redirect=my_bookings.php&msg=login_required'));
            exit;
        }
        $user = auth_get_user();
        $ref  = trim($_POST['booking_ref'] ?? $_GET['booking_ref'] ?? '');
        
        if (empty($ref)) {
            header('Location: ' . ag_base_url('my_bookings.php?error=' . urlencode('Missing booking reference code.')));
            exit;
        }

        $res = auth_cancel_user_booking($user['id'], $ref);
        if ($res['success']) {
            header('Location: ' . ag_base_url('my_bookings.php?msg=cancelled&ref=' . urlencode($res['bookingRef']) . '&refund=' . urlencode(number_format($res['refund']))));
            exit;
        } else {
            header('Location: ' . ag_base_url('my_bookings.php?error=' . urlencode($res['message'])));
            exit;
        }

    default:
        if (auth_is_logged_in()) {
            header('Location: ' . ag_base_url('index.php'));
        } else {
            header('Location: ' . ag_base_url('login.php'));
        }
        exit;
}
