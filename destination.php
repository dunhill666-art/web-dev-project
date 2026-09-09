<?php
// ============================================================
// AeroGlide — destination.php
// Interactive destination package details page showing hotels,
// car rentals, slideshows, image lightbox, price calculator,
// and redirection to checkout.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

if (!function_exists('asset_gallery')) {
    /**
     * Gather images belonging to a specific hotel or car.
     */
    function asset_gallery(string $itemName, array $heroFallbackKeywords, int $max = 4): array
    {
        $files  = asset_index();
        $needle = strtolower(preg_replace('/[^a-z0-9]+/i', '', $itemName));

        $matches = [];
        $seen = [];

        // 1. Direct match on full squashed needle
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

        // 2. Keyword token fallback for words (e.g. "Fazzio", "Communal", "Bellevue", "Ocean", "Taalisay")
        if (empty($matches)) {
            $words = array_filter(explode(' ', strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $itemName))), fn($w) => strlen($w) >= 4);
            foreach ($words as $w) {
                foreach ($files as $f) {
                    if (isset($seen[$f['name']])) {
                        continue;
                    }
                    if (strpos($f['squash'], $w) !== false) {
                        $matches[] = $f;
                        $seen[$f['name']] = true;
                    }
                }
                if (!empty($matches)) {
                    break;
                }
            }
        }

        usort($matches, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        if (empty($matches)) {
            return [asset_find($heroFallbackKeywords)];
        }

        return array_map('asset_url', array_slice($matches, 0, $max));
    }
}

/* ------------------------------------------------------------
   Read input parameters (passed via URL query string)
------------------------------------------------------------ */
$mode   = strtolower(trim($_GET['mode'] ?? 'packages'));
$adults = max(1, (int) ($_GET['adults'] ?? 1));
$cabin  = trim($_GET['cabin'] ?? 'Economy');

$city      = isset($_GET['city'])     ? trim($_GET['city'])     : 'Mayon Volcano, Albay';
$fareValue = isset($_GET['fare'])     ? trim($_GET['fare'])     : 'smart';
$fareName  = isset($_GET['fareName']) ? trim($_GET['fareName']) : 'Smart Saver';
$fareDesc  = isset($_GET['desc'])     ? trim($_GET['desc'])     : 'Flight + checked baggage';
$farePriceRaw = isset($_GET['price']) ? trim($_GET['price'])    : '₱4,999';

$basePrice = (float) preg_replace('/[^0-9.]/', '', $farePriceRaw);
if ($basePrice <= 0) {
    $basePrice = 4999;
}

$cabinMult = 1.0;
if (strpos($cabin, 'Premium') !== false) {
    $cabinMult = 1.3;
} else if (strpos($cabin, 'Business') !== false) {
    $cabinMult = 1.8;
} else if (strpos($cabin, 'First') !== false) {
    $cabinMult = 2.5;
}

if ($mode === 'hotels') {
    $farePriceNum = 0;
    $farePriceRaw = '₱0 (Flight skipped)';
    $defaultNights = 1;
    $defaultDays   = 1;
} else {
    $farePriceNum = round($basePrice * $cabinMult * $adults);
    $farePriceRaw = '₱' . number_format($farePriceNum);
    $defaultNights = ($mode === 'flights') ? 0 : 3;
    $defaultDays   = ($mode === 'flights') ? 0 : 3;
}

/* ------------------------------------------------------------
   Destination Inventory & Catalog
------------------------------------------------------------ */
$catalog = [
    'albay' => [
        'label'   => 'Mayon Volcano, Albay',
        'asset'   => ['albay', 'mayon'],
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
            ['name' => 'Coron Westown Resort',      'tier' => '5-star waterfront', 'rating' => 4.7, 'price' => 7200],
            ['name' => 'Sangat Island Dive Resort',  'tier' => '4-star dive lodge', 'rating' => 4.5, 'price' => 5400],
            ['name' => 'Coron Soleil Garden Resort', 'tier' => '3-star town center','rating' => 4.2, 'price' => 3100],
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
            ['name' => 'Nay Palad Hideaway', 'tier' => '5-star luxury villas', 'rating' => 4.9, 'price' => 15000],
            ['name' => 'Bravo Beach Resort',  'tier' => '4-star beachfront',    'rating' => 4.6, 'price' => 6800],
            ['name' => 'Kermit Surf Resort',  'tier' => '3-star surf lodge',    'rating' => 4.3, 'price' => 2900],
        ],
        'cars' => [
            ['name' => 'Yamaha Fazzio Scooter', 'seats' => 2,  'price' => 600],
            ['name' => 'Economy Sedan',         'seats' => 4,  'price' => 2000],
            ['name' => 'Midsize SUV',           'seats' => 7,  'price' => 3600],
        ],
    ],
    'bukidnon' => [
        'label'   => 'Bukidnon',
        'asset'   => ['bukidnon'],
        'blurb'   => 'Rolling hills, pine forests, and cool mountain adventure escapes.',
        'hotels'  => [
            ['name' => 'Communal Ranch Lodge', 'tier' => '4-star mountain view', 'rating' => 4.7, 'price' => 4500],
            ['name' => 'Dahilayan Eco Resort', 'tier' => '3-star adventure stay', 'rating' => 4.4, 'price' => 3200],
            ['name' => 'Pine Breeze Inn',       'tier' => '2-star cozy cabin',    'rating' => 4.0, 'price' => 1900],
        ],
        'cars' => [
            ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3500],
            ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4800],
            ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1900],
        ],
    ],
    'chocolate' => [
        'label'   => 'Chocolate Hills, Bohol',
        'asset'   => ['chocolate'],
        'blurb'   => 'Iconic conical hills, tarsier sanctuaries, and pristine river cruises.',
        'hotels'  => [
            ['name' => 'Bellevue Resort Bohol',      'tier' => '5-star beachfront', 'rating' => 4.8, 'price' => 7800],
            ['name' => 'Loboc River Resort',         'tier' => '4-star eco lodge',   'rating' => 4.6, 'price' => 4200],
            ['name' => 'Chocolate Hills View Hotel', 'tier' => '3-star scenic',     'rating' => 4.2, 'price' => 2500],
        ],
        'cars' => [
            ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1800],
            ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3300],
            ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4600],
        ],
    ],
    'malapascua' => [
        'label'   => 'Malapascua Island, Cebu',
        'asset'   => ['malapascua'],
        'blurb'   => 'World-famous thresher shark diving and serene white sand beaches.',
        'hotels'  => [
            ['name' => 'Ocean Vida Beach & Dive Resort', 'tier' => '4-star beachfront', 'rating' => 4.7, 'price' => 4900],
            ['name' => 'Tepanee Beach Resort',           'tier' => '4-star cliffside',  'rating' => 4.5, 'price' => 4200],
            ['name' => 'Exotic Island Dive Resort',      'tier' => '3-star dive lodge', 'rating' => 4.3, 'price' => 2800],
        ],
        'cars' => [
            ['name' => 'Island Motorbike Rental', 'seats' => 2,  'price' => 500],
            ['name' => 'Outrigger Boat Transfer', 'seats' => 6,  'price' => 1500],
            ['name' => 'Economy Sedan',           'seats' => 4,  'price' => 1800],
        ],
    ],
    'taal' => [
        'label'   => 'Taal Volcano, Batangas',
        'asset'   => ['taal'],
        'blurb'   => 'Stunning volcanic crater lake views and relaxing cool highland breezes.',
        'hotels'  => [
            ['name' => 'Taalisay Ridge Resort',   'tier' => '4-star volcano view', 'rating' => 4.6, 'price' => 5400],
            ['name' => 'Lakeview Haven Hotel',    'tier' => '3-star lakeside',     'rating' => 4.3, 'price' => 3100],
            ['name' => 'Batangas Travelers Inn', 'tier' => '2-star budget',       'rating' => 3.9, 'price' => 1600],
        ],
        'cars' => [
            ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1700],
            ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3100],
            ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4400],
        ],
    ],
    'default' => [
        'label'   => null,
        'asset'   => ['albay', 'coron'],
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

if (!function_exists('peso')) {
    function peso(float $n): string
    {
        return '₱' . number_format($n, 0);
    }
}
$pageTitle = htmlspecialchars($destination['label'], ENT_QUOTES) . ' — AeroGlide';
$activeNav = 'deals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="destination-main">

<!-- ============ DESTINATION HERO ============ -->
<section class="dest-hero" style="background-image: linear-gradient(180deg, rgba(10,20,35,.15), rgba(10,20,35,.75)), url('<?= htmlspecialchars($heroImage, ENT_QUOTES) ?>');">
  <div class="dest-hero-inner">
    <a href="index.php#deals" class="dest-back-link">&larr; Back to Deals</a>
    <h1 class="dest-hero-title"><?= htmlspecialchars($destination['label'], ENT_QUOTES) ?></h1>
    <p class="dest-hero-blurb"><?= htmlspecialchars($destination['blurb'], ENT_QUOTES) ?></p>

    <div class="dest-hero-box">
      <div class="dest-box-header">
        <span class="dest-box-tag">Selected Package</span>
        <h3 class="dest-box-title"><?= htmlspecialchars($fareName, ENT_QUOTES) ?> &mdash; <?= htmlspecialchars($fareDesc, ENT_QUOTES) ?></h3>
        <div class="dest-box-price" id="dest-fare-chip-price-disp"><?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?></div>
      </div>

      <div class="dest-box-guests-row">
        <div class="dest-box-guests-info">
          <strong>Travelers &amp; Guests</strong>
          <span>Rate: ₱<?= number_format(round($basePrice * $cabinMult)) ?> / person</span>
        </div>
        <div class="dest-counter">
          <button type="button" class="cnt-btn" id="dest-guest-dec" aria-label="Decrease travelers">−</button>
          <span class="cnt-val" id="dest-guest-count"><?= $adults ?></span>
          <button type="button" class="cnt-btn" id="dest-guest-inc" aria-label="Increase travelers">+</button>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="dest-content-container">

<!-- ============ FLIGHT SCHEDULES ============ -->
<?php if ($mode !== 'hotels'): ?>
<section class="dest-section" id="section-flights">
  <div class="dest-section-header">
    <h2 class="section-main-title">Available Flight Schedules</h2>
    <p class="section-tagline">Choose your preferred flight schedule to <?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>.</p>
  </div>

  <div class="flight-schedule-list" id="flight-schedule-grid">
    <div class="flight-sched-card is-selected" tabindex="0" role="button" data-schedule="Philippines AirAsia AG-204 (03:55 AM MNL T2 &rarr; 05:20 AM)">
      <div class="sched-airline-col">
        <span class="sched-airline-logo">✈️</span>
        <div>
          <strong class="sched-airline-name">Philippines AirAsia</strong>
          <span class="sched-flight-no">Flight AG-204 &middot; Airbus A320</span>
        </div>
      </div>

      <div class="sched-route-col">
        <div class="sched-time-box">
          <span class="sched-time">03:55 AM</span>
          <span class="sched-ap">MNL T2</span>
        </div>
        <div class="sched-duration-box">
          <span class="sched-dur-text">1h 25m</span>
          <div class="sched-line"></div>
          <span class="sched-badge">Nonstop</span>
        </div>
        <div class="sched-time-box">
          <span class="sched-time">05:20 AM</span>
          <span class="sched-ap"><?= htmlspecialchars(explode(',', $destination['label'])[0], ENT_QUOTES) ?></span>
        </div>
      </div>

      <div class="sched-select-col">
        <span class="sched-tag">Included</span>
        <button type="button" class="btn-sched-select">Selected</button>
      </div>
    </div>

    <div class="flight-sched-card" tabindex="0" role="button" data-schedule="Philippines AirAsia AG-418 (05:55 AM MNL T2 &rarr; 07:20 AM)">
      <div class="sched-airline-col">
        <span class="sched-airline-logo">✈️</span>
        <div>
          <strong class="sched-airline-name">Philippines AirAsia</strong>
          <span class="sched-flight-no">Flight AG-418 &middot; Airbus A320</span>
        </div>
      </div>

      <div class="sched-route-col">
        <div class="sched-time-box">
          <span class="sched-time">05:55 AM</span>
          <span class="sched-ap">MNL T2</span>
        </div>
        <div class="sched-duration-box">
          <span class="sched-dur-text">1h 25m</span>
          <div class="sched-line"></div>
          <span class="sched-badge">Nonstop</span>
        </div>
        <div class="sched-time-box">
          <span class="sched-time">07:20 AM</span>
          <span class="sched-ap"><?= htmlspecialchars(explode(',', $destination['label'])[0], ENT_QUOTES) ?></span>
        </div>
      </div>

      <div class="sched-select-col">
        <span class="sched-tag">Included</span>
        <button type="button" class="btn-sched-select">Select</button>
      </div>
    </div>

    <div class="flight-sched-card" tabindex="0" role="button" data-schedule="Cebu Pacific AG-514 (11:40 AM MNL T3 &rarr; 01:00 PM)">
      <div class="sched-airline-col">
        <span class="sched-airline-logo">✈️</span>
        <div>
          <strong class="sched-airline-name">Cebu Pacific</strong>
          <span class="sched-flight-no">Flight AG-514 &middot; Airbus A321neo</span>
        </div>
      </div>

      <div class="sched-route-col">
        <div class="sched-time-box">
          <span class="sched-time">11:40 AM</span>
          <span class="sched-ap">MNL T3</span>
        </div>
        <div class="sched-duration-box">
          <span class="sched-dur-text">1h 20m</span>
          <div class="sched-line"></div>
          <span class="sched-badge">Nonstop</span>
        </div>
        <div class="sched-time-box">
          <span class="sched-time">01:00 PM</span>
          <span class="sched-ap"><?= htmlspecialchars(explode(',', $destination['label'])[0], ENT_QUOTES) ?></span>
        </div>
      </div>

      <div class="sched-select-col">
        <span class="sched-tag">Included</span>
        <button type="button" class="btn-sched-select">Select</button>
      </div>
    </div>

    <div class="flight-sched-card" tabindex="0" role="button" data-schedule="Philippine Airlines AG-788 (05:45 PM MNL T2 &rarr; 07:10 PM)">
      <div class="sched-airline-col">
        <span class="sched-airline-logo">✈️</span>
        <div>
          <strong class="sched-airline-name">Philippine Airlines</strong>
          <span class="sched-flight-no">Flight AG-788 &middot; Boeing 737</span>
        </div>
      </div>

      <div class="sched-route-col">
        <div class="sched-time-box">
          <span class="sched-time">05:45 PM</span>
          <span class="sched-ap">MNL T2</span>
        </div>
        <div class="sched-duration-box">
          <span class="sched-dur-text">1h 25m</span>
          <div class="sched-line"></div>
          <span class="sched-badge">Nonstop</span>
        </div>
        <div class="sched-time-box">
          <span class="sched-time">07:10 PM</span>
          <span class="sched-ap"><?= htmlspecialchars(explode(',', $destination['label'])[0], ENT_QUOTES) ?></span>
        </div>
      </div>

      <div class="sched-select-col">
        <span class="sched-tag">Included</span>
        <button type="button" class="btn-sched-select">Select</button>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ HOTELS ============ -->
<section class="dest-section">
  <div class="dest-section-header">
    <h2 class="section-main-title">Available Hotels</h2>
    <p class="section-tagline">Pick a stay in <?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>. Preview 4 images via slide show or click to enlarge.</p>
  </div>

  <div class="option-grid" id="hotel-grid" data-kind="hotel">
    <?php foreach ($destination['hotels'] as $i => $hotel): 
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
          <p class="option-meta"><?= htmlspecialchars($hotel['tier'], ENT_QUOTES) ?> &middot; ★ <?= htmlspecialchars(number_format($hotel['rating'], 1), ENT_QUOTES) ?></p>
        </div>
        <div class="option-price-col">
          <span class="option-price"><?= peso($hotel['price']) ?></span>
          <span class="option-price-unit">per night</span>
          <span class="option-select-btn"><?= $i === 0 ? 'Selected' : 'Select' ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="nights-stepper">
    <span class="nights-label">Length of stay</span>
    <div class="nights-control">
      <button type="button" class="nights-btn" id="nights-dec" aria-label="Decrease nights">&minus;</button>
      <span class="nights-count-disp"><span id="nights-count"><?= $defaultNights ?></span> <span class="nights-suffix">night(s)</span></span>
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
      $carImages = asset_gallery($car['name'], $destination['asset']);
      $hasMultiple = count($carImages) > 1;
    ?>
    <div class="option-card<?= $i === 0 ? ' is-selected' : '' ?>" tabindex="0" role="button" aria-label="Select <?= htmlspecialchars($car['name'], ENT_QUOTES) ?>"
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
          <p class="option-meta"><?= (int) $car['seats'] ?> seats &middot; Free cancellation</p>
        </div>
        <div class="option-price-col">
          <span class="option-price"><?= peso($car['price']) ?></span>
          <span class="option-price-unit">per day</span>
          <span class="option-select-btn"><?= $i === 0 ? 'Selected' : 'Select' ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="nights-stepper">
    <span class="nights-label">Rental length</span>
    <div class="nights-control">
      <button type="button" class="nights-btn" id="days-dec" aria-label="Decrease days">&minus;</button>
      <span class="nights-count-disp"><span id="days-count"><?= $defaultDays ?></span> <span class="nights-suffix">day(s)</span></span>
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

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- ============ STICKY TRIP SUMMARY BAR ============ -->
<div class="trip-summary-bar" id="trip-summary-bar">
  <div class="summary-line">
    <span id="sum-flight-label">Flight (<?= htmlspecialchars($fareName, ENT_QUOTES) ?> &middot; <?= $adults ?> Adult<?= $adults > 1 ? 's' : '' ?>)</span>
    <span id="sum-flight" class="summary-price"><?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?></span>
  </div>
  <div class="summary-line">
    <span id="sum-hotel-label">Hotel &middot; <?= $defaultNights ?> night(s)</span>
    <span id="sum-hotel" class="summary-price">—</span>
  </div>
  <div class="summary-line">
    <span id="sum-car-label">Car rental &middot; <?= $defaultDays ?> day(s)</span>
    <span id="sum-car" class="summary-price">—</span>
  </div>
  <div class="summary-total">
    <span>Estimated total</span>
    <span id="sum-total">—</span>
  </div>
  <button type="button" class="btn-proceed" id="btn-proceed">Proceed to booking</button>
</div>

<!-- Hidden hand-off form: carries choices to checkout.php -->
<form id="checkout-form" action="checkout.php" method="GET" style="display:none">
  <input type="hidden" name="mode"           value="<?= htmlspecialchars($mode, ENT_QUOTES) ?>">
  <input type="hidden" name="adults"         value="<?= htmlspecialchars($adults, ENT_QUOTES) ?>">
  <input type="hidden" name="cabin"          value="<?= htmlspecialchars($cabin, ENT_QUOTES) ?>">
  <input type="hidden" name="city"           value="<?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>">
  <input type="hidden" name="fareName"       value="<?= htmlspecialchars($fareName, ENT_QUOTES) ?>">
  <input type="hidden" name="fareDesc"       value="<?= htmlspecialchars($fareDesc, ENT_QUOTES) ?>">
  <input type="hidden" name="price"          value="<?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?>">
  <input type="hidden" name="flightSchedule" value="">
  <input type="hidden" name="hotelName"      value="">
  <input type="hidden" name="hotelPrice"     value="">
  <input type="hidden" name="nights"         value="">
  <input type="hidden" name="carName"        value="">
  <input type="hidden" name="carPrice"       value="">
  <input type="hidden" name="days"           value="">
</form>

<script>
(function() {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
  const basePerPerson = <?= json_encode(round($basePrice * $cabinMult)) ?>;
  const isHotelMode   = <?= json_encode($mode === 'hotels') ?>;
  const fareNameStr   = <?= json_encode($fareName) ?>;
  let currentAdults   = <?= json_encode($adults) ?>;

  let nights = <?= json_encode($defaultNights) ?>;
  let days   = <?= json_encode($defaultDays) ?>;
  let hotelPrice = <?= json_encode((float) $destination['hotels'][0]['price']) ?>;
  let carPrice   = <?= json_encode((float) $destination['cars'][0]['price']) ?>;

  function updateTravelers(newCount) {
    currentAdults = Math.max(1, Math.min(9, newCount));
    const countDisp = $('#dest-guest-count');
    if (countDisp) countDisp.textContent = currentAdults;

    const flightTotal = isHotelMode ? 0 : Math.round(basePerPerson * currentAdults);

    const priceDisp = $('#dest-fare-chip-price-disp');
    if (priceDisp) priceDisp.textContent = isHotelMode ? '₱0 (Flight skipped)' : peso(flightTotal);

    const sumFlightLabel = $('#sum-flight-label');
    if (sumFlightLabel) sumFlightLabel.innerHTML = `Flight (${fareNameStr} &middot; ${currentAdults} Guest${currentAdults !== 1 ? 's' : ''})`;

    const sumFlight = $('#sum-flight');
    if (sumFlight) sumFlight.textContent = isHotelMode ? '₱0' : peso(flightTotal);

    const form = $('#checkout-form');
    if (form) {
      const hAdults = form.querySelector('[name="adults"]');
      if (hAdults) hAdults.value = currentAdults;
      const hPrice = form.querySelector('[name="price"]');
      if (hPrice) hPrice.value = isHotelMode ? '₱0' : peso(flightTotal);
    }

    recalc();
  }

  $('#dest-guest-dec')?.addEventListener('click', () => updateTravelers(currentAdults - 1));
  $('#dest-guest-inc')?.addEventListener('click', () => updateTravelers(currentAdults + 1));

  // Flight schedule selector
  $$('.flight-sched-card').forEach(card => {
    card.addEventListener('click', () => {
      $$('.flight-sched-card').forEach(c => {
        c.classList.remove('is-selected');
        const btn = c.querySelector('.btn-sched-select');
        if (btn) btn.textContent = 'Select';
      });
      card.classList.add('is-selected');
      const btn = card.querySelector('.btn-sched-select');
      if (btn) btn.textContent = 'Selected';
    });
  });

  function selectCard(card) {
    const grid = card.closest('.option-grid');
    $$('.option-card', grid).forEach(c => {
      c.classList.remove('is-selected');
      const btn = c.querySelector('.option-select-btn');
      if (btn) btn.textContent = 'Select';
    });
    card.classList.add('is-selected');
    const selectedBtn = card.querySelector('.option-select-btn');
    if (selectedBtn) selectedBtn.textContent = 'Selected';
  }

  $$('.option-card', $('#hotel-grid')).forEach(card => {
    card.addEventListener('click', (e) => {
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

  $('#nights-dec')?.addEventListener('click', () => { if (nights > 0) { nights--; $('#nights-count').textContent = nights; recalc(); } });
  $('#nights-inc')?.addEventListener('click', () => { if (nights < 14) { nights++; $('#nights-count').textContent = nights; recalc(); } });
  $('#days-dec')?.addEventListener('click', () => { if (days > 0) { days--; $('#days-count').textContent = days; recalc(); } });
  $('#days-inc')?.addEventListener('click', () => { if (days < 14) { days++; $('#days-count').textContent = days; recalc(); } });

  function recalc() {
    const flightTotal = isHotelMode ? 0 : Math.round(basePerPerson * currentAdults);
    const hotelTotal  = hotelPrice * nights;
    const carTotal    = carPrice * days;
    const total       = flightTotal + hotelTotal + carTotal;

    if ($('#sum-hotel-label')) {
      $('#sum-hotel-label').textContent = nights === 0 ? 'Hotel · Optional (0 nights)' : `Hotel · ${nights} night${nights > 1 ? 's' : ''}`;
    }
    if ($('#sum-car-label')) {
      $('#sum-car-label').textContent = days === 0 ? 'Car rental · Optional (0 days)' : `Car rental · ${days} day${days > 1 ? 's' : ''}`;
    }
    if ($('#sum-hotel')) $('#sum-hotel').textContent = peso(hotelTotal);
    if ($('#sum-car'))   $('#sum-car').textContent   = peso(carTotal);
    if ($('#sum-total')) $('#sum-total').textContent = peso(total);
  }

  recalc();

  $('#btn-proceed')?.addEventListener('click', () => {
    const form = $('#checkout-form');
    if (!form) return;

    const schedCard = $('.flight-sched-card.is-selected');
    const hotelCard = $('#hotel-grid .option-card.is-selected');
    const carCard   = $('#car-grid .option-card.is-selected');

    form.querySelector('[name="flightSchedule"]').value = schedCard ? schedCard.dataset.schedule : 'Standard Schedule';
    form.querySelector('[name="hotelName"]').value  = hotelCard ? hotelCard.dataset.name : '';
    form.querySelector('[name="hotelPrice"]').value = hotelPrice;
    form.querySelector('[name="nights"]').value     = nights;
    form.querySelector('[name="carName"]').value    = carCard ? carCard.dataset.name : '';
    form.querySelector('[name="carPrice"]').value   = carPrice;
    form.querySelector('[name="days"]').value       = days;

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

    slides.forEach((slideImg, sIdx) => {
      slideImg.addEventListener('click', (e) => {
        e.stopPropagation();
        openLightbox(images, sIdx);
      });
    });

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
