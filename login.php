<?php
// ============================================================
// AeroGlide — login.php
// User Login Page: styled with AeroGlide sky theme tokens,
// handles session login and redirects back to trip booking.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

// If already logged in, redirect to intended page or homepage
$redirect = $_REQUEST['redirect'] ?? 'index.php';
$msg      = $_REQUEST['msg']      ?? '';
$error    = $_REQUEST['error']    ?? '';
$city     = $_REQUEST['city']     ?? '';
$fare     = $_REQUEST['fare']     ?? '';
$fareName = $_REQUEST['fareName'] ?? '';
$desc     = $_REQUEST['desc']     ?? '';
$price    = $_REQUEST['price']    ?? '';

if (auth_is_logged_in()) {
    $target = $redirect;
    if ($city !== '') {
        $target .= '?city=' . urlencode($city) . '&fare=' . urlencode($fare) . '&fareName=' . urlencode($fareName) . '&desc=' . urlencode($desc) . '&price=' . urlencode($price);
    }
    header('Location: ' . $target);
    exit;
}

if ($msg === 'login_required' && $error === '') {
    $error = 'An AeroGlide account is required to book a trip. Please log in or sign up below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = auth_login($username, $password);
    if ($res['success']) {
        $target = $redirect;
        if ($city !== '') {
            $target .= '?city=' . urlencode($city) . '&fare=' . urlencode($fare) . '&fareName=' . urlencode($fareName) . '&desc=' . urlencode($desc) . '&price=' . urlencode($price);
        }
        header('Location: ' . $target);
        exit;
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
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
    position: relative;
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
    letter-spacing: 0.02em;
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

  .auth-footer-text a:hover {
    text-decoration: underline;
  }

  .demo-account-hint {
    background: #f4f8fd;
    border: 1px dashed #c0d8f0;
    border-radius: 14px;
    padding: 0.85rem 1rem;
    font-size: 0.8rem;
    color: var(--ag-muted);
    margin-top: 1.5rem;
    text-align: center;
  }

  .demo-account-hint code {
    background: #e1eefb;
    color: var(--ag-blue-2);
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 700;
  }
</style>
</head>
<body class="auth-page">

<header class="auth-nav">
  <a class="nav-brand" href="index.php" aria-label="AeroGlide Home">
    <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="brand-logo-img">
    <span class="brand-text">AeroGlide</span>
  </a>
  <a href="index.php" style="color: var(--ag-blue-2); text-decoration:none; font-weight:700; font-size:0.9rem;">&larr; Back to Home</a>
</header>

<main class="auth-main-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="auth-brand-logo">
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-subtitle">Log in to book your next flight &amp; hotel getaway</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="auth-alert auth-alert-error">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <?php if ($msg !== '' && $msg !== 'login_required'): ?>
      <div class="auth-alert auth-alert-info">
        <?= htmlspecialchars($msg, ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES) ?>">
      <input type="hidden" name="city" value="<?= htmlspecialchars($city, ENT_QUOTES) ?>">
      <input type="hidden" name="fare" value="<?= htmlspecialchars($fare, ENT_QUOTES) ?>">
      <input type="hidden" name="fareName" value="<?= htmlspecialchars($fareName, ENT_QUOTES) ?>">
      <input type="hidden" name="desc" value="<?= htmlspecialchars($desc, ENT_QUOTES) ?>">
      <input type="hidden" name="price" value="<?= htmlspecialchars($price, ENT_QUOTES) ?>">

      <div class="auth-form-group">
        <label class="auth-label" for="username">Username or Email</label>
        <input class="auth-input" type="text" id="username" name="username" placeholder="e.g. demo or demo@aeroglide.com" required autofocus>
      </div>

      <div class="auth-form-group">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
          <label class="auth-label" for="password" style="margin-bottom:0;">Password</label>
          <a href="forgot_password.php" style="font-size:0.8rem; color:var(--ag-blue-1); font-weight:600; text-decoration:none;">Forgot password?</a>
        </div>
        <input class="auth-input" type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-auth-submit">Log In to AeroGlide</button>
    </form>

    <div class="auth-footer-text">
      Don't have an account yet? <a href="signup.php?redirect=<?= urlencode($redirect) ?>&city=<?= urlencode($city) ?>&fare=<?= urlencode($fare) ?>&fareName=<?= urlencode($fareName) ?>&desc=<?= urlencode($desc) ?>&price=<?= urlencode($price) ?>">Sign up here</a>
    </div>

    <div class="demo-account-hint">
      💡 <strong>Quick Demo Login:</strong><br>
      Username: <code>demo</code> &nbsp;|&nbsp; Password: <code>password123</code>
    </div>
  </div>
</main>

</body>
</html>
