<?php
// ============================================================
// AeroGlide — index.php
// Public homepage: replicating the modern AeroGlide design mockup.
// ============================================================
 
session_start();
 
$isLoggedIn = isset($_SESSION['user_id']);
$username   = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : '';
 
/**
 * Resolve image assets by KEYWORD instead of exact filename.
 */
function asset_find(array $keywords, ?array $fallbackKeywords = ['coron']): string
{
    static $files = null;
    $folders = ['asset', 'assets'];
 
    if ($files === null) {
        $files = [];
        foreach ($folders as $folder) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
                if (!is_file($path)) {
                    continue;
                }
                $base = basename($path);
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', pathinfo($base, PATHINFO_FILENAME)));
                $files[] = ['folder' => $folder, 'name' => $base, 'norm' => ' ' . $norm . ' '];
            }
        }
    }
 
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
        return $found['folder'] . '/' . rawurlencode($found['name']);
    }
 
    if ($fallbackKeywords && ($found = $match($fallbackKeywords))) {
        return $found['folder'] . '/' . rawurlencode($found['name']);
    }
 
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs><rect width="900" height="600" fill="url(#g)"/><circle cx="690" cy="145" r="70" fill="#fff" opacity=".45"/><path d="M0 430 C170 360 250 470 420 405 C590 340 700 430 900 365 V600 H0Z" fill="#fff" opacity=".48"/></svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AeroGlide — Book Smarter, Travel Further</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="aeroglide-page">
 
<!-- Atmospheric floating background clouds -->

<!-- ============ NAVBAR ============ -->
<nav class="main-nav">
  <div class="nav-container">
    <a class="nav-brand" href="index.php" aria-label="AeroGlide Home">
      <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="brand-logo-img">
      <span class="brand-text">AeroGlide</span>
    </a>
 
    <div class="nav-menu">
      <a href="index.php" class="nav-item is-active">Home</a>
      <a href="#booking" class="nav-item">Flights</a>
      <a href="#deals" class="nav-item">Package</a>
      <a href="#about" class="nav-item">Support</a>
    </div>
 
    <div class="nav-right">
      <form class="nav-search-bar" id="nav-search-form" role="search">
        <input type="text" id="nav-search-input" placeholder="SEARCH" aria-label="Search destinations">
        <button type="submit" class="search-submit-btn" aria-label="Submit search">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
      </form>
 
      <?php if ($isLoggedIn): ?>
        <span class="nav-user-greeting">Hi, <?= htmlspecialchars($username) ?></span>
        <a class="nav-auth-btn" href="logout.php" title="Logout">
          <span>Logout</span>
        </a>
      <?php else: ?>
        <a class="nav-auth-btn" href="login.php">
          <svg class="auth-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Sign up</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
 
<!-- ============ HERO SECTION ============ -->
<header class="hero-section">
  <div class="hero-container">
    <div class="hero-content">
      <h1 class="hero-title">
        BOOK SMARTER,<br>
        TRAVEL FURTHER
      </h1>
      <p class="hero-subtitle">
        Your next destination<br>
        is just a click away
      </p>
 
      <!-- Preview Slider -->
      <div class="hero-slider-wrap">
        <div class="hero-slides" id="hero-slider-track">
          <div class="hero-slide is-active" data-name="Vigan">
            <img src="<?= htmlspecialchars(asset_find(['vigan']), ENT_QUOTES) ?>" alt="Vigan, Ilocos Sur, Philippines">
          </div>
          <div class="hero-slide" data-name="The Ruins">
            <img src="<?= htmlspecialchars(asset_find(['ruins']), ENT_QUOTES) ?>" alt="The Ruins, Bacolod City, Philippines">
          </div>
          <div class="hero-slide" data-name="Hundred Islands">
            <img src="<?= htmlspecialchars(asset_find(['pangasinan']), ENT_QUOTES) ?>" alt="Pangasinan's Hidden Gem, Hundred Islands, Philippines">
          </div>
          <div class="hero-slide" data-name="Kambugahay Falls">
            <img src="<?= htmlspecialchars(asset_find(['kambugahay']), ENT_QUOTES) ?>" alt="Kambugahay Falls, Siquijor, Philippines">
          </div>
          <div class="hero-slide" data-name="Maria Cristina Falls">
            <img src="<?= htmlspecialchars(asset_find(['maria', 'cristina']), ENT_QUOTES) ?>" alt="Maria Cristina Falls, Philippines">
          </div>
          <div class="hero-slide" data-name="Banaue Rice Terraces">
            <img src="<?= htmlspecialchars(asset_find(['banaue']), ENT_QUOTES) ?>" alt="Banaue Rice Terraces, Ifugao, Philippines">
          </div>
          <div class="hero-slide" data-name="Aurora">
            <img src="<?= htmlspecialchars(asset_find(['aurora']), ENT_QUOTES) ?>" alt="Aurora, Philippines">
          </div>
        </div>
 
        <!-- Slider controls: < 1/07 > -->
        <div class="hero-slider-nav">
          <button type="button" class="slider-arrow" id="slide-prev" aria-label="Previous destination">‹</button>
          <span class="slider-index" id="slide-index-display">1/07</span>
          <button type="button" class="slider-arrow" id="slide-next" aria-label="Next destination">›</button>
        </div>
 
        <a href="#booking" class="btn-book-now">
          Book a trip now
        </a>
      </div>
    </div>
 
    <!-- Airplane Visual -->
    <div class="hero-plane-visual">
      <div class="plane-float-wrapper">
        <?php
          $planeSrc = asset_find(['airplane'], null);
          $planeIsPlaceholder = str_starts_with($planeSrc, 'data:image/svg+xml');
        ?>
        <?php if (!$planeIsPlaceholder): ?>
          <img src="<?= htmlspecialchars($planeSrc, ENT_QUOTES) ?>" alt="AeroGlide Airplane soaring through the clouds" class="hero-plane-img">
        <?php else: ?>
          <svg class="hero-plane-fallback" viewBox="0 0 640 320" role="img" aria-label="AeroGlide airplane illustration">
            <g fill="currentColor">
              <path d="M594 132c-7-11-22-17-38-15l-151 18-93-91c-8-8-19-12-30-10l-31 5 59 112-129 17-58-52-25 4 31 72-15 39 25-4 29-32 137-18-22 126 31-5 71-134 155-20c32-4 54-17 54-32z"/>
            </g>
          </svg>
        <?php endif; ?>
      </div>
      <div class="plane-clouds-anchor" aria-hidden="true"></div>
    </div>
  </div>
</header>
 
<main>
 
<!-- ============ INTERACTIVE FLIGHTS BOOKING CARD ============ -->
<section class="booking-section" id="booking" aria-label="Flight booking panel">
  <div class="booking-container">
    <div class="booking-card">
      <!-- Top Pills Row -->
      <div class="booking-top-bar">
        <div class="pill-dropdown-group">
          <!-- Flights dropdown -->
          <div class="dropdown-wrapper" id="dropdown-category">
            <button type="button" class="pill-btn is-active" id="btn-category" aria-haspopup="true" aria-expanded="false">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
              <span id="label-category">Flights</span>
              <span class="arrow-down">▼</span>
            </button>
            <div class="dropdown-menu" id="menu-category" hidden>
              <button type="button" class="dropdown-option is-selected" data-val="Flights">Flights</button>
              <button type="button" class="dropdown-option" data-val="Hotels">Hotels</button>
              <button type="button" class="dropdown-option" data-val="Packages">Packages</button>
            </div>
          </div>
 
          <!-- Adult dropdown -->
          <div class="dropdown-wrapper" id="dropdown-passengers">
            <button type="button" class="pill-btn" id="btn-passengers" aria-haspopup="true" aria-expanded="false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span id="label-passengers">Adult</span>
              <span class="arrow-down">▼</span>
            </button>
            <div class="dropdown-menu" id="menu-passengers" hidden>
              <button type="button" class="dropdown-option is-selected" data-val="1 Adult">1 Adult</button>
              <button type="button" class="dropdown-option" data-val="2 Adults">2 Adults</button>
              <button type="button" class="dropdown-option" data-val="3 Adults">3 Adults</button>
              <button type="button" class="dropdown-option" data-val="Family (2+2)">Family (2+2)</button>
            </div>
          </div>
 
          <!-- Economy dropdown -->
          <div class="dropdown-wrapper" id="dropdown-class">
            <button type="button" class="pill-btn" id="btn-class" aria-haspopup="true" aria-expanded="false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16"/><path d="M10 4v16"/></svg>
              <span id="label-class">Economy</span>
              <span class="arrow-down">▼</span>
            </button>
            <div class="dropdown-menu" id="menu-class" hidden>
              <button type="button" class="dropdown-option is-selected" data-val="Economy">Economy</button>
              <button type="button" class="dropdown-option" data-val="Premium Economy">Premium Economy</button>
              <button type="button" class="dropdown-option" data-val="Business">Business</button>
              <button type="button" class="dropdown-option" data-val="First Class">First Class</button>
            </div>
          </div>
        </div>
 
        <!-- Trip Type radios -->
        <div class="trip-radio-group" role="radiogroup" aria-label="Trip direction">
          <label class="radio-label">
            <input type="radio" name="trip_mode" value="two_way" checked>
            <span class="radio-circle"></span>
            <span>Two way</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="trip_mode" value="one_way">
            <span class="radio-circle"></span>
            <span>One way</span>
          </label>
        </div>
      </div>
 
      <!-- Main Search Inputs Bar (White Capsule) -->
      <form class="search-capsule" id="flight-search-form">
        <!-- Field 1: Destinations -->
        <div class="search-segment segment-dest">
          <label class="segment-label" for="input-destination">
            Destinations <span class="label-arrow">↓</span>
          </label>
          <input type="text" id="input-destination" class="segment-input" placeholder="Where are you going?" autocomplete="off" required>
          <div class="dest-quick-dropdown" id="dest-quick-menu" hidden>
            <button type="button" class="quick-dest-item" data-city="Boracay, Aklan">Boracay, Aklan</button>
            <button type="button" class="quick-dest-item" data-city="Coron, Palawan">Coron, Palawan</button>
            <button type="button" class="quick-dest-item" data-city="Siargao, Surigao del Norte">Siargao, Surigao del Norte</button>
            <button type="button" class="quick-dest-item" data-city="Taal Volcano, Batangas">Taal Volcano, Batangas</button>
            <button type="button" class="quick-dest-item" data-city="Chocolate Hills, Bohol">Chocolate Hills, Bohol</button>
            <button type="button" class="quick-dest-item" data-city="Mayon Volcano, Albay">Mayon Volcano, Albay</button>
            <button type="button" class="quick-dest-item" data-city="Malapascua Island, Cebu">Malapascua Island, Cebu</button>
            <button type="button" class="quick-dest-item" data-city="Bukidnon">Bukidnon</button>
          </div>
        </div>
 
        <div class="segment-divider" aria-hidden="true"></div>
 
        <!-- Field 2: Check In -->
        <div class="search-segment segment-date">
          <label class="segment-label" for="input-checkin">
            Check In
          </label>
          <div class="date-input-wrap">
            <input type="text" id="input-checkin" class="segment-input date-text" placeholder="Choose dates" readonly>
            <input type="date" id="native-checkin" class="native-date-picker" aria-hidden="true">
            <span class="calendar-icon" aria-hidden="true">📅</span>
          </div>
        </div>
 
        <div class="segment-divider" aria-hidden="true"></div>
 
        <!-- Field 3: Check Out -->
        <div class="search-segment segment-date" id="wrap-checkout">
          <label class="segment-label" for="input-checkout">
            Check Out
          </label>
          <div class="date-input-wrap">
            <input type="text" id="input-checkout" class="segment-input date-text" placeholder="Choose dates" readonly>
            <input type="date" id="native-checkout" class="native-date-picker" aria-hidden="true">
            <span class="calendar-icon" aria-hidden="true">📅</span>
          </div>
        </div>
 
        <div class="segment-divider" aria-hidden="true"></div>
 
        <!-- Field 4: Guest -->
        <div class="search-segment segment-guest">
          <label class="segment-label" id="label-guests-trigger">
            Guest <span class="label-arrow">↓</span>
          </label>
          <div class="guest-trigger-val" id="btn-guests-modal" role="button" tabindex="0" aria-label="Select guests">
            <span id="guest-display-text">Add Guests</span>
          </div>
          <!-- Guest Counter Popover -->
          <div class="guest-popover" id="guest-popover-box" hidden>
            <div class="guest-row">
              <span class="guest-type">Adults (12+ yrs)</span>
              <div class="counter-ctrl">
                <button type="button" class="cnt-btn" id="adult-dec">−</button>
                <span class="cnt-val" id="adult-count">1</span>
                <button type="button" class="cnt-btn" id="adult-inc">+</button>
              </div>
            </div>
            <div class="guest-row">
              <span class="guest-type">Children (2-11 yrs)</span>
              <div class="counter-ctrl">
                <button type="button" class="cnt-btn" id="child-dec">−</button>
                <span class="cnt-val" id="child-count">0</span>
                <button type="button" class="cnt-btn" id="child-inc">+</button>
              </div>
            </div>
            <button type="button" class="btn-guest-done" id="btn-guest-done">Done</button>
          </div>
        </div>
 
        <!-- Search Action Button -->
        <button type="submit" class="btn-search-flights" aria-label="Search available flights">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
      </form>
    </div>
  </div>
</section>
 
<!-- ============ EXCLUSIVE AEROGLIDE DEALS ============ -->
<section class="deals-section" id="deals">
  <div class="section-header">
    <h2 class="section-main-title">Exclusive AeroGlide Deals</h2>
    <p class="section-tagline">Premium Travel Experiences at Unbeatable Rates.</p>
  </div>
 
  <div class="deals-grid">
    <!-- Deal 1: Boracay, Aklan -->
    <article class="deal-card" data-city="Boracay, Aklan">
      <div class="deal-media">
        <img src="<?= htmlspecialchars(asset_find(['boracay']), ENT_QUOTES) ?>" alt="Boracay White Beach, Aklan, Philippines">
      </div>
      <div class="deal-content">
        <h3 class="deal-destination-title">Boracay, Aklan</h3>
 
        <div class="fare-options-list" role="radiogroup" aria-label="Fare options for Boracay">
          <label class="fare-option">
            <input type="radio" name="fare_boracay" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱3,499</span>
          </label>
 
          <label class="fare-option is-active">
            <input type="radio" name="fare_boracay" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱4,999</span>
          </label>
 
          <label class="fare-option">
            <input type="radio" name="fare_boracay" value="budget">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Budget Employer</span>
              <span class="fare-tier-desc">Flight + baggage + meal</span>
            </div>
            <span class="fare-tier-price">₱6,499</span>
          </label>
        </div>
 
        <button type="button" class="btn-learn-more" data-target="Boracay, Aklan">
          Learn more
        </button>
      </div>
    </article>
 
    <!-- Deal 2: Coron, Palawan -->
    <article class="deal-card" data-city="Coron, Palawan">
      <div class="deal-media">
        <img src="<?= htmlspecialchars(asset_find(['coron']), ENT_QUOTES) ?>" alt="Coron, Palawan, Philippines">
      </div>
      <div class="deal-content">
        <h3 class="deal-destination-title">Coron, Palawan</h3>
 
        <div class="fare-options-list" role="radiogroup" aria-label="Fare options for Coron">
          <label class="fare-option">
            <input type="radio" name="fare_coron" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱4,299</span>
          </label>
 
          <label class="fare-option is-active">
            <input type="radio" name="fare_coron" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱5,799</span>
          </label>
 
          <label class="fare-option">
            <input type="radio" name="fare_coron" value="budget">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Budget Employer</span>
              <span class="fare-tier-desc">Flight + baggage + meal</span>
            </div>
            <span class="fare-tier-price">₱7,299</span>
          </label>
        </div>
 
        <button type="button" class="btn-learn-more" data-target="Coron, Palawan">
          Learn more
        </button>
      </div>
    </article>
 
    <!-- Deal 3: Siargao, Surigao del Norte -->
    <article class="deal-card" data-city="Siargao, Surigao del Norte">
      <div class="deal-media">
        <img src="<?= htmlspecialchars(asset_find(['siargao']), ENT_QUOTES) ?>" alt="Siargao Island, Surigao del Norte, Philippines">
      </div>
      <div class="deal-content">
        <h3 class="deal-destination-title">Siargao, Surigao del Norte</h3>
 
        <div class="fare-options-list" role="radiogroup" aria-label="Fare options for Siargao">
          <label class="fare-option">
            <input type="radio" name="fare_siargao" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱3,999</span>
          </label>
 
          <label class="fare-option is-active">
            <input type="radio" name="fare_siargao" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱5,499</span>
          </label>
 
          <label class="fare-option">
            <input type="radio" name="fare_siargao" value="budget">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Budget Employer</span>
              <span class="fare-tier-desc">Flight + baggage + meal</span>
            </div>
            <span class="fare-tier-price">₱6,999</span>
          </label>
        </div>
 
        <button type="button" class="btn-learn-more" data-target="Siargao, Surigao del Norte">
          Learn more
        </button>
      </div>
    </article>
  </div>
</section>
 
<!-- ============ POPULAR DESTINATIONS ============ -->
<section class="popular-section" id="destinations">
  <div class="section-header">
    <h2 class="section-main-title">Popular Destinations</h2>
  </div>
 
  <div class="popular-capsule-grid">
    <!-- 1: Albay (Mayon Volcano) -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Mayon Volcano, Albay" aria-label="Select Mayon Volcano, Albay">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['albay']), ENT_QUOTES) ?>" alt="Mayon Volcano and Cagsawa Ruins, Albay, Philippines">
      
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,499*</strong>
          <span class="hover-destination">Mayon Volcano</span>
          <span class="hover-book-btn">Book now</span>
        </div></div>
      <div class="capsule-info">
        <h3 class="capsule-city">Albay</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
 
    <!-- 2: Bukidnon -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Bukidnon" aria-label="Select Bukidnon">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['bukidnon']), ENT_QUOTES) ?>" alt="Communal Ranch, Bukidnon, Philippines">
      
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,799*</strong>
          <span class="hover-destination">Bukidnon</span>
          <span class="hover-book-btn">Book now</span>
        </div></div>
      <div class="capsule-info">
        <h3 class="capsule-city">Bukidnon</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
 
    <!-- 3: Chocolate Hills, Bohol -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Chocolate Hills, Bohol" aria-label="Select Chocolate Hills, Bohol">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['chocolate']), ENT_QUOTES) ?>" alt="Chocolate Hills, Carmen, Bohol, Philippines">
      
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,299*</strong>
          <span class="hover-destination">Chocolate Hills</span>
          <span class="hover-book-btn">Book now</span>
        </div></div>
      <div class="capsule-info">
        <h3 class="capsule-city">Chocolate Hills</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
 
    <!-- 4: Malapascua Island, Cebu -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Malapascua Island, Cebu" aria-label="Select Malapascua Island, Cebu">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['malapascua']), ENT_QUOTES) ?>" alt="Malapascua Island, Cebu, Philippines">
      
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱3,199*</strong>
          <span class="hover-destination">Malapascua Island</span>
          <span class="hover-book-btn">Book now</span>
        </div></div>
      <div class="capsule-info">
        <h3 class="capsule-city">Malapascua Island</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
 
    <!-- 5: Taal Volcano, Batangas -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Taal Volcano, Batangas" aria-label="Select Taal Volcano, Batangas">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['taal']), ENT_QUOTES) ?>" alt="Taal Volcano, Batangas, Philippines">
      
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,199*</strong>
          <span class="hover-destination">Taal Volcano</span>
          <span class="hover-book-btn">Book now</span>
        </div></div>
      <div class="capsule-info">
        <h3 class="capsule-city">Taal Volcano</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ ABOUT US ============ -->
<section class="about-section" id="about">
  <div class="about-container">
    <div class="about-text-column">
      <h2 class="about-title">About us</h2>
      <p class="about-paragraph">
        AeroGlide is a modern airline created to make air travel simple, affordable, and enjoyable for everyone. We connect travelers to exciting destinations around the world while providing convenient booking options, competitive fares, and a smooth travel experience from start to finish.
      </p>
      <p class="about-paragraph">
        We believe that traveling should be more than simply getting from one place to another. It should be about discovering new places, experiencing different cultures, and creating unforgettable memories. That is why AeroGlide is committed to providing reliable service while keeping travel accessible and budget-friendly.
      </p>
    </div>
 
    <div class="about-image-column">
      <div class="about-arch-frame">
        <img src="<?= htmlspecialchars(asset_find(['about', 'plane']), ENT_QUOTES) ?>" alt="AeroGlide airliner taking off against glowing sunset sky" class="about-arch-img">
      </div>
    </div>
  </div>
</section>
 
<!-- ============ AEROGLIDE CLIENTS ============ -->
<section class="clients-section" id="reviews">
  <div class="section-header">
    <h2 class="section-main-title">AeroGlide Clients</h2>
  </div>
 
  <div class="clients-cards-grid">
    <!-- Testimonial 1 -->
    <div class="client-card">
      <div class="client-stars" aria-label="5 stars rating">★★★★★</div>
      <blockquote class="client-quote">
        “Booking my trip with AeroGlide was surprisingly easy. The website is clean, fast, and the prices were very affordable. I’ll definitely use AeroGlide again!”
      </blockquote>
      <div class="client-author">
        <img src="<?= htmlspecialchars(asset_find(['avatar', 'noblesam']), ENT_QUOTES) ?>" alt="Noblesam Martizano" class="client-avatar">
        <span class="client-name">Noblesam Martizano</span>
      </div>
    </div>
 
    <!-- Testimonial 2 -->
    <div class="client-card">
      <div class="client-stars" aria-label="5 stars rating">★★★★★</div>
      <blockquote class="client-quote">
        “I really liked how simple it was to compare destinations and travel packages. The whole booking experience felt smooth and hassle-free.”
      </blockquote>
      <div class="client-author">
        <img src="<?= htmlspecialchars(asset_find(['avatar', 'bianca']), ENT_QUOTES) ?>" alt="Bianca Briel Cruz" class="client-avatar">
        <span class="client-name">Bianca Briel Cruz</span>
      </div>
    </div>
 
    <!-- Testimonial 3 -->
    <div class="client-card">
      <div class="client-stars" aria-label="5 stars rating">★★★★★</div>
      <blockquote class="client-quote">
        “AeroGlide made planning my vacation much easier. The deals were great, and I loved how straightforward the website was to use.”
      </blockquote>
      <div class="client-author">
        <img src="<?= htmlspecialchars(asset_find(['avatar', 'dane']), ENT_QUOTES) ?>" alt="Dane Nicolle" class="client-avatar">
        <span class="client-name">Dane Nicolle</span>
      </div>
    </div>
  </div>
</section>
 
</main>
 
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
          <a href="#" class="social-circle-btn" aria-label="Follow us on Instagram">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          </a>
          <a href="#" class="social-circle-btn" aria-label="Follow us on YouTube">▶</a>
        </div>
      </div>
    </div>
 
    <div class="footer-links-grid">
      <!-- Col 1 -->
      <div class="footer-col">
        <h4 class="footer-col-title">Travel</h4>
        <ul class="footer-nav-list">
          <li><a href="#destinations">Asia</a></li>
          <li><a href="#destinations">Europe</a></li>
          <li><a href="#destinations">Australia</a></li>
          <li><a href="#destinations">America</a></li>
        </ul>
      </div>
 
      <!-- Col 2 -->
      <div class="footer-col">
        <h4 class="footer-col-title">Company</h4>
        <ul class="footer-nav-list">
          <li><a href="#about">About Us</a></li>
          <li><a href="#deals">Packages</a></li>
          <li><a href="#about">Contact Us</a></li>
        </ul>
      </div>
 
      <!-- Col 3 -->
      <div class="footer-col">
        <h4 class="footer-col-title">Extra Links</h4>
        <ul class="footer-nav-list">
          <li><a href="#about">Customer Support</a></li>
          <li><a href="#about">Terms and Conditions</a></li>
          <li><a href="#about">Privacy Policy</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>
 
<!-- Interactive UI Scripts -->
<script>
(function() {
  'use strict';
 
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
 
  /* ---------- IMAGE FALLBACK HANDLING ----------
     Replace failed images with a real data-URI placeholder instead of
     leaving the browser's broken-image icon / ALT text visible. */
  const fallbackSvg = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600">' +
    '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#dceefe"/><stop offset="1" stop-color="#bcdcf5"/></linearGradient></defs>' +
    '<rect width="900" height="600" fill="url(#g)"/><circle cx="690" cy="145" r="70" fill="#fff" opacity=".45"/>' +
    '<path d="M0 430 C170 360 250 470 420 405 C590 340 700 430 900 365 V600 H0Z" fill="#fff" opacity=".48"/></svg>'
  );
 
  document.querySelectorAll('img').forEach(img => {
    const applyFallback = () => {
      if (img.dataset.fallbackApplied === '1') return;
      console.warn('[AeroGlide] Missing image asset:', img.getAttribute('src'));
      img.dataset.fallbackApplied = '1';
      img.classList.add('img-fallback');
      img.alt = '';
      img.src = fallbackSvg;
    };
 
    img.addEventListener('error', applyFallback, { once: true });
    if (img.complete && img.naturalWidth === 0) applyFallback();
  });
 
  /* ---------- Toast notification ---------- */
  function showToast(msg) {
    let t = $('#global-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'global-toast';
      t.className = 'toast-notification';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('is-visible');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('is-visible'), 3200);
  }
 
  /* ---------- HERO SLIDER (NO AUTO-SCROLL TO TOP!) ---------- */
  const slides = $$('.hero-slide');
  const indexDisplay = $('#slide-index-display');
  let currentSlide = 0;
  let sliderTimer = null;
 
  function setSlide(n) {
    currentSlide = (n + slides.length) % slides.length;
    slides.forEach((s, idx) => s.classList.toggle('is-active', idx === currentSlide));
    if (indexDisplay) {
      indexDisplay.textContent = `${currentSlide + 1}/${String(slides.length).padStart(2, '0')}`;
    }
    // Note: NEVER calling window.scrollTo or scrollIntoView here to ensure the user scrolls freely!
  }
 
  function startSlideShow() {
    clearInterval(sliderTimer);
    sliderTimer = setInterval(() => setSlide(currentSlide + 1), 4500);
  }
 
  $('#slide-prev')?.addEventListener('click', () => { setSlide(currentSlide - 1); startSlideShow(); });
  $('#slide-next')?.addEventListener('click', () => { setSlide(currentSlide + 1); startSlideShow(); });
  slides.forEach((slide, idx) => slide.addEventListener('click', () => { setSlide(idx); startSlideShow(); }));
 
  const sliderTrack = $('#hero-slider-track');
  sliderTrack?.addEventListener('mouseenter', () => clearInterval(sliderTimer));
  sliderTrack?.addEventListener('mouseleave', startSlideShow);
  setSlide(0);
  startSlideShow();
 
  /* ---------- DROPDOWN PILLS INTERACTIVITY ---------- */
  const dropdownWrappers = $$('.dropdown-wrapper');
  dropdownWrappers.forEach(wrap => {
    const btn = $('button', wrap);
    const menu = $('.dropdown-menu', wrap);
    if (!btn || !menu) return;
 
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';
      closeAllDropdowns();
      if (!isExpanded) {
        btn.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
      }
    });
 
    $$('.dropdown-option', menu).forEach(opt => {
      opt.addEventListener('click', (e) => {
        e.stopPropagation();
        const val = opt.dataset.val;
        const label = $('span:not(.arrow-down)', btn);
        if (label) label.textContent = val;
        $$('.dropdown-option', menu).forEach(o => o.classList.toggle('is-selected', o === opt));
        closeAllDropdowns();
        showToast(`Selected: ${val}`);
      });
    });
  });
 
  function closeAllDropdowns() {
    dropdownWrappers.forEach(wrap => {
      const btn = $('button', wrap);
      const menu = $('.dropdown-menu', wrap);
      if (btn) btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
    const guestPopover = $('#guest-popover-box');
    if (guestPopover) guestPopover.hidden = true;
    const destQuick = $('#dest-quick-menu');
    if (destQuick) destQuick.hidden = true;
  }
 
  document.addEventListener('click', closeAllDropdowns);
 
  /* ---------- TRIP TYPE RADIOS ---------- */
  const tripRadios = $$('input[name="trip_mode"]');
  const wrapCheckout = $('#wrap-checkout');
  const inputCheckout = $('#input-checkout');
 
  tripRadios.forEach(r => {
    r.addEventListener('change', () => {
      const isOneWay = r.value === 'one_way';
      if (wrapCheckout) wrapCheckout.classList.toggle('is-disabled', isOneWay);
      if (inputCheckout) {
        inputCheckout.disabled = isOneWay;
        if (isOneWay) inputCheckout.value = '—';
        else if (inputCheckout.value === '—') inputCheckout.value = '';
      }
    });
  });
 
  /* ---------- DATES SETUP ---------- */
  const inDays = (n) => {
    const d = new Date(Date.now() + n * 864e5);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  };
  const inputCheckin = $('#input-checkin');
  const nativeCheckin = $('#native-checkin');
  const nativeCheckout = $('#native-checkout');
 
  if (inputCheckin) inputCheckin.value = inDays(5);
  if (inputCheckout) inputCheckout.value = inDays(12);
 
  // Sync date pickers
  inputCheckin?.addEventListener('click', () => nativeCheckin?.showPicker?.() || nativeCheckin?.focus());
  inputCheckout?.addEventListener('click', () => {
    if (!inputCheckout.disabled) nativeCheckout?.showPicker?.() || nativeCheckout?.focus();
  });
 
  nativeCheckin?.addEventListener('change', (e) => {
    if (e.target.value) {
      const d = new Date(e.target.value);
      inputCheckin.value = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
  });
  nativeCheckout?.addEventListener('change', (e) => {
    if (e.target.value) {
      const d = new Date(e.target.value);
      inputCheckout.value = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
  });
 
  /* ---------- DESTINATION AUTOCOMPLETE / QUICK SELECT ---------- */
  const destInput = $('#input-destination');
  const destMenu = $('#dest-quick-menu');
 
  destInput?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (destMenu) destMenu.hidden = false;
  });
 
  $$('.quick-dest-item').forEach(item => {
    item.addEventListener('click', (e) => {
      e.stopPropagation();
      destInput.value = item.dataset.city;
      destMenu.hidden = true;
      showToast(`Destination set to ${item.dataset.city}`);
    });
  });
 
  /* ---------- GUEST POPOVER COUNTER ---------- */
  const guestBtn = $('#btn-guests-modal');
  const guestPopover = $('#guest-popover-box');
  const guestText = $('#guest-display-text');
  let adults = 1, children = 0;
 
  function updateGuestText() {
    let parts = [];
    if (adults > 0) parts.push(`${adults} Adult${adults > 1 ? 's' : ''}`);
    if (children > 0) parts.push(`${children} Child${children > 1 ? 'ren' : ''}`);
    guestText.textContent = parts.length ? parts.join(', ') : 'Add Guests';
  }
 
  guestBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    closeAllDropdowns();
    if (guestPopover) guestPopover.hidden = !guestPopover.hidden;
  });
 
  guestPopover?.addEventListener('click', (e) => e.stopPropagation());
 
  $('#adult-dec')?.addEventListener('click', () => { if (adults > 1) { adults--; $('#adult-count').textContent = adults; updateGuestText(); } });
  $('#adult-inc')?.addEventListener('click', () => { if (adults < 9) { adults++; $('#adult-count').textContent = adults; updateGuestText(); } });
  $('#child-dec')?.addEventListener('click', () => { if (children > 0) { children--; $('#child-count').textContent = children; updateGuestText(); } });
  $('#child-inc')?.addEventListener('click', () => { if (children < 9) { children++; $('#child-count').textContent = children; updateGuestText(); } });
  $('#btn-guest-done')?.addEventListener('click', () => { if (guestPopover) guestPopover.hidden = true; });
 
  /* ---------- FLIGHT SEARCH SUBMIT ---------- */
  $('#flight-search-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const dest = destInput?.value.trim();
    if (!dest) {
      destInput?.focus();
      showToast('Please enter your destination.');
      return;
    }
    showToast(`Searching flights to ${dest}... Best fares found!`);
  });
 
  /* ---------- POPULAR DESTINATIONS CLICK ---------- */
  $$('.capsule-card').forEach(card => {
    card.addEventListener('click', () => {
      const city = card.dataset.destination;
      if (destInput) destInput.value = city;
      showToast(`${city} selected!`);
      $('#booking')?.scrollIntoView({ behavior: 'smooth' });
    });
  });
 
  /* ---------- DEALS FARE SELECTION ---------- */
  $$('.deal-card').forEach(card => {
    const fareLabels = $$('.fare-option', card);
    fareLabels.forEach(label => {
      label.addEventListener('click', () => {
        fareLabels.forEach(l => l.classList.remove('is-active'));
        label.classList.add('is-active');
      });
    });
 
    const learnBtn = $('.btn-learn-more', card);
    learnBtn?.addEventListener('click', () => {
      const city = card.dataset.city;
      const activeOption = $('.fare-option.is-active', card);
      const fareValue = activeOption?.querySelector('input[type="radio"]')?.value || 'smart';
      const fareName  = activeOption?.querySelector('.fare-tier-name')?.textContent.trim() || 'Smart Saver';
      const fareDesc  = activeOption?.querySelector('.fare-tier-desc')?.textContent.trim() || '';
      const farePrice = activeOption?.querySelector('.fare-tier-price')?.textContent.trim() || '';
 
      // Hand the selected package off to the destination details page,
      // where the traveler can browse hotels & car rentals for that city.
      const params = new URLSearchParams({
        city: city,
        fare: fareValue,
        fareName: fareName,
        desc: fareDesc,
        price: farePrice
      });
      window.location.href = `destination.php?${params.toString()}`;
    });
  });
 
  /* ---------- NAV SEARCH ---------- */
  $('#nav-search-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const q = $('#nav-search-input')?.value.trim().toLowerCase();
    if (!q) return;
    const match = $$('.capsule-card, .deal-card').find(el =>
      (el.dataset.destination || el.dataset.city || '').toLowerCase().includes(q)
    );
    if (match) {
      match.scrollIntoView({ behavior: 'smooth', block: 'center' });
      match.classList.add('highlight-flash');
      setTimeout(() => match.classList.remove('highlight-flash'), 1800);
      showToast(`Found destination matching "${q}"!`);
    } else {
      showToast(`No destinations found for "${q}".`);
    }
  });
 
})();
</script>
</body>
</html>