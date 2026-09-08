<?php
// ============================================================
// AeroGlide — my_bookings.php
// Dedicated "My Trips" dashboard for authenticated users.
// Shows complete summary of booked flights, hotels, cars, & boarding passes.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

if (!auth_is_logged_in()) {
    header('Location: login.php?redirect=my_bookings.php&msg=login_required');
    exit;
}

$currentUser  = auth_get_user();
$userId       = $currentUser['id'];
$username     = $currentUser['username'];
$userBookings = auth_get_user_bookings($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Booked Trips — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .my-trips-main {
    max-width: 1080px;
    margin: 40px auto 80px;
    padding: 0 24px;
  }
  .trips-page-header {
    margin-bottom: 32px;
  }
  .trips-page-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--ink, #0a1425);
    margin: 0 0 6px;
  }
  .trips-page-sub {
    color: var(--muted, #5b6b7f);
    font-size: 1rem;
    margin: 0;
  }
  .empty-trips-card {
    background: #ffffff;
    border: 1px solid var(--ag-line, #e2e8f0);
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
  }
  .empty-trips-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
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
    letter-spacing: 0.5px;
  }
  .th-date {
    font-size: 0.85rem;
    color: var(--muted);
    font-weight: 600;
  }
  .th-city-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--ink);
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
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
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 4px;
  }
  .th-detail-val {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--ink);
  }
  .th-footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
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
  .btn-view-ticket:hover {
    background: #0a4fa0;
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
  .ticket-modal-overlay.is-active {
    display: flex;
  }
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
      <a href="my_bookings.php" class="nav-item is-active">My Trips</a>
      <a href="index.php#about" class="nav-item">Support</a>
    </div>

    <div class="nav-right">
      <span class="nav-user-greeting">Hi, <?= htmlspecialchars($username) ?></span>
      <a class="nav-auth-btn" href="logout.php" title="Logout"><span>Logout</span></a>
    </div>
  </div>
</nav>

<main class="my-trips-main">
  <div class="trips-page-header">
    <h1 class="trips-page-title">My Booked Trips</h1>
    <p class="trips-page-sub">View your flight itineraries, boarding passes, and booking summaries.</p>
  </div>

  <?php if (empty($userBookings)): ?>
    <div class="empty-trips-card">
      <div class="empty-trips-icon">✈️</div>
      <h2 style="font-family:'Montserrat',sans-serif; font-size:1.4rem; margin-bottom:8px;">No booked trips yet</h2>
      <p style="color:var(--muted); margin-bottom:24px;">Explore our exclusive flight deals and package options to book your first getaway!</p>
      <a href="index.php#deals" class="btn-proceed" style="text-decoration:none; display:inline-block;">Browse Exclusive Deals</a>
    </div>
  <?php else: ?>
    <?php foreach ($userBookings as $idx => $b): 
      $bRef   = $b['bookingRef'] ?? ('AG-' . strtoupper(substr(md5($idx . time()), 0, 8)));
      $bCity  = $b['city'] ?? 'Boracay, Aklan';
      $bDate  = isset($b['bookingDate']) ? date('M d, Y', strtotime($b['bookingDate'])) : date('M d, Y');
      $bSched = $b['flightSchedule'] ?? 'Philippines AirAsia AG-204 (03:55 AM MNL T2 → 05:20 AM)';
      $bFare  = $b['fareName'] ?? 'Smart Saver';
      $bAdults= $b['adults'] ?? 1;
      $bCabin = $b['cabin'] ?? 'Economy';
      $bHotel = $b['hotelName'] ?? '';
      $bCar   = $b['carName'] ?? '';
      $bTotal = $b['grandTotal'] ?? $b['subTotal'] ?? 4999;
      $bCoupon= $b['discountLabel'] ?? '';
    ?>
    <div class="trip-history-card">
      <div class="th-top-row">
        <span class="th-ref-badge">Ref: <?= htmlspecialchars($bRef, ENT_QUOTES) ?></span>
        <span class="th-date">Booked on <?= htmlspecialchars($bDate, ENT_QUOTES) ?> &middot; <strong style="color:#16a34a;">CONFIRMED</strong></span>
      </div>

      <h2 class="th-city-title">
        <span>📍</span> <?= htmlspecialchars($bCity, ENT_QUOTES) ?>
      </h2>

      <div class="th-details-grid">
        <div class="th-detail-box">
          <span class="th-detail-label">Flight Package &amp; Cabin</span>
          <span class="th-detail-val"><?= htmlspecialchars($bFare, ENT_QUOTES) ?> &middot; <?= htmlspecialchars($bCabin, ENT_QUOTES) ?> (<?= $bAdults ?> Guest<?= $bAdults > 1 ? 's' : '' ?>)</span>
        </div>
        <div class="th-detail-box">
          <span class="th-detail-label">Flight Schedule</span>
          <span class="th-detail-val" style="color:#0d6efd;"><?= htmlspecialchars($bSched, ENT_QUOTES) ?></span>
        </div>
        <?php if ($bHotel !== ''): ?>
        <div class="th-detail-box">
          <span class="th-detail-label">Hotel Stay</span>
          <span class="th-detail-val">🏨 <?= htmlspecialchars($bHotel, ENT_QUOTES) ?> (<?= $b['nights'] ?? 1 ?> nights)</span>
        </div>
        <?php endif; ?>
        <?php if ($bCar !== ''): ?>
        <div class="th-detail-box">
          <span class="th-detail-label">Car Rental</span>
          <span class="th-detail-val">🚗 <?= htmlspecialchars($bCar, ENT_QUOTES) ?> (<?= $b['days'] ?? 1 ?> days)</span>
        </div>
        <?php endif; ?>
      </div>

      <div class="th-footer-row">
        <div>
          <span style="font-size:0.8rem; color:var(--muted); display:block;">Total Paid</span>
          <strong style="font-size:1.3rem; color:var(--ink); font-family:'Montserrat',sans-serif;">₱<?= number_format($bTotal) ?></strong>
          <?php if ($bCoupon !== ''): ?>
            <span style="font-size:0.75rem; color:#16a34a; font-weight:700; display:block; margin-top:2px;">🎉 <?= htmlspecialchars($bCoupon, ENT_QUOTES) ?></span>
          <?php endif; ?>
        </div>

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
      </div>
    </div>
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

<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand-block">
      <div class="footer-brand-header">
        <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="footer-brand-logo">
        <span class="footer-brand-name">AeroGlide</span>
      </div>
      <p style="font-size:0.82rem; color:#9ca3af; margin-top:8px; max-width:280px;">
        The premier Philippine domestic travel platform. Book flight deals, hotels, and island rentals with ease.
      </p>
    </div>
    <div class="footer-links-grid">
      <div>
        <h3 class="footer-col-title">Philippines Destinations</h3>
        <ul class="footer-nav-list">
          <li><a href="boracay.php">Boracay, Aklan</a></li>
          <li><a href="coron.php">Coron, Palawan</a></li>
          <li><a href="siargao.php">Siargao, Surigao</a></li>
          <li><a href="albay.php">Mayon Volcano, Albay</a></li>
          <li><a href="chocolate-hills.php">Chocolate Hills, Bohol</a></li>
          <li><a href="taal-volcano.php">Taal Volcano, Batangas</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-col-title">Explore &amp; Deals</h3>
        <ul class="footer-nav-list">
          <li><a href="index.php#deals">Flight Deals</a></li>
          <li><a href="index.php#destinations">Popular Islands</a></li>
          <li><a href="index.php#promos">Coupon Codes</a></li>
          <li><a href="index.php#tracker">Flight Tracker</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-col-title">Account &amp; Support</h3>
        <ul class="footer-nav-list">
          <li><a href="index.php#about">About AeroGlide</a></li>
          <li><a href="my_bookings.php">My Trips</a></li>
          <li><a href="forgot_password.php">Reset Password</a></li>
          <li><a href="index.php#about">Customer Support</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom-line">
    <p>© <?php echo date('Y'); ?> AeroGlide Philippines Inc. · Book smarter, travel further. All rights reserved.</p>
  </div>
</footer>

<script>
(function() {
  'use strict';
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
          <div class="ticket-brand">
            ✈ AIRLINE TICKET
          </div>
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
})();
</script>
</body>
</html>
