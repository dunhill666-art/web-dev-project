<?php
// ============================================================
// AeroGlide — my_bookings.php
// User Dashboard & My Trips area with Flight Cancellation Flow
// ============================================================

require_once __DIR__ . '/database/function.php';
require_once __DIR__ . '/database/validation.php';
require_once __DIR__ . '/database/success.php';
require_once __DIR__ . '/auth_helper.php';

if (!auth_is_logged_in()) {
    header('Location: ' . ag_base_url('login.php?redirect=my_bookings.php&msg=login_required'));
    exit;
}

$currentUser  = auth_get_user();
$userId       = $currentUser['id'];
$username     = $currentUser['username'];
$userBookings = auth_get_user_bookings($userId);
$usedCoupon   = auth_user_has_claimed_coupon($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard &amp; My Trips — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ag_base_url('style.css') ?>">
<style>
  .user-dash-wrap {
    max-width: 1120px;
    margin: 40px auto 80px;
    padding: 0 24px;
  }
  .user-header-card {
    background: linear-gradient(135deg, #0d6efd, #0a4fa0);
    border-radius: 24px;
    padding: 2.25rem 2.5rem;
    color: #ffffff;
    box-shadow: 0 16px 40px rgba(13, 110, 253, 0.22);
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
  }
  .user-header-info h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 900;
    margin: 0 0 6px 0;
  }
  .user-header-info p {
    margin: 0;
    font-size: 0.95rem;
    opacity: 0.9;
  }
  .user-badge-grid {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }
  .u-stat-chip {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 14px;
    padding: 10px 18px;
    font-size: 0.85rem;
    font-weight: 700;
  }
  .th-city-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--ink, #0a1425);
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .trip-history-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(10, 20, 35, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .trip-history-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(13, 110, 253, 0.1);
    border-color: #bcd8f3;
  }
  .th-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    border-bottom: 1px dashed #e2e8f0;
    margin-bottom: 16px;
  }
  .th-ref-badge {
    background: #eef5ff;
    color: #0d6efd;
    font-weight: 800;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.85rem;
  }
  .th-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .th-detail-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px 16px;
  }
  .th-detail-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #5b6b7f;
    text-transform: uppercase;
    display: block;
    margin-bottom: 4px;
  }
  .th-detail-val {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0a1425;
  }
  .btn-view-ticket {
    background: #0d6efd;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-view-ticket:hover { background: #0a4fa0; }

  .btn-cancel-trip {
    background: #fff;
    color: #dc2626;
    border: 1.5px solid #fca5a5;
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-cancel-trip:hover {
    background: #fdeceb;
    border-color: #f87171;
    transform: translateY(-1px);
  }

  .empty-trips-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
  }

  /* Ticket Modal */
  .ticket-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(8, 14, 26, 0.85);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 2000;
  }
  .ticket-modal-overlay.is-active { display: flex; }
  .ticket-modal-card {
    background: #fff;
    border-radius: 20px;
    max-width: 820px;
    width: 100%;
    position: relative;
    box-shadow: 0 24px 60px rgba(0,0,0,0.3);
    overflow: hidden;
  }
  .modal-close-btn {
    position: absolute;
    top: 14px;
    right: 18px;
    font-size: 1.8rem;
    color: #fff;
    cursor: pointer;
    z-index: 10;
  }

  /* Cancel Modal Styling */
  .cancel-modal-card {
    background: #ffffff;
    border-radius: 24px;
    max-width: 540px;
    width: 100%;
    position: relative;
    box-shadow: 0 25px 60px rgba(10, 20, 35, 0.35);
    overflow: hidden;
    animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes modalPop {
    from { opacity:0; transform: scale(0.92); }
    to   { opacity:1; transform: scale(1); }
  }
  .cancel-modal-header {
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
    padding: 2.25rem 2rem 1.5rem;
    text-align: center;
    border-bottom: 1px solid #fecdd3;
  }
  .cancel-warning-icon {
    font-size: 2.75rem;
    margin-bottom: 8px;
  }
  .cancel-modal-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.6rem;
    font-weight: 900;
    color: #9f1239;
    margin: 0 0 6px;
  }
  .cancel-modal-subtitle {
    font-size: 0.9rem;
    color: #be123c;
    margin: 0;
    line-height: 1.4;
  }
  .cancel-modal-body {
    padding: 1.75rem 2rem 2rem;
  }
  .cancel-summary-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 18px;
    margin-bottom: 20px;
  }
  .cs-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.88rem;
    padding: 5px 0;
    border-bottom: 1px dashed #e2e8f0;
  }
  .cs-row:last-child { border-bottom: none; }
  .cs-label { color: #64748b; font-weight: 600; }
  .cs-val { color: #0f172a; font-weight: 800; text-align: right; }

  .cancel-calc-box {
    background: #fff1f2;
    border: 1.5px solid #fecdd3;
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 24px;
  }
  .cc-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.92rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: #334155;
  }
  .cc-fee { color: #dc2626; }
  .cc-refund {
    border-top: 1.5px solid #fca5a5;
    padding-top: 10px;
    margin-top: 8px;
    font-size: 1.05rem;
  }
  .cc-note {
    font-size: 0.78rem;
    color: #9f1239;
    margin-top: 10px;
    line-height: 1.35;
    background: rgba(255, 255, 255, 0.7);
    padding: 8px 12px;
    border-radius: 8px;
  }
  .cancel-btn-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .btn-keep-booking {
    background: #f1f5f9;
    color: #334155;
    border: none;
    padding: 12px 18px;
    border-radius: 14px;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s ease;
  }
  .btn-keep-booking:hover { background: #e2e8f0; }

  .btn-confirm-cancel {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #ffffff;
    border: none;
    padding: 12px 18px;
    border-radius: 14px;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
    transition: all 0.2s ease;
  }
  .btn-confirm-cancel:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.4);
  }
</style>
</head>
<body class="aeroglide-page">

<?php
$activeNav = 'trips';
include __DIR__ . '/includes/navbar.php';
?>

<main class="user-dash-wrap">
  <!-- USER WELCOME CARD -->
  <div class="user-header-card">
    <div class="user-header-info">
      <h1>Welcome, <?= htmlspecialchars($currentUser['name']) ?> 👋</h1>
      <p>Manage your booked flight itineraries, print boarding passes, or initiate trip cancellations with automated refund processing.</p>
    </div>
    <div class="user-badge-grid">
      <div class="u-stat-chip">✈️ <?= count($userBookings) ?> Booked Trip<?= count($userBookings) === 1 ? '' : 's' ?></div>
      <div class="u-stat-chip">🎁 Coupon Status: <?= $usedCoupon ? "Claimed ('{$usedCoupon}')" : '3 Deals Available' ?></div>
    </div>
  </div>

  <!-- CANCELLATION NOTIFICATION BANNER -->
  <?php if (($_GET['msg'] ?? '') === 'cancelled'): 
    $cRef    = htmlspecialchars($_GET['ref'] ?? 'Your booking', ENT_QUOTES);
    $cRefund = htmlspecialchars($_GET['refund'] ?? '4,500', ENT_QUOTES);
  ?>
    <div style="background:#ecfdf5; border:1.5px solid #a7f3d0; border-radius:18px; padding:1.25rem 1.5rem; margin-bottom:24px; display:flex; align-items:center; gap:16px; box-shadow:0 6px 20px rgba(16,185,129,0.12);">
      <div style="font-size:2.2rem; line-height:1;">✓</div>
      <div>
        <h3 style="font-family:'Montserrat',sans-serif; font-weight:800; font-size:1.1rem; color:#065f46; margin:0 0 4px;">Trip Cancelled Successfully</h3>
        <p style="margin:0; font-size:0.9rem; color:#047857; line-height:1.4;">
          Reservation <strong><?= $cRef ?></strong> has been updated to <strong>CANCELLED</strong>. An estimated refund of <strong>₱<?= $cRefund ?></strong> (after ₱500 cancellation fee) will be credited to your original payment method.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($_GET['error'])): ?>
    <div style="background:#fdeceb; border:1.5px solid #f4b8b3; border-radius:18px; padding:1.25rem 1.5rem; margin-bottom:24px; color:#8a1f14; font-weight:700;">
      ⚠️ <?= htmlspecialchars($_GET['error'], ENT_QUOTES) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($usedCoupon)): ?>
    <?php render_success_banner('Active Promo Coupon Claimed', "Your account currently has active promo '{$usedCoupon}' applied to your trip history. Each traveler account is limited to 1 claimed coupon.", ag_base_url('index.php#deals'), 'View Deals'); ?>
  <?php endif; ?>

  <h2 style="font-family:'Montserrat',sans-serif; font-size:1.5rem; font-weight:800; margin-bottom:20px; color:#0a1425;">Your Flight &amp; Package History</h2>

  <?php if (empty($userBookings)): ?>
    <div class="empty-trips-card">
      <div style="font-size:3rem; margin-bottom:1rem;">✈️</div>
      <h2 style="font-family:'Montserrat',sans-serif; font-size:1.4rem; margin-bottom:8px;">No booked trips found</h2>
      <p style="color:#5b6b7f; margin-bottom:24px;">Explore our domestic flight routes and getaway deals to schedule your next adventure!</p>
      <a href="<?= ag_base_url('index.php#deals') ?>" class="btn-proceed" style="text-decoration:none; display:inline-block;">Browse Philippine Deals</a>
    </div>
  <?php else: ?>
    <?php foreach ($userBookings as $idx => $b): 
      $bRef        = $b['bookingRef'] ?? ('AG-' . strtoupper(substr(md5($idx . time()), 0, 8)));
      $bCity       = $b['city'] ?? 'Boracay, Aklan';
      $bDate       = isset($b['bookingDate']) ? date('M d, Y', strtotime($b['bookingDate'])) : date('M d, Y');
      $bSched      = $b['flightSchedule'] ?? 'Philippines AirAsia AG-204 (03:55 AM MNL T2 → 05:20 AM)';
      $bFare       = $b['fareName'] ?? 'Smart Saver';
      $bAdults     = $b['adults'] ?? 1;
      $bCabin      = $b['cabin'] ?? 'Economy';
      $bHotel      = $b['hotelName'] ?? '';
      $bCar        = $b['carName'] ?? '';
      $bTotal      = $b['grandTotal'] ?? $b['subTotal'] ?? 4999;
      $bCoupon     = $b['discountLabel'] ?? '';
      $isCancelled = (strtolower($b['status'] ?? '') === 'cancelled');
    ?>

    <?php if ($isCancelled): ?>
      <!-- CANCELLED BOOKING CARD -->
      <div class="trip-history-card" style="background:#fafafa; border-color:#cbd5e1; box-shadow:none;">
        <div class="th-top-row">
          <span class="th-ref-badge" style="background:#fdeceb; color:#9f1239; font-weight:800;">Ref: <?= htmlspecialchars($bRef, ENT_QUOTES) ?></span>
          <span style="font-size:0.85rem; color:#9f1239; font-weight:700; background:#ffe4e6; padding:4px 12px; border-radius:999px;">
            ● CANCELLED
          </span>
        </div>

        <h2 class="th-city-title" style="color:#64748b;">
          <span>📍</span> <?= htmlspecialchars($bCity, ENT_QUOTES) ?>
          <span style="font-size:0.75rem; font-weight:800; color:#dc2626; background:#fee2e2; padding:3px 10px; border-radius:8px; margin-left:8px;">VOID</span>
        </h2>

        <div class="th-details-grid">
          <div class="th-detail-box" style="background:#f1f5f9;">
            <span class="th-detail-label">Fare &amp; Cabin Class</span>
            <span class="th-detail-val" style="color:#64748b;"><?= htmlspecialchars($bFare, ENT_QUOTES) ?> &middot; <?= htmlspecialchars($bCabin, ENT_QUOTES) ?> (<?= $bAdults ?> Guest<?= $bAdults > 1 ? 's' : '' ?>)</span>
          </div>
          <div class="th-detail-box" style="background:#f1f5f9;">
            <span class="th-detail-label">Flight Schedule</span>
            <span class="th-detail-val" style="color:#94a3b8; text-decoration:line-through;"><?= htmlspecialchars($bSched, ENT_QUOTES) ?></span>
          </div>
        </div>

        <!-- CANCELLATION & REFUND BREAKDOWN BOX -->
        <?php 
          $cFee = $b['cancellationFee'] > 0 ? $b['cancellationFee'] : min(500, $bTotal);
          $cRefundVal = $b['refundAmount'] > 0 ? $b['refundAmount'] : max(0, $bTotal - $cFee);
          $cDate = !empty($b['cancelledAt']) ? date('M d, Y', strtotime($b['cancelledAt'])) : date('M d, Y');
        ?>
        <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:14px; padding:16px 20px; margin-bottom:16px;">
          <div style="font-size:0.8rem; font-weight:800; color:#9f1239; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
            <span>💸</span> Cancellation &amp; Refund Record
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; font-size:0.9rem;">
            <div><span style="color:#64748b; font-size:0.8rem; display:block;">Original Total Paid:</span> <strong>₱<?= number_format($bTotal) ?></strong></div>
            <div><span style="color:#64748b; font-size:0.8rem; display:block;">Cancellation Fee:</span> <strong style="color:#dc2626;">- ₱<?= number_format($cFee) ?></strong></div>
            <div><span style="color:#64748b; font-size:0.8rem; display:block;">Refund Credited:</span> <strong style="color:#16a34a; font-size:1.05rem;">₱<?= number_format($cRefundVal) ?></strong></div>
            <div><span style="color:#64748b; font-size:0.8rem; display:block;">Refund Destination:</span> <strong>Original Payment Method</strong></div>
          </div>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:16px; border-top:1px solid #e2e8f0;">
          <span style="font-size:0.85rem; color:#64748b; font-weight:600;">Cancelled on <?= $cDate ?></span>
          <span style="font-size:0.88rem; font-weight:800; color:#9f1239; background:#ffe4e6; padding:8px 16px; border-radius:10px;">❌ Ticket Void / Flight Cancelled</span>
        </div>
      </div>

    <?php else: ?>
      <!-- CONFIRMED BOOKING CARD -->
      <div class="trip-history-card">
        <div class="th-top-row">
          <span class="th-ref-badge">Ref: <?= htmlspecialchars($bRef, ENT_QUOTES) ?></span>
          <span style="font-size:0.85rem; color:#5b6b7f; font-weight:600;">Booked on <?= htmlspecialchars($bDate, ENT_QUOTES) ?> &middot; <strong style="color:#16a34a;">CONFIRMED</strong></span>
        </div>

        <h2 class="th-city-title">
          <span>📍</span> <?= htmlspecialchars($bCity, ENT_QUOTES) ?>
        </h2>

        <div class="th-details-grid">
          <div class="th-detail-box">
            <span class="th-detail-label">Fare &amp; Cabin Class</span>
            <span class="th-detail-val"><?= htmlspecialchars($bFare, ENT_QUOTES) ?> &middot; <?= htmlspecialchars($bCabin, ENT_QUOTES) ?> (<?= $bAdults ?> Guest<?= $bAdults > 1 ? 's' : '' ?>)</span>
          </div>
          <div class="th-detail-box">
            <span class="th-detail-label">Flight Schedule</span>
            <span class="th-detail-val" style="color:#0d6efd;"><?= htmlspecialchars($bSched, ENT_QUOTES) ?></span>
          </div>
          <?php if ($bHotel !== ''): ?>
          <div class="th-detail-box">
            <span class="th-detail-label">Hotel Accommodation</span>
            <span class="th-detail-val">🏨 <?= htmlspecialchars($bHotel, ENT_QUOTES) ?> (<?= $b['nights'] ?? 1 ?> nights)</span>
          </div>
          <?php endif; ?>
          <?php if ($bCar !== ''): ?>
          <div class="th-detail-box">
            <span class="th-detail-label">Car Rental Service</span>
            <span class="th-detail-val">🚗 <?= htmlspecialchars($bCar, ENT_QUOTES) ?> (<?= $b['days'] ?? 1 ?> days)</span>
          </div>
          <?php endif; ?>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:16px; border-top:1px solid #f1f5f9; flex-wrap:wrap; gap:16px;">
          <div>
            <span style="font-size:0.8rem; color:#5b6b7f; display:block;">Grand Total Paid</span>
            <strong style="font-size:1.3rem; color:#0a1425; font-family:'Montserrat',sans-serif;">₱<?= number_format($bTotal) ?></strong>
            <?php if ($bCoupon !== ''): ?>
              <span style="font-size:0.75rem; color:#16a34a; font-weight:700; display:block; margin-top:2px;">🎉 <?= htmlspecialchars($bCoupon, ENT_QUOTES) ?></span>
            <?php endif; ?>
          </div>

          <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <button type="button" class="btn-view-ticket" data-ticket='<?= json_encode([
              'ref'     => $bRef,
              'name'    => $b['travelerName'] ?? $currentUser['name'],
              'city'    => $bCity,
              'sched'   => $bSched,
              'cabin'   => $bCabin,
              'fare'    => $bFare,
              'date'    => date('M d, Y', strtotime('+3 days')),
            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              <span>🎟️</span> View Boarding Pass
            </button>

            <button type="button" class="btn-cancel-trip" data-cancel='<?= json_encode([
              'ref'    => $bRef,
              'city'   => $bCity,
              'date'   => $bDate,
              'sched'  => $bSched,
              'total'  => $bTotal,
              'name'   => $b['travelerName'] ?? $currentUser['name'],
            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              <span>❌</span> Cancel Trip
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php endforeach; ?>
  <?php endif; ?>
</main>

<!-- BOARDING PASS TICKET MODAL -->
<div class="ticket-modal-overlay" id="ticket-modal">
  <div class="ticket-modal-card">
    <span class="modal-close-btn" id="modal-close">&times;</span>
    <div id="modal-ticket-container"></div>
    <div style="text-align:center; padding:16px 24px 24px; background:#ffffff; border-top:1px dashed #e2e8f0;">
      <button type="button" class="btn-proceed" onclick="window.print();">🖨️ Print Boarding Pass</button>
    </div>
  </div>
</div>

<!-- CANCELLATION CONFIRMATION MODAL -->
<div class="ticket-modal-overlay" id="cancel-modal">
  <div class="cancel-modal-card">
    <span class="modal-close-btn" id="cancel-modal-close" style="color:#9f1239;">&times;</span>
    <div class="cancel-modal-header">
      <div class="cancel-warning-icon">⚠️</div>
      <h2 class="cancel-modal-title">Cancel this trip?</h2>
      <p class="cancel-modal-subtitle">Are you sure you want to cancel this reservation? Cancellation has financial consequences.</p>
    </div>

    <div class="cancel-modal-body">
      <div class="cancel-summary-box">
        <div class="cs-row"><span class="cs-label">Booking Ref</span><strong class="cs-val" id="cm-ref">AG-12345</strong></div>
        <div class="cs-row"><span class="cs-label">Flight Route</span><strong class="cs-val" id="cm-city">Boracay, Aklan</strong></div>
        <div class="cs-row"><span class="cs-label">Passenger Name</span><span class="cs-val" id="cm-name">Traveler</span></div>
        <div class="cs-row"><span class="cs-label">Schedule</span><span class="cs-val" id="cm-sched" style="font-size:0.82rem;">AirAsia AG-204</span></div>
      </div>

      <div class="cancel-calc-box">
        <div class="cc-row"><span>Original Total Paid:</span><strong id="cm-total">₱5,000</strong></div>
        <div class="cc-row cc-fee"><span>Cancellation Fee:</span><strong>- ₱500</strong></div>
        <div class="cc-row cc-refund"><span>Estimated Refund:</span><strong id="cm-refund" style="color:#16a34a; font-size:1.15rem;">₱4,500</strong></div>
        <div class="cc-note">💡 Refund will be automatically recorded and credited back to your <strong>Original Payment Method</strong>.</div>
      </div>

      <form method="POST" action="<?= ag_base_url('auth.php') ?>">
        <input type="hidden" name="action" value="cancel_booking">
        <input type="hidden" name="booking_ref" id="cm-input-ref" value="">
        
        <div class="cancel-btn-group">
          <button type="button" class="btn-keep-booking" id="btn-keep-booking">Go Back &amp; Keep Booking</button>
          <button type="submit" class="btn-confirm-cancel">Confirm Cancellation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function() {
  'use strict';

  // Ticket Modal logic
  const modal = document.getElementById('ticket-modal');
  const closeBtn = document.getElementById('modal-close');
  const container = document.getElementById('modal-ticket-container');

  function renderTicketHTML(t) {
    const flightName = t.sched.split('(')[0] || 'Philippines AirAsia AG-204';
    const flightTime = t.sched.includes('(') ? t.sched.split('(')[1].split(')')[0] : '03:55 AM MNL T2';
    const cityClean  = t.city.split(',')[0];

    return `
      <div class="ticket-wrapper" style="margin:0; box-shadow:none; border-radius:0; border:none;">
        <div class="ticket-header">
          <div class="ticket-brand">✈ AIRLINE TICKET</div>
          <div class="ticket-stub-title">BOARDING PASS</div>
        </div>

        <div class="ticket-body">
          <div class="ticket-main">
            <div class="ticket-grid">
              <div class="ticket-field">
                <span class="tf-label">Name of Passenger</span>
                <span class="tf-val">${t.name}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Date</span>
                <span class="tf-val">${t.date}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Time</span>
                <span class="tf-val tf-val-highlight">${flightTime}</span>
              </div>

              <div class="ticket-field">
                <span class="tf-label">Class</span>
                <span class="tf-val">${t.cabin} (${t.fare})</span>
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
                <span class="tf-val">${flightName}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Destination</span>
                <span class="tf-val tf-val-highlight">${t.city}</span>
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
              <span style="font-size:0.75rem; font-weight:800; color:#5b6b7f; letter-spacing:1px;">${t.ref}</span>
            </div>
          </div>

          <div class="ticket-stub">
            <div class="stub-grid">
              <div class="ticket-field">
                <span class="tf-label">Name</span>
                <span class="tf-val" style="font-size:0.85rem;">${t.name}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">From</span>
                <span class="tf-val">MNL</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">To</span>
                <span class="tf-val tf-val-highlight">${cityClean}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Date</span>
                <span class="tf-val" style="font-size:0.82rem;">${t.date}</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Gate / Seat</span>
                <span class="tf-val">T2 / 14A</span>
              </div>
              <div class="ticket-field">
                <span class="tf-label">Flight</span>
                <span class="tf-val">${flightName.split(' ')[0]}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  document.querySelectorAll('.btn-view-ticket').forEach(btn => {
    btn.addEventListener('click', () => {
      const data = JSON.parse(btn.dataset.ticket);
      container.innerHTML = renderTicketHTML(data);
      modal.classList.add('is-active');
    });
  });

  closeBtn?.addEventListener('click', () => modal.classList.remove('is-active'));
  modal?.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('is-active'); });

  // Cancel Modal Logic
  const cancelModal = document.getElementById('cancel-modal');
  const cancelClose = document.getElementById('cancel-modal-close');
  const keepBtn     = document.getElementById('btn-keep-booking');

  const cmRef       = document.getElementById('cm-ref');
  const cmCity      = document.getElementById('cm-city');
  const cmName      = document.getElementById('cm-name');
  const cmSched     = document.getElementById('cm-sched');
  const cmTotal     = document.getElementById('cm-total');
  const cmRefund    = document.getElementById('cm-refund');
  const cmInputRef  = document.getElementById('cm-input-ref');

  document.querySelectorAll('.btn-cancel-trip').forEach(btn => {
    btn.addEventListener('click', () => {
      const data = JSON.parse(btn.dataset.cancel);
      const totalNum = parseFloat(data.total) || 0;
      const feeNum   = Math.min(500, totalNum);
      const refundNum = Math.max(0, totalNum - feeNum);

      cmRef.textContent      = data.ref;
      cmCity.textContent     = data.city;
      cmName.textContent     = data.name;
      cmSched.textContent    = data.sched;
      cmTotal.textContent    = '₱' + totalNum.toLocaleString('en-US');
      cmRefund.textContent   = '₱' + refundNum.toLocaleString('en-US');
      cmInputRef.value       = data.ref;

      cancelModal.classList.add('is-active');
    });
  });

  const hideCancelModal = () => cancelModal.classList.remove('is-active');
  cancelClose?.addEventListener('click', hideCancelModal);
  keepBtn?.addEventListener('click', hideCancelModal);
  cancelModal?.addEventListener('click', (e) => { if (e.target === cancelModal) hideCancelModal(); });

})();
</script>
</body>
</html>
