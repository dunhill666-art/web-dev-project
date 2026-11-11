<?php
// ============================================================
// AeroGlide — flights.php
// Modern Airline Flight Search Engine & Booking Portal
// Powered by live MySQL database flight inventory
// ============================================================

require_once __DIR__ . '/user/auth_helper.php';
require_once __DIR__ . '/database/flight_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

$activeNav = 'flights';
$pageTitle = 'Search Commercial Flights — AeroGlide';

// Search Parameters
$originInput = strtoupper(trim($_GET['origin'] ?? 'MNL'));
$destInput   = trim($_GET['destination'] ?? $_GET['city'] ?? 'Boracay, Aklan');
$dateInput   = trim($_GET['date'] ?? date('Y-m-d'));
$adults      = max(1, (int)($_GET['adults'] ?? 1));
$cabin       = trim($_GET['cabin'] ?? 'Economy');
$sortOption  = trim($_GET['sort'] ?? 'recommended');
$stopsFilter = trim($_GET['stops'] ?? 'all');
$timeFilter  = trim($_GET['time'] ?? '');

// Resolve route details & query live flight inventory
$routeInfo = flight_resolve_route($destInput, $originInput);
$searchResults = flight_search_results($destInput, $originInput, $dateInput, [
    'time'  => $timeFilter,
    'stops' => $stopsFilter,
    'sort'  => $sortOption === 'price' ? 'price_asc' : ($sortOption === 'earliest' ? 'earliest' : 'recommended'),
]);

$flightsList = $searchResults['flights'] ?? [];
$carousel    = $searchResults['carousel'] ?? [];
$route       = $searchResults['route'] ?? $routeInfo;

// Available Philippine Destinations for dropdown
$availableDestinations = [
    'Boracay, Aklan'             => 'MPH · Caticlan Airport',
    'Coron, Palawan'             => 'USU · Busuanga Airport',
    'Siargao, Surigao del Norte' => 'IAO · Sayak Airport',
    'Mayon Volcano, Albay'       => 'LGP · Bicol International',
    'Chocolate Hills, Bohol'     => 'TAG · Bohol-Panglao Airport',
    'Malapascua Island, Cebu'    => 'CEB · Mactan-Cebu International',
    'Bukidnon'                   => 'CGY · Laguindingan Airport',
    'Kaohsiung, Taiwan'          => 'KHH · Kaohsiung International',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>

<!-- Modern Font Stack: Inter & Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
/* ================= Modern Travel Tech Styles ================= */
:root {
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-display: 'Plus Jakarta Sans', sans-serif;
  
  --navy-900: #09172A;
  --navy-800: #0F233E;
  --navy-700: #1A365D;

  --cobalt-600: #1D4ED8;
  --cobalt-700: #1E40AF;
  --cobalt-50:  #EFF6FF;

  --amber-500: #F59E0B;
  --amber-600: #D97706;
  --amber-700: #B45309;

  --slate-50:  #F8FAFC;
  --slate-100: #F1F5F9;
  --slate-200: #E2E8F0;
  --slate-300: #CBD5E1;
  --slate-400: #94A3B8;
  --slate-500: #64748B;
  --slate-600: #475569;
  --slate-700: #334155;
  --slate-900: #0F172A;

  --emerald-50:  #ECFDF5;
  --emerald-600: #059669;
  --emerald-700: #047857;
}

body {
  font-family: var(--font-sans);
  background-color: var(--slate-50);
  color: var(--slate-900);
  margin: 0;
  -webkit-font-smoothing: antialiased;
}

/* Hero Flight Engine */
.flight-hero {
  background: linear-gradient(180deg, var(--navy-900) 0%, var(--navy-800) 100%);
  color: #ffffff;
  padding: 40px 20px 80px;
  border-bottom: 1px solid var(--navy-700);
}
.flight-hero-wrap {
  max-width: 1140px;
  margin: 0 auto;
}
.flight-hero-title {
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.5vw, 2.5rem);
  font-weight: 800;
  letter-spacing: -0.025em;
  margin: 0 0 8px;
  line-height: 1.2;
}
.flight-hero-sub {
  color: var(--slate-400);
  font-size: 1rem;
  margin: 0 0 28px;
}

/* Search Widget Card */
.search-widget-card {
  background: #ffffff;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 20px 40px rgba(9, 23, 42, 0.2), 0 1px 3px rgba(0,0,0,0.08);
  border: 1px solid var(--slate-200);
  color: var(--slate-900);
}
.widget-top-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--slate-100);
}
.trip-type-pills {
  display: inline-flex;
  background: var(--slate-100);
  padding: 3px;
  border-radius: 10px;
  gap: 2px;
}
.trip-pill {
  padding: 6px 14px;
  font-size: 0.8125rem;
  font-weight: 600;
  border: none;
  background: transparent;
  color: var(--slate-600);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.trip-pill.is-active {
  background: #ffffff;
  color: var(--navy-900);
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.widget-selectors {
  display: flex;
  align-items: center;
  gap: 10px;
}
.widget-select {
  padding: 7px 12px;
  border-radius: 8px;
  border: 1px solid var(--slate-200);
  background: var(--slate-50);
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--slate-700);
  cursor: pointer;
  outline: none;
}
.widget-select:focus {
  border-color: var(--cobalt-600);
  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}

/* Form Inputs Grid */
.search-form-grid {
  display: grid;
  grid-template-columns: 2.5fr auto 2.5fr 2.5fr 1.5fr;
  gap: 12px;
  align-items: flex-end;
  margin-top: 20px;
}
@media (max-width: 992px) {
  .search-form-grid {
    grid-template-columns: 1fr;
  }
}
.field-box {
  display: flex;
  flex-direction: column;
}
.field-box label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--slate-500);
  margin-bottom: 6px;
}
.field-input-wrap {
  position: relative;
}
.field-ctrl {
  width: 100%;
  height: 48px;
  padding: 0 14px;
  border: 1.5px solid var(--slate-200);
  border-radius: 12px;
  font-family: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--slate-900);
  background: #ffffff;
  box-sizing: border-box;
  transition: all 0.15s ease;
}
.field-ctrl:focus {
  outline: none;
  border-color: var(--cobalt-600);
  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}
.btn-swap-route {
  width: 40px;
  height: 48px;
  border-radius: 12px;
  border: 1.5px solid var(--slate-200);
  background: var(--slate-50);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--slate-500);
  transition: all 0.15s ease;
}
.btn-swap-route:hover {
  background: var(--slate-100);
  color: var(--navy-900);
  border-color: var(--slate-300);
}
.btn-search-flights {
  height: 48px;
  background: var(--amber-600);
  color: #ffffff;
  border: none;
  border-radius: 12px;
  font-family: inherit;
  font-size: 0.9375rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
  transition: all 0.15s ease;
}
.btn-search-flights:hover {
  background: var(--amber-700);
  transform: translateY(-1px);
}

/* Results Section Workspace */
.results-workspace {
  max-width: 1140px;
  margin: -40px auto 60px;
  padding: 0 20px;
  position: relative;
  z-index: 10;
}

/* 7-Day Low Fare Carousel */
.fare-carousel-card {
  background: #ffffff;
  border: 1px solid var(--slate-200);
  border-radius: 14px;
  padding: 12px 16px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.carousel-track {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  overflow-x: auto;
}
@media (max-width: 768px) {
  .carousel-track {
    grid-template-columns: repeat(7, minmax(110px, 1fr));
  }
}
.carousel-day {
  padding: 10px 8px;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
  border: 1px solid transparent;
  background: var(--slate-50);
  transition: all 0.15s ease;
  text-decoration: none;
  color: inherit;
}
.carousel-day:hover {
  background: #ffffff;
  border-color: var(--slate-300);
}
.carousel-day.is-active {
  background: var(--cobalt-50);
  border-color: var(--cobalt-600);
  box-shadow: 0 0 0 1px var(--cobalt-600);
}
.c-date {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--slate-500);
  display: block;
  margin-bottom: 3px;
}
.c-price {
  font-size: 0.875rem;
  font-weight: 800;
  color: var(--slate-900);
}
.carousel-day.is-active .c-price {
  color: var(--cobalt-600);
}
.c-badge-lowest {
  display: inline-block;
  font-size: 0.5625rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--emerald-700);
  background: var(--emerald-50);
  border: 1px solid #A7F3D0;
  padding: 1px 4px;
  border-radius: 4px;
  margin-top: 4px;
}

/* Flight Result Card */
.flight-card {
  background: #ffffff;
  border: 1px solid var(--slate-200);
  border-radius: 16px;
  margin-bottom: 18px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.04);
  overflow: hidden;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.flight-card:hover {
  border-color: var(--slate-300);
  box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}
.flight-summary-row {
  padding: 20px 24px;
  display: grid;
  grid-template-columns: 2.2fr 4.2fr 2.6fr;
  align-items: center;
  gap: 20px;
}
@media (max-width: 900px) {
  .flight-summary-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
.airline-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}
.airline-logo-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: var(--slate-50);
  border: 1px solid var(--slate-200);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.95rem;
  color: var(--cobalt-600);
  flex-shrink: 0;
}
.airline-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--slate-900);
  line-height: 1.2;
}
.flight-no {
  font-size: 0.75rem;
  color: var(--slate-400);
  margin-top: 2px;
}

/* Timeline */
.timeline-block {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}
.time-col {
  text-align: left;
}
.time-col.dest {
  text-align: right;
}
.t-val {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--slate-900);
  font-variant-numeric: tabular-nums;
  line-height: 1;
}
.t-code {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--slate-600);
  margin-top: 4px;
}
.flight-track-mid {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 10px;
}
.track-duration {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--slate-500);
  margin-bottom: 4px;
}
.track-line {
  width: 100%;
  height: 2px;
  background: var(--slate-300);
  position: relative;
  border-radius: 99px;
}
.track-plane {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(90deg);
  width: 14px;
  height: 14px;
  color: var(--slate-500);
}
.track-stops {
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--emerald-600);
  margin-top: 4px;
}

/* Price & Button */
.price-action-box {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
  border-left: 1px solid var(--slate-100);
  padding-left: 16px;
}
@media (max-width: 900px) {
  .price-action-box {
    border-left: none;
    padding-left: 0;
    justify-content: space-between;
    border-top: 1px solid var(--slate-100);
    padding-top: 14px;
  }
}
.price-tag-wrap {
  text-align: right;
}
.price-from {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--slate-400);
  text-transform: uppercase;
}
.price-amount {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--slate-900);
  line-height: 1;
}
.btn-toggle-tiers {
  padding: 10px 18px;
  background: var(--navy-900);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
}
.btn-toggle-tiers:hover {
  background: var(--navy-800);
}

/* Cabin Tier Options (Expandable) */
.tier-matrix-row {
  background: #fbfcfe;
  border-top: 1px solid var(--slate-200);
  padding: 18px 24px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
@media (max-width: 768px) {
  .tier-matrix-row {
    grid-template-columns: 1fr;
  }
}
.tier-card {
  background: #ffffff;
  border: 1.5px solid var(--slate-200);
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.tier-card:hover {
  border-color: var(--cobalt-600);
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.tier-card.is-recommended {
  border-color: var(--cobalt-600);
  background: #ffffff;
}
.tier-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.tier-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--slate-900);
}
.tier-tag {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 2px 6px;
  border-radius: 4px;
}
.tier-tag.standard { background: var(--slate-100); color: var(--slate-600); }
.tier-tag.flex     { background: var(--cobalt-50); color: var(--cobalt-600); }
.tier-tag.business { background: #FEF3C7; color: #92400E; }

.tier-perks {
  list-style: none;
  padding: 0;
  margin: 0 0 14px;
  font-size: 0.75rem;
  color: var(--slate-600);
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.tier-perks li {
  display: flex;
  align-items: center;
  gap: 6px;
}
.tier-perks li svg {
  width: 14px;
  height: 14px;
  color: var(--emerald-600);
  flex-shrink: 0;
}
.tier-bottom-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--slate-100);
  padding-top: 10px;
}
.tier-price {
  font-size: 1rem;
  font-weight: 800;
  color: var(--slate-900);
}
.btn-select-tier {
  padding: 6px 14px;
  background: var(--cobalt-600);
  color: #ffffff;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 700;
  transition: background 0.15s ease;
}
.btn-select-tier:hover {
  background: var(--cobalt-700);
}
</style>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ============ 1. HERO FLIGHT SEARCH ENGINE ============ -->
<section class="flight-hero">
  <div class="flight-hero-wrap">
    <h1 class="flight-hero-title">Commercial Flight Schedules &amp; Booking</h1>
    <p class="flight-hero-sub">Direct access to daily Philippine aviation schedules, verified seat inventory, and transparent fare tiers.</p>

    <!-- Search Engine Card -->
    <div class="search-widget-card">
      <div class="widget-top-row">
        <!-- Trip Type -->
        <div class="trip-type-pills">
          <button type="button" class="trip-pill is-active">One Way</button>
          <button type="button" class="trip-pill" onclick="alert('Round trip calendar feature: Select departure and return dates below.')">Round Trip</button>
        </div>

        <!-- Passengers & Cabin class -->
        <div class="widget-selectors">
          <select class="widget-select" id="selCabin">
            <option value="Economy" <?= $cabin === 'Economy' ? 'selected' : '' ?>>Economy Class</option>
            <option value="Premium Economy" <?= $cabin === 'Premium Economy' ? 'selected' : '' ?>>Premium Economy</option>
            <option value="Business" <?= $cabin === 'Business' ? 'selected' : '' ?>>Business Class</option>
          </select>
          <select class="widget-select" id="selAdults">
            <option value="1" <?= $adults === 1 ? 'selected' : '' ?>>1 Adult</option>
            <option value="2" <?= $adults === 2 ? 'selected' : '' ?>>2 Adults</option>
            <option value="3" <?= $adults === 3 ? 'selected' : '' ?>>3 Adults</option>
            <option value="4" <?= $adults === 4 ? 'selected' : '' ?>>4 Adults</option>
            <option value="5" <?= $adults === 5 ? 'selected' : '' ?>>5+ Adults (Group)</option>
          </select>
        </div>
      </div>

      <!-- Main Input Grid -->
      <form method="GET" action="flights.php" class="search-form-grid" id="mainSearchForm">
        <input type="hidden" name="cabin" id="hiddenCabin" value="<?= htmlspecialchars($cabin) ?>">
        <input type="hidden" name="adults" id="hiddenAdults" value="<?= (int)$adults ?>">

        <!-- Origin -->
        <div class="field-box">
          <label for="originInput">Origin</label>
          <div class="field-input-wrap">
            <input type="text" class="field-ctrl" id="originInput" name="origin" value="<?= htmlspecialchars($originInput) ?>" placeholder="Manila (MNL)" readonly>
          </div>
        </div>

        <!-- Swap -->
        <div>
          <button type="button" class="btn-swap-route" title="Route Locked: Outbound MNL Hub" aria-label="Route Hub">⇄</button>
        </div>

        <!-- Destination -->
        <div class="field-box">
          <label for="destinationSelect">Destination</label>
          <div class="field-input-wrap">
            <select class="field-ctrl" id="destinationSelect" name="destination" onchange="document.getElementById('mainSearchForm').submit()">
              <?php foreach ($availableDestinations as $dLabel => $dMeta): ?>
                <option value="<?= htmlspecialchars($dLabel) ?>" <?= strcasecmp($destInput, $dLabel) === 0 ? 'selected' : '' ?>>
                  <?= htmlspecialchars($dLabel) ?> (<?= htmlspecialchars($dMeta) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Flight Date -->
        <div class="field-box">
          <label for="dateInput">Departure Date</label>
          <div class="field-input-wrap">
            <input type="date" class="field-ctrl" id="dateInput" name="date" value="<?= htmlspecialchars($dateInput) ?>" min="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <!-- Submit CTA -->
        <div>
          <button type="submit" class="btn-search-flights">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <span>Search</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- ============ 2. FLIGHT RESULTS & SELECTION VIEW ============ -->
<main class="results-workspace">

  <!-- 7-Day Fare Strip (Cebu Pacific / Travel-Tech Style) -->
  <div class="fare-carousel-card">
    <div style="font-size:0.75rem;font-weight:700;color:var(--slate-500);text-transform:uppercase;margin-bottom:8px;display:flex;justify-content:space-between;">
      <span>7-Day Fare Calendar &middot; <?= htmlspecialchars($route['origin_code'] ?? 'MNL') ?> &rarr; <?= htmlspecialchars($route['dest_code'] ?? 'DEST') ?></span>
      <span style="color:var(--cobalt-600);">Real-time Database Rates</span>
    </div>

    <div class="carousel-track">
      <?php foreach ($carousel as $day): ?>
        <a href="flights.php?origin=<?= urlencode($originInput) ?>&destination=<?= urlencode($destInput) ?>&date=<?= urlencode($day['date']) ?>&adults=<?= $adults ?>&cabin=<?= urlencode($cabin) ?>" 
           class="carousel-day <?= $day['is_selected'] ? 'is-active' : '' ?>">
          <span class="c-date"><?= htmlspecialchars($day['label']) ?></span>
          <span class="c-price"><?= htmlspecialchars($day['price_short']) ?></span>
          <?php if (!empty($day['is_lowest'])): ?>
            <span class="c-badge-lowest">Lowest</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Header Info & Sort -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div>
      <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);margin:0;">
        <?= htmlspecialchars($route['origin_name'] ?? 'Manila') ?> &rarr; <?= htmlspecialchars($route['dest_name'] ?? $destInput) ?>
      </h2>
      <p style="font-size:0.8125rem;color:var(--slate-500);margin:2px 0 0;">
        <?= count($flightsList) ?> scheduled flight(s) available on <?= date('D, M j, Y', strtotime($dateInput)) ?> &middot; Flight Duration: <?= htmlspecialchars($route['flight_time'] ?? '1h 15m') ?>
      </p>
    </div>

    <div style="display:flex;align-items:center;gap:8px;font-size:0.8125rem;">
      <span style="color:var(--slate-500);font-weight:600;">Sort:</span>
      <a href="flights.php?origin=<?= urlencode($originInput) ?>&destination=<?= urlencode($destInput) ?>&date=<?= urlencode($dateInput) ?>&sort=recommended" class="trip-pill <?= $sortOption === 'recommended' ? 'is-active' : '' ?>" style="text-decoration:none;">Recommended</a>
      <a href="flights.php?origin=<?= urlencode($originInput) ?>&destination=<?= urlencode($destInput) ?>&date=<?= urlencode($dateInput) ?>&sort=price" class="trip-pill <?= $sortOption === 'price' ? 'is-active' : '' ?>" style="text-decoration:none;">Lowest Price</a>
      <a href="flights.php?origin=<?= urlencode($originInput) ?>&destination=<?= urlencode($destInput) ?>&date=<?= urlencode($dateInput) ?>&sort=earliest" class="trip-pill <?= $sortOption === 'earliest' ? 'is-active' : '' ?>" style="text-decoration:none;">Earliest</a>
    </div>
  </div>

  <!-- Flight Cards List -->
  <?php if (empty($flightsList)): ?>
    <div style="background:#fff;border-radius:16px;padding:40px;text-align:center;border:1px solid var(--slate-200);">
      <p style="font-size:1.1rem;font-weight:700;color:var(--slate-700);margin-bottom:8px;">No scheduled flights found for this specific date.</p>
      <p style="font-size:0.875rem;color:var(--slate-500);margin-bottom:20px;">Try picking an alternative date from the fare carousel above.</p>
    </div>
  <?php else: ?>
    <?php foreach ($flightsList as $idx => $f): ?>
      <?php
        $baseFare   = (float)$f['base_price'];
        $smartFare  = $baseFare + 1200;
        $flexFare   = $baseFare + 2800;
        $bizFare    = $baseFare * 2.2;
        $flightDesc = $f['airline'] . ' ' . $f['flight_number'] . ' (' . $f['departure_time'] . ' ' . $f['origin_code'] . ' → ' . $f['arrival_time'] . ' ' . $f['dest_code'] . ')';
      ?>
      <article class="flight-card">
        <div class="flight-summary-row">
          <!-- Airline Metadata -->
          <div class="airline-meta">
            <div class="airline-logo-box">
              <?= htmlspecialchars($f['airline_code'] ?? 'AG') ?>
            </div>
            <div>
              <div class="airline-name"><?= htmlspecialchars($f['airline']) ?></div>
              <div class="flight-no"><?= htmlspecialchars($f['flight_number']) ?> &middot; <?= htmlspecialchars($f['aircraft']) ?></div>
              <span style="font-size:0.6875rem;color:var(--emerald-600);font-weight:700;display:inline-flex;align-items:center;gap:4px;margin-top:2px;">
                <span style="width:6px;height:6px;border-radius:50%;background:var(--emerald-600);"></span> <?= $f['seats'] ?> seats remaining
              </span>
            </div>
          </div>

          <!-- Route Timeline Visualizer -->
          <div class="timeline-block">
            <div class="time-col">
              <div class="t-val"><?= htmlspecialchars($f['departure_time']) ?></div>
              <div class="t-code"><?= htmlspecialchars($f['origin_code']) ?></div>
            </div>

            <div class="flight-track-mid">
              <span class="track-duration"><?= htmlspecialchars($f['duration']) ?></span>
              <div class="track-line">
                <svg class="track-plane" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 22h20L12 2z"/></svg>
              </div>
              <span class="track-stops"><?= htmlspecialchars($f['stops']) ?></span>
            </div>

            <div class="time-col dest">
              <div class="t-val"><?= htmlspecialchars($f['arrival_time']) ?></div>
              <div class="t-code"><?= htmlspecialchars($f['dest_code']) ?></div>
            </div>
          </div>

          <!-- Price & Selection Trigger -->
          <div class="price-action-box">
            <div class="price-tag-wrap">
              <div class="price-from">Starting at</div>
              <div class="price-amount">₱<?= number_format($baseFare, 0) ?></div>
            </div>
            <button type="button" class="btn-toggle-tiers" onclick="toggleTiers('tiers-<?= $idx ?>')">
              <span>View Fares</span>
              <span>▼</span>
            </button>
          </div>
        </div>

        <!-- Cabin Tier Selector Matrix -->
        <div class="tier-matrix-row" id="tiers-<?= $idx ?>">
          
          <!-- Tier 1: Lite / Basic -->
          <div class="tier-card">
            <div>
              <div class="tier-title-row">
                <span class="tier-name">Lite Escape</span>
                <span class="tier-tag standard">Standard</span>
              </div>
              <ul class="tier-perks">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 7kg Carry-on baggage</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Automated seat assignment</li>
                <li><span style="color:#94a3b8;font-size:0.75rem;">✕ Checked bag not included</span></li>
              </ul>
            </div>
            <div class="tier-bottom-row">
              <span class="tier-price">₱<?= number_format($baseFare, 0) ?></span>
              <a href="checkout.php?city=<?= urlencode($destInput) ?>&mode=flights&price=<?= $baseFare ?>&fareName=Lite+Escape&cabin=Economy&adults=<?= $adults ?>&flightSchedule=<?= urlencode($flightDesc) ?>" class="btn-select-tier">Select &rarr;</a>
            </div>
          </div>

          <!-- Tier 2: Smart Saver (Recommended) -->
          <div class="tier-card is-recommended">
            <div>
              <div class="tier-title-row">
                <span class="tier-name">Smart Saver</span>
                <span class="tier-tag flex">Popular</span>
              </div>
              <ul class="tier-perks">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 20kg Checked baggage</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 7kg Carry-on baggage</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Standard seat selection</li>
              </ul>
            </div>
            <div class="tier-bottom-row">
              <span class="tier-price">₱<?= number_format($smartFare, 0) ?></span>
              <a href="checkout.php?city=<?= urlencode($destInput) ?>&mode=flights&price=<?= $smartFare ?>&fareName=Smart+Saver&cabin=Economy&adults=<?= $adults ?>&flightSchedule=<?= urlencode($flightDesc) ?>" class="btn-select-tier" style="background:var(--cobalt-600);">Select &rarr;</a>
            </div>
          </div>

          <!-- Tier 3: Business / Flex -->
          <div class="tier-card">
            <div>
              <div class="tier-title-row">
                <span class="tier-name">Executive Flex</span>
                <span class="tier-tag business">Priority</span>
              </div>
              <ul class="tier-perks">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 35kg Priority luggage</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Free flight rebooking</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Priority check-in &amp; boarding</li>
              </ul>
            </div>
            <div class="tier-bottom-row">
              <span class="tier-price">₱<?= number_format($flexFare, 0) ?></span>
              <a href="checkout.php?city=<?= urlencode($destInput) ?>&mode=flights&price=<?= $flexFare ?>&fareName=Executive+Flex&cabin=Business&adults=<?= $adults ?>&flightSchedule=<?= urlencode($flightDesc) ?>" class="btn-select-tier">Select &rarr;</a>
            </div>
          </div>

        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Expand / Collapse Flight Tier Selectors
function toggleTiers(id) {
  const el = document.getElementById(id);
  if (!el) return;
  if (el.style.display === 'none') {
    el.style.display = 'grid';
  } else {
    el.style.display = 'none';
  }
}

// Sync selectors with hidden form values
document.getElementById('selCabin').addEventListener('change', function() {
  document.getElementById('hiddenCabin').value = this.value;
  document.getElementById('mainSearchForm').submit();
});
document.getElementById('selAdults').addEventListener('change', function() {
  document.getElementById('hiddenAdults').value = this.value;
  document.getElementById('mainSearchForm').submit();
});
</script>

</body>
</html>

