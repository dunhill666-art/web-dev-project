<?php
// ============================================================
// AeroGlide — destination.php
// Destination details, Cebu Pacific-style dynamic flight pricing,
// 7-day date carousel, hotel stays & car rental selection.
// ============================================================

require_once __DIR__ . '/user/auth_helper.php';
require_once __DIR__ . '/database/flight_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

if (!function_exists('peso')) {
    function peso(float $n): string
    {
        return '₱' . number_format($n, 0);
    }
}

if (!function_exists('asset_gallery')) {
    function asset_gallery(string $itemName, array $heroFallbackKeywords, int $max = 4): array
    {
        $files  = asset_index();
        $needle = strtolower(preg_replace('/[^a-z0-9]+/i', '', $itemName));

        $matches = [];
        $seen = [];

        // 1. Direct match on full squashed needle
        if ($needle !== '') {
            foreach ($files as $f) {
                if (isset($seen[$f['name']])) continue;
                if (strpos($f['squash'], $needle) !== false || strpos($needle, $f['squash']) !== false) {
                    $matches[] = $f;
                    $seen[$f['name']] = true;
                }
            }
        }

        // 2. Keyword token fallback for words
        if (empty($matches)) {
            $words = array_filter(explode(' ', strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $itemName))), fn($w) => strlen($w) >= 3 && !in_array($w, ['hotel', 'resort', 'inn', 'lodge', 'beach', 'view', 'dive', 'stay', 'town', 'island', 'house', 'park', 'villas', 'haven', 'travelers', 'rental', 'white', 'background']));
            foreach ($words as $w) {
                foreach ($files as $f) {
                    if (isset($seen[$f['name']])) continue;
                    if (strpos($f['squash'], $w) !== false || strpos($f['norm'], ' ' . $w . ' ') !== false) {
                        $matches[] = $f;
                        $seen[$f['name']] = true;
                    }
                }
                if (!empty($matches)) break;
            }
        }

        // 3. Category fallback if no specific asset matches
        if (empty($matches)) {
            $lowerItem = strtolower($itemName);
            if (str_contains($lowerItem, 'sedan') || str_contains($lowerItem, 'car')) {
                return [asset_find(['sedan', 'economy sedan'])];
            } elseif (str_contains($lowerItem, 'suv')) {
                return [asset_find(['suv', 'mid size suv'])];
            } elseif (str_contains($lowerItem, 'van')) {
                return [asset_find(['van', 'tourist van'])];
            } elseif (str_contains($lowerItem, 'scooter') || str_contains($lowerItem, 'motorbike') || str_contains($lowerItem, 'bike') || str_contains($lowerItem, 'fazzio')) {
                return [asset_find(['fazzio', 'mio'])];
            } elseif (str_contains($lowerItem, 'boat') || str_contains($lowerItem, 'transfer') || str_contains($lowerItem, 'outrigger')) {
                return [asset_find(['coron'])];
            } else {
                $hotelPool = [
                    'asset/Bellevue Resort Bohol.jpg',
                    'asset/Communal Ranch Lodge.jpg',
                    'asset/Discovery Shores Boracay.jpg',
                    'asset/Henann Regency Resort & Spa 1.jpg',
                    'asset/Ocean Vida Beach & Dive Resort.jpg',
                    'asset/Shangri-La Boracay Resort & Spa 1.jpg',
                    'asset/Taalisay Ridge Resort.jpg',
                    'asset/albay/Harbor View Inn 1.jpg',
                    'asset/albay/Seaside Garden Hotel 1.jpg',
                    'asset/coron/coron-soleil-garden-resort.jpg',
                    'asset/coron/Coron Westown Resort, Palawan, Philippines.jpg',
                    'asset/siargao/Bravo Beach Resort, Siargao Island _ 2026 Updated Prices, Deals.jpg',
                    'asset/siargao/Nay Palad Hideaway.jpg',
                    'asset/siargao/kermit surf.jpg',
                ];
                $idx = abs(crc32($itemName)) % count($hotelPool);
                return [asset_url($hotelPool[$idx])];
            }
        }

        usort($matches, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return array_map('asset_url', array_slice($matches, 0, $max));
    }
}

/* ------------------------------------------------------------
   Read input parameters (passed via URL query string)
------------------------------------------------------------ */
$mode   = strtolower(trim($_GET['mode'] ?? 'packages'));
$adults = max(1, (int) ($_GET['adults'] ?? 1));
$cabin  = trim($_GET['cabin'] ?? 'Economy');

$city       = isset($_GET['city'])        ? trim($_GET['city'])        : 'Mayon Volcano, Albay';
$origin     = isset($_GET['origin'])      ? strtoupper(trim($_GET['origin'])) : 'MNL';
$departDate = isset($_GET['departDate'])  ? trim($_GET['departDate'])  : (isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d'));
$returnDate = isset($_GET['returnDate'])  ? trim($_GET['returnDate'])  : '';

$fareValue  = isset($_GET['fare'])        ? trim($_GET['fare'])        : 'smart';
$fareName   = isset($_GET['fareName'])    ? trim($_GET['fareName'])    : 'Smart Saver';
$fareDesc   = isset($_GET['desc'])        ? trim($_GET['desc'])        : 'Flight + checked baggage';
$farePriceRaw = isset($_GET['price'])     ? trim($_GET['price'])       : '₱4,999';

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
        'blurb'   => 'Handpicked stays and rides for your trip to the world-famous perfect cone.',
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

// Fetch Cebu Pacific style dynamic flights & 7-day carousel data
$flightSearchResults = flight_search_results($destination['label'], $origin, $departDate);
$routeInfo = $flightSearchResults['route'];
$carouselDays = $flightSearchResults['carousel'];
$availableFlights = $flightSearchResults['flights'];

$firstAvailableFlight = null;
foreach ($availableFlights as $af) {
    if (!empty($af['is_available'])) {
        $firstAvailableFlight = $af;
        break;
    }
}

// Curate flight price to match lowest available flight card if no explicit override is provided
if ($mode !== 'hotels' && (!isset($_GET['price']) || $basePrice === 4999.0)) {
    if ($firstAvailableFlight) {
        $basePrice = (float)$firstAvailableFlight['base_price'];
        $farePriceNum = round($basePrice * $cabinMult * $adults);
        $farePriceRaw = '₱' . number_format($farePriceNum);
    } elseif (!empty($availableFlights)) {
        $basePrice = (float)$availableFlights[0]['base_price'];
        $farePriceNum = round($basePrice * $cabinMult * $adults);
        $farePriceRaw = '₱' . number_format($farePriceNum);
    } elseif (!empty($routeInfo['base_rate'])) {
        $basePrice = (float)$routeInfo['base_rate'];
        $farePriceNum = round($basePrice * $cabinMult * $adults);
        $farePriceRaw = '₱' . number_format($farePriceNum);
    } else {
        $basePrice = 0;
        $farePriceNum = 0;
        $farePriceRaw = 'Unavailable';
    }
}

$pageTitle = htmlspecialchars($destination['label'], ENT_QUOTES) . ' — AeroGlide Flights & Packages';
$activeNav = 'deals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
/* ============================================================
   DESTINATION PAGE & FLIGHT SELECTION INTERFACE (CEBU PACIFIC STYLE)
   ============================================================ */
.destination-main {
  padding-bottom: 120px;
}

/* Top Search Summary Strip */
.cp-search-summary-strip {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 1.1rem 2rem;
  box-shadow: 0 2px 8px rgba(10, 20, 35, 0.04);
}
.cp-summary-wrap {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.25rem;
}
.cp-summary-info-group {
  display: flex;
  align-items: center;
  gap: 2.5rem;
  flex-wrap: wrap;
}
.cp-summary-col {
  display: flex;
  flex-direction: column;
}
.cp-summary-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 2px;
}
.cp-summary-val {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0f172a;
}
.cp-summary-sub {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}
.cp-btn-edit-search {
  background: #ffffff;
  color: #0284c7;
  border: 1.5px solid #0284c7;
  padding: 0.55rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.cp-btn-edit-search:hover {
  background: #0284c7;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
}

/* Stepper Progress Bar */
.cp-stepper-bar {
  background: #ffe600;
  padding: 1.1rem 1.5rem;
  box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.04);
}
.cp-stepper-container {
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}
.cp-step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  cursor: default;
}
.cp-step-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(10, 20, 35, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  color: #0f172a;
  margin-bottom: 6px;
  border: 2px solid transparent;
  transition: all 0.25s ease;
}
.cp-step-item.is-active .cp-step-icon-wrap {
  background: #0f172a;
  color: #ffe600;
  border-color: #0f172a;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25);
  transform: scale(1.1);
}
.cp-step-item.is-done .cp-step-icon-wrap {
  background: #16a34a;
  color: #fff;
}
.cp-step-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
  white-space: nowrap;
}
.cp-step-item.is-active .cp-step-label {
  font-weight: 900;
  color: #0f172a;
}
.cp-step-track {
  position: absolute;
  top: 19px;
  left: 40px;
  right: 40px;
  height: 2px;
  background: rgba(10, 20, 35, 0.18);
  z-index: 1;
}

/* Destination Hero Box */
.dest-hero {
  position: relative;
  background-size: cover;
  background-position: center;
  padding: 4rem 2rem 5rem;
  color: #ffffff;
}
.dest-hero-inner {
  max-width: 1200px;
  margin: 0 auto;
}
.dest-back-link {
  display: inline-block;
  color: #ffffff;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  opacity: 0.9;
  transition: opacity 0.2s ease;
}
.dest-back-link:hover { opacity: 1; text-decoration: underline; }
.dest-hero-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  margin: 0 0 0.5rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}
.dest-hero-blurb {
  font-size: 1.1rem;
  max-width: 600px;
  margin: 0 0 1.5rem;
  opacity: 0.95;
  line-height: 1.5;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
}
.dest-hero-box {
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 20px;
  padding: 1.25rem 1.6rem;
  max-width: 440px;
  box-shadow: 0 16px 36px rgba(10, 20, 35, 0.22);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.dest-box-header {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.dest-box-tag {
  align-self: flex-start;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.8px;
  color: #0d6efd;
  background: #eef5ff;
  padding: 4px 10px;
  border-radius: 12px;
  text-transform: uppercase;
}
.dest-box-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0a1425;
  margin: 4px 0 2px;
}
.dest-box-price {
  font-size: 1.5rem;
  font-weight: 900;
  color: #0d6efd;
  font-family: 'Montserrat', sans-serif;
}
.dest-box-guests-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.85rem;
  border-top: 1px dashed #cbd5e1;
}
.dest-box-guests-info {
  display: flex;
  flex-direction: column;
}
.dest-box-guests-info strong {
  font-size: 0.9rem;
  font-weight: 800;
  color: #0a1425;
}
.dest-box-guests-info span {
  font-size: 0.78rem;
  color: #5b6b7f;
}
.dest-counter {
  display: flex;
  align-items: center;
  gap: 8px;
}
.cnt-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #0f172a;
  font-size: 1.1rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}
.cnt-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
.cnt-val {
  font-weight: 800;
  font-size: 1rem;
  min-width: 24px;
  text-align: center;
  color: #0f172a;
}

/* Main Container */
.dest-content-container {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}
.dest-section {
  margin-bottom: 3rem;
}

/* Route Header */
.cp-route-header {
  margin-bottom: 1.5rem;
}
.cp-route-eyebrow {
  font-size: 0.9rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
  display: block;
}
.cp-route-title {
  font-size: 1.75rem;
  font-weight: 900;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
}
.cp-route-plane-icon {
  color: #0284c7;
  font-size: 1.4rem;
}

/* 7-Day Horizontal Date Carousel */
.cp-date-carousel-wrap {
  display: flex;
  align-items: stretch;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 1.75rem;
  box-shadow: 0 4px 14px rgba(10, 20, 35, 0.05);
}
.cp-carousel-arrow {
  background: #ffffff;
  border: none;
  border-right: 1px solid #cbd5e1;
  width: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0284c7;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
  flex-shrink: 0;
}
.cp-carousel-arrow.next {
  border-right: none;
  border-left: 1px solid #cbd5e1;
}
.cp-carousel-arrow:hover {
  background: #f0f9ff;
  color: #0369a1;
}
.cp-carousel-cards {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  flex: 1;
}
@media (max-width: 900px) {
  .cp-carousel-cards { grid-template-columns: repeat(4, 1fr); }
  .cp-carousel-card:nth-child(n+5) { display: none; }
}
@media (max-width: 540px) {
  .cp-carousel-cards { grid-template-columns: repeat(3, 1fr); }
  .cp-carousel-card:nth-child(n+4) { display: none; }
}
.cp-carousel-card {
  padding: 1rem 0.6rem;
  text-align: center;
  border-right: 1px solid #e2e8f0;
  cursor: pointer;
  background: #ffffff;
  transition: background 0.15s ease;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;
  text-decoration: none;
}
.cp-carousel-card:last-child {
  border-right: none;
}
.cp-carousel-card:hover:not(.is-active) {
  background: #f8fafc;
}
.cp-card-date {
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
}
.cp-card-price {
  font-size: 0.88rem;
  font-weight: 800;
  color: #0f172a;
}
.cp-card-price.no-flights {
  font-size: 0.78rem;
  font-weight: 600;
  color: #94a3b8;
}
.cp-card-price.sold-out {
  font-size: 0.78rem;
  font-weight: 800;
  color: #ef4444;
  background: #fee2e2;
  padding: 2px 7px;
  border-radius: 5px;
  display: inline-block;
}
.cp-lowest-pill {
  background: #16a34a;
  color: #ffffff;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  padding: 2px 6px;
  border-radius: 4px;
  margin-top: 3px;
  letter-spacing: 0.3px;
  line-height: 1;
}
/* Active Card Highlight */
.cp-carousel-card.is-active {
  background: #ffe600 !important;
  border-color: #ffd200 !important;
}
.cp-carousel-card.is-active .cp-card-date {
  color: #0f172a;
  font-weight: 800;
}
.cp-carousel-card.is-active .cp-card-price {
  color: #0f172a;
  font-weight: 900;
}
.cp-carousel-card.is-active .cp-card-price.sold-out {
  background: rgba(220, 38, 38, 0.15);
  color: #b91c1c;
}
.cp-carousel-card.is-active .cp-lowest-pill {
  background: #0f172a;
  color: #ffe600;
}

/* Filter & Sort Toolbar */
.cp-filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-sizing: border-box;
}
.cp-filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.cp-filter-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.2px;
}
.cp-select-pill {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  padding: 0.45rem 2rem 0.45rem 0.85rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%230284c7' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
  transition: all 0.2s ease;
  height: 38px;
}
.cp-select-pill:hover {
  border-color: #94a3b8;
}
.cp-select-pill:focus {
  outline: none;
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
.cp-select-pill.is-active-filter {
  border-color: #0284c7;
  background-color: #f0f9ff;
  color: #0369a1;
  font-weight: 700;
}

@media (max-width: 860px) {
  .cp-filter-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 0.85rem;
  }
  .cp-filter-group {
    width: 100%;
    justify-content: flex-start;
  }
  .cp-filter-group.sort-group {
    border-top: 1px dashed #cbd5e1;
    padding-top: 0.75rem;
    justify-content: space-between;
  }
}

/* Flight List & Cards */
.cp-flights-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}
.cp-flight-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.35rem 1.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  flex-wrap: wrap;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  position: relative;
}
.cp-flight-card:hover:not(.is-sold-out) {
  border-color: #38bdf8;
  box-shadow: 0 8px 24px rgba(2, 132, 199, 0.08);
}
.cp-flight-card.is-selected {
  border-color: #0284c7;
  background: #f8fcff;
  box-shadow: 0 6px 20px rgba(2, 132, 199, 0.12);
}
.cp-flight-card.is-sold-out {
  opacity: 0.65;
  background: #f8fafc;
  border-color: #e2e8f0;
  cursor: not-allowed;
}
.cp-flight-card.is-sold-out:hover {
  transform: none;
  box-shadow: none;
  border-color: #e2e8f0;
}
.cp-flight-airline {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 200px;
}
.cp-airline-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #e0f2fe, #bae6fd);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.cp-airline-info {
  display: flex;
  flex-direction: column;
}
.cp-airline-name {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0f172a;
}
.cp-flight-equipment {
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 500;
}
.cp-flight-timeline {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
  min-width: 280px;
  justify-content: center;
}
.cp-time-col {
  text-align: center;
}
.cp-time-text {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  display: block;
}
.cp-port-text {
  font-size: 0.76rem;
  font-weight: 700;
  color: #64748b;
}
.cp-duration-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 140px;
}
.cp-dur-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
}
.cp-flight-line {
  width: 100%;
  height: 2px;
  background: #cbd5e1;
  position: relative;
  border-radius: 2px;
}
.cp-flight-line::after {
  content: '✈';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.75rem;
  color: #0284c7;
  background: #ffffff;
  padding: 0 4px;
}
.cp-badge-nonstop {
  font-size: 0.7rem;
  font-weight: 700;
  color: #0284c7;
  background: #f0f9ff;
  padding: 2px 8px;
  border-radius: 6px;
  margin-top: 4px;
}

/* Status Badges */
.cp-flight-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  margin-bottom: 4px;
}
.cp-flight-badge.badge-available {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}
.cp-flight-badge.badge-warning {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}
.cp-flight-badge.badge-soldout {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.cp-flight-badge.badge-lowest {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.cp-flight-action {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  min-width: 140px;
}
.cp-flight-fare {
  font-size: 1.15rem;
  font-weight: 900;
  color: #0f172a;
}
.cp-flight-fare.muted {
  color: #94a3b8;
  font-size: 1rem;
}
.cp-flight-fare-sub {
  font-size: 0.72rem;
  color: #64748b;
}
.cp-btn-flight-select {
  background: #0284c7;
  color: #ffffff;
  border: none;
  padding: 0.6rem 1.6rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
}
.cp-btn-flight-select:hover:not(:disabled) {
  background: #0369a1;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
}
.cp-flight-card.is-selected .cp-btn-flight-select {
  background: #0f172a;
}
.cp-btn-flight-select.is-disabled, .cp-btn-flight-select:disabled {
  background: #cbd5e1 !important;
  color: #64748b !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
  opacity: 0.85;
}

.cp-empty-state-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 4px 16px rgba(10, 20, 35, 0.04);
  margin: 1.5rem 0;
}
.cp-empty-plane-graphic {
  width: 140px;
  height: 140px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cp-empty-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 0.6rem;
}
.cp-empty-text {
  font-size: 0.92rem;
  color: #64748b;
  max-width: 520px;
  margin: 0 auto 1.5rem;
  line-height: 1.6;
}
.cp-operator-footnote {
  margin-top: 2rem;
  padding: 1.25rem 0;
  border-top: 1px solid #e2e8f0;
  font-size: 0.8rem;
  color: #64748b;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
}
.cp-operator-list {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.cp-operator-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-weight: 600;
  color: #334155;
}
.cp-operator-item span { color: #eab308; }

/* Sticky Trip Summary Bar */
.trip-summary-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(14px);
  border-top: 1.5px solid #e2e8f0;
  padding: 0.85rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  z-index: 1000;
  box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.08);
  box-sizing: border-box;
}
.summary-items {
  display: flex;
  align-items: center;
  gap: 2.5rem;
  flex-wrap: wrap;
}
.summary-action {
  display: flex;
  align-items: center;
  gap: 1.75rem;
  flex-shrink: 0;
}
.summary-line {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.summary-line span:first-child {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
}
.summary-price {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}
.summary-total {
  display: flex;
  flex-direction: column;
  text-align: right;
  gap: 2px;
}
.total-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.summary-total #sum-total {
  font-size: 1.5rem;
  font-weight: 900;
  color: #0284c7;
}
.btn-proceed {
  background: #0284c7;
  color: #ffffff;
  border: none;
  padding: 0.85rem 2rem;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
  white-space: nowrap;
}
.btn-proceed:hover:not(:disabled) {
  background: #0369a1;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(2, 132, 199, 0.45);
}
.btn-proceed:disabled, .btn-proceed.is-disabled {
  background: #cbd5e1 !important;
  color: #64748b !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
  opacity: 0.85;
}

@media (max-width: 992px) {
  .destination-main {
    padding-bottom: 160px;
  }
  .trip-summary-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 0.85rem;
    padding: 0.85rem 1.25rem;
  }
  .summary-items {
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
  }
  .summary-action {
    justify-content: space-between;
    width: 100%;
    border-top: 1px solid #f1f5f9;
    padding-top: 0.5rem;
  }
}

@media (max-width: 640px) {
  .destination-main {
    padding-bottom: 180px;
  }
  .summary-items {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    text-align: center;
  }
  .summary-line span:first-child {
    font-size: 0.72rem;
  }
  .summary-price {
    font-size: 0.95rem;
  }
  .summary-total #sum-total {
    font-size: 1.25rem;
  }
  .btn-proceed {
    padding: 0.7rem 1.25rem;
    font-size: 0.88rem;
  }
}
</style>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ============ CEBU PACIFIC-STYLE TOP SEARCH SUMMARY STRIP ============ -->
<section class="cp-search-summary-strip">
  <div class="cp-summary-wrap">
    <div class="cp-summary-info-group">
      <div class="cp-summary-col">
        <span class="cp-summary-label">Departing Flight</span>
        <strong class="cp-summary-val"><?= htmlspecialchars($routeInfo['origin_name'], ENT_QUOTES) ?> To <?= htmlspecialchars($routeInfo['label'], ENT_QUOTES) ?></strong>
        <span class="cp-summary-sub"><?= date('d M Y', strtotime($departDate)) ?></span>
      </div>

      <?php if (!empty($returnDate)): ?>
      <div class="cp-summary-col">
        <span class="cp-summary-label">Returning Flight</span>
        <strong class="cp-summary-val"><?= htmlspecialchars($routeInfo['label'], ENT_QUOTES) ?> To <?= htmlspecialchars($routeInfo['origin_name'], ENT_QUOTES) ?></strong>
        <span class="cp-summary-sub"><?= date('d M Y', strtotime($returnDate)) ?></span>
      </div>
      <?php endif; ?>

      <div class="cp-summary-col">
        <span class="cp-summary-label">Guests</span>
        <strong class="cp-summary-val"><?= $adults ?> <?= $adults > 1 ? 'Adults' : 'Adult' ?> &middot; <?= htmlspecialchars($cabin, ENT_QUOTES) ?></strong>
        <span class="cp-summary-sub"><?= ucfirst($mode) ?> Mode</span>
      </div>
    </div>

    <a href="<?= ag_base_url('index.php#booking') ?>" class="cp-btn-edit-search">Edit Search</a>
  </div>
</section>

<!-- ============ CEBU PACIFIC-STYLE STEP PROGRESS BAR ============ -->
<div class="cp-stepper-bar">
  <div class="cp-stepper-container">
    <div class="cp-step-track"></div>
    <div class="cp-step-item is-active">
      <div class="cp-step-icon-wrap">✈</div>
      <span class="cp-step-label">Select Flight</span>
    </div>
    <div class="cp-step-item">
      <div class="cp-step-icon-wrap">👤</div>
      <span class="cp-step-label">Guest Details</span>
    </div>
    <div class="cp-step-item">
      <div class="cp-step-icon-wrap">🧳</div>
      <span class="cp-step-label">Add-ons</span>
    </div>
    <div class="cp-step-item">
      <div class="cp-step-icon-wrap">💳</div>
      <span class="cp-step-label">Payment</span>
    </div>
    <div class="cp-step-item">
      <div class="cp-step-icon-wrap">✓</div>
      <span class="cp-step-label">Confirmation</span>
    </div>
  </div>
</div>

<main class="destination-main">

<!-- ============ DESTINATION HERO ============ -->
<section class="dest-hero" style="background-image: linear-gradient(180deg, rgba(10,20,35,.15), rgba(10,20,35,.75)), url('<?= htmlspecialchars($heroImage, ENT_QUOTES) ?>');">
  <div class="dest-hero-inner">
    <a href="<?= ag_base_url('index.php#deals') ?>" class="dest-back-link">&larr; Back to Deals</a>
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

<!-- ============ CEBU PACIFIC-STYLE FLIGHT RESULTS & DATE CAROUSEL ============ -->
<?php if ($mode !== 'hotels'): ?>
<section class="dest-section" id="section-flights">
  
  <div class="dest-section-header">
    <h2 class="section-main-title">Available Flights</h2>
    <p class="section-tagline">Choose your preferred flight schedule from <?= htmlspecialchars($routeInfo['origin_name'], ENT_QUOTES) ?> to <?= htmlspecialchars($routeInfo['label'], ENT_QUOTES) ?>.</p>
  </div>

  <div class="cp-route-header">
    <span class="cp-route-eyebrow">Select your departing flight</span>
    <h2 class="cp-route-title">
      <?= htmlspecialchars($routeInfo['origin_name'], ENT_QUOTES) ?> 
      <span class="cp-route-plane-icon">✈</span> 
      <?= htmlspecialchars($routeInfo['label'], ENT_QUOTES) ?>
    </h2>
  </div>

  <!-- 7-Day Horizontal Date Carousel (Cebu Pacific Style) -->
  <div class="cp-date-carousel-wrap">
    <button type="button" class="cp-carousel-arrow prev" id="btn-carousel-prev" aria-label="Previous date range">&#10094;</button>
    <div class="cp-carousel-cards" id="cp-carousel-cards-container">
      <?php foreach ($carouselDays as $cDay): ?>
        <a href="javascript:void(0)" 
           class="cp-carousel-card<?= $cDay['is_selected'] ? ' is-active' : '' ?><?= !empty($cDay['is_sold_out']) ? ' is-sold-out-date' : '' ?>"
           data-date="<?= htmlspecialchars($cDay['date'], ENT_QUOTES) ?>">
          <span class="cp-card-date"><?= htmlspecialchars($cDay['label'], ENT_QUOTES) ?></span>
          <?php if (!empty($cDay['has_flights'])): ?>
            <span class="cp-card-price"><?= htmlspecialchars($cDay['price_label'], ENT_QUOTES) ?></span>
            <?php if (!empty($cDay['is_lowest'])): ?>
              <span class="cp-lowest-pill">Lowest Fare</span>
            <?php endif; ?>
          <?php elseif (!empty($cDay['is_sold_out'])): ?>
            <span class="cp-card-price sold-out">Sold Out</span>
          <?php else: ?>
            <span class="cp-card-price no-flights">No Flights</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cp-carousel-arrow next" id="btn-carousel-next" aria-label="Next date range">&#10095;</button>
  </div>

  <!-- Filter & Sort Toolbar -->
  <div class="cp-filter-bar">
    <div class="cp-filter-group">
      <span class="cp-filter-label">⚡ Filter by:</span>
      <select class="cp-select-pill" id="filter-flight-time" aria-label="Filter by departure time">
        <option value="">All Flight Times</option>
        <option value="morning">Morning (00:00 - 11:59)</option>
        <option value="afternoon">Afternoon (12:00 - 17:59)</option>
        <option value="evening">Evening (18:00 - 23:59)</option>
      </select>

      <select class="cp-select-pill" id="filter-flight-stops" aria-label="Filter by stops">
        <option value="all">All Stops</option>
        <option value="Nonstop">Nonstop only</option>
        <option value="1 Stop">1 Stop</option>
      </select>

      <select class="cp-select-pill" id="filter-flight-price" aria-label="Filter by price">
        <option value="">All Prices</option>
        <option value="under_3000">Under ₱3,000</option>
        <option value="3000_6000">₱3,000 - ₱6,000</option>
        <option value="above_6000">Above ₱6,000</option>
      </select>
    </div>

    <div class="cp-filter-group sort-group">
      <span class="cp-filter-label">⇅ Sort by:</span>
      <select class="cp-select-pill" id="filter-flight-sort" aria-label="Sort flights">
        <option value="earliest">Earliest Departure</option>
        <option value="cheapest">Lowest Price</option>
        <option value="fastest">Shortest Duration</option>
      </select>
    </div>
  </div>

  <!-- Flight Cards List & Empty State Container -->
  <div class="cp-flights-list" id="flight-schedule-grid">
    <?php if (!empty($availableFlights)): 
      $selectedCardId = $firstAvailableFlight['id'] ?? ($availableFlights[0]['id'] ?? 0);
    ?>
      <?php foreach ($availableFlights as $fIdx => $flight): 
        $isSelected = ($flight['id'] === $selectedCardId && $flight['is_available']);
        $airlineLogo = '✈️';
        if (stripos($flight['airline'], 'Cebu') !== false) $airlineLogo = '🟡';
        elseif (stripos($flight['airline'], 'Philippine Airlines') !== false) $airlineLogo = '🔵';
        elseif (stripos($flight['airline'], 'AirAsia') !== false) $airlineLogo = '🔴';
      ?>
      <div class="cp-flight-card<?= $isSelected ? ' is-selected' : '' ?><?= !$flight['is_available'] ? ' is-sold-out' : '' ?>" 
           tabindex="<?= $flight['is_available'] ? '0' : '-1' ?>" role="button" 
           data-id="<?= $flight['id'] ?>"
           data-airline="<?= htmlspecialchars($flight['airline'], ENT_QUOTES) ?>"
           data-flight-no="<?= htmlspecialchars($flight['flight_number'], ENT_QUOTES) ?>"
           data-departure="<?= htmlspecialchars($flight['departure_time'], ENT_QUOTES) ?>"
           data-price="<?= (float) $flight['base_price'] ?>"
           data-available="<?= $flight['is_available'] ? '1' : '0' ?>"
           data-schedule="<?= htmlspecialchars($flight['airline'] . ' ' . $flight['flight_number'] . ' (' . $flight['departure_time'] . ' ' . $flight['origin_code'] . ' → ' . $flight['arrival_time'] . ' ' . $flight['dest_code'] . ')', ENT_QUOTES) ?>">
        
        <div class="cp-flight-airline">
          <div class="cp-airline-badge"><?= $airlineLogo ?></div>
          <div class="cp-airline-info">
            <strong class="cp-airline-name"><?= htmlspecialchars($flight['airline'], ENT_QUOTES) ?></strong>
            <span class="cp-flight-equipment">Flight <?= htmlspecialchars($flight['flight_number'], ENT_QUOTES) ?> &middot; <?= htmlspecialchars($flight['aircraft'], ENT_QUOTES) ?></span>
          </div>
        </div>

        <div class="cp-flight-timeline">
          <div class="cp-time-col">
            <span class="cp-time-text"><?= htmlspecialchars($flight['departure_time'], ENT_QUOTES) ?></span>
            <span class="cp-port-text"><?= htmlspecialchars($flight['origin_code'], ENT_QUOTES) ?></span>
          </div>

          <div class="cp-duration-col">
            <span class="cp-dur-label"><?= htmlspecialchars($flight['duration'], ENT_QUOTES) ?></span>
            <div class="cp-flight-line"></div>
            <span class="cp-badge-nonstop"><?= htmlspecialchars($flight['stops'], ENT_QUOTES) ?></span>
          </div>

          <div class="cp-time-col">
            <span class="cp-time-text"><?= htmlspecialchars($flight['arrival_time'], ENT_QUOTES) ?></span>
            <span class="cp-port-text"><?= htmlspecialchars($flight['dest_code'], ENT_QUOTES) ?></span>
          </div>
        </div>

        <div class="cp-flight-action">
          <?php if (!empty($flight['is_available'])): ?>
            <?php if (!empty($flight['is_lowest'])): ?>
              <span class="cp-flight-badge badge-lowest">★ Lowest Fare</span>
            <?php elseif ($flight['seats'] <= 5): ?>
              <span class="cp-flight-badge badge-warning">⚡ Only <?= $flight['seats'] ?> seats left!</span>
            <?php else: ?>
              <span class="cp-flight-badge badge-available">✓ Available</span>
            <?php endif; ?>
            <span class="cp-flight-fare"><?= htmlspecialchars($flight['price_formatted'], ENT_QUOTES) ?></span>
            <span class="cp-flight-fare-sub">Included with package</span>
            <button type="button" class="cp-btn-flight-select"><?= $isSelected ? 'Selected' : 'Select' ?></button>
          <?php else: ?>
            <span class="cp-flight-badge badge-soldout">✕ Sold Out</span>
            <span class="cp-flight-fare muted">Fully Booked</span>
            <span class="cp-flight-fare-sub">No seats remaining</span>
            <button type="button" class="cp-btn-flight-select is-disabled" disabled>Sold Out</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Cebu Pacific Style Empty State (Screenshot 4) -->
      <div class="cp-empty-state-card" id="cp-empty-state">
        <div class="cp-empty-plane-graphic">
          <svg width="120" height="120" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="100" cy="100" r="80" fill="#f0f9ff"/>
            <path d="M40 130 C60 110, 100 110, 120 130" stroke="#bae6fd" stroke-width="4" stroke-linecap="round"/>
            <path d="M90 70 C110 50, 150 50, 170 70" stroke="#bae6fd" stroke-width="4" stroke-linecap="round"/>
            <g transform="translate(45, 60) rotate(-5)">
              <path d="M15 45 L50 45 L75 20 L88 20 L72 45 L105 45 L115 32 L125 32 L120 48 L125 50 L120 52 L125 68 L115 68 L105 55 L72 55 L88 80 L75 80 L50 55 L15 55 C5 55 0 50 15 45 Z" fill="#38bdf8"/>
              <path d="M15 48 L120 48 L125 50 L120 52 L15 52 Z" fill="#ffd200"/>
              <circle cx="35" cy="50" r="2.5" fill="#ffffff"/>
              <circle cx="45" cy="50" r="2.5" fill="#ffffff"/>
              <circle cx="55" cy="50" r="2.5" fill="#ffffff"/>
            </g>
          </svg>
        </div>
        <h3 class="cp-empty-title">No flights available</h3>
        <p class="cp-empty-text">Flights for this day are either sold out or unavailable. Please choose another date or try editing your search details.</p>
        <button type="button" class="cp-btn-edit-search" id="btn-empty-change-date">Choose Another Date</button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Flight Operator Footnote (Cebu Pacific Style) -->
  <div class="cp-operator-footnote">
    <div class="cp-operator-list">
      <span>Flight operated by:</span>
      <span class="cp-operator-item"><span>✈</span> Cebu Pacific</span>
      <span class="cp-operator-item"><span>✈</span> Cebgo</span>
      <span class="cp-operator-item"><span>✈</span> Philippine Airlines</span>
      <span class="cp-operator-item"><span>✈</span> Philippines AirAsia</span>
    </div>
    <span>Fares are subject to government approval.</span>
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
          <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>" 
               class="slide-img<?= $imgIdx === 0 ? ' active' : '' ?>" 
               alt="<?= htmlspecialchars($hotel['name'], ENT_QUOTES) ?> photo <?= $imgIdx + 1 ?>"
               onerror="this.onerror=null; this.src='<?= ag_base_url('asset/logo.png') ?>';">
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
          <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>" 
               class="slide-img<?= $imgIdx === 0 ? ' active' : '' ?>" 
               alt="<?= htmlspecialchars($car['name'], ENT_QUOTES) ?> photo <?= $imgIdx + 1 ?>"
               onerror="this.onerror=null; this.src='<?= ag_base_url('asset/logo.png') ?>';">
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
  <div class="summary-items">
    <div class="summary-line" id="sum-flight-col">
      <span id="sum-flight-label">Flight (<?= htmlspecialchars($fareName, ENT_QUOTES) ?> &middot; <?= $adults ?> Adult<?= $adults > 1 ? 's' : '' ?>)</span>
      <span id="sum-flight" class="summary-price"><?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?></span>
    </div>
    <div class="summary-line" id="sum-hotel-col">
      <span id="sum-hotel-label">Hotel &middot; <?= $defaultNights ?> night(s)</span>
      <span id="sum-hotel" class="summary-price">—</span>
    </div>
    <div class="summary-line" id="sum-car-col">
      <span id="sum-car-label">Car rental &middot; <?= $defaultDays ?> day(s)</span>
      <span id="sum-car" class="summary-price">—</span>
    </div>
  </div>
  <div class="summary-action">
    <div class="summary-total">
      <span class="total-label">Estimated total</span>
      <span id="sum-total">—</span>
    </div>
    <button type="button" class="btn-proceed" id="btn-proceed">Proceed to booking</button>
  </div>
</div>

<!-- Hidden hand-off form: carries choices to checkout.php -->
<form id="checkout-form" action="<?= ag_base_url('checkout.php') ?>" method="GET" style="display:none">
  <input type="hidden" name="mode"           value="<?= htmlspecialchars($mode, ENT_QUOTES) ?>">
  <input type="hidden" name="adults"         value="<?= htmlspecialchars($adults, ENT_QUOTES) ?>">
  <input type="hidden" name="cabin"          value="<?= htmlspecialchars($cabin, ENT_QUOTES) ?>">
  <input type="hidden" name="city"           value="<?= htmlspecialchars($destination['label'], ENT_QUOTES) ?>">
  <input type="hidden" name="origin"         value="<?= htmlspecialchars($origin, ENT_QUOTES) ?>">
  <input type="hidden" name="departDate"     value="<?= htmlspecialchars($departDate, ENT_QUOTES) ?>">
  <input type="hidden" name="returnDate"     value="<?= htmlspecialchars($returnDate, ENT_QUOTES) ?>">
  <input type="hidden" name="fareName"       value="<?= htmlspecialchars($fareName, ENT_QUOTES) ?>">
  <input type="hidden" name="fareDesc"       value="<?= htmlspecialchars($fareDesc, ENT_QUOTES) ?>">
  <input type="hidden" name="price"          value="<?= htmlspecialchars($farePriceRaw, ENT_QUOTES) ?>">
  <input type="hidden" name="flightSchedule" value="<?= !empty($firstAvailableFlight) ? htmlspecialchars($firstAvailableFlight['airline'] . ' ' . $firstAvailableFlight['flight_number'] . ' (' . $firstAvailableFlight['departure_time'] . ' ' . $firstAvailableFlight['origin_code'] . ' → ' . $firstAvailableFlight['arrival_time'] . ' ' . $firstAvailableFlight['dest_code'] . ')', ENT_QUOTES) : (!empty($availableFlights) ? htmlspecialchars($availableFlights[0]['airline'] . ' ' . $availableFlights[0]['flight_number'], ENT_QUOTES) : 'Standard Flight') ?>">
  <input type="hidden" name="hotelName"      value="<?= htmlspecialchars($destination['hotels'][0]['name'] ?? '', ENT_QUOTES) ?>">
  <input type="hidden" name="hotelPrice"     value="<?= (float)($destination['hotels'][0]['price'] ?? 0) ?>">
  <input type="hidden" name="nights"         value="<?= $defaultNights ?>">
  <input type="hidden" name="carName"        value="<?= htmlspecialchars($destination['cars'][0]['name'] ?? '', ENT_QUOTES) ?>">
  <input type="hidden" name="carPrice"       value="<?= (float)($destination['cars'][0]['price'] ?? 0) ?>">
  <input type="hidden" name="days"           value="<?= $defaultDays ?>">
</form>

<script>
(function() {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
  const cabinMult     = <?= json_encode($cabinMult) ?>;
  let basePerPerson   = <?= json_encode(round($basePrice * $cabinMult)) ?>;
  const isHotelMode   = <?= json_encode($mode === 'hotels') ?>;
  const fareNameStr   = <?= json_encode($fareName) ?>;
  const currentCity   = <?= json_encode($destination['label']) ?>;
  const currentOrigin = <?= json_encode($origin) ?>;
  const baseUrl       = <?= json_encode(ag_base_url()) ?>;
  let currentDate     = <?= json_encode($departDate) ?>;
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
    if (priceDisp) {
      if (isHotelMode) {
        priceDisp.textContent = '₱0 (Flight skipped)';
      } else if (basePerPerson <= 0) {
        priceDisp.textContent = 'Unavailable';
      } else {
        priceDisp.textContent = peso(flightTotal);
      }
    }

    const sumFlightLabel = $('#sum-flight-label');
    if (sumFlightLabel) {
      if (isHotelMode) {
        sumFlightLabel.innerHTML = `Flight skipped (Hotel mode)`;
      } else if (basePerPerson <= 0) {
        sumFlightLabel.innerHTML = `Flight (${fareNameStr} &middot; ${currentAdults} Guest${currentAdults !== 1 ? 's' : ''})`;
      } else {
        sumFlightLabel.innerHTML = `Flight (${fareNameStr} &middot; ${currentAdults} Guest${currentAdults !== 1 ? 's' : ''})`;
      }
    }

    const sumFlight = $('#sum-flight');
    if (sumFlight) {
      if (isHotelMode) {
        sumFlight.textContent = '₱0';
      } else if (basePerPerson <= 0) {
        sumFlight.textContent = '—';
      } else {
        sumFlight.textContent = peso(flightTotal);
      }
    }

    const form = $('#checkout-form');
    if (form) {
      const hAdults = form.querySelector('[name="adults"]');
      if (hAdults) hAdults.value = currentAdults;
      const hPrice = form.querySelector('[name="price"]');
      if (hPrice) hPrice.value = isHotelMode ? '₱0' : (basePerPerson > 0 ? peso(flightTotal) : '₱0');
    }

    recalc();
  }

  $('#dest-guest-dec')?.addEventListener('click', () => updateTravelers(currentAdults - 1));
  $('#dest-guest-inc')?.addEventListener('click', () => updateTravelers(currentAdults + 1));

  // ==========================================================
  // CEBU PACIFIC-STYLE DATE CAROUSEL & FLIGHT ASYNC LOADER
  // ==========================================================
  const carouselContainer = $('#cp-carousel-cards-container');
  const flightGrid = $('#flight-schedule-grid');
  const timeFilter = $('#filter-flight-time');
  const stopsFilter = $('#filter-flight-stops');
  const priceFilter = $('#filter-flight-price');
  const sortFilter = $('#filter-flight-sort');

  function bindFlightCards() {
    $$('.cp-flight-card').forEach(card => {
      card.addEventListener('click', () => {
        // Do not allow selecting sold out flights
        if (card.dataset.available === '0') return;

        $$('.cp-flight-card').forEach(c => {
          c.classList.remove('is-selected');
          const btn = c.querySelector('.cp-btn-flight-select');
          if (btn && c.dataset.available !== '0') btn.textContent = 'Select';
        });
        card.classList.add('is-selected');
        const btn = card.querySelector('.cp-btn-flight-select');
        if (btn) btn.textContent = 'Selected';

        const sched = card.dataset.schedule;
        const cardPrice = parseFloat(card.dataset.price);
        const form = $('#checkout-form');
        if (form && sched) {
          form.querySelector('[name="flightSchedule"]').value = sched;
        }
        if (!isHotelMode && cardPrice && !isNaN(cardPrice)) {
          basePerPerson = Math.round(cardPrice * cabinMult);
          updateTravelers(currentAdults);
        }

        // Ensure proceed button is enabled
        $('#btn-proceed')?.removeAttribute('disabled');
        $('#btn-proceed')?.classList.remove('is-disabled');
        if ($('#btn-proceed')) $('#btn-proceed').textContent = 'Proceed to booking';
      });
      card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
      });
    });
  }
  bindFlightCards();

  async function loadFlightsForDate(dateStr) {
    currentDate = dateStr;
    const timeVal = timeFilter?.value || '';
    const stopsVal = stopsFilter?.value || 'all';
    const priceVal = priceFilter?.value || '';
    const sortVal = sortFilter?.value || 'earliest';

    // Highlight active filters
    timeFilter?.classList.toggle('is-active-filter', timeVal !== '');
    stopsFilter?.classList.toggle('is-active-filter', stopsVal !== 'all');
    priceFilter?.classList.toggle('is-active-filter', priceVal !== '');

    // Update URL query string without reloading page
    const url = new URL(window.location.href);
    url.searchParams.set('departDate', dateStr);
    url.searchParams.set('date', dateStr);
    window.history.replaceState({}, '', url.toString());

    // Update checkout form hidden date
    const form = $('#checkout-form');
    if (form) {
      form.querySelector('[name="departDate"]').value = dateStr;
    }

    try {
      const apiEndpoint = `${baseUrl}/api/flights.php?destination=${encodeURIComponent(currentCity)}&origin=${encodeURIComponent(currentOrigin)}&date=${encodeURIComponent(dateStr)}&time=${encodeURIComponent(timeVal)}&stops=${encodeURIComponent(stopsVal)}&price_range=${encodeURIComponent(priceVal)}&sort=${encodeURIComponent(sortVal)}`;
      const res = await fetch(apiEndpoint);
      const data = await res.json();

      if (!data.success) return;

      // 1. Render Carousel
      if (carouselContainer && data.carousel) {
        carouselContainer.innerHTML = data.carousel.map(c => {
          let priceHtml = '';
          if (c.has_flights) {
            priceHtml = `<span class="cp-card-price">${c.price_label}</span>`;
            if (c.is_lowest) {
              priceHtml += `<span class="cp-lowest-pill">Lowest Fare</span>`;
            }
          } else if (c.is_sold_out) {
            priceHtml = `<span class="cp-card-price sold-out">Sold Out</span>`;
          } else {
            priceHtml = `<span class="cp-card-price no-flights">No Flights</span>`;
          }

          return `
            <a href="javascript:void(0)" 
               class="cp-carousel-card${c.is_selected ? ' is-active' : ''}${c.is_sold_out ? ' is-sold-out-date' : ''}"
               data-date="${c.date}">
              <span class="cp-card-date">${c.label}</span>
              ${priceHtml}
            </a>
          `;
        }).join('');
        bindCarouselClicks();
      }

      // 2. Render Flight Cards or Empty State
      if (flightGrid) {
        if (data.flights && data.flights.length > 0) {
          const firstAvail = data.flights.find(f => f.is_available);

          flightGrid.innerHTML = data.flights.map((f) => {
            const isSel = (firstAvail && f.id === firstAvail.id);
            let logo = '✈️';
            if (f.airline.includes('Cebu')) logo = '🟡';
            else if (f.airline.includes('Philippine Airlines')) logo = '🔵';
            else if (f.airline.includes('AirAsia')) logo = '🔴';

            let badgeHtml = '';
            let actionBtnHtml = '';
            if (f.is_available) {
              if (f.is_lowest) {
                badgeHtml = '<span class="cp-flight-badge badge-lowest">★ Lowest Fare</span>';
              } else if (f.seats <= 5) {
                badgeHtml = `<span class="cp-flight-badge badge-warning">⚡ Only ${f.seats} seats left!</span>`;
              } else {
                badgeHtml = '<span class="cp-flight-badge badge-available">✓ Available</span>';
              }
              actionBtnHtml = `<button type="button" class="cp-btn-flight-select">${isSel ? 'Selected' : 'Select'}</button>`;
            } else {
              badgeHtml = '<span class="cp-flight-badge badge-soldout">✕ Sold Out</span>';
              actionBtnHtml = '<button type="button" class="cp-btn-flight-select is-disabled" disabled>Sold Out</button>';
            }

            return `
              <div class="cp-flight-card${isSel ? ' is-selected' : ''}${!f.is_available ? ' is-sold-out' : ''}" 
                   tabindex="${f.is_available ? '0' : '-1'}" role="button" 
                   data-id="${f.id}"
                   data-airline="${f.airline}"
                   data-flight-no="${f.flight_number}"
                   data-departure="${f.departure_time}"
                   data-price="${f.base_price}"
                   data-available="${f.is_available ? '1' : '0'}"
                   data-schedule="${f.airline} ${f.flight_number} (${f.departure_time} ${f.origin_code} → ${f.arrival_time} ${f.dest_code})">
                <div class="cp-flight-airline">
                  <div class="cp-airline-badge">${logo}</div>
                  <div class="cp-airline-info">
                    <strong class="cp-airline-name">${f.airline}</strong>
                    <span class="cp-flight-equipment">Flight ${f.flight_number} &middot; ${f.aircraft}</span>
                  </div>
                </div>
                <div class="cp-flight-timeline">
                  <div class="cp-time-col">
                    <span class="cp-time-text">${f.departure_time}</span>
                    <span class="cp-port-text">${f.origin_code}</span>
                  </div>
                  <div class="cp-duration-col">
                    <span class="cp-dur-label">${f.duration}</span>
                    <div class="cp-flight-line"></div>
                    <span class="cp-badge-nonstop">${f.stops}</span>
                  </div>
                  <div class="cp-time-col">
                    <span class="cp-time-text">${f.arrival_time}</span>
                    <span class="cp-port-text">${f.dest_code}</span>
                  </div>
                </div>
                <div class="cp-flight-action">
                  ${badgeHtml}
                  <span class="cp-flight-fare${!f.is_available ? ' muted' : ''}">${f.is_available ? f.price_formatted : 'Fully Booked'}</span>
                  <span class="cp-flight-fare-sub">${f.is_available ? 'Included with package' : 'No seats remaining'}</span>
                  ${actionBtnHtml}
                </div>
              </div>
            `;
          }).join('');
          bindFlightCards();

          if (firstAvail) {
            if (form) {
              form.querySelector('[name="flightSchedule"]').value = `${firstAvail.airline} ${firstAvail.flight_number} (${firstAvail.departure_time} ${firstAvail.origin_code} → ${firstAvail.arrival_time} ${firstAvail.dest_code})`;
            }
            if (!isHotelMode && firstAvail.base_price) {
              basePerPerson = Math.round(parseFloat(firstAvail.base_price) * cabinMult);
              updateTravelers(currentAdults);
            }
            $('#btn-proceed')?.removeAttribute('disabled');
            $('#btn-proceed')?.classList.remove('is-disabled');
            if ($('#btn-proceed')) $('#btn-proceed').textContent = 'Proceed to booking';
          } else {
            // All flights sold out for this day
            if (form) form.querySelector('[name="flightSchedule"]').value = 'Sold Out';
            if (!isHotelMode) {
              basePerPerson = 0;
              if ($('#sum-flight-label')) $('#sum-flight-label').innerHTML = `Flight (Sold Out &middot; ${currentAdults} Guest${currentAdults !== 1 ? 's' : ''})`;
              if ($('#sum-flight')) $('#sum-flight').textContent = 'Sold Out';
              if ($('#dest-fare-chip-price-disp')) $('#dest-fare-chip-price-disp').textContent = 'Sold Out';
            }
            $('#btn-proceed')?.setAttribute('disabled', 'disabled');
            $('#btn-proceed')?.classList.add('is-disabled');
            if ($('#btn-proceed')) $('#btn-proceed').textContent = 'Flights Sold Out — Pick Another Date';
            recalc();
          }
        } else {
          // Empty State (No flights on this day)
          flightGrid.innerHTML = `
            <div class="cp-empty-state-card" id="cp-empty-state">
              <div class="cp-empty-plane-graphic">
                <svg width="120" height="120" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="100" cy="100" r="80" fill="#f0f9ff"/>
                  <path d="M40 130 C60 110, 100 110, 120 130" stroke="#bae6fd" stroke-width="4" stroke-linecap="round"/>
                  <path d="M90 70 C110 50, 150 50, 170 70" stroke="#bae6fd" stroke-width="4" stroke-linecap="round"/>
                  <g transform="translate(45, 60) rotate(-5)">
                    <path d="M15 45 L50 45 L75 20 L88 20 L72 45 L105 45 L115 32 L125 32 L120 48 L125 50 L120 52 L125 68 L115 68 L105 55 L72 55 L88 80 L75 80 L50 55 L15 55 C5 55 0 50 15 45 Z" fill="#38bdf8"/>
                    <path d="M15 48 L120 48 L125 50 L120 52 L15 52 Z" fill="#ffd200"/>
                    <circle cx="35" cy="50" r="2.5" fill="#ffffff"/>
                    <circle cx="45" cy="50" r="2.5" fill="#ffffff"/>
                    <circle cx="55" cy="50" r="2.5" fill="#ffffff"/>
                  </g>
                </svg>
              </div>
              <h3 class="cp-empty-title">No flights available</h3>
              <p class="cp-empty-text">Flights for this day are either sold out or unavailable. Please choose another date or try editing your search details.</p>
              <button type="button" class="cp-btn-edit-search" id="btn-empty-change-date">Choose Another Date</button>
            </div>
          `;
          $('#btn-empty-change-date')?.addEventListener('click', () => {
            const nextAvail = $$('.cp-carousel-card').find(c => !c.querySelector('.no-flights') && !c.querySelector('.sold-out'));
            if (nextAvail) {
              nextAvail.click();
            } else {
              window.location.href = `${baseUrl}/index.php#booking`;
            }
          });

          if (!isHotelMode) {
            basePerPerson = 0;
            if ($('#sum-flight-label')) $('#sum-flight-label').innerHTML = `Flight (No flights available)`;
            if ($('#sum-flight')) $('#sum-flight').textContent = 'Unavailable';
            if ($('#dest-fare-chip-price-disp')) $('#dest-fare-chip-price-disp').textContent = 'No Flights';
          }
          $('#btn-proceed')?.setAttribute('disabled', 'disabled');
          $('#btn-proceed')?.classList.add('is-disabled');
          if ($('#btn-proceed')) $('#btn-proceed').textContent = 'No Flights Available — Pick Another Date';
          recalc();
        }
      }
    } catch (err) {
      console.error('Error fetching flights:', err);
    }
  }

  function bindCarouselClicks() {
    $$('.cp-carousel-card').forEach(card => {
      card.addEventListener('click', () => {
        const d = card.dataset.date;
        if (d) loadFlightsForDate(d);
      });
    });
  }
  bindCarouselClicks();

  $('#btn-carousel-prev')?.addEventListener('click', () => {
    const cur = new Date(currentDate);
    cur.setDate(cur.getDate() - 3);
    const dStr = cur.toISOString().split('T')[0];
    loadFlightsForDate(dStr);
  });

  $('#btn-carousel-next')?.addEventListener('click', () => {
    const cur = new Date(currentDate);
    cur.setDate(cur.getDate() + 3);
    const dStr = cur.toISOString().split('T')[0];
    loadFlightsForDate(dStr);
  });

  [timeFilter, stopsFilter, priceFilter, sortFilter].forEach(el => {
    el?.addEventListener('change', () => loadFlightsForDate(currentDate));
  });

  $('#btn-empty-change-date')?.addEventListener('click', () => {
    const nextAvail = $$('.cp-carousel-card').find(c => !c.querySelector('.no-flights') && !c.querySelector('.sold-out'));
    if (nextAvail) {
      nextAvail.click();
    } else {
      window.location.href = `${baseUrl}/index.php#booking`;
    }
  });

  // Hotel and Car selection handlers
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

    if (basePerPerson > 0 || isHotelMode) {
      if ($('#sum-total')) $('#sum-total').textContent = peso(total);
    } else {
      if ($('#sum-total')) $('#sum-total').textContent = total > 0 ? (peso(total) + ' (Flight skipped)') : '—';
    }
  }

  recalc();

  $('#btn-proceed')?.addEventListener('click', () => {
    const form = $('#checkout-form');
    if (!form) return;

    const schedCard = $('.cp-flight-card.is-selected');
    const hotelCard = $('#hotel-grid .option-card.is-selected');
    const carCard   = $('#car-grid .option-card.is-selected');

    if (!isHotelMode && (!schedCard || schedCard.dataset.available === '0')) {
      alert('Please select an available flight schedule before proceeding to booking.');
      return;
    }

    if (schedCard) {
      form.querySelector('[name="flightSchedule"]').value = schedCard.dataset.schedule || 'Standard Schedule';
    }
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

  lightboxClose?.addEventListener('click', () => lightbox.classList.remove('is-active'));
  lightbox?.addEventListener('click', (e) => { if(e.target === lightbox) lightbox.classList.remove('is-active'); });

  lightboxPrev?.addEventListener('click', () => {
    currentLightboxIndex = (currentLightboxIndex - 1 + currentLightboxImages.length) % currentLightboxImages.length;
    lightboxImg.src = currentLightboxImages[currentLightboxIndex];
  });

  lightboxNext?.addEventListener('click', () => {
    currentLightboxIndex = (currentLightboxIndex + 1) % currentLightboxImages.length;
    lightboxImg.src = currentLightboxImages[currentLightboxIndex];
  });

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
