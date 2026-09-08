<?php
// ============================================================
// AeroGlide — destination.php
// Shown after a traveler clicks "Learn more" on an Exclusive Deal.
// Displays the chosen flight package plus available hotels and
// car rentals for that destination, with a running price total.
// ============================================================
 
session_start();
 
$isLoggedIn = isset($_SESSION['user_id']);
$username   = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : '';
 
/**
 * Build (once) a flat index of every file inside /asset and /assets,
 * with two normalized keys per file:
 *   - norm   : lowercase, non-alnum runs collapsed to single spaces
 *              (used for loose keyword-list matching)
 *   - squash : lowercase, ALL non-alnum characters stripped entirely
 *              (used for "does this name appear in the filename"
 *              matching, so "Midsize SUV" still finds a file saved as
 *              "mid size suv for tourist white background", and
 *              "Discovery Shores Boracay" finds every photo that
 *              starts with those words regardless of what follows —
 *              no digit suffix required).
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
            $squash = strtolower(preg_replace('/[^a-z0-9]+/i', '', $stem));
            $files[] = [
                'folder' => $folder,
                'name'   => $base,
                'norm'   => ' ' . trim($norm) . ' ',
                'squash' => $squash,
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
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs><rect width="900" height="600" fill="url(#g)"/><circle cx="690" cy="145" r="70" fill="#fff" opacity=".45"/><path d="M0 430 C170 360 250 470 420 405 C590 340 700 430 900 365 V600 H0Z" fill="#fff" opacity=".48"/></svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

/**
 * Resolve a SINGLE image by keyword list (hero banners, logo, etc).
 * Falls back to $fallbackKeywords, then to a generated placeholder.
 */
function asset_find(array $keywords, ?array $fallbackKeywords = ['coron']): string
{
    $files = asset_index();

    $match = function (array $kw) use ($files): ?array {
        foreach ($files as $f) {
            $ok = true;
            foreach ($kw as $k) {
                if (strpos($f['norm'], strtolower($k)) === false) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $f;
            }
        }
        return null;
    };

    if ($found = $match($keywords)) {
        return asset_url($found);
    }
    if ($fallbackKeywords && ($found = $match($fallbackKeywords))) {
        return asset_url($found);
    }
    return asset_placeholder();
}

/**
 * Gather EVERY real photo that belongs to a named hotel/vehicle, up to
 * $max. A file "belongs" if the item's name (squashed, no spaces or
 * punctuation) appears inside the file's squashed name — so it finds
 * "Discovery Shores Boracay (1)", "...Island - Phillipines", etc. all
 * at once, and it finds "mid size suv for tourist white background"
 * for an item literally named "Midsize SUV". No digit suffix needed,
 * and it never pads a card with unrelated/duplicate fallback photos —
 * if only one real photo exists for that item, the gallery just has
 * one photo (and the UI hides slideshow arrows/dots for it).
 * If NOTHING matches at all, it falls back to a single destination
 * scenery photo so the card never looks empty.
 */
function asset_gallery(string $itemName, array $heroFallbackKeywords, int $max = 4): array
{
    $files  = asset_index();
    $needle = strtolower(preg_replace('/[^a-z0-9]+/i', '', $itemName));

    $matches = [];
    $seen = [];
    if ($needle !== '') {
        foreach ($files as $f) {
            if (isset($seen[$f['name']])) {
                continue;
            }
            if (strpos($f['squash'], $needle) !== false) {
                $matches[] = $f;
                $seen[$f['name']] = true;
            }
        }
    }

    // Keep numbered/related variants in a stable, human-friendly order
    usort($matches, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

    if (empty($matches)) {
        return [asset_find($heroFallbackKeywords)];
    }

    return array_map('asset_url', array_slice($matches, 0, $max));
}
 
/* ------------------------------------------------------------
   Read what the traveler picked on the homepage.
------------------------------------------------------------ */
$city      = isset($_GET['city'])     ? trim($_GET['city'])     : 'Boracay, Aklan';
$fareValue = isset($_GET['fare'])     ? trim($_GET['fare'])     : 'smart';
$fareName  = isset($_GET['fareName']) ? trim($_GET['fareName']) : 'Smart Saver';
$fareDesc  = isset($_GET['desc'])     ? trim($_GET['desc'])     : 'Flight + checked baggage';
$farePriceRaw = isset($_GET['price']) ? trim($_GET['price'])    : '₱4,999';
 
$farePriceNum = (float) preg_replace('/[^0-9.]/', '', $farePriceRaw);
if ($farePriceNum <= 0) {
    $farePriceNum = 4999;
    $farePriceRaw = '₱4,999';
}
 
/* ------------------------------------------------------------
   Mock hotel & car-rental inventory per destination.
------------------------------------------------------------ */
$catalog = [
    'boracay' => [
        'label'   => 'Boracay, Aklan',
        'asset'   => ['boracay'],
        'blurb'   => 'Powder-white sand and turquoise water on the Philippines\' most famous island.',
        'hotels'  => [
            ['name' => 'Shangri-La Boracay Resort & Spa', 'tier' => '5-star beachfront', 'rating' => 4.8, 'price' => 8500],
            ['name' => 'Discovery Shores Boracay',        'tier' => '4-star boutique',    'rating' => 4.6, 'price' => 6200],
            ['name' => 'Henann Regency Resort & Spa',     'tier' => '3-star Station 2',   'rating' => 4.3, 'price' => 3800],
        ],
        'cars' => [
            ['name' => 'Economy Sedan',   'seats' => 4,  'price' => 1800],
            ['name' => 'Midsize SUV',     'seats' => 7,  'price' => 3200],
            ['name' => 'Tourist Van',     'seats' => 10, 'price' => 4500],
        ],
    ],
    'coron' => [
        'label'   => 'Coron, Palawan',
        'asset'   => ['coron'],
        'blurb'   => 'Limestone cliffs, hidden lagoons, and legendary WWII wreck diving.',
        'hotels'  => [
            ['name' => 'Coron Westown Resort',        'tier' => '5-star waterfront', 'rating' => 4.7, 'price' => 7200],
            ['name' => 'Sangat Island Dive Resort',    'tier' => '4-star dive lodge', 'rating' => 4.5, 'price' => 5400],
            ['name' => 'Coron Soleil Garden Resort',   'tier' => '3-star town center','rating' => 4.2, 'price' => 3100],
        ],
        'cars' => [
            ['name' => 'Economy Sedan',   'seats' => 4,  'price' => 1900],
            ['name' => 'Midsize SUV',     'seats' => 7,  'price' => 3400],
            ['name' => 'Tourist Van',     'seats' => 10, 'price' => 4700],
        ],
    ],
    'siargao' => [
        'label'   => 'Siargao, Surigao del Norte',
        'asset'   => ['siargao'],
        'blurb'   => 'The surfing capital of the Philippines, with laid-back island vibes.',
        'hotels'  => [
            ['name' => 'Nay Palad Hideaway',      'tier' => '5-star luxury villas', 'rating' => 4.9, 'price' => 15000],
            ['name' => 'Bravo Beach Resort',       'tier' => '4-star beachfront',    'rating' => 4.6, 'price' => 6800],
            ['name' => 'Kermit Surf Resort',       'tier' => '3-star surf lodge',    'rating' => 4.3, 'price' => 2900],
        ],
        'cars' => [
            ['name' => 'Scooter / Motorbike', 'seats' => 2,  'price' => 600],
            ['name' => 'Economy Sedan',       'seats' => 4,  'price' => 2000],
            ['name' => 'Midsize SUV',         'seats' => 7,  'price' => 3600],
        ],
    ],
    'default' => [
        'label'   => null,
        'asset'   => ['coron'],
        'blurb'   => 'Handpicked stays and rides for your trip.',
        'hotels'  => [
            ['name' => 'Seaside Garden Hotel',   'tier' => '4-star comfort', 'rating' => 4.5, 'price' => 5200],
            ['name' => 'Harbor View Inn',        'tier' => '3-star cozy',    'rating' => 4.2, 'price' => 3400],
            ['name' => 'Budget Traveler Lodge',  'tier' => '2-star basic',   'rating' => 3.9, 'price' => 1800],
        ],
        'cars' => [
            ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1800],
            ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3200],
            ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4500],
        ],
    ],
];
 
$cityKey = 'default';
foreach (array_keys($catalog) as $key) {
    if ($key !== 'default' && stripos($city, $key) !== false) {
        $cityKey = $key;
        break;
    }
}
$destination = $catalog[$cityKey];
if ($destination['label'] === null) {
    $destination['label'] = $city;
}
 
$heroImage = asset_find($destination['asset']);
 
function peso(float $n): string
{
    return '₱' . number_format($n, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($destination['label'], ENT_QUOTES) ?> — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
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
      <a href="index.php#deals" class="nav-item is-active">Package</a>
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
 
<main class="destination-main">
 
<!-- ============ DESTINATION HERO ============ -->
<section class="dest-hero" style="background-image: linear-gradient(180deg, rgba(10,20,35,.15), rgba(10,20,35,.75)), url('<?= htmlspecialchars($heroImage, ENT_QUOTES) ?>');">
  <div class="dest-hero-inner">
    <a href="index.php#deals" class="dest-back-link">&larr; Back to Deals</a>
    <h1 class="dest-hero-title"><?= htmlspecialchars($destination['label'], ENT_QUOTES) ?></h1>
    <p class="dest-hero-blurb"><?= htmlspecialchars($destination['blurb'], ENT_QUOTES) ?></p>
 
    <div class="dest-fare-chip">
      <span class="dest-fare-chip-label">Selected flight package</span>
      <span class="dest-fare-chip-name"><?= htmlspecialchars($fareName, ENT_QUOTES) ?> — <?= htmlspecialchars($fareDesc, ENT_QUOTES) ?></span>
      <span class="dest-fare-chip-price"><?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?></span>
    </div>
  </div>
</section>
 
<div class="dest-content-container">
<!-- ============ HOTELS ============ -->
<section class="dest-section">
  <div class="dest-section-header">
    <h2 class="section-main-title">Available Hotels</h2>
    <p class="section-tagline">Pick a stay in <?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>. Preview 4 images via slide show or click to enlarge.</p>
  </div>
 
  <div class="option-grid" id="hotel-grid" data-kind="hotel">
    <?php foreach ($destination['hotels'] as $i => $hotel): 
      // Pull every real photo that belongs to this hotel (no digit
      // guessing) so cards only ever show genuine, distinct images.
      $hotelImages = asset_gallery($hotel['name'], $destination['asset']);
      $hasMultiple = count($hotelImages) > 1;
    ?>
    <div class="option-card<?= $i === 0 ? ' is-selected' : '' ?>"
         role="button" tabindex="0"
         data-name="<?= htmlspecialchars($hotel['name'], ENT_QUOTES) ?>"
         data-price="<?= (float) $hotel['price'] ?>"
         data-images='<?= json_encode($hotelImages) ?>'>
         
      <!-- Image Slideshow Container -->
      <div class="card-slideshow-container<?= $hasMultiple ? '' : ' is-single' ?>">
        <?php foreach ($hotelImages as $imgIdx => $imgSrc): ?>
          <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>" class="slide-img<?= $imgIdx === 0 ? ' active' : '' ?>" alt="<?= htmlspecialchars($hotel['name'], ENT_QUOTES) ?> photo <?= $imgIdx + 1 ?>">
        <?php endforeach; ?>
        <?php if ($hasMultiple): ?>
        <button type="button" class="slide-arrow prev-arrow" aria-label="Previous image">&#10094;</button>
        <button type="button" class="slide-arrow next-arrow" aria-label="Next image">&#10095;</button>
        <div class="slide-indicators">
          <?php foreach ($hotelImages as $si => $_): ?>
            <span class="dot<?= $si === 0 ? ' active' : '' ?>"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="option-info-row">
        <div class="option-body">
          <h3 class="option-name"><?= htmlspecialchars($hotel['name'], ENT_QUOTES) ?></h3>
          <p class="option-meta"><?= htmlspecialchars($hotel['tier'], ENT_QUOTES) ?> · ★ <?= htmlspecialchars(number_format($hotel['rating'], 1), ENT_QUOTES) ?></p>
        </div>
        <div class="option-price-col">
          <span class="option-price"><?= peso($hotel['price']) ?></span>
          <span class="option-price-unit">per night</span>
          <span class="option-select-btn">Select</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
 
  <div class="nights-stepper">
    <span class="nights-label">Length of stay</span>
    <div class="nights-control">
      <button type="button" class="nights-btn" id="nights-dec" aria-label="Decrease nights">−</button>
      <span class="nights-count-disp"><span id="nights-count">3</span> <span class="nights-suffix">night(s)</span></span>
      <button type="button" class="nights-btn" id="nights-inc" aria-label="Increase nights">+</button>
    </div>
  </div>
</section>
 
<!-- ============ CAR RENTALS ============ -->
<section class="dest-section">
  <div class="dest-section-header">
    <h2 class="section-main-title">Available Car Rentals</h2>
    <p class="section-tagline">Get around <?= htmlspecialchars($destination['label'], ENT_QUOTES) ?> at your own pace.</p>
  </div>
 
  <div class="option-grid" id="car-grid" data-kind="car">
    <?php foreach ($destination['cars'] as $i => $car): 
      // Most vehicles only have ONE real photo in /asset — that's fine,
      // the gallery just returns that single image and the card shows
      // a plain static photo (no arrows/dots) instead of faking a loop.
      $carImages = asset_gallery($car['name'], $destination['asset']);
      $hasMultiple = count($carImages) > 1;
    ?>
    <div class="option-card<?= $i === 0 ? ' is-selected' : '' ?>"
         role="button" tabindex="0"
         data-name="<?= htmlspecialchars($car['name'], ENT_QUOTES) ?>"
         data-price="<?= (float) $car['price'] ?>"
         data-images='<?= json_encode($carImages) ?>'>
         
      <!-- Image Slideshow Container -->
      <div class="card-slideshow-container<?= $hasMultiple ? '' : ' is-single' ?>">
        <?php foreach ($carImages as $imgIdx => $imgSrc): ?>
          <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>" class="slide-img<?= $imgIdx === 0 ? ' active' : '' ?>" alt="<?= htmlspecialchars($car['name'], ENT_QUOTES) ?> photo <?= $imgIdx + 1 ?>">
        <?php endforeach; ?>
        <?php if ($hasMultiple): ?>
        <button type="button" class="slide-arrow prev-arrow" aria-label="Previous image">&#10094;</button>
        <button type="button" class="slide-arrow next-arrow" aria-label="Next image">&#10095;</button>
        <div class="slide-indicators">
          <?php foreach ($carImages as $si => $_): ?>
            <span class="dot<?= $si === 0 ? ' active' : '' ?>"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="option-info-row">
        <div class="option-body">
          <h3 class="option-name"><?= htmlspecialchars($car['name'], ENT_QUOTES) ?></h3>
          <p class="option-meta"><?= (int) $car['seats'] ?> seats · Free cancellation</p>
        </div>
        <div class="option-price-col">
          <span class="option-price"><?= peso($car['price']) ?></span>
          <span class="option-price-unit">per day</span>
          <span class="option-select-btn">Select</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
 
  <div class="nights-stepper">
    <span class="nights-label">Rental length</span>
    <div class="nights-control">
      <button type="button" class="nights-btn" id="days-dec" aria-label="Decrease days">−</button>
      <span class="nights-count-disp"><span id="days-count">3</span> <span class="nights-suffix">day(s)</span></span>
      <button type="button" class="nights-btn" id="days-inc" aria-label="Increase days">+</button>
    </div>
  </div>
</section>
</div>
 
</main>
 
<!-- LIGHTBOX MODAL FOR ENLARGING IMAGES -->
<div id="image-lightbox" class="lightbox-modal">
  <span class="lightbox-close">&times;</span>
  <button type="button" class="lightbox-arrow lightbox-prev" aria-label="Previous">&#10094;</button>
  <img class="lightbox-content" id="lightbox-img" alt="Enlarged view">
  <button type="button" class="lightbox-arrow lightbox-next" aria-label="Next">&#10095;</button>
  <div id="lightbox-caption"></div>
</div>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand-col">
      <div class="footer-brand-header">
        <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="footer-brand-logo">
        <span class="footer-brand-name">A e r o G l i d e</span>
      </div>
      <div class="footer-social-section">
        <span class="social-title">Follow</span>
        <div class="social-buttons-list">
          <a href="#" class="social-circle-btn" aria-label="Follow us on Facebook">f</a>
          <a href="#" class="social-circle-btn" aria-label="Follow us on X">𝕏</a>
          <a href="#" class="social-circle-btn" aria-label="Follow us on YouTube">▶</a>
        </div>
      </div>
    </div>
    <div class="footer-links-grid">
      <div class="footer-col">
        <h4 class="footer-col-title">Travel</h4>
        <ul class="footer-nav-list">
          <li><a href="index.php#destinations">Asia</a></li>
          <li><a href="index.php#destinations">Europe</a></li>
          <li><a href="index.php#destinations">Australia</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">Company</h4>
        <ul class="footer-nav-list">
          <li><a href="index.php#about">About Us</a></li>
          <li><a href="index.php#deals">Packages</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">Extra Links</h4>
        <ul class="footer-nav-list">
          <li><a href="index.php#about">Customer Support</a></li>
          <li><a href="index.php#about">Terms and Conditions</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>
 
<!-- ============ STICKY TRIP SUMMARY BAR ============ -->
<div class="trip-summary-bar" id="trip-summary-bar">
  <div class="summary-line">
    <span>Flight (<?= htmlspecialchars($fareName, ENT_QUOTES) ?>)</span>
    <span id="sum-flight" class="summary-price"><?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?></span>
  </div>
  <div class="summary-line">
    <span id="sum-hotel-label">Hotel · 3 nights</span>
    <span id="sum-hotel" class="summary-price">—</span>
  </div>
  <div class="summary-line">
    <span id="sum-car-label">Car rental · 3 days</span>
    <span id="sum-car" class="summary-price">—</span>
  </div>
  <div class="summary-total">
    <span>Estimated total</span>
    <span id="sum-total">—</span>
  </div>
  <button type="button" class="btn-proceed" id="btn-proceed">Proceed to booking</button>
</div>

<!-- Hidden hand-off form: carries the traveler's picks to checkout.php -->
<form id="checkout-form" action="checkout.php" method="GET" style="display:none">
  <input type="hidden" name="city"     value="<?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>">
  <input type="hidden" name="fareName" value="<?= htmlspecialchars($fareName, ENT_QUOTES) ?>">
  <input type="hidden" name="fareDesc" value="<?= htmlspecialchars($fareDesc, ENT_QUOTES) ?>">
  <input type="hidden" name="price"    value="<?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?>">
  <input type="hidden" name="hotelName"  value="">
  <input type="hidden" name="hotelPrice" value="">
  <input type="hidden" name="nights"     value="">
  <input type="hidden" name="carName"    value="">
  <input type="hidden" name="carPrice"   value="">
  <input type="hidden" name="days"       value="">
</form>
 
<script>
(function() {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
 
  const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
  const flightPrice = <?= json_encode($farePriceNum) ?>;
 
  let nights = 3;
  let days = 3;
  let hotelPrice = <?= json_encode((float) $destination['hotels'][0]['price']) ?>;
  let carPrice   = <?= json_encode((float) $destination['cars'][0]['price']) ?>;
 
  function selectCard(card) {
    const grid = card.closest('.option-grid');
    $$('.option-card', grid).forEach(c => c.classList.remove('is-selected'));
    card.classList.add('is-selected');
  }
 
  $$('.option-card', $('#hotel-grid')).forEach(card => {
    card.addEventListener('click', (e) => {
      // Prevent selection trigger if interacting with slider elements
      if(e.target.closest('.slide-arrow') || e.target.closest('.slide-indicators')) return;
      selectCard(card);
      hotelPrice = parseFloat(card.dataset.price);
      recalc();
    });
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
  });
 
  $$('.option-card', $('#car-grid')).forEach(card => {
    card.addEventListener('click', (e) => {
      if(e.target.closest('.slide-arrow') || e.target.closest('.slide-indicators')) return;
      selectCard(card);
      carPrice = parseFloat(card.dataset.price);
      recalc();
    });
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
  });
 
  $('#nights-dec')?.addEventListener('click', () => { if (nights > 1) { nights--; $('#nights-count').textContent = nights; recalc(); } });
  $('#nights-inc')?.addEventListener('click', () => { if (nights < 14) { nights++; $('#nights-count').textContent = nights; recalc(); } });
  $('#days-dec')?.addEventListener('click', () => { if (days > 1) { days--; $('#days-count').textContent = days; recalc(); } });
  $('#days-inc')?.addEventListener('click', () => { if (days < 14) { days++; $('#days-count').textContent = days; recalc(); } });
 
  function recalc() {
    const hotelTotal = hotelPrice * nights;
    const carTotal = carPrice * days;
    const total = flightPrice + hotelTotal + carTotal;
 
    $('#sum-hotel-label').textContent = `Hotel · ${nights} night${nights > 1 ? 's' : ''}`;
    $('#sum-car-label').textContent = `Car rental · ${days} day${days > 1 ? 's' : ''}`;
    $('#sum-hotel').textContent = peso(hotelTotal);
    $('#sum-car').textContent = peso(carTotal);
    $('#sum-total').textContent = peso(total);
  }
 
  recalc();
 
  $('#btn-proceed')?.addEventListener('click', () => {
    const form = $('#checkout-form');
    if (!form) return;

    const hotelCard = $('#hotel-grid .option-card.is-selected');
    const carCard   = $('#car-grid .option-card.is-selected');

    form.querySelector('[name="hotelName"]').value  = hotelCard ? hotelCard.dataset.name : '';
    form.querySelector('[name="hotelPrice"]').value = hotelPrice;
    form.querySelector('[name="nights"]').value      = nights;
    form.querySelector('[name="carName"]').value    = carCard ? carCard.dataset.name : '';
    form.querySelector('[name="carPrice"]').value   = carPrice;
    form.querySelector('[name="days"]').value        = days;

    form.submit();
  });

  // ==========================================================
  // SLIDESHOW & LIGHTBOX ENLARGE LOGIC
  // ==========================================================
  const lightbox = $('#image-lightbox');
  const lightboxImg = $('#lightbox-img');
  const lightboxClose = $('.lightbox-close');
  const lightboxPrev = $('.lightbox-prev');
  const lightboxNext = $('.lightbox-next');

  let currentLightboxImages = [];
  let currentLightboxIndex = 0;

  function openLightbox(images, index) {
    currentLightboxImages = images;
    currentLightboxIndex = index;
    lightboxImg.src = currentLightboxImages[currentLightboxIndex];
    lightbox.classList.add('is-active');
  }

  lightboxClose.addEventListener('click', () => lightbox.classList.remove('is-active'));
  lightbox.addEventListener('click', (e) => { if(e.target === lightbox) lightbox.classList.remove('is-active'); });

  lightboxPrev.addEventListener('click', () => {
    currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length;
    lightboxImg.src = currentLightboxImages[currentLightboxIndex];
  });

  lightboxNext.addEventListener('click', () => {
    currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length;
    lightboxImg.src = currentLightboxImages[currentLightboxIndex];
  });

  // Initialize Card Slideshows & Auto-advance
  $$('.option-card').forEach(card => {
    const images = JSON.parse(card.dataset.images || '[]');
    if (images.length === 0) return;

    const slides  = $$('.slide-img', card);
    const dots    = $$('.dot', card);
    const nextBtn = $('.next-arrow', card);
    const prevBtn = $('.prev-arrow', card);
    let currentIndex = 0;
    let slideInterval;

    // Enlarge on clicking the visible slide image — works even for
    // single-image cards, which have no arrows/dots at all.
    slides.forEach((slideImg, sIdx) => {
      slideImg.addEventListener('click', (e) => {
        e.stopPropagation();
        openLightbox(images, sIdx);
      });
    });

    // A single real photo just sits there statically — no arrows,
    // no dots, no auto-rotation to fake motion that isn't there.
    if (slides.length <= 1) return;

    function showSlide(idx) {
      slides[currentIndex].classList.remove('active');
      if (dots[currentIndex]) dots[currentIndex].classList.remove('active');
      currentIndex = (idx + slides.length) % slides.length;
      slides[currentIndex].classList.add('active');
      if (dots[currentIndex]) dots[currentIndex].classList.add('active');
    }

    nextBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      showSlide(currentIndex + 1);
      resetInterval();
    });

    prevBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      showSlide(currentIndex - 1);
      resetInterval();
    });

    // Auto slideshow slow rotation loop
    function startInterval() {
      slideInterval = setInterval(() => {
        showSlide(currentIndex + 1);
      }, 4500);
    }

    function resetInterval() {
      clearInterval(slideInterval);
      startInterval();
    }

    startInterval();
  });

})();
</script>
</body>
</html>