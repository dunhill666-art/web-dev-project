<?php
// ============================================================
// AeroGlide — reset_password.php
// Password Reset Confirmation Form & Handler
// ============================================================

require_once __DIR__ . '/database/function.php';
require_once __DIR__ . '/database/validation.php';
require_once __DIR__ . '/auth_helper.php';

$token   = $_REQUEST['token'] ?? '';
$error   = $_GET['error'] ?? '';
$success = $_GET['msg']   ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token       = $_POST['token'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    $passErr = val_password($newPassword);

    if ($newPassword !== $confirm) {
        $error = 'Passwords do not match. Please verify and re-type.';
    } elseif ($passErr) {
        $error = $passErr;
    } else {
        $res = auth_reset_password($token, $newPassword);
        if ($res['success']) {
            header('Location: ' . ag_base_url('login.php?msg=' . urlencode('Password updated successfully! You can now log in.')));
            exit;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ag_base_url('style.css') ?>">
<style>
  :root{
    --ag-sky-1:#eaf4fe;
    --ag-sky-2:#cfe7fb;
    --ag-blue-1:#0d6efd;
    --ag-blue-2:#0a4fa0;
    --ag-ink:#0a1425;
    --ag-muted:#5b6b7f;
    --ag-line:#e3e9f2;
    --ag-card:#ffffff;
    --ag-shadow:0 20px 50px rgba(10, 30, 60, 0.12);
  }

  body.auth-page {
    min-height: 100vh;
    background:
      radial-gradient(1200px 500px at 80% -10%, var(--ag-sky-2), transparent 60%),
      linear-gradient(168deg, #eef6ff 0%, #dcedfb 46%, #d2e7fa 100%);
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--ag-ink);
    display: flex;
    flex-direction: column;
  }

  .auth-nav {
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
  }

  .auth-main-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem 4rem;
  }

  .auth-card {
    width: 100%;
    max-width: 440px;
    background: #ffffff;
    border-radius: 24px;
    padding: 2.5rem 2.25rem;
    box-shadow: var(--ag-shadow);
    border: 1px solid rgba(255, 255, 255, 0.8);
  }

  .auth-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .auth-brand-logo {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    margin-bottom: 0.75rem;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
  }

  .auth-title {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: 1.6rem;
    color: var(--ag-ink);
    margin-bottom: 0.35rem;
  }

  .auth-subtitle {
    font-size: 0.9rem;
    color: var(--ag-muted);
    line-height: 1.4;
  }

  .auth-alert {
    padding: 0.85rem 1.1rem;
    border-radius: 14px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    line-height: 1.4;
  }

  .auth-alert-error {
    background: #fdeceb;
    color: #8a1f14;
    border: 1px solid #f4b8b3;
  }

  .auth-alert-info {
    background: #eef5ff;
    color: var(--ag-blue-2);
    border: 1px solid #bcd8f3;
  }

  .auth-form-group {
    margin-bottom: 1.25rem;
  }

  .auth-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--ag-ink);
  }

  .auth-input {
    width: 100%;
    padding: 0.85rem 1.1rem;
    border: 1.5px solid var(--ag-line);
    border-radius: 14px;
    font-family: inherit;
    font-size: 0.95rem;
    color: var(--ag-ink);
    background: #fbfdff;
    transition: all 0.2s ease;
    box-sizing: border-box;
  }

  .auth-input:focus {
    outline: none;
    border-color: var(--ag-blue-1);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
  }

  .btn-auth-submit {
    width: 100%;
    margin-top: 1rem;
    padding: 0.95rem;
    background: linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2));
    color: #fff;
    border: none;
    border-radius: 14px;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 10px 24px rgba(13, 110, 253, 0.28);
    transition: all 0.2s ease;
  }

  .btn-auth-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(13, 110, 253, 0.36);
  }

  .auth-footer-text {
    text-align: center;
    margin-top: 1.75rem;
    font-size: 0.88rem;
    color: var(--ag-muted);
  }

  .auth-footer-text a {
    color: var(--ag-blue-1);
    font-weight: 700;
    text-decoration: none;
  }
</style>
</head>
<body class="auth-page">

<header class="auth-nav">
  <a class="nav-brand" href="<?= ag_base_url('index.php') ?>" aria-label="AeroGlide Home">
    <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="brand-logo-img">
    <span class="brand-text">AeroGlide</span>
  </a>
  <a href="<?= ag_base_url('login.php') ?>" style="color: var(--ag-blue-2); text-decoration:none; font-weight:700; font-size:0.9rem;">&larr; Back to Log In</a>
</header>

<main class="auth-main-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="auth-brand-logo">
      <h1 class="auth-title">Create New Password</h1>
      <p class="auth-subtitle">Enter your new account password below.</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="auth-alert auth-alert-error">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="auth-alert auth-alert-info">
        <?= htmlspecialchars($success, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= ag_base_url('reset_password.php') ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

      <div class="auth-form-group">
        <label class="auth-label" for="password">New Password</label>
        <input class="auth-input" type="password" id="password" name="password" placeholder="At least 6 characters" required autofocus>
      </div>

      <div class="auth-form-group">
        <label class="auth-label" for="confirm_password">Confirm New Password</label>
        <input class="auth-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-type new password" required>
      </div>

      <button type="submit" class="btn-auth-submit">Update Password &amp; Continue</button>
    </form>

    <div class="auth-footer-text">
      <a href="<?= ag_base_url('login.php') ?>">Return to Log In</a>
    </div>
  </div>
</main>

</body>
</html>
