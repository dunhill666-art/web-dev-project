<?php
// auth request handler

if (file_exists(__DIR__ . '/../database/function.php')) {
    require_once __DIR__ . '/../database/function.php';
    require_once __DIR__ . '/../database/validation.php';
} else {
    require_once __DIR__ . '/database/function.php';
    require_once __DIR__ . '/database/validation.php';
}
require_once __DIR__ . '/auth_helper.php';

$action = $_REQUEST['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $redirect = $_POST['redirect'] ?? ag_base_url('index.php');
        $valResult = validateLoginInput($_POST);

        if (!empty($valResult['errors'])) {
            $msg = implode(' ', $valResult['errors']);
            header('Location: ' . ag_base_url('user/login.php?status=error&message=' . urlencode($msg) . '&redirect=' . urlencode($redirect)));
            exit;
        }

        $username = $valResult['data']['username'];
        $password = $valResult['data']['password'];

        $res = auth_login($username, $password);
        if ($res['success']) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: ' . ag_base_url('user/login.php?status=error&message=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect)));
            exit;
        }

    case 'register':
    case 'signup':
        $redirect = $_POST['redirect'] ?? ag_base_url('index.php');
        $valResult = validateSignupInput($_POST);

        if (!empty($valResult['errors'])) {
            $msg = implode(' ', $valResult['errors']);
            header('Location: ' . ag_base_url('user/signup.php?status=error&message=' . urlencode($msg) . '&redirect=' . urlencode($redirect)));
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
            header('Location: ' . ag_base_url('user/signup.php?status=error&message=' . urlencode($res['message']) . '&redirect=' . urlencode($redirect)));
            exit;
        }

    case 'forgot':
        $identity = trim($_POST['identity'] ?? '');
        if (empty($identity)) {
            header('Location: ' . ag_base_url('user/forgot_password.php?error=' . urlencode('Please enter your email or username.')));
            exit;
        }

        $res = auth_forgot_password($identity);
        if ($res['success']) {
            header('Location: ' . ag_base_url('user/reset_password.php?token=' . urlencode($res['token']) . '&msg=' . urlencode('Reset link generated for ' . $res['username'] . '. Enter your new password below.')));
            exit;
        } else {
            header('Location: ' . ag_base_url('user/forgot_password.php?error=' . urlencode($res['message'])));
            exit;
        }

    case 'reset':
        $token       = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';

        $passErr = val_password($newPassword);
        if ($passErr) {
            header('Location: ' . ag_base_url('user/reset_password.php?token=' . urlencode($token) . '&error=' . urlencode($passErr)));
            exit;
        }

        $res = auth_reset_password($token, $newPassword);
        if ($res['success']) {
            header('Location: ' . ag_base_url('user/login.php?msg=' . urlencode('Password reset successful! Please log in with your new password.')));
            exit;
        } else {
            header('Location: ' . ag_base_url('user/reset_password.php?token=' . urlencode($token) . '&error=' . urlencode($res['message'])));
            exit;
        }

    case 'logout':
        auth_logout();
        header('Location: ' . ag_base_url('index.php?msg=' . urlencode('You have been logged out safely.')));
        exit;

    case 'request_cancel_booking':
    case 'cancel_booking':
    case 'cancel':
        if (!auth_is_logged_in()) {
            header('Location: ' . ag_base_url('user/login.php?redirect=' . urlencode(ag_base_url('user/my_bookings.php')) . '&msg=login_required'));
            exit;
        }
        $user   = auth_get_user();
        $ref    = trim($_POST['booking_ref'] ?? $_GET['booking_ref'] ?? '');
        $reason = trim($_POST['cancellation_reason'] ?? $_POST['reason'] ?? '');
        
        if (empty($ref)) {
            header('Location: ' . ag_base_url('user/my_bookings.php?error=' . urlencode('Missing booking reference code.')));
            exit;
        }

        // Security validation: Regular users can only transition to PENDING_CANCELLATION
        $res = auth_request_cancel_user_booking($user['id'], $ref, $reason);
        if ($res['success']) {
            header('Location: ' . ag_base_url('user/my_bookings.php?msg=pending_cancellation&ref=' . urlencode($res['bookingRef']) . '&refund=' . urlencode(number_format($res['refund']))));
            exit;
        } else {
            header('Location: ' . ag_base_url('user/my_bookings.php?error=' . urlencode($res['message'])));
            exit;
        }

    case 'admin_approve_cancellation':
        if (!auth_is_admin()) {
            header('Location: ' . ag_base_url('user/login.php?msg=' . urlencode('Unauthorized access.')));
            exit;
        }
        $admin = auth_get_user();
        $bId   = trim($_POST['booking_id'] ?? $_POST['booking_ref'] ?? '');
        $notes = trim($_POST['admin_notes'] ?? '');
        $res   = auth_admin_approve_cancellation($admin['id'], $bId, $notes);
        $redirect = $_POST['redirect'] ?? ag_base_url('admin/bookings.php?msg=' . urlencode($res['message']));
        header('Location: ' . $redirect);
        exit;

    case 'admin_reject_cancellation':
        if (!auth_is_admin()) {
            header('Location: ' . ag_base_url('user/login.php?msg=' . urlencode('Unauthorized access.')));
            exit;
        }
        $admin = auth_get_user();
        $bId   = trim($_POST['booking_id'] ?? $_POST['booking_ref'] ?? '');
        $notes = trim($_POST['admin_notes'] ?? '');
        $res   = auth_admin_reject_cancellation($admin['id'], $bId, $notes);
        $redirect = $_POST['redirect'] ?? ag_base_url('admin/bookings.php?msg=' . urlencode($res['message']));
        header('Location: ' . $redirect);
        exit;

    default:
        if (auth_is_logged_in()) {
            header('Location: ' . ag_base_url('index.php'));
        } else {
            header('Location: ' . ag_base_url('user/login.php'));
        }
        exit;
}
