<?php
// ============================================================
// AeroGlide — checkout.php
// Reached from destination.php's "Proceed to booking" button.
// Step 1 (GET):  review the selected flight + hotel + car and
//                enter traveler / payment details.
// Step 2 (POST): validate the form and show a booking
//                confirmation with a reference number.
// This is a demo checkout — no real payment is processed.
// ============================================================

session_start();

 $isLoggedIn = isset($_SESSION['user_id']);
 $username   = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : '';

/**
 * Same lightweight asset lookup used on destination.php, kept local
 * to this file so checkout.php works standalone. Only used here for
 * the nav/footer logo.
 */
function asset_index(): array
{
    static $files = null;
    if ($files !== null) {
        return $files;
    }

    $files = [];
    foreach (['asset', 'assets'] as $folder) {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($dir)) {
            continue;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $base = basename($path);
            $stem = pathinfo($base, PATHINFO_FILENAME);
            $norm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $stem));
            $files[] = [
                'folder' => $folder,
                'name'   => $base,
                'norm'   => ' ' . trim($norm) . ' ',
            ];
        }
    }
    return $files;
}

function asset_url(array $file): string
{
    return $file['folder'] . '/' . rawurlencode($file['name']);
}

function asset_placeholder(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs><rect width="900" height="600" fill="url(#g)"/></svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function asset_find(array $keywords): string
{
    $files = asset_index();
    foreach ($files as $f) {
        $ok = true;
        foreach ($keywords as $k) {
            if (strpos($f['norm'], strtolower($k)) === false) {
                $ok = false;
                break;
            }
        }
        if ($ok) {
            return asset_url($f);
        }
    }
    return asset_placeholder();
}

function peso(float $n): string
{
    return '₱' . number_format($n, 0);
}

/* ------------------------------------------------------------
   Read what the traveler picked on destination.php. GET carries
   the initial hand-off; the checkout form re-posts the same
   values as hidden fields so they survive validation round-trips.
------------------------------------------------------------ */
 $tripCity     = isset($_REQUEST['city'])     ? trim($_REQUEST['city'])     : '';
 $fareName     = isset($_REQUEST['fareName']) ? trim($_REQUEST['fareName']) : '';
 $fareDesc     = isset($_REQUEST['fareDesc']) ? trim($_REQUEST['fareDesc']) : '';
 $farePriceRaw = isset($_REQUEST['price'])    ? trim($_REQUEST['price'])    : '';
 $farePriceNum = (float) preg_replace('/[^0-9.]/', '', $farePriceRaw);

 $hotelName  = isset($_REQUEST['hotelName'])  ? trim($_REQUEST['hotelName'])        : '';
 $hotelPrice = isset($_REQUEST['hotelPrice']) ? (float) $_REQUEST['hotelPrice']     : 0;
 $nights     = isset($_REQUEST['nights'])     ? max(1, (int) $_REQUEST['nights'])   : 1;

 $carName  = isset($_REQUEST['carName'])  ? trim($_REQUEST['carName'])      : '';
 $carPrice = isset($_REQUEST['carPrice']) ? (float) $_REQUEST['carPrice']   : 0;
 $days     = isset($_REQUEST['days'])     ? max(1, (int) $_REQUEST['days']) : 1;

// A trip is considered valid if it at least has a destination and a flight fare.
 $hasTrip = ($tripCity !== '' && $farePriceNum > 0);

// Recompute totals server-side rather than trusting a client total.
 $hotelTotal = $hotelPrice * $nights;
 $carTotal   = $carPrice * $days;
 $grandTotal = $farePriceNum + $hotelTotal + $carTotal;

/* ------------------------------------------------------------
   Handle the traveler / payment form submission.
------------------------------------------------------------ */
 $errors        = [];
 $bookingRef    = null;
 $isSubmission  = ($_SERVER['REQUEST_METHOD'] === 'POST');

 $travelerName  = '';
 $travelerEmail = '';
 $travelerPhone = '';
 $paymentMethod = 'card';
 $cardNumber    = '';
 $cardExpiry    = '';
 $cardCvv       = '';
 $promoCode     = '';

if ($isSubmission && $hasTrip) {
    $travelerName  = trim($_POST['travelerName']  ?? '');
    $travelerEmail = trim($_POST['travelerEmail'] ?? '');
    $travelerPhone = trim($_POST['travelerPhone'] ?? '');
    $paymentMethod = $_POST['paymentMethod']       ?? 'card';
    $cardNumber    = trim($_POST['cardNumber']     ?? '');
    $cardExpiry    = trim($_POST['cardExpiry']     ?? '');
    $cardCvv       = trim($_POST['cardCvv']        ?? '');
    $promoCode     = trim($_POST['promoCode']      ?? '');

    if ($travelerName === '') {
        $errors[] = "Please enter the lead traveler's full name.";
    }
    if (!filter_var($travelerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $travelerPhone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!in_array($paymentMethod, ['card', 'gcash', 'bank'], true)) {
        $paymentMethod = 'card';
    }
    if ($paymentMethod === 'card') {
        if (!preg_match('/^[0-9\s]{12,19}$/', $cardNumber)) {
            $errors[] = 'Please enter a valid card number.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $cardExpiry)) {
            $errors[] = 'Please enter the expiry as MM/YY.';
        }
        if (!preg_match('/^[0-9]{3,4}$/', $cardCvv)) {
            $errors[] = 'Please enter a valid CVV.';
        }
    }

    if (empty($errors)) {
        // Demo booking reference — swap this block for a real
        // payment + persistence flow when wiring this up for real.
        $bookingRef = 'AG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  /* ————— Theme tokens (matched to homepage sky/flight look) ————— */
  :root{
    --ag-sky-1:#eaf4fe;          /* pale sky */
    --ag-sky-2:#cfe7fb;          /* horizon glow */
    --ag-blue-1:#0d6efd;         /* primary blue */
    --ag-blue-2:#0a4fa0;         /* deep blue */
    --ag-ink:#0a1425;            /* near-black navy */
    --ag-muted:#5b6b7f;
    --ag-line:#e3e9f2;
    --ag-card:#ffffff;
    --ag-shadow:0 16px 40px rgba(10, 79, 160, .10);
    --ag-shadow-soft:0 8px 24px rgba(10, 79, 160, .08);
    --ag-radius:18px;
  }

  .checkout-main{
    background:
      radial-gradient(1200px 400px at 80% -10%, var(--ag-sky-2), transparent 60%),
      linear-gradient(180deg, var(--ag-sky-1) 0%, #f7fafd 320px);
    font-family:'Plus Jakarta Sans', sans-serif;
    color:var(--ag-ink);
    min-height:60vh;
  }

  /* ————— Hero: open blue sky like the homepage banner ————— */
  .checkout-hero{
    position:relative;
    background:linear-gradient(160deg, #d8ecfd 0%, var(--ag-sky-2) 45%, #a9d3f5 100%);
    color:var(--ag-ink);
    padding:56px 24px 110px;
    overflow:hidden;
  }
  .checkout-hero::before, .checkout-hero::after{
    content:""; position:absolute; border-radius:50%;
    background:rgba(255,255,255,.65); filter:blur(1px);
  }
  .checkout-hero::before{
    width:220px; height:70px; top:52px; right:8%;
    box-shadow:120px 26px 0 -8px rgba(255,255,255,.55), -160px 34px 0 -18px rgba(255,255,255,.4);
  }
  .checkout-hero::after{
    width:150px; height:48px; bottom:70px; left:6%;
    box-shadow:90px 18px 0 -6px rgba(255,255,255,.5);
  }
  .checkout-hero-inner{ position:relative; z-index:2; max-width:1080px; margin:0 auto; }
  .checkout-back-link{
    display:inline-flex; align-items:center; gap:6px;
    color:var(--ag-blue-2); text-decoration:none; font-weight:700; font-size:.88rem;
    background:rgba(255,255,255,.75); padding:8px 14px; border-radius:999px;
    transition:background .2s ease, transform .2s ease;
  }
  .checkout-back-link:hover{ background:#fff; transform:translateX(-2px); }
  .checkout-hero-title{
    font-family:'Montserrat',sans-serif; font-weight:900;
    font-size:clamp(1.8rem, 4vw, 2.4rem); letter-spacing:-.02em;
    margin:18px 0 6px; color:#0a2f5e;
  }
  .checkout-hero-sub{ color:#31537d; margin:0; font-size:1rem; }
  .hero-plane{
    position:absolute; z-index:1; right:6%; bottom:64px;
    opacity:.9; animation:ag-float 5s ease-in-out infinite;
  }
  @keyframes ag-float{
    0%,100%{ transform:translateY(0) rotate(-4deg); }
    50%{ transform:translateY(-14px) rotate(-4deg); }
  }

  /* ————— Step indicator ————— */
  .step-bar{
    display:flex; gap:0; max-width:640px; margin:0 auto;
    position:relative; z-index:5;
    transform:translateY(-84px); margin-bottom:-52px;
  }
  .step{
    flex:1; display:flex; align-items:center; gap:10px;
    background:rgba(255,255,255,.85); backdrop-filter:blur(6px);
    border:1px solid rgba(255,255,255,.9);
    padding:10px 16px; font-size:.8rem; font-weight:700; color:var(--ag-muted);
  }
  .step:first-child{ border-radius:999px 0 0 999px; }
  .step:last-child{ border-radius:0 999px 999px 0; }
  .step .step-num{
    width:26px; height:26px; border-radius:50%; flex:0 0 auto;
    display:inline-flex; align-items:center; justify-content:center;
    background:#e2ecf7; color:var(--ag-muted); font-size:.78rem;
  }
  .step.is-done .step-num{ background:var(--ag-blue-1); color:#fff; }
  .step.is-done{ color:var(--ag-blue-2); }

  /* ————— Layout ————— */
  .checkout-container{
    max-width:1080px; margin:56px auto 80px; padding:0 24px;
    display:grid; grid-template-columns:1.15fr .85fr; gap:26px; align-items:start;
  }
  @media (max-width:860px){
    .checkout-container{ grid-template-columns:1fr; margin-top:24px; }
    .step-bar{ transform:none; margin:24px auto 0; }
  }

  .checkout-card{
    background:var(--ag-card); border:1px solid var(--ag-line);
    border-radius:var(--ag-radius); padding:30px; box-shadow:var(--ag-shadow);
  }
  .checkout-card + .checkout-card{ margin-top:26px; }
  .checkout-card h2{
    font-family:'Montserrat',sans-serif; font-size:1.1rem; margin:0 0 20px;
    display:flex; align-items:center; gap:10px; letter-spacing:-.01em;
  }
  .checkout-card h2 .h2-icon{
    width:30px; height:30px; border-radius:9px; background:var(--ag-sky-1);
    display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto;
    color:var(--ag-blue-2);
  }

  /* ————— Fields ————— */
  .field-group{ margin-bottom:18px; }
  .field-row{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  @media (max-width:520px){ .field-row{ grid-template-columns:1fr; } }
  .field-label{ display:block; font-size:.82rem; font-weight:700; margin-bottom:7px; color:var(--ag-ink); letter-spacing:.02em; }
  .field-input{
    width:100%; padding:12px 14px; border:1px solid var(--ag-line); border-radius:12px;
    font-family:inherit; font-size:.95rem; box-sizing:border-box; color:var(--ag-ink);
    background:#fbfdff; transition:border-color .15s ease, box-shadow .15s ease;
  }
  .field-input::placeholder{ color:#a5b3c4; }
  .field-input:focus{
    outline:none; border-color:var(--ag-blue-1);
    box-shadow:0 0 0 4px rgba(13, 110, 253, .12); background:#fff;
  }

  /* ————— Payment method tiles ————— */
  .pay-methods{ display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .pay-method{
    flex:1; min-width:130px; border:1.5px solid var(--ag-line); border-radius:14px;
    padding:14px 12px; text-align:center; cursor:pointer; font-weight:700; font-size:.88rem;
    color:var(--ag-muted); user-select:none; background:#fbfdff;
    display:flex; flex-direction:column; align-items:center; gap:8px;
    transition:border-color .15s ease, background .15s ease, transform .15s ease;
  }
  .pay-method:hover{ transform:translateY(-2px); border-color:#bcd8f3; }
  .pay-method input{ display:none; }
  .pay-method .pm-icon{
    width:38px; height:38px; border-radius:10px; background:#eef5fd;
    display:inline-flex; align-items:center; justify-content:center; color:var(--ag-blue-2);
  }
  .pay-method.is-active{
    border-color:var(--ag-blue-1); background:#eef5ff; color:var(--ag-blue-2);
    box-shadow:0 6px 18px rgba(13,110,253,.14);
  }
  .pay-method.is-active .pm-icon{ background:var(--ag-blue-1); color:#fff; }

  .card-fields{ display:none; }
  .card-fields.is-visible{ display:block; animation:ag-fadein .25s ease; }
  @keyframes ag-fadein{ from{ opacity:0; transform:translateY(-4px); } to{ opacity:1; transform:none; } }

  /* ————— Errors ————— */
  .error-box{
    background:#fdeceb; border:1px solid #f4b8b3; color:#8a1f14;
    border-radius:12px; padding:16px 18px; margin-bottom:22px; font-size:.9rem;
  }
  .error-box ul{ margin:6px 0 0 18px; padding:0; }

  /* ————— Summary ————— */
  .summary-row{
    display:flex; justify-content:space-between; gap:12px; padding:14px 0;
    border-bottom:1px dashed var(--ag-line); font-size:.92rem; align-items:flex-start;
  }
  .summary-row:last-of-type{ border-bottom:none; }
  .summary-row .label{ color:var(--ag-muted); }
  .summary-row .item-name{ font-weight:700; color:var(--ag-ink); display:block; margin-bottom:2px; }
  .summary-row .item-meta{ color:var(--ag-muted); font-size:.82rem; }
  .summary-row .item-price{ font-weight:700; color:var(--ag-blue-2); white-space:nowrap; }
  .summary-total-row{
    display:flex; justify-content:space-between; align-items:center;
    padding-top:16px; margin-top:8px; border-top:2px solid var(--ag-ink);
    font-weight:800; font-size:1.15rem;
  }
  .summary-total-row .total-pill{
    background:linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2));
    color:#fff; padding:6px 14px; border-radius:999px; font-size:1rem;
  }

  .promo-row{ display:flex; gap:8px; margin-top:18px; }
  .promo-row input{ flex:1; }
  .btn-secondary{
    border:1px solid var(--ag-line); background:#fff; border-radius:12px; padding:0 18px;
    font-weight:700; font-size:.85rem; cursor:pointer; color:var(--ag-blue-2);
  }

  /* ————— Confirm button ————— */
  .btn-confirm{
    width:100%; margin-top:24px;
    background:linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2));
    color:#fff; border:none; border-radius:14px; padding:16px;
    font-size:1.02rem; font-weight:800; cursor:pointer; font-family:inherit;
    box-shadow:0 10px 26px rgba(13, 110, 253, .30);
    transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
  }
  .btn-confirm:hover{ transform:translateY(-2px); box-shadow:0 14px 32px rgba(13,110,253,.36); filter:brightness(1.04); }
  .btn-confirm:active{ transform:translateY(0); }
  .lock-note{ text-align:center; color:var(--ag-muted); font-size:.78rem; margin-top:12px; }

  /* ————— No-trip state ————— */
  .no-trip-box{ max-width:560px; margin:100px auto; text-align:center; padding:0 24px; }
  .no-trip-box h1{ font-family:'Montserrat',sans-serif; font-weight:800; }
  .no-trip-box p{ color:var(--ag-muted); }
  .no-trip-box a.btn-primary-link{
    display:inline-block; margin-top:20px;
    background:linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2));
    color:#fff; text-decoration:none; padding:14px 26px; border-radius:12px;
    font-weight:700; box-shadow:0 10px 26px rgba(13,110,253,.3);
    transition:transform .15s ease;
  }
  .no-trip-box a.btn-primary-link:hover{ transform:translateY(-2px); }

  /* ————— Confirmation state ————— */
  .confirm-box{ max-width:660px; margin:80px auto; padding:0 24px; text-align:center; }
  .confirm-box h1{ font-family:'Montserrat',sans-serif; font-weight:900; letter-spacing:-.02em; }
  .confirm-badge{
    width:72px; height:72px; border-radius:50%;
    background:linear-gradient(135deg, #4fc27b, #1c8a45); color:#fff;
    display:flex; align-items:center; justify-content:center; margin:0 auto 20px;
    font-size:2rem; box-shadow:0 12px 30px rgba(28,138,69,.30);
  }
  .confirm-ref{
    display:inline-block; margin:14px 0 28px; padding:12px 20px; border-radius:12px;
    background:#eef5ff; color:var(--ag-blue-2); font-weight:800; letter-spacing:.05em;
    border:1px dashed #bcd8f3;
  }
  .confirm-summary{ text-align:left; }
  .confirm-actions{ display:flex; gap:12px; justify-content:center; margin-top:28px; flex-wrap:wrap; }
  .confirm-actions a{
    text-decoration:none; padding:13px 24px; border-radius:12px; font-weight:700; font-size:.9rem;
    transition:transform .15s ease;
  }
  .confirm-actions a:hover{ transform:translateY(-2px); }
  .confirm-actions .primary{
    background:linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2)); color:#fff;
    box-shadow:0 10px 26px rgba(13,110,253,.28);
  }
  .confirm-actions .secondary{ background:#fff; color:var(--ag-blue-2); border:1px solid var(--ag-line); }

  @media print{
    .main-nav, .site-footer, .confirm-actions, .step-bar{ display:none !important; }
    .checkout-main{ background:#fff; }
    .checkout-card{ box-shadow:none; }
  }
</style>
</head>
<body class="aeroglide-page">

<!-- ============ NAVBAR ============ -->
<nav class="main-nav">
  <div class="nav-container">
    <a class="nav-brand" href="index.php" aria-label="AeroGlide Home">
      <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="brand-logo-img">
      <span class="brand-text">AeroGlide</span>
    </a>
    <div class="nav-menu">
      <a href="index.php" class="nav-item">Home</a>
      <a href="index.php#booking" class="nav-item">Flights</a>
      <a href="index.php#deals" class="nav-item">Package</a>
      <a href="index.php#about" class="nav-item">Support</a>
    </div>
    <div class="nav-right">
      <?php if ($isLoggedIn): ?>
        <span class="nav-user-greeting">Hi, <?= htmlspecialchars($username) ?></span>
        <a class="nav-auth-btn" href="logout.php" title="Logout"><span>Logout</span></a>
      <?php else: ?>
        <a class="nav-auth-btn" href="login.php">
          <svg class="auth-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Sign up</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="checkout-main">

<?php if (!$hasTrip): ?>

  <!-- ============ NO TRIP SELECTED ============ -->
  <div class="no-trip-box">
    <div class="confirm-badge" style="background:linear-gradient(135deg,#cfe7fb,#a9d3f5); box-shadow:var(--ag-shadow-soft);">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#0a4fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </div>
    <h1>No trip selected yet</h1>
    <p>Head back and pick a destination, hotel and car rental before checking out.</p>
    <a class="btn-primary-link" href="index.php#deals">Browse destinations</a>
  </div>

<?php elseif ($bookingRef): ?>

  <!-- ============ BOOKING CONFIRMED ============ -->
  <div class="confirm-box">
    <div class="confirm-badge">&#10003;</div>
    <h1>Booking confirmed!</h1>
    <p style="color:var(--ag-muted);">Thanks, <?= htmlspecialchars(explode(' ', $travelerName)[0], ENT_QUOTES) ?> — your trip to <strong style="color:var(--ag-ink);"><?= htmlspecialchars($tripCity, ENT_QUOTES) ?></strong> is booked.</p>
    <div class="confirm-ref">Reference: <?= htmlspecialchars($bookingRef, ENT_QUOTES) ?></div>

    <div class="checkout-card confirm-summary">
      <h2>
        <span class="h2-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
        </span>
        Trip summary
      </h2>
      <div class="summary-row">
        <div><span class="item-name">Flight — <?= htmlspecialchars($fareName, ENT_QUOTES) ?></span><span class="item-meta"><?= htmlspecialchars($fareDesc, ENT_QUOTES) ?></span></div>
        <span class="item-price"><?= peso($farePriceNum) ?></span>
      </div>
      <?php if ($hotelName !== ''): ?>
      <div class="summary-row">
        <div><span class="item-name"><?= htmlspecialchars($hotelName, ENT_QUOTES) ?></span><span class="item-meta"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?> · <?= peso($hotelPrice) ?>/night</span></div>
        <span class="item-price"><?= peso($hotelTotal) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($carName !== ''): ?>
      <div class="summary-row">
        <div><span class="item-name"><?= htmlspecialchars($carName, ENT_QUOTES) ?></span><span class="item-meta"><?= $days ?> day<?= $days > 1 ? 's' : '' ?> · <?= peso($carPrice) ?>/day</span></div>
        <span class="item-price"><?= peso($carTotal) ?></span>
      </div>
      <?php endif; ?>
      <div class="summary-total-row">
        <span>Total paid</span>
        <span class="total-pill"><?= peso($grandTotal) ?></span>
      </div>
    </div>

    <p class="lock-note">A confirmation for <?= htmlspecialchars($travelerEmail, ENT_QUOTES) ?> has been generated. This is a demo build — no real charge was made.</p>

    <div class="confirm-actions">
      <a href="#" class="primary" onclick="window.print(); return false;">Print confirmation</a>
      <a href="index.php" class="secondary">Back to home</a>
    </div>
  </div>

<?php else: ?>

  <!-- ============ CHECKOUT HERO — sky banner matching the homepage ============ -->
  <section class="checkout-hero">
    <svg class="hero-plane" width="88" height="88" viewBox="0 0 24 24" fill="#f4f8fc" stroke="#0a4fa0" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
    <div class="checkout-hero-inner">
      <a class="checkout-back-link" href="javascript:history.back()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
        Back to <?= htmlspecialchars($tripCity, ENT_QUOTES) ?>
      </a>
      <h1 class="checkout-hero-title">Secure your trip</h1>
      <p class="checkout-hero-sub">You're one step away from <?= htmlspecialchars($tripCity, ENT_QUOTES) ?> — review your trip and enter your details to confirm.</p>
    </div>
  </section>

  <!-- ============ STEP INDICATOR ============ -->
  <div class="step-bar">
    <div class="step is-done">
      <span class="step-num">&#10003;</span><span>Review trip</span>
    </div>
    <div class="step is-done">
      <span class="step-num">2</span><span>Your details</span>
    </div>
    <div class="step">
      <span class="step-num">3</span><span>Confirm &amp; pay</span>
    </div>
  </div>

  <div class="checkout-container">

    <!-- ============ TRAVELER + PAYMENT FORM ============ -->
    <div>
      <form class="checkout-card" method="POST" action="checkout.php" id="checkout-payment-form">
        <?php if (!empty($errors)): ?>
          <div class="error-box">
            <strong>Please fix the following:</strong>
            <ul>
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <h2>
          <span class="h2-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          Lead traveler
        </h2>
        <div class="field-group">
          <label class="field-label" for="travelerName">Full name</label>
          <input class="field-input" type="text" id="travelerName" name="travelerName" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($travelerName, ENT_QUOTES) ?>" required>
        </div>
        <div class="field-row">
          <div class="field-group">
            <label class="field-label" for="travelerEmail">Email</label>
            <input class="field-input" type="email" id="travelerEmail" name="travelerEmail" placeholder="you@example.com" value="<?= htmlspecialchars($travelerEmail, ENT_QUOTES) ?>" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="travelerPhone">Phone</label>
            <input class="field-input" type="tel" id="travelerPhone" name="travelerPhone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($travelerPhone, ENT_QUOTES) ?>" required>
          </div>
        </div>

        <h2 style="margin-top:30px;">
          <span class="h2-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </span>
          Payment method
        </h2>
        <div class="pay-methods" id="pay-methods">
          <label class="pay-method<?= $paymentMethod === 'card' ? ' is-active' : '' ?>" data-method="card">
            <input type="radio" name="paymentMethod" value="card" <?= $paymentMethod === 'card' ? 'checked' : '' ?>>
            <span class="pm-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </span>
            Card
          </label>
          <label class="pay-method<?= $paymentMethod === 'gcash' ? ' is-active' : '' ?>" data-method="gcash">
            <input type="radio" name="paymentMethod" value="gcash" <?= $paymentMethod === 'gcash' ? 'checked' : '' ?>>
            <span class="pm-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
            </span>
            GCash
          </label>
          <label class="pay-method<?= $paymentMethod === 'bank' ? ' is-active' : '' ?>" data-method="bank">
            <input type="radio" name="paymentMethod" value="bank" <?= $paymentMethod === 'bank' ? 'checked' : '' ?>>
            <span class="pm-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="10" x2="21" y2="10"/><line x1="3" y1="14" x2="21" y2="14"/><rect x="4" y="3" width="16" height="18" rx="1"/></svg>
            </span>
            Bank transfer
          </label>
        </div>

        <div class="card-fields<?= $paymentMethod === 'card' ? ' is-visible' : '' ?>" id="card-fields">
          <div class="field-group">
            <label class="field-label" for="cardNumber">Card number</label>
            <input class="field-input" type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" inputmode="numeric" value="<?= htmlspecialchars($cardNumber, ENT_QUOTES) ?>">
          </div>
          <div class="field-row">
            <div class="field-group">
              <label class="field-label" for="cardExpiry">Expiry (MM/YY)</label>
              <input class="field-input" type="text" id="cardExpiry" name="cardExpiry" placeholder="08/29" value="<?= htmlspecialchars($cardExpiry, ENT_QUOTES) ?>">
            </div>
            <div class="field-group">
              <label class="field-label" for="cardCvv">CVV</label>
              <input class="field-input" type="text" id="cardCvv" name="cardCvv" placeholder="123" inputmode="numeric" value="<?= htmlspecialchars($cardCvv, ENT_QUOTES) ?>">
            </div>
          </div>
        </div>

        <p style="color:var(--ag-muted); font-size:.84rem;" id="alt-pay-note">
          <?php if ($paymentMethod === 'gcash'): ?>
            You'll get a GCash payment prompt after confirming.
          <?php elseif ($paymentMethod === 'bank'): ?>
            Bank transfer details will be sent to your email after confirming.
          <?php endif; ?>
        </p>

        <!-- Carry the selected trip through to the confirmation step -->
        <input type="hidden" name="city" value="<?= htmlspecialchars($tripCity, ENT_QUOTES) ?>">
        <input type="hidden" name="fareName" value="<?= htmlspecialchars($fareName, ENT_QUOTES) ?>">
        <input type="hidden" name="fareDesc" value="<?= htmlspecialchars($fareDesc, ENT_QUOTES) ?>">
        <input type="hidden" name="price" value="<?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?>">
        <input type="hidden" name="hotelName" value="<?= htmlspecialchars($hotelName, ENT_QUOTES) ?>">
        <input type="hidden" name="hotelPrice" value="<?= htmlspecialchars((string) $hotelPrice, ENT_QUOTES) ?>">
        <input type="hidden" name="nights" value="<?= (int) $nights ?>">
        <input type="hidden" name="carName" value="<?= htmlspecialchars($carName, ENT_QUOTES) ?>">
        <input type="hidden" name="carPrice" value="<?= htmlspecialchars((string) $carPrice, ENT_QUOTES) ?>">
        <input type="hidden" name="days" value="<?= (int) $days ?>">

        <button type="submit" class="btn-confirm">Confirm &amp; pay <?= peso($grandTotal) ?></button>
        <p class="lock-note">🔒 Demo checkout — no real payment is processed.</p>
      </form>
    </div>

    <!-- ============ ORDER SUMMARY ============ -->
    <div>
      <div class="checkout-card">
        <h2>
          <span class="h2-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </span>
          Your trip to <?= htmlspecialchars($tripCity, ENT_QUOTES) ?>
        </h2>

        <div class="summary-row">
          <div>
            <span class="item-name">✈️ Flight — <?= htmlspecialchars($fareName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= htmlspecialchars($fareDesc, ENT_QUOTES) ?></span>
          </div>
          <span class="item-price"><?= peso($farePriceNum) ?></span>
        </div>

        <?php if ($hotelName !== ''): ?>
        <div class="summary-row">
          <div>
            <span class="item-name">🏨 <?= htmlspecialchars($hotelName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?> · <?= peso($hotelPrice) ?>/night</span>
          </div>
          <span class="item-price"><?= peso($hotelTotal) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($carName !== ''): ?>
        <div class="summary-row">
          <div>
            <span class="item-name">🚗 <?= htmlspecialchars($carName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= $days ?> day<?= $days > 1 ? 's' : '' ?> · <?= peso($carPrice) ?>/day</span>
          </div>
          <span class="item-price"><?= peso($carTotal) ?></span>
        </div>
        <?php endif; ?>

        <div class="promo-row">
          <input class="field-input" type="text" placeholder="Promo code" value="<?= htmlspecialchars($promoCode, ENT_QUOTES) ?>" disabled>
          <button type="button" class="btn-secondary" disabled>Apply</button>
        </div>

        <div class="summary-total-row">
          <span>Total</span>
          <span class="total-pill"><?= peso($grandTotal) ?></span>
        </div>
      </div>
    </div>

  </div>

<?php endif; ?>

</main>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand-col">
      <div class="footer-brand-header">
        <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="footer-brand-logo">
        <span class="footer-brand-name">A e r o G l i d e</span>
      </div>
    </div>
    <div class="footer-links-grid">
      <div class="footer-col">
        <h4 class="footer-col-title">Travel</h4>
        <ul class="footer-nav-list">
          <li><a href="index.php#destinations">Asia</a></li>
          <li><a href="index.php#destinations">Europe</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">Company</h4>
        <ul class="footer-nav-list">
          <li><a href="index.php#about">About Us</a></li>
          <li><a href="index.php#about">Customer Support</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>

<script>
(function () {
  'use strict';
  var methods = document.querySelectorAll('#pay-methods .pay-method');
  var cardFields = document.getElementById('card-fields');
  var altNote = document.getElementById('alt-pay-note');

  var notes = {
    card: '',
    gcash: "You'll get a GCash payment prompt after confirming.",
    bank: 'Bank transfer details will be sent to your email after confirming.'
  };

  methods.forEach(function (label) {
    label.addEventListener('click', function () {
      methods.forEach(function (l) { l.classList.remove('is-active'); });
      label.classList.add('is-active');
      var method = label.dataset.method;
      if (cardFields) cardFields.classList.toggle('is-visible', method === 'card');
      if (altNote) altNote.textContent = notes[method] || '';
    });
  });
})();
</script>

</body>
</html>