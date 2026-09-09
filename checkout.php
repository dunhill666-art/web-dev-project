<?php
// ============================================================
// AeroGlide — checkout.php
// Reached from destination.php's "Proceed to booking" button.
// Step 1 (GET):  review trip, enforce account login, apply promo code.
// Step 2 (POST): validate form, process coupon discount, & show booking confirmation.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

$isLoggedIn = auth_is_logged_in();
$currentUser = auth_get_user();
$username   = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

if (!function_exists('peso')) {
    function peso(float $n): string
    {
        return '₱' . number_format($n, 0);
    }
}

/* ------------------------------------------------------------
   Read trip parameters
------------------------------------------------------------ */
$mode         = strtolower(trim($_REQUEST['mode'] ?? 'packages'));
$adults       = max(1, (int) ($_REQUEST['adults'] ?? 1));
$cabin        = trim($_REQUEST['cabin'] ?? 'Economy');

$tripCity     = isset($_REQUEST['city'])     ? trim($_REQUEST['city'])     : '';
$fareName     = isset($_REQUEST['fareName']) ? trim($_REQUEST['fareName']) : '';
$fareDesc     = isset($_REQUEST['fareDesc']) ? trim($_REQUEST['fareDesc']) : '';
$farePriceRaw = isset($_REQUEST['price'])    ? trim($_REQUEST['price'])    : '';
$farePriceNum = (float) preg_replace('/[^0-9.]/', '', $farePriceRaw);

$flightSchedule = isset($_REQUEST['flightSchedule']) ? trim($_REQUEST['flightSchedule']) : '';

if ($mode === 'hotels') {
    $farePriceNum = 0;
}

$hotelName  = isset($_REQUEST['hotelName'])  ? trim($_REQUEST['hotelName'])        : '';
$hotelPrice = isset($_REQUEST['hotelPrice']) ? (float) $_REQUEST['hotelPrice']     : 0;
$nights     = isset($_REQUEST['nights'])     ? max(0, (int) $_REQUEST['nights'])   : 0;

$carName  = isset($_REQUEST['carName'])  ? trim($_REQUEST['carName'])      : '';
$carPrice = isset($_REQUEST['carPrice']) ? (float) $_REQUEST['carPrice']   : 0;
$days     = isset($_REQUEST['days'])     ? max(0, (int) $_REQUEST['days']) : 0;

$promoCode = isset($_REQUEST['promoCode']) ? trim($_REQUEST['promoCode']) : '';

$hotelTotal = $hotelPrice * $nights;
$carTotal   = $carPrice * $days;
$subTotal   = $farePriceNum + $hotelTotal + $carTotal;

$hasTrip = ($tripCity !== '' && ($farePriceNum > 0 || $hotelTotal > 0 || $carTotal > 0));

/* ------------------------------------------------------------
   Coupon Code Discount Deduction Engine (1 per user account limit)
------------------------------------------------------------ */
$alreadyClaimedCoupon = $isLoggedIn ? auth_user_has_claimed_coupon($currentUser['id']) : null;
$discountNum          = 0;
$discountLabel        = '';
$promoCodeUpper       = strtoupper($promoCode);
$couponLimitNotice    = '';

if ($alreadyClaimedCoupon !== null && $promoCodeUpper !== '' && $promoCodeUpper !== strtoupper($alreadyClaimedCoupon)) {
    $couponLimitNotice = "Account limit: You have already claimed your 1 coupon deal for this account (Coupon: {$alreadyClaimedCoupon}). Each account is allowed 1 coupon deal.";
    $discountNum       = 0;
    $discountLabel     = '';
} else {
    if (in_array($promoCodeUpper, ['5PEOPLE2026', '5PEOPLE'], true)) {
        if ($adults >= 5) {
            $discountNum   = round($subTotal * 0.30);
            $discountLabel = '5 People Deal (30% OFF)';
        } else {
            $discountNum   = 0;
            $discountLabel = '';
        }
    } else if (in_array($promoCodeUpper, ['GLIDENIGHT', 'SEPTEMBER30'], true)) {
        $discountNum   = round($subTotal * 0.30);
        $discountLabel = 'September Flash Sale (30% OFF)';
    } else if (in_array($promoCodeUpper, ['SEPTDEAL2026', 'SEPTDEAL', 'CHRISTMAS1500', 'HOLIDAY1500'], true)) {
        $discountNum   = min(1500, $subTotal);
        $discountLabel = 'September Deal (-₱1,500)';
    } else if (in_array($promoCodeUpper, ['BERFLY1000', 'BERFLY', 'FLY1000'], true)) {
        $discountNum   = min(1000, $subTotal);
        $discountLabel = 'Ber-Months Fly Deal (-₱1,000)';
    } else if (in_array($promoCodeUpper, ['AEROGLIDE10', 'WELCOME10'], true)) {
        $discountNum   = round($subTotal * 0.10);
        $discountLabel = 'Welcome Coupon (10% OFF)';
    }
}

$grandTotal = max(0, $subTotal - $discountNum);

/* ------------------------------------------------------------
   Handle Traveler Form Submission
------------------------------------------------------------ */
$errors        = [];
$bookingRef    = null;
$isSubmission  = ($_SERVER['REQUEST_METHOD'] === 'POST');

$travelerName  = $currentUser['name']  ?? '';
$travelerEmail = $currentUser['email'] ?? '';
$travelerPhone = '';
$paymentMethod = 'card';
$cardNumber    = '';
$cardExpiry    = '';
$cardCvv       = '';

if ($isSubmission && $hasTrip && $isLoggedIn) {
    $travelerName  = trim($_POST['travelerName']  ?? '');
    $travelerEmail = trim($_POST['travelerEmail'] ?? '');
    $travelerPhone = trim($_POST['travelerPhone'] ?? '');
    $paymentMethod = $_POST['paymentMethod']       ?? 'card';
    $cardNumber    = trim($_POST['cardNumber']     ?? '');
    $cardExpiry    = trim($_POST['cardExpiry']     ?? '');
    $cardCvv       = trim($_POST['cardCvv']        ?? '');

    if ($travelerName === '') {
        $errors[] = "Please enter the lead traveler's full name.";
    }
    if (!filter_var($travelerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    $cleanPhone = preg_replace('/[^0-9+]/', '', $travelerPhone);
    if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 13) {
        $errors[] = 'Please enter a valid phone number (e.g. 09XXXXXXXXX or +639XXXXXXXXX).';
    }
    if (!in_array($paymentMethod, ['card', 'gcash', 'bank'], true)) {
        $paymentMethod = 'card';
    }
    if ($paymentMethod === 'card') {
        $cleanCard = preg_replace('/[^0-9]/', '', $cardNumber);
        if (strlen($cleanCard) !== 16) {
            $errors[] = 'Please enter a valid 16-digit card number.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $cardExpiry)) {
            $errors[] = 'Please enter a valid expiry date in MM/YY format (e.g. 08/29).';
        }
        $cleanCvv = preg_replace('/[^0-9]/', '', $cardCvv);
        if (strlen($cleanCvv) !== 3) {
            $errors[] = 'Please enter a 3-digit CVV security code.';
        }
    }

    if (empty($errors)) {
        $bookingRef = 'AG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        // Save booking into user's DB history
        $newBookingRecord = [
            'bookingRef'     => $bookingRef,
            'bookingDate'    => date('Y-m-d H:i:s'),
            'travelerName'   => $travelerName,
            'travelerEmail'  => $travelerEmail,
            'travelerPhone'  => $travelerPhone,
            'city'           => $tripCity,
            'mode'           => $mode,
            'adults'         => $adults,
            'cabin'          => $cabin,
            'fareName'       => $fareName,
            'fareDesc'       => $fareDesc,
            'flightSchedule' => $flightSchedule ?: 'Philippines AirAsia AG-204 (03:55 AM MNL T2 → 05:20 AM)',
            'hotelName'      => $hotelName,
            'hotelPrice'     => $hotelPrice,
            'nights'         => $nights,
            'carName'        => $carName,
            'carPrice'       => $carPrice,
            'days'           => $days,
            'subTotal'       => $subTotal,
            'discountNum'    => $discountNum,
            'discountLabel'  => $discountLabel,
            'grandTotal'     => $grandTotal,
            'promoCode'      => $discountNum > 0 ? $promoCodeUpper : '',
        ];

        auth_add_user_booking($currentUser['id'], $newBookingRecord);

        if ($discountNum > 0 && $promoCodeUpper !== '') {
            auth_record_user_coupon($currentUser['id'], $promoCodeUpper);
        }
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
  :root{
    --ag-sky-1:#eaf4fe;
    --ag-sky-2:#cfe7fb;
    --ag-blue-1:#0d6efd;
    --ag-blue-2:#0a4fa0;
    --ag-ink:#0a1425;
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

  .error-box{
    background:#fdeceb; border:1px solid #f4b8b3; color:#8a1f14;
    border-radius:12px; padding:16px 18px; margin-bottom:22px; font-size:.9rem;
  }
  .error-box ul{ margin:6px 0 0 18px; padding:0; }

  .summary-row{
    display:flex; justify-content:space-between; gap:12px; padding:14px 0;
    border-bottom:1px dashed var(--ag-line); font-size:.92rem; align-items:flex-start;
  }
  .summary-row:last-of-type{ border-bottom:none; }
  .summary-row .label{ color:var(--ag-muted); }
  .summary-row .item-name{ font-weight:700; color:var(--ag-ink); display:block; margin-bottom:2px; }
  .summary-row .item-meta{ color:var(--ag-muted); font-size:.82rem; }
  .summary-row .item-price{ font-weight:700; color:var(--ag-blue-2); white-space:nowrap; }
  .summary-discount-row{ color:#15803d; background:#f0fdf4; padding:10px 12px; border-radius:10px; border:1px solid #bbf7d0; margin-top:8px; }
  .summary-discount-row .item-name{ color:#166534; }
  .summary-discount-row .item-price{ color:#15803d; font-weight:800; }

  .summary-total-row{
    display:flex; justify-content:space-between; align-items:center;
    padding-top:16px; margin-top:8px; border-top:2px solid var(--ag-ink);
    font-weight:800; font-size:1.15rem;
  }
  .summary-total-row .total-pill{
    background:linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2));
    color:#fff; padding:6px 14px; border-radius:999px; font-size:1rem;
  }

  .promo-box-container{ margin-top:18px; }
  .promo-row{ display:flex; gap:8px; }
  .promo-row input{ flex:1; font-weight:600; }
  .btn-apply-promo{
    border:1px solid var(--ag-blue-1); background:var(--ag-blue-1); color:#fff; border-radius:12px; padding:0 18px;
    font-weight:700; font-size:.88rem; cursor:pointer; transition:0.2s;
  }
  .btn-apply-promo:hover{ background:var(--ag-blue-2); }
  .promo-status-msg{ font-size:0.8rem; margin-top:6px; font-weight:600; }
  .promo-status-msg.is-success{ color:#16a34a; }
  .promo-status-msg.is-error{ color:#dc2626; }

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

  /* Restricted Auth Card */
  .auth-lock-card {
    text-align: center;
    padding: 3rem 2rem;
    max-width: 580px;
    margin: 40px auto;
  }
  .auth-lock-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #eef5ff; color: var(--ag-blue-1);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.25rem; font-size: 1.8rem;
    box-shadow: 0 8px 20px rgba(13,110,253,0.15);
  }
  .auth-lock-title { font-family:'Montserrat',sans-serif; font-size:1.5rem; font-weight:800; margin-bottom:0.5rem; }
  .auth-lock-text { color: var(--ag-muted); font-size:0.95rem; line-height:1.5; margin-bottom:1.75rem; }
  .auth-lock-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
  .auth-lock-btn-primary { background: linear-gradient(135deg, var(--ag-blue-1), var(--ag-blue-2)); color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; box-shadow: 0 8px 20px rgba(13,110,253,0.25); }
  .auth-lock-btn-secondary { background: #fff; color: var(--ag-blue-2); text-decoration: none; padding: 12px 28px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; border: 1.5px solid var(--ag-line); }

  @media print{
    .main-nav, .site-footer, .confirm-actions, .step-bar{ display:none !important; }
    .checkout-main{ background:#fff; }
    .checkout-card{ box-shadow:none; }
  }
</style>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="checkout-main">

<?php if (!$hasTrip): ?>

  <div class="no-trip-box">
    <div class="confirm-badge" style="background:linear-gradient(135deg,#cfe7fb,#a9d3f5); box-shadow:var(--ag-shadow-soft);">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#0a4fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </div>
    <h1>No trip selected yet</h1>
    <p>Head back and pick a destination, hotel and car rental before checking out.</p>
    <a class="btn-primary-link" href="index.php#deals">Browse destinations</a>
  </div>

<?php elseif (!$isLoggedIn): ?>

  <!-- ============ ACCOUNT RESTRICTION NOTICE ============ -->
  <section class="checkout-hero">
    <div class="checkout-hero-inner">
      <a class="checkout-back-link" href="javascript:history.back()">&larr; Back to <?= htmlspecialchars($tripCity, ENT_QUOTES) ?></a>
      <h1 class="checkout-hero-title">Account Required</h1>
      <p class="checkout-hero-sub">You're almost there! Log in or sign up to finalize your trip to <?= htmlspecialchars($tripCity, ENT_QUOTES) ?>.</p>
    </div>
  </section>

  <div class="checkout-card auth-lock-card">
    <div class="auth-lock-icon">🔒</div>
    <h2 class="auth-lock-title">Please Log In to Complete Booking</h2>
    <p class="auth-lock-text">
      Booking a trip to <strong><?= htmlspecialchars($tripCity, ENT_QUOTES) ?></strong> requires an active AeroGlide account embedded in our system. Log in below or create a free account in 30 seconds — all your trip details will be saved!
    </p>

    <?php
      $authParams = http_build_query([
        'redirect'   => 'checkout.php',
        'msg'        => 'login_required',
        'city'       => $tripCity,
        'fareName'   => $fareName,
        'desc'       => $fareDesc,
        'price'      => $farePriceRaw,
        'hotelName'  => $hotelName,
        'hotelPrice' => (string)$hotelPrice,
        'nights'     => $nights,
        'carName'    => $carName,
        'carPrice'   => (string)$carPrice,
        'days'       => $days,
        'promoCode'  => $promoCode
      ]);
    ?>
    <div class="auth-lock-btns">
      <a href="login.php?<?= $authParams ?>" class="auth-lock-btn-primary">Log In to Account</a>
      <a href="signup.php?<?= $authParams ?>" class="auth-lock-btn-secondary">Sign Up Free</a>
    </div>
  </div>

<?php elseif ($bookingRef): ?>

  <!-- ============ BOOKING CONFIRMED ============ -->
  <div class="confirm-box" style="max-width:820px;">
    <div class="confirm-badge">&#10003;</div>
    <h1>Booking confirmed!</h1>
    <p style="color:var(--ag-muted);">Thanks, <?= htmlspecialchars(explode(' ', $travelerName)[0], ENT_QUOTES) ?> &mdash; your trip to <strong style="color:var(--ag-ink);"><?= htmlspecialchars($tripCity, ENT_QUOTES) ?></strong> is booked.</p>
    <div class="confirm-ref">Reference: <?= htmlspecialchars($bookingRef, ENT_QUOTES) ?></div>

    <!-- ============ OFFICIAL AIRLINE BOARDING PASS TICKET ============ -->
    <div class="ticket-wrapper">
      <div class="ticket-header">
        <div class="ticket-brand">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
          AIRLINE TICKET
        </div>
        <div class="ticket-stub-title">BOARDING PASS</div>
      </div>

      <div class="ticket-body">
        <div class="ticket-main">
          <div class="ticket-grid">
            <div class="ticket-field">
              <span class="tf-label">Name of Passenger</span>
              <span class="tf-val"><?= htmlspecialchars($travelerName, ENT_QUOTES) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Date</span>
              <span class="tf-val"><?= date('M d, Y', strtotime('+3 days')) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Time</span>
              <span class="tf-val tf-val-highlight"><?= htmlspecialchars(str_contains($flightSchedule, '(') ? trim(explode(')', explode('(', $flightSchedule)[1] ?? '')[0]) : '03:55 AM MNL T2', ENT_QUOTES) ?></span>
            </div>

            <div class="ticket-field">
              <span class="tf-label">Class</span>
              <span class="tf-val"><?= htmlspecialchars($cabin, ENT_QUOTES) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Gate</span>
              <span class="tf-val">T2 / Gate 4B</span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Seat</span>
              <span class="tf-val tf-val-highlight">14A</span>
            </div>

            <div class="ticket-field">
              <span class="tf-label">From</span>
              <span class="tf-val">Manila (MNL)</span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Flight</span>
              <span class="tf-val"><?= htmlspecialchars(trim(explode('(', $flightSchedule)[0] ?: 'Philippines AirAsia AG-204'), ENT_QUOTES) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Destination</span>
              <span class="tf-val tf-val-highlight"><?= htmlspecialchars($tripCity, ENT_QUOTES) ?></span>
            </div>
          </div>

          <div class="ticket-barcode-wrap">
            <svg class="barcode-svg" viewBox="0 0 240 40">
              <rect x="0" y="0" width="3" height="40" fill="#0e305d"/>
              <rect x="5" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="8" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="15" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="20" y="0" width="5" height="40" fill="#0e305d"/>
              <rect x="28" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="32" y="0" width="3" height="40" fill="#0e305d"/>
              <rect x="38" y="0" width="6" height="40" fill="#0e305d"/>
              <rect x="47" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="52" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="60" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="64" y="0" width="5" height="40" fill="#0e305d"/>
              <rect x="72" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="77" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="84" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="88" y="0" width="6" height="40" fill="#0e305d"/>
              <rect x="97" y="0" width="3" height="40" fill="#0e305d"/>
              <rect x="103" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="108" y="0" width="5" height="40" fill="#0e305d"/>
              <rect x="116" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="120" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="127" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="132" y="0" width="6" height="40" fill="#0e305d"/>
              <rect x="141" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="145" y="0" width="3" height="40" fill="#0e305d"/>
              <rect x="151" y="0" width="5" height="40" fill="#0e305d"/>
              <rect x="159" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="164" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="171" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="175" y="0" width="6" height="40" fill="#0e305d"/>
              <rect x="184" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="189" y="0" width="5" height="40" fill="#0e305d"/>
              <rect x="197" y="0" width="1" height="40" fill="#0e305d"/>
              <rect x="201" y="0" width="4" height="40" fill="#0e305d"/>
              <rect x="208" y="0" width="2" height="40" fill="#0e305d"/>
              <rect x="213" y="0" width="6" height="40" fill="#0e305d"/>
            </svg>
            <span style="font-size:0.75rem; font-weight:800; color:#5b6b7f; letter-spacing:1px;"><?= htmlspecialchars($bookingRef, ENT_QUOTES) ?></span>
          </div>
        </div>

        <div class="ticket-stub">
          <div class="stub-grid">
            <div class="ticket-field">
              <span class="tf-label">Name</span>
              <span class="tf-val" style="font-size:0.85rem;"><?= htmlspecialchars($travelerName, ENT_QUOTES) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">From</span>
              <span class="tf-val">MNL</span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">To</span>
              <span class="tf-val tf-val-highlight"><?= htmlspecialchars(explode(',', $tripCity)[0], ENT_QUOTES) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Date</span>
              <span class="tf-val" style="font-size:0.82rem;"><?= date('M d, Y', strtotime('+3 days')) ?></span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Gate / Seat</span>
              <span class="tf-val">T2 / 14A</span>
            </div>
            <div class="ticket-field">
              <span class="tf-label">Flight</span>
              <span class="tf-val"><?= htmlspecialchars(trim(explode('(', $flightSchedule)[0] ?: 'AG-204')) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="checkout-card confirm-summary">
      <h2>
        <span class="h2-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
        </span>
        Trip summary
      </h2>
      <div class="summary-row">
        <div><span class="item-name">Flight &mdash; <?= htmlspecialchars($fareName, ENT_QUOTES) ?></span><span class="item-meta"><?= htmlspecialchars($fareDesc, ENT_QUOTES) ?></span></div>
        <span class="item-price"><?= peso($farePriceNum) ?></span>
      </div>
      <?php if ($hotelName !== ''): ?>
      <div class="summary-row">
        <div><span class="item-name"><?= htmlspecialchars($hotelName, ENT_QUOTES) ?></span><span class="item-meta"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?> &middot; <?= peso($hotelPrice) ?>/night</span></div>
        <span class="item-price"><?= peso($hotelTotal) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($carName !== ''): ?>
      <div class="summary-row">
        <div><span class="item-name"><?= htmlspecialchars($carName, ENT_QUOTES) ?></span><span class="item-meta"><?= $days ?> day<?= $days > 1 ? 's' : '' ?> &middot; <?= peso($carPrice) ?>/day</span></div>
        <span class="item-price"><?= peso($carTotal) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($discountNum > 0): ?>
      <div class="summary-row summary-discount-row">
        <div><span class="item-name">🎉 Coupon Discount</span><span class="item-meta"><?= htmlspecialchars($discountLabel, ENT_QUOTES) ?></span></div>
        <span class="item-price">-<?= peso($discountNum) ?></span>
      </div>
      <?php endif; ?>

      <div class="summary-total-row">
        <span>Total paid</span>
        <span class="total-pill"><?= peso($grandTotal) ?></span>
      </div>
    </div>

    <p class="lock-note">A confirmation for <?= htmlspecialchars($travelerEmail, ENT_QUOTES) ?> has been generated. Your booking has been saved to your account!</p>

    <div class="confirm-actions">
      <a href="my_bookings.php" class="primary">View My Trips</a>
      <a href="#" class="secondary" onclick="window.print(); return false;">Print ticket</a>
      <a href="index.php" class="secondary">Back to home</a>
    </div>
  </div>

<?php else: ?>

  <section class="checkout-hero">
    <svg class="hero-plane" width="88" height="88" viewBox="0 0 24 24" fill="#f4f8fc" stroke="#0a4fa0" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
    <div class="checkout-hero-inner">
      <a class="checkout-back-link" href="javascript:history.back()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
        Back to <?= htmlspecialchars($tripCity, ENT_QUOTES) ?>
      </a>
      <h1 class="checkout-hero-title">Secure your trip</h1>
      <p class="checkout-hero-sub">Logged in as <strong><?= htmlspecialchars($travelerName ?: $username, ENT_QUOTES) ?></strong> — review your trip and enter payment details to confirm.</p>
    </div>
  </section>

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
        <input type="hidden" name="promoCode" id="hidden-promo-code" value="<?= htmlspecialchars($promoCode, ENT_QUOTES) ?>">

        <button type="submit" class="btn-confirm" id="btn-submit-booking">Confirm &amp; pay <span id="btn-pay-total"><?= peso($grandTotal) ?></span></button>
        <p class="lock-note">🔒 256-bit SSL Encrypted Payment &middot; Official AeroGlide Ticket Issuance</p>
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
            <span class="item-name">✈️ Flight &mdash; <?= $mode === 'hotels' ? 'Flight Skipped (Hotels & Cars only)' : htmlspecialchars($fareName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= $mode === 'hotels' ? 'No airfare included' : htmlspecialchars($fareDesc, ENT_QUOTES) . ' (' . $adults . ' Guest' . ($adults > 1 ? 's' : '') . ' &middot; ' . htmlspecialchars($cabin) . ')' ?></span>
            <?php if ($mode !== 'hotels' && $flightSchedule !== ''): ?>
              <span class="item-meta" style="color:#0d6efd; font-weight:700; display:block; margin-top:2px;">🕒 <?= htmlspecialchars($flightSchedule, ENT_QUOTES) ?></span>
            <?php endif; ?>
          </div>
          <span class="item-price"><?= $mode === 'hotels' ? '₱0' : peso($farePriceNum) ?></span>
        </div>

        <?php if ($hotelName !== ''): ?>
        <div class="summary-row">
          <div>
            <span class="item-name">🏨 <?= htmlspecialchars($hotelName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= $nights ?> night<?= $nights !== 1 ? 's' : '' ?> &middot; <?= peso($hotelPrice) ?>/night</span>
          </div>
          <span class="item-price"><?= peso($hotelTotal) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($carName !== ''): ?>
        <div class="summary-row">
          <div>
            <span class="item-name">🚗 <?= htmlspecialchars($carName, ENT_QUOTES) ?></span>
            <span class="item-meta"><?= $days ?> day<?= $days !== 1 ? 's' : '' ?> &middot; <?= peso($carPrice) ?>/day</span>
          </div>
          <span class="item-price"><?= peso($carTotal) ?></span>
        </div>
        <?php endif; ?>

        <!-- Dynamic Discount Row -->
        <div class="summary-row summary-discount-row" id="discount-row" style="<?= $discountNum > 0 ? '' : 'display:none;' ?>">
          <div>
            <span class="item-name">🎉 Coupon Discount</span>
            <span class="item-meta" id="discount-label"><?= htmlspecialchars($discountLabel, ENT_QUOTES) ?></span>
          </div>
          <span class="item-price" id="discount-amount">-<?= peso($discountNum) ?></span>
        </div>

        <div class="promo-box-container">
          <div class="promo-row">
            <input class="field-input" type="text" id="promo-input" placeholder="Coupon code" value="<?= htmlspecialchars($promoCode, ENT_QUOTES) ?>" autocomplete="off">
            <button type="button" class="btn-apply-promo" id="btn-apply-promo">Apply</button>
          </div>
          <div class="promo-status-msg" id="promo-status">
            💡 Try codes: <strong>5people2026</strong> (30% OFF), <strong>septdeal2026</strong> (-₱1,500), or <strong>berfly1000</strong> (-₱1,000)
          </div>
        </div>

        <div class="summary-total-row">
          <span>Total</span>
          <span class="total-pill" id="total-pill-disp"><?= peso($grandTotal) ?></span>
        </div>
      </div>
    </div>

  </div>

<?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function () {
  'use strict';
  const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
  const subTotal = <?= json_encode($subTotal) ?>;

  const methods = document.querySelectorAll('#pay-methods .pay-method');
  const cardFields = document.getElementById('card-fields');
  const altNote = document.getElementById('alt-pay-note');

  const notes = {
    card: '',
    gcash: "You'll get a GCash payment prompt after confirming.",
    bank: 'Bank transfer details will be sent to your email after confirming.'
  };

  methods.forEach(function (label) {
    label.addEventListener('click', function () {
      methods.forEach(function (l) { l.classList.remove('is-active'); });
      label.classList.add('is-active');
      const method = label.dataset.method;
      if (cardFields) cardFields.classList.toggle('is-visible', method === 'card');
      if (altNote) altNote.textContent = notes[method] || '';
    });
  });

  /* ================= STRICT INPUT RESTRICTIONS & FORMATTING ================= */
  const cardNumInput = document.getElementById('cardNumber');
  const cardExpInput = document.getElementById('cardExpiry');
  const cardCvvInput = document.getElementById('cardCvv');
  const phoneInput   = document.getElementById('travelerPhone');

  if (cardNumInput) {
    cardNumInput.setAttribute('maxlength', '19');
    cardNumInput.setAttribute('inputmode', 'numeric');
    cardNumInput.addEventListener('input', (e) => {
      let digits = e.target.value.replace(/\D/g, '').substring(0, 16);
      let formatted = digits.match(/.{1,4}/g)?.join(' ') || digits;
      e.target.value = formatted;
    });
  }

  if (cardExpInput) {
    cardExpInput.setAttribute('maxlength', '5');
    cardExpInput.setAttribute('inputmode', 'numeric');
    cardExpInput.addEventListener('input', (e) => {
      let digits = e.target.value.replace(/\D/g, '').substring(0, 4);
      if (digits.length >= 2) {
        let mm = parseInt(digits.substring(0, 2), 10);
        if (mm > 12) digits = '12' + digits.substring(2);
        if (mm === 0) digits = '01' + digits.substring(2);
        e.target.value = digits.substring(0, 2) + (digits.length > 2 ? '/' + digits.substring(2) : '');
      } else {
        e.target.value = digits;
      }
    });
  }

  if (cardCvvInput) {
    cardCvvInput.setAttribute('maxlength', '3');
    cardCvvInput.setAttribute('inputmode', 'numeric');
    cardCvvInput.addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
    });
  }

  if (phoneInput) {
    phoneInput.setAttribute('maxlength', '13');
    phoneInput.addEventListener('input', (e) => {
      let val = e.target.value;
      if (val.startsWith('+')) {
        e.target.value = '+' + val.substring(1).replace(/\D/g, '').substring(0, 12);
      } else {
        e.target.value = val.replace(/\D/g, '').substring(0, 11);
      }
    });
  }

  /* ================= PROMO CODE AUTO-DEDUCTION ENGINE ================= */
  const promoInput = document.getElementById('promo-input');
  const btnApply   = document.getElementById('btn-apply-promo');
  const promoMsg   = document.getElementById('promo-status');
  const discRow    = document.getElementById('discount-row');
  const discLabel  = document.getElementById('discount-label');
  const discAmt    = document.getElementById('discount-amount');
  const totalPill  = document.getElementById('total-pill-disp');
  const btnPayTot  = document.getElementById('btn-pay-total');
  const hiddenCode = document.getElementById('hidden-promo-code');

  function applyCoupon(code) {
    const cleanCode = (code || '').trim().toUpperCase();
    let discount = 0;
    let label = '';
    const guestCount = <?= json_encode($adults) ?>;

    if (['5PEOPLE2026', '5PEOPLE'].includes(cleanCode)) {
      if (guestCount < 5) {
        if (discRow) discRow.style.display = 'none';
        if (totalPill) totalPill.textContent = peso(subTotal);
        if (btnPayTot) btnPayTot.textContent = peso(subTotal);
        if (hiddenCode) hiddenCode.value = '';
        if (promoMsg) {
          promoMsg.className = 'promo-status-msg is-error';
          promoMsg.innerHTML = `⚠️ Code <strong>5people2026</strong> requires at least 5 guests in your booking. (Your booking has: ${guestCount} guest${guestCount !== 1 ? 's' : ''})`;
        }
        return;
      }
      discount = Math.round(subTotal * 0.30);
      label = '5 People Deal (30% OFF)';
    } else if (['GLIDENIGHT', 'SEPTEMBER30'].includes(cleanCode)) {
      discount = Math.round(subTotal * 0.30);
      label = 'September Flash Sale (30% OFF)';
    } else if (['SEPTDEAL2026', 'SEPTDEAL', 'CHRISTMAS1500', 'HOLIDAY1500'].includes(cleanCode)) {
      discount = Math.min(1500, subTotal);
      label = 'September Deal (-₱1,500)';
    } else if (['BERFLY1000', 'BERFLY', 'FLY1000'].includes(cleanCode)) {
      discount = Math.min(1000, subTotal);
      label = 'Ber Months Fly Deal (-₱1,000)';
    } else if (['AEROGLIDE10', 'WELCOME10'].includes(cleanCode)) {
      discount = Math.round(subTotal * 0.10);
      label = 'Welcome Coupon (10% OFF)';
    }

    if (discount > 0) {
      const finalTotal = Math.max(0, subTotal - discount);
      if (discRow) discRow.style.display = 'flex';
      if (discLabel) discLabel.textContent = label;
      if (discAmt) discAmt.textContent = '-' + peso(discount);
      if (totalPill) totalPill.textContent = peso(finalTotal);
      if (btnPayTot) btnPayTot.textContent = peso(finalTotal);
      if (hiddenCode) hiddenCode.value = cleanCode;
      if (promoMsg) {
        promoMsg.className = 'promo-status-msg is-success';
        promoMsg.innerHTML = `✅ Coupon <strong>${cleanCode}</strong> applied! You saved <strong>${peso(discount)}</strong>.`;
      }
    } else if (cleanCode !== '') {
      if (discRow) discRow.style.display = 'none';
      if (totalPill) totalPill.textContent = peso(subTotal);
      if (btnPayTot) btnPayTot.textContent = peso(subTotal);
      if (hiddenCode) hiddenCode.value = '';
      if (promoMsg) {
        promoMsg.className = 'promo-status-msg is-error';
        promoMsg.innerHTML = `❌ Invalid code. Try <strong>5people2026</strong>, <strong>septdeal2026</strong>, or <strong>berfly1000</strong>.`;
      }
    }
  }

  if (btnApply && promoInput) {
    btnApply.addEventListener('click', () => applyCoupon(promoInput.value));
    promoInput.addEventListener('keyup', (e) => {
      if (e.key === 'Enter') applyCoupon(promoInput.value);
    });
    if (promoInput.value) {
      applyCoupon(promoInput.value);
    }
  }
})();
</script>
</body>
</html>
