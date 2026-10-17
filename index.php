<?php
// main landing page

require_once __DIR__ . '/user/auth_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

// hero background photo
 $heroBgSrc   = asset_find(['hero'], ['plane']);
 $heroBgIsImg = !str_starts_with($heroBgSrc, 'data:image/svg+xml');

// about section photo
 $aboutImgSrc = asset_find(['about'], ['banaue']);

$pageTitle = 'AeroGlide — Book Smarter, Travel Further';
$activeNav = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
/* ================= CEBU PACIFIC-STYLE DYNAMIC PRICE CALENDAR ================= */
.cp-calendar-popover {
  position: absolute !important;
  top: calc(100% + 14px) !important;
  left: 50% !important;
  transform: translateX(-50%) translateY(-6px) !important;
  background: #ffffff !important;
  border-radius: 18px !important;
  box-shadow: 0 18px 45px rgba(10, 20, 35, 0.18), 0 4px 12px rgba(10, 20, 35, 0.08) !important;
  border: 1px solid #e2e8f0 !important;
  padding: 1.5rem !important;
  z-index: 1000 !important;
  width: 680px !important;
  max-width: calc(100vw - 32px) !important;
  opacity: 0 !important;
  pointer-events: none !important;
  transition: opacity 0.22s ease, transform 0.22s ease !important;
}
.cp-calendar-popover.is-open {
  opacity: 1 !important;
  pointer-events: auto !important;
  transform: translateX(-50%) translateY(0) !important;
}
.cp-cal-months {
  display: flex !important;
  gap: 2rem !important;
  position: relative !important;
}
@media (max-width: 720px) {
  .cp-calendar-popover {
    width: 340px !important;
    padding: 1rem !important;
  }
  .cp-cal-months {
    flex-direction: column !important;
    gap: 1.5rem !important;
  }
  .cp-cal-month:nth-child(2) {
    display: none !important;
  }
}
.cp-cal-month {
  flex: 1 !important;
  min-width: 0 !important;
}
.cp-cal-header {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  margin-bottom: 1rem !important;
  padding: 0 4px !important;
}
.cp-cal-title {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: 1rem !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}
.cp-cal-nav-btn {
  background: transparent !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 50% !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  color: #0284c7 !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
  transition: background 0.2s ease, border-color 0.2s ease, transform 0.15s ease !important;
}
.cp-cal-nav-btn:hover:not(:disabled) {
  background: #f0f9ff !important;
  border-color: #38bdf8 !important;
  transform: scale(1.08) !important;
}
.cp-cal-nav-btn:disabled {
  opacity: 0.3 !important;
  cursor: not-allowed !important;
}
.cp-cal-weekdays {
  display: grid !important;
  grid-template-columns: repeat(7, 1fr) !important;
  text-align: center !important;
  margin-bottom: 8px !important;
}
.cp-cal-weekday {
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  color: #64748b !important;
  padding: 4px 0 !important;
}
.cp-cal-weekday.is-sun {
  color: #dc2626 !important;
}
.cp-cal-grid {
  display: grid !important;
  grid-template-columns: repeat(7, 1fr) !important;
  gap: 3px 2px !important;
}
.cp-cal-cell {
  aspect-ratio: 1 !important;
  min-height: 48px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 10px !important;
  cursor: pointer !important;
  background: transparent !important;
  border: 1px solid transparent !important;
  padding: 2px !important;
  position: relative !important;
  user-select: none !important;
  transition: background 0.15s ease, transform 0.1s ease, border-color 0.15s ease !important;
}
.cp-cal-cell.is-empty {
  cursor: default !important;
  pointer-events: none !important;
  background: transparent !important;
  border: none !important;
}
.cp-cal-cell.is-disabled {
  cursor: not-allowed !important;
  opacity: 0.35 !important;
  pointer-events: none !important;
}
.cp-cal-cell:hover:not(.is-disabled):not(.is-empty):not(.is-selected) {
  background: #f0f7ff !important;
  border-color: #bae6fd !important;
}
.cp-cal-day {
  font-size: 0.85rem !important;
  font-weight: 700 !important;
  color: #1e293b !important;
  line-height: 1.1 !important;
}
.cp-cal-cell.is-sun .cp-cal-day {
  color: #dc2626 !important;
}
.cp-cal-price {
  font-size: 0.68rem !important;
  font-weight: 700 !important;
  color: #64748b !important;
  line-height: 1 !important;
  margin-top: 3px !important;
  white-space: nowrap !important;
}
.cp-cal-price.is-lowest {
  color: #16a34a !important;
  font-weight: 900 !important;
}
.cp-cal-cell.is-selected {
  background: #ffcc00 !important;
  border-color: #eab308 !important;
  box-shadow: 0 4px 12px rgba(234, 179, 8, 0.4) !important;
  transform: scale(1.04) !important;
}
.cp-cal-cell.is-selected .cp-cal-day {
  color: #0f172a !important;
  font-weight: 900 !important;
}
.cp-cal-cell.is-selected .cp-cal-price {
  color: #0f172a !important;
  font-weight: 800 !important;
}
.cp-cal-cell.in-range {
  background: #fef9c3 !important;
  border-radius: 4px !important;
}
.cp-cal-footer {
  margin-top: 1.25rem !important;
  padding-top: 0.85rem !important;
  border-top: 1px solid #f1f5f9 !important;
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  font-size: 0.78rem !important;
  color: #64748b !important;
}
.cp-cal-legend {
  display: flex !important;
  align-items: center !important;
  gap: 14px !important;
}
.cp-legend-item {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}
.cp-legend-dot {
  width: 9px !important;
  height: 9px !important;
  border-radius: 50% !important;
}
.cp-legend-dot.is-lowest {
  background: #16a34a !important;
}
.cp-legend-dot.is-selected {
  background: #ffcc00 !important;
  border: 1px solid #ca8a04 !important;
}
</style>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ============ HERO SECTION (airplane-sky photo background) ============ -->
<header class="hero-section"<?= $heroBgIsImg ? ' style="background-image: url(\'' . htmlspecialchars($heroBgSrc, ENT_QUOTES) . '\');"' : '' ?>>
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

      <!-- Preview Slideshow -->
      <div class="hero-slider-wrap">
        <div class="hero-preview-row">
          <div class="hero-slide-stage" id="hero-slider-track">
            <div class="hero-slide-viewport">
              <div class="hero-slide-track">
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
            </div>
          </div>

          <div class="hero-preview-controls">
            <button type="button" class="hero-preview-arrow" id="slide-prev" aria-label="Previous destination">&#10094;</button>
            <span class="hero-stage-caption-index" id="slide-index-display">01 / 07</span>
            <button type="button" class="hero-preview-arrow" id="slide-next" aria-label="Next destination">&#10095;</button>
          </div>
        </div>

        <a href="#booking" class="btn-book-now">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/></svg>
          Book a trip now
        </a>
      </div>
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
        <div class="search-segment segment-date" id="segment-checkin">
          <label class="segment-label" for="input-checkin">
            Check In
          </label>
          <div class="date-input-wrap">
            <input type="text" id="input-checkin" class="segment-input date-text" placeholder="Choose dates" readonly>
            <input type="hidden" id="raw-checkin" name="departDate" value="<?= date('Y-m-d') ?>">
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
            <input type="hidden" id="raw-checkout" name="returnDate" value="">
            <span class="calendar-icon" aria-hidden="true">📅</span>
          </div>
        </div>

        <!-- Cebu Pacific-Style 2-Month Dynamic Price Calendar Popover -->
        <div class="cp-calendar-popover" id="cp-calendar-popover" hidden aria-label="Two-month flight pricing calendar">
          <div class="cp-cal-months" id="cp-cal-months-container">
            <!-- Dynamically populated by JavaScript with real async prices -->
          </div>
          <div class="cp-cal-footer">
            <div class="cp-cal-legend">
              <div class="cp-legend-item"><span class="cp-legend-dot is-lowest"></span> Lowest fare</div>
              <div class="cp-legend-item"><span class="cp-legend-dot is-selected"></span> Selected date</div>
            </div>
            <span class="cp-cal-route-hint" id="cp-cal-route-hint">Route: Manila &rarr; <span id="cp-cal-dest-name">Mayon Volcano, Albay</span></span>
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
          <label class="fare-option" data-baseprice="3499">
            <input type="radio" name="fare_boracay" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱3,499</span>
          </label>

          <label class="fare-option is-active" data-baseprice="4999">
            <input type="radio" name="fare_boracay" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱4,999</span>
          </label>

          <label class="fare-option" data-baseprice="6499">
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
          <label class="fare-option" data-baseprice="4299">
            <input type="radio" name="fare_coron" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱4,299</span>
          </label>

          <label class="fare-option is-active" data-baseprice="5799">
            <input type="radio" name="fare_coron" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱5,799</span>
          </label>

          <label class="fare-option" data-baseprice="7299">
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
          <label class="fare-option" data-baseprice="3999">
            <input type="radio" name="fare_siargao" value="lite">
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Lite Escape</span>
              <span class="fare-tier-desc">Flight only</span>
            </div>
            <span class="fare-tier-price">₱3,999</span>
          </label>

          <label class="fare-option is-active" data-baseprice="5499">
            <input type="radio" name="fare_siargao" value="smart" checked>
            <span class="fare-radio-dot"></span>
            <div class="fare-details">
              <span class="fare-tier-name">Smart Saver</span>
              <span class="fare-tier-desc">Flight + checked baggage</span>
            </div>
            <span class="fare-tier-price">₱5,499</span>
          </label>

          <label class="fare-option" data-baseprice="6999">
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
    <p class="section-tagline">Discover the Philippines, one island at a time.</p>
  </div>

  <div class="popular-capsule-grid">
    <!-- 1: Albay (Mayon Volcano) -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Mayon Volcano, Albay" data-url="destinations/albay.php" aria-label="Select Mayon Volcano, Albay">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['albay']), ENT_QUOTES) ?>" alt="Mayon Volcano and Cagsawa Ruins, Albay, Philippines">
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,499*</strong>
          <span class="hover-destination">Mayon Volcano</span>
          <span class="hover-book-btn">Book now</span>
        </div>
      </div>
      <div class="capsule-info">
        <h3 class="capsule-city">Albay</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>

    <!-- 2: Bukidnon -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Bukidnon" data-url="destinations/bukidnon.php" aria-label="Select Bukidnon">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['bukidnon']), ENT_QUOTES) ?>" alt="Communal Ranch, Bukidnon, Philippines">
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,799*</strong>
          <span class="hover-destination">Bukidnon</span>
          <span class="hover-book-btn">Book now</span>
        </div>
      </div>
      <div class="capsule-info">
        <h3 class="capsule-city">Bukidnon</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>

    <!-- 3: Chocolate Hills, Bohol -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Chocolate Hills, Bohol" data-url="destinations/chocolate-hills.php" aria-label="Select Chocolate Hills, Bohol">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['chocolate']), ENT_QUOTES) ?>" alt="Chocolate Hills, Carmen, Bohol, Philippines">
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,299*</strong>
          <span class="hover-destination">Chocolate Hills</span>
          <span class="hover-book-btn">Book now</span>
        </div>
      </div>
      <div class="capsule-info">
        <h3 class="capsule-city">Chocolate Hills</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>

    <!-- 4: Malapascua Island, Cebu -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Malapascua Island, Cebu" data-url="destinations/malapascua-island.php" aria-label="Select Malapascua Island, Cebu">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['malapascua']), ENT_QUOTES) ?>" alt="Malapascua Island, Cebu, Philippines">
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱3,199*</strong>
          <span class="hover-destination">Malapascua Island</span>
          <span class="hover-book-btn">Book now</span>
        </div>
      </div>
      <div class="capsule-info">
        <h3 class="capsule-city">Malapascua Island</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>

    <!-- 5: Taal Volcano, Batangas -->
    <div class="capsule-card" role="button" tabindex="0" data-destination="Taal Volcano, Batangas" data-url="destinations/taal-volcano.php" aria-label="Select Taal Volcano, Batangas">
      <div class="capsule-image-wrap">
        <img src="<?= htmlspecialchars(asset_find(['taal']), ENT_QUOTES) ?>" alt="Taal Volcano, Batangas, Philippines">
        <div class="capsule-hover-panel">
          <span class="hover-price-label">For as low as</span>
          <strong class="hover-price">₱2,199*</strong>
          <span class="hover-destination">Taal Volcano</span>
          <span class="hover-book-btn">Book now</span>
        </div>
      </div>
      <div class="capsule-info">
        <h3 class="capsule-city">Taal Volcano</h3>
        <p class="capsule-country">Philippines</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ REAL-TIME FLIGHT TRACKING ============ -->
<section class="tracker-section" id="tracker" aria-label="Real-time flight tracking">
  <div class="tracker-container">

    <!-- Phone mockup replicating the flight status screenshot -->
    <div class="phone-mockup">
      <div class="phone-screen">
        <div class="phone-notch"></div>
        <div class="phone-statusbar">
          <span>9:41</span>
          <span>▮▮▮ ⌁ 100%</span>
        </div>

        <div class="phone-app-header">
          <div class="pa-title">AeroGlide · Flight AG 293</div>
          <div class="pa-sub">Thu, Sep 10 — Sat, Sep 12 · 1 Adult · Economy</div>
        </div>

        <div class="flight-card">
          <div class="fc-route">
            <div class="fc-airport">
              <div class="fc-time">07:25 AM</div>
              <div class="fc-code">MNL</div>
              <div class="fc-term">Terminal 3</div>
            </div>
            <div class="fc-duration">
              <span class="fc-plane-icon">✈</span>
              <div class="fc-track"></div>
              <div class="fc-dur-text">1h 15m · Direct</div>
            </div>
            <div class="fc-airport">
              <div class="fc-time">08:40 AM</div>
              <div class="fc-code">MPH</div>
              <div class="fc-term">Caticlan Airport</div>
            </div>
          </div>

          <div class="fc-gates">
            <div class="fc-gate-box">
              <div class="gb-label">Boarding</div>
              <div class="gb-value">03 – 13</div>
            </div>
            <div class="fc-gate-box">
              <div class="gb-label">Gate</div>
              <div class="gb-value">L26</div>
            </div>
            <div class="fc-gate-box">
              <div class="gb-label">Seat</div>
              <div class="gb-value">21A</div>
            </div>
          </div>

          <div class="fc-status">
            <span class="pulse-dot"></span>
            On time · Boarding starts 06:40 AM
          </div>
        </div>

        <div class="phone-alert">
          <span>🔔</span>
          <span>Gate changed to <strong>L26</strong> · Terminal navigation updated.</span>
        </div>
      </div>
    </div>

    <!-- Tracker copy + stats + QR -->
    <div class="tracker-text-column">
      <span class="tracker-eyebrow">✈ AeroGlide Live</span>
      <h2 class="tracker-title">Track Your Flight<br>in Real Time</h2>
      <p class="tracker-paragraph">
        Follow flights across the archipelago with live updates on departure times,
        gates, and terminals. Get instant alerts for delays and gate changes,
        and navigate unfamiliar airports with ease — everything you need,
        right in your pocket.
      </p>

      <ul class="tracker-feature-list">
        <li class="tracker-feature">
          <span class="tf-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2s4 6 4 10a4 4 0 0 1-8 0c0-1 .5-2.5 1-3.5L12 2z"/><path d="M12 12v10"/></svg>
          </span>
          <div class="tf-text">
            <strong>Real-Time Flight Tracking</strong>
            <span>Live position, altitude and speed for every AeroGlide flight across the Philippines.</span>
          </div>
        </li>
        <li class="tracker-feature">
          <span class="tf-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 22 2l-9 19-2-8-8-2z"/></svg>
          </span>
          <div class="tf-text">
            <strong>Airport Navigation</strong>
            <span>Terminal maps, gate directions and walking times so you never miss a connection.</span>
          </div>
        </li>
        <li class="tracker-feature">
          <span class="tf-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </span>
          <div class="tf-text">
            <strong>Instant Change Alerts</strong>
            <span>Push notifications for gate changes, delays and schedule updates the moment they happen.</span>
          </div>
        </li>
      </ul>

      <div class="tracker-cta">
        <a class="btn-tracker-primary" href="#booking">
          Track a flight
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
        <div class="tracker-qr">
          <canvas id="tracker-qr-canvas" width="108" height="108" aria-hidden="true"></canvas>
          <div class="qr-text"><b>Scan to download</b><br>AeroGlide mobile app</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ DISCOVER WHAT'S HAPPENING (coupons) ============ -->
<section class="promo-section" id="promos" aria-label="Discover what's happening">
  <div class="promo-container">

    <div class="promo-section-header">
      <span class="promo-eyebrow">What's Happening</span>
      <h2 class="promo-title">Discover What's Happening</h2>
      <p class="promo-subtitle">Seasonal coupon events — grab them before they fly away.</p>
    </div>

    <div class="promo-grid">

      <!-- Coupon 1: 5 People Deal -->
      <a class="promo-banner promo-banner-blue" href="#booking" aria-label="5 people deal — 30% off group flights">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['5', 'people']), ENT_QUOTES) ?>" alt="AeroGlide 5 people deal" loading="lazy">
        <span class="promo-banner-scrim scrim-blue" aria-hidden="true"></span>

        <span class="promo-flag">👥 5 People Deal</span>
        <h3 class="promo-sale-title">5 People Deal:<br>30% OFF Group Fares</h3>
        <p class="promo-sale-dates">Code: <strong>5people2026</strong></p>
        <p class="promo-sale-desc">Enjoy 30% OFF group bookings to Boracay, Coron, and Siargao.</p>
        <span class="btn-promo-book">Claim coupon →</span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>

      <!-- Coupon 2: September Deal -->
      <a class="promo-banner promo-banner-purple" href="#booking" aria-label="September deal — 1500 pesos flat off">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['september', 'deal']), ENT_QUOTES) ?>" alt="AeroGlide september deal" loading="lazy">
        <span class="promo-banner-scrim scrim-purple" aria-hidden="true"></span>

        <span class="promo-flag">⚡ September Special</span>
        <h3 class="promo-sale-title">September Deal:<br>₱1,500 Flat OFF</h3>
        <p class="promo-sale-dates">Code: <strong>septdeal2026</strong></p>
        <p class="promo-sale-desc">Save ₱1,500 directly on any flight + hotel getaway bundle.</p>
        <span class="btn-promo-book">Claim coupon →</span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>

      <!-- Coupon 3: Ber Months Fly Deal -->
      <a class="promo-banner promo-banner-green" href="#booking" aria-label="Ber months fly deal — 1000 pesos flat off">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['christmas', 'deal']), ENT_QUOTES) ?>" alt="AeroGlide christmas deal" loading="lazy">
        <span class="promo-banner-scrim scrim-green" aria-hidden="true"></span>

        <span class="promo-flag">🌴 Ber-Months Special</span>
        <h3 class="promo-sale-title">Ber-Fly Pass:<br>₱1,000 Flat OFF</h3>
        <p class="promo-sale-dates">Code: <strong>berfly1000</strong></p>
        <p class="promo-sale-desc">Enjoy ₱1,000 discount on island hops across the Philippines.</p>
        <span class="btn-promo-book">Claim coupon →</span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>
    </div>
  </div>
</section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Toast Notification -->
<div class="toast-notification" id="toast" role="status" aria-live="polite"></div>

<script>
(function () {
  'use strict';

  /* ---------- Toast helper ---------- */
  const toast = document.getElementById('toast');
  let toastTimer;
  function showToast(msg) {
    toast.textContent = msg;
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 2600);
  }

  /* ---------- HERO SLIDER ---------- */
  const track  = document.querySelector('.hero-slide-track');
  const slides = Array.from(document.querySelectorAll('.hero-slide'));
  const indexDisplay = document.getElementById('slide-index-display');
  const STEP = 92; // 84px slide + 8px gap
  let current = 0;

  function pad(n) { return String(n).padStart(2, '0'); }
  function updateSlider() {
    const max = slides.length - 1;
    current = Math.max(0, Math.min(current, max));
    track.style.transform = `translateX(${-current * STEP}px)`;
    slides.forEach((s, i) => s.classList.toggle('is-active', i === current));
    indexDisplay.textContent = `${pad(current + 1)} / ${pad(slides.length)}`;
  }
  document.getElementById('slide-prev').addEventListener('click', () => { current--; updateSlider(); });
  document.getElementById('slide-next').addEventListener('click', () => { current++; updateSlider(); });

  // Auto-advance every 4s, pause on hover
  let auto = setInterval(() => { current = (current + 1) % slides.length; updateSlider(); }, 4000);
  const stage = document.getElementById('hero-slider-track');
  stage.addEventListener('mouseenter', () => clearInterval(auto));
  stage.addEventListener('mouseleave', () => {
    auto = setInterval(() => { current = (current + 1) % slides.length; updateSlider(); }, 4000);
  });
  updateSlider();

  /* ---------- BOOKING PILL DROPDOWNS ---------- */
  const labelCat   = document.getElementById('label-category');
  const labelClass = document.getElementById('label-class');

  const btnCat    = document.getElementById('btn-category');
  const menuCat   = document.getElementById('menu-category');
  const btnClass  = document.getElementById('btn-class');
  const menuClass = document.getElementById('menu-class');

  btnCat?.addEventListener('click', e => {
    e.stopPropagation();
    if (menuCat) {
      menuCat.hidden = !menuCat.hidden;
      if (menuClass) menuClass.hidden = true;
    }
  });

  menuCat?.querySelectorAll('.dropdown-option').forEach(opt => {
    opt.addEventListener('click', e => {
      e.stopPropagation();
      menuCat.querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('is-selected'));
      opt.classList.add('is-selected');
      if (labelCat) labelCat.textContent = opt.dataset.val || opt.textContent.trim();
      menuCat.hidden = true;
    });
  });

  btnClass?.addEventListener('click', e => {
    e.stopPropagation();
    if (menuClass) {
      menuClass.hidden = !menuClass.hidden;
      if (menuCat) menuCat.hidden = true;
    }
  });

  menuClass?.querySelectorAll('.dropdown-option').forEach(opt => {
    opt.addEventListener('click', e => {
      e.stopPropagation();
      menuClass.querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('is-selected'));
      opt.classList.add('is-selected');
      if (labelClass) labelClass.textContent = opt.dataset.val || opt.textContent.trim();
      menuClass.hidden = true;
    });
  });

  /* ---------- DESTINATION AUTO-SUGGEST ---------- */
  const destInput = document.getElementById('input-destination');
  const destMenu  = document.getElementById('dest-quick-menu');

  if (destInput && destMenu) {
    destInput.addEventListener('focus', () => { destMenu.hidden = false; });
    destInput.addEventListener('click', e => { e.stopPropagation(); destMenu.hidden = false; });

    destMenu.querySelectorAll('.quick-dest-item').forEach(item => {
      item.addEventListener('click', e => {
        e.stopPropagation();
        destInput.value = item.dataset.city || item.textContent.trim();
        destMenu.hidden = true;
      });
    });
  }

  /* ============================================================
     CEBU PACIFIC-STYLE DYNAMIC 2-MONTH PRICE CALENDAR CONTROLLER
     ============================================================ */
  const calPopover      = document.getElementById('cp-calendar-popover');
  const calContainer    = document.getElementById('cp-cal-months-container');
  const calDestNameDisp = document.getElementById('cp-cal-dest-name');
  const textCheckin     = document.getElementById('input-checkin');
  const textCheckout    = document.getElementById('input-checkout');
  const rawCheckin      = document.getElementById('raw-checkin');
  const rawCheckout     = document.getElementById('raw-checkout');
  const wrapCheckin     = document.getElementById('segment-checkin');
  const wrapCheckout    = document.getElementById('wrap-checkout');

  const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

  const now = new Date();
  let calBaseYear  = now.getFullYear();
  let calBaseMonth = now.getMonth(); // 0-indexed

  let activeCalendarTarget = 'checkin'; // 'checkin' | 'checkout'
  let selectedCheckinDate  = rawCheckin?.value || '';
  let selectedCheckoutDate = rawCheckout?.value || '';
  let currentTripMode      = 'two_way';

  const pricesCache = {}; // Cache: { "Destination_YYYY_MM": { "YYYY-MM-DD": { price, price_k, is_lowest, ... } } }

  const formatDisplayDate = dStr => {
    if (!dStr) return '';
    const d = new Date(dStr + 'T00:00:00');
    if (isNaN(d.getTime())) return dStr;
    return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
  };

  // Preset initial checkin date
  if (selectedCheckinDate && textCheckin) {
    textCheckin.value = formatDisplayDate(selectedCheckinDate);
  }

  async function fetchMonthPricing(destination, year, month) {
    const monthStr = `${year}-${String(month + 1).padStart(2, '0')}`;
    const cacheKey = `${destination}_${monthStr}`;

    if (pricesCache[cacheKey]) {
      return pricesCache[cacheKey];
    }

    try {
      const res = await fetch(`api/flight_prices.php?destination=${encodeURIComponent(destination)}&month=${encodeURIComponent(monthStr)}`);
      const data = await res.json();
      if (data.success && data.daily) {
        pricesCache[cacheKey] = data.daily;
        return data.daily;
      }
    } catch (e) {
      console.warn('Could not fetch daily prices:', e);
    }
    return {};
  }

  async function renderTwoMonthCalendar() {
    if (!calContainer) return;

    const destVal = (destInput?.value || 'Mayon Volcano, Albay').trim();
    if (calDestNameDisp) calDestNameDisp.textContent = destVal;

    // Month 1: baseYear, baseMonth
    // Month 2: baseYear, baseMonth + 1
    const m1Year = calBaseYear;
    const m1Month = calBaseMonth;

    let m2Year = calBaseYear;
    let m2Month = calBaseMonth + 1;
    if (m2Month > 11) {
      m2Month = 0;
      m2Year += 1;
    }

    // Fetch prices for both months in parallel
    const [pricesM1, pricesM2] = await Promise.all([
      fetchMonthPricing(destVal, m1Year, m1Month),
      fetchMonthPricing(destVal, m2Year, m2Month)
    ]);

    const isPrevDisabled = (m1Year < now.getFullYear() || (m1Year === now.getFullYear() && m1Month <= now.getMonth()));

    const buildMonthHTML = (year, month, pricesData, isFirstMonth) => {
      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const monthTitle = `${monthNames[month]} ${year}`;

      let cellsHTML = '';
      // Leading blanks
      for (let b = 0; b < firstDay; b++) {
        cellsHTML += `<div class="cp-cal-cell is-empty"></div>`;
      }

      // Days
      for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayOfWeek = (firstDay + day - 1) % 7;
        const isSun = (dayOfWeek === 0);

        const thisDateObj = new Date(year, month, day);
        const todayObj = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const isPast = (thisDateObj < todayObj);

        const priceItem = pricesData[dateStr];
        let priceTagHTML = '';
        let isLowest = false;

        if (priceItem && priceItem.has_flights && priceItem.price_k) {
          isLowest = !!priceItem.is_lowest;
          priceTagHTML = `<span class="cp-cal-price${isLowest ? ' is-lowest' : ''}">${priceItem.price_k}</span>`;
        } else if (!isPast) {
          priceTagHTML = `<span class="cp-cal-price">—</span>`;
        }

        const isCheckin = (dateStr === selectedCheckinDate);
        const isCheckout = (dateStr === selectedCheckoutDate);
        const isSelected = isCheckin || isCheckout;

        let inRange = false;
        if (selectedCheckinDate && selectedCheckoutDate && currentTripMode === 'two_way') {
          inRange = (dateStr > selectedCheckinDate && dateStr < selectedCheckoutDate);
        }

        let classes = ['cp-cal-cell'];
        if (isSun) classes.push('is-sun');
        if (isPast) classes.push('is-disabled');
        if (isSelected) classes.push('is-selected');
        if (inRange) classes.push('in-range');

        cellsHTML += `
          <div class="${classes.join(' ')}" data-date="${dateStr}" role="button" tabindex="0">
            <span class="cp-cal-day">${day}</span>
            ${priceTagHTML}
          </div>
        `;
      }

      const navLeftHTML = isFirstMonth ? `<button type="button" class="cp-cal-nav-btn" id="cp-cal-prev" ${isPrevDisabled ? 'disabled' : ''} aria-label="Previous month">&#10094;</button>` : `<div></div>`;
      const navRightHTML = !isFirstMonth ? `<button type="button" class="cp-cal-nav-btn" id="cp-cal-next" aria-label="Next month">&#10095;</button>` : `<div></div>`;

      return `
        <div class="cp-cal-month">
          <div class="cp-cal-header">
            ${navLeftHTML}
            <span class="cp-cal-title">${monthTitle} <span style="font-size:0.75rem; color:#64748b;">▼</span></span>
            ${navRightHTML}
          </div>
          <div class="cp-cal-weekdays">
            ${dayNames.map((dn, idx) => `<span class="cp-cal-weekday${idx === 0 ? ' is-sun' : ''}">${dn}</span>`).join('')}
          </div>
          <div class="cp-cal-grid">
            ${cellsHTML}
          </div>
        </div>
      `;
    };

    calContainer.innerHTML = `
      ${buildMonthHTML(m1Year, m1Month, pricesM1, true)}
      ${buildMonthHTML(m2Year, m2Month, pricesM2, false)}
    `;

    // Bind prev / next buttons
    document.getElementById('cp-cal-prev')?.addEventListener('click', e => {
      e.stopPropagation();
      calBaseMonth--;
      if (calBaseMonth < 0) {
        calBaseMonth = 11;
        calBaseYear--;
      }
      renderTwoMonthCalendar();
    });

    document.getElementById('cp-cal-next')?.addEventListener('click', e => {
      e.stopPropagation();
      calBaseMonth++;
      if (calBaseMonth > 11) {
        calBaseMonth = 0;
        calBaseYear++;
      }
      renderTwoMonthCalendar();
    });

    // Bind cell selection
    calContainer.querySelectorAll('.cp-cal-cell:not(.is-empty):not(.is-disabled)').forEach(cell => {
      cell.addEventListener('click', e => {
        e.stopPropagation();
        const d = cell.dataset.date;
        if (!d) return;

        if (activeCalendarTarget === 'checkin') {
          selectedCheckinDate = d;
          if (rawCheckin) rawCheckin.value = d;
          if (textCheckin) textCheckin.value = formatDisplayDate(d);

          if (currentTripMode === 'two_way') {
            // Auto transition to picking checkout date
            activeCalendarTarget = 'checkout';
            if (selectedCheckoutDate && selectedCheckoutDate <= selectedCheckinDate) {
              selectedCheckoutDate = '';
              if (rawCheckout) rawCheckout.value = '';
              if (textCheckout) textCheckout.value = '';
            }
            renderTwoMonthCalendar();
          } else {
            closeCalendar();
          }
        } else if (activeCalendarTarget === 'checkout') {
          if (selectedCheckinDate && d < selectedCheckinDate) {
            // If picked before checkin, make it the new checkin date
            selectedCheckinDate = d;
            selectedCheckoutDate = '';
            if (rawCheckin) rawCheckin.value = d;
            if (textCheckin) textCheckin.value = formatDisplayDate(d);
            if (rawCheckout) rawCheckout.value = '';
            if (textCheckout) textCheckout.value = '';
            activeCalendarTarget = 'checkout';
            renderTwoMonthCalendar();
          } else {
            selectedCheckoutDate = d;
            if (rawCheckout) rawCheckout.value = d;
            if (textCheckout) textCheckout.value = formatDisplayDate(d);
            closeCalendar();
          }
        }
      });
    });
  }

  function openCalendar(target) {
    activeCalendarTarget = target || 'checkin';
    if (calPopover) {
      calPopover.hidden = false;
      setTimeout(() => calPopover.classList.add('is-open'), 10);
    }
    renderTwoMonthCalendar();
  }

  function closeCalendar() {
    if (calPopover) {
      calPopover.classList.remove('is-open');
      setTimeout(() => { calPopover.hidden = true; }, 220);
    }
  }

  textCheckin?.addEventListener('click', e => {
    e.stopPropagation();
    openCalendar('checkin');
  });

  wrapCheckin?.addEventListener('click', e => {
    e.stopPropagation();
    openCalendar('checkin');
  });

  textCheckout?.addEventListener('click', e => {
    e.stopPropagation();
    if (currentTripMode === 'two_way') openCalendar('checkout');
  });

  wrapCheckout?.addEventListener('click', e => {
    e.stopPropagation();
    if (currentTripMode === 'two_way') openCalendar('checkout');
  });

  calPopover?.addEventListener('click', e => e.stopPropagation());

  // Close when clicking outside
  document.addEventListener('click', e => {
    if (!e.target.closest('#cp-calendar-popover') && !e.target.closest('#segment-checkin') && !e.target.closest('#wrap-checkout')) {
      closeCalendar();
    }
  });

  // Re-fetch calendar prices when destination changes
  destInput?.addEventListener('change', () => {
    if (!calPopover?.hidden) renderTwoMonthCalendar();
  });
  destMenu?.querySelectorAll('.quick-dest-item').forEach(item => {
    item.addEventListener('click', () => {
      setTimeout(() => {
        if (!calPopover?.hidden) renderTwoMonthCalendar();
      }, 50);
    });
  });

  /* ---------- TRIP DIRECTION RADIOS ---------- */
  document.querySelectorAll('input[name="trip_mode"]').forEach(radio => {
    radio.addEventListener('change', () => {
      currentTripMode = radio.value;
      if (radio.value === 'one_way') {
        if (wrapCheckout) {
          wrapCheckout.style.opacity = '0.4';
          wrapCheckout.style.pointerEvents = 'none';
        }
        if (textCheckout) textCheckout.value = '';
        if (rawCheckout) rawCheckout.value = '';
        selectedCheckoutDate = '';
      } else {
        if (wrapCheckout) {
          wrapCheckout.style.opacity = '1';
          wrapCheckout.style.pointerEvents = 'auto';
        }
      }
      if (!calPopover?.hidden) renderTwoMonthCalendar();
    });
  });


  /* ---------- GUEST POPOVER & SEARCH STATE ---------- */
  const guestBtn  = document.getElementById('btn-guests-modal');
  const guestBox  = document.getElementById('guest-popover-box');
  const guestText = document.getElementById('guest-display-text');
  let adults = 1, children = 0;

  function getSearchState() {
    const catText   = (labelCat?.textContent || 'Flights').trim();
    const classText = (labelClass?.textContent || 'Economy').trim();

    let mode = 'flights';
    if (catText.toLowerCase().includes('hotel')) mode = 'hotels';
    else if (catText.toLowerCase().includes('package')) mode = 'packages';

    const totalGuests = Math.max(1, adults + children);

    let cabinMult = 1.0;
    if (classText.includes('Premium Economy')) cabinMult = 1.3;
    else if (classText.includes('Business')) cabinMult = 1.8;
    else if (classText.includes('First Class')) cabinMult = 2.5;

    return { mode, adults: totalGuests, classText, cabinMult };
  }

  function renderGuests() {
    if (document.getElementById('adult-count')) document.getElementById('adult-count').textContent = adults;
    if (document.getElementById('child-count')) document.getElementById('child-count').textContent = children;
    
    const totalGuests = adults + children;
    if (guestText) {
      if (totalGuests >= 5) {
        guestText.innerHTML = `${totalGuests} Guests 🔥 <span style="font-size:0.75rem; color:#d97706; font-weight:800;">5people2026 Unlocked!</span>`;
      } else if (totalGuests === 0) {
        guestText.textContent = 'Add Guests';
      } else {
        guestText.textContent = `${totalGuests} Guest${totalGuests !== 1 ? 's' : ''}`;
      }
    }
  }

  guestBtn?.addEventListener('click', e => {
    e.stopPropagation();
    if (guestBox) guestBox.hidden = !guestBox.hidden;
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.segment-guest') && guestBox) guestBox.hidden = true;
    if (!e.target.closest('#dropdown-category') && menuCat) menuCat.hidden = true;
    if (!e.target.closest('#dropdown-class') && menuClass) menuClass.hidden = true;
    if (!e.target.closest('.segment-dest') && destMenu) destMenu.hidden = true;
  });

  document.getElementById('adult-inc')?.addEventListener('click', () => { if (adults < 9)  { adults++;  renderGuests(); } });
  document.getElementById('adult-dec')?.addEventListener('click', () => { if (adults > 0)  { adults--;  renderGuests(); } });
  document.getElementById('child-inc')?.addEventListener('click', () => { if (children < 9){ children++; renderGuests(); } });
  document.getElementById('child-dec')?.addEventListener('click', () => { if (children > 0){ children--; renderGuests(); } });
  document.getElementById('btn-guest-done')?.addEventListener('click', () => { if (guestBox) guestBox.hidden = true; });
  renderGuests();

  /* ---------- FARE OPTION RADIOS ---------- */
  document.querySelectorAll('.fare-option input').forEach(radio => {
    radio.addEventListener('change', () => {
      const list = radio.closest('.fare-options-list');
      list.querySelectorAll('.fare-option').forEach(o => o.classList.remove('is-active'));
      radio.closest('.fare-option').classList.add('is-active');
    });
  });

  /* ---------- LEARN MORE BUTTONS (CURATED PER-PERSON DEALS - DISCONNECTED FROM SEARCH GUEST COUNT TO PREVENT HUMAN ERROR) ---------- */
  document.querySelectorAll('.btn-learn-more').forEach(btn => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.deal-card');
      const activeOption = card ? card.querySelector('.fare-option.is-active') : null;
      let fareVal = 'smart', fareName = 'Smart Saver', desc = 'Flight + checked baggage', price = '₱4,999';
      if (activeOption) {
        const input = activeOption.querySelector('input[type="radio"]');
        if (input) fareVal = input.value;
        const nameEl = activeOption.querySelector('.fare-tier-name');
        if (nameEl) fareName = nameEl.textContent.trim();
        const descEl = activeOption.querySelector('.fare-tier-desc');
        if (descEl) desc = descEl.textContent.trim();
        const priceEl = activeOption.querySelector('.fare-tier-price');
        if (priceEl) price = priceEl.textContent.trim();
      }
      const targetCity = btn.dataset.target || 'Boracay, Aklan';
      // Always pass adults=1 for Exclusive AeroGlide Deals so deal rate stays per-person and avoids human error
      window.location.href = `destination.php?city=${encodeURIComponent(targetCity)}&fare=${encodeURIComponent(fareVal)}&fareName=${encodeURIComponent(fareName)}&desc=${encodeURIComponent(desc)}&price=${encodeURIComponent(price)}&adults=1`;
    });
  });

  /* ---------- CAPSULE DESTINATION CARDS ---------- */
  document.querySelectorAll('.capsule-card').forEach(card => {
    const go = () => {
      const state = getSearchState();
      const targetUrl = card.dataset.url;
      const targetCity = card.dataset.destination || '';
      window.location.href = `${targetUrl}?city=${encodeURIComponent(targetCity)}&mode=${state.mode}&adults=${state.adults}&cabin=${encodeURIComponent(state.classText)}`;
    };
    card.addEventListener('click', go);
    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
  });

  /* ---------- SEARCH FORM ---------- */
  document.getElementById('flight-search-form')?.addEventListener('submit', e => {
    e.preventDefault();
    const dest = destInput ? destInput.value.trim() : '';
    if (!dest) {
      showToast('Please choose a destination first ✈');
      if (destInput) destInput.focus();
      return;
    }
    const state = getSearchState();
    const depDate = rawCheckin?.value || '';
    const retDate = rawCheckout?.value || '';

    let url = `destination.php?city=${encodeURIComponent(dest)}&mode=${state.mode}&adults=${state.adults}&cabin=${encodeURIComponent(state.classText)}`;
    if (depDate) url += `&departDate=${encodeURIComponent(depDate)}&date=${encodeURIComponent(depDate)}`;
    if (retDate) url += `&returnDate=${encodeURIComponent(retDate)}`;

    window.location.href = url;
  });


  /* ---------- NAV SEARCH ---------- */
  document.getElementById('nav-search-form')?.addEventListener('submit', e => {
    e.preventDefault();
    const q = document.getElementById('nav-search-input')?.value.trim();
    if (!q) return;
    if (destInput) destInput.value = q;
    document.getElementById('booking')?.scrollIntoView({ behavior: 'smooth' });
    if (destMenu) destMenu.hidden = false;
  });

  /* ---------- PSEUDO-QR CODE (tracker) ---------- */
  const qrCanvas = document.getElementById('tracker-qr-canvas');
  if (qrCanvas && qrCanvas.getContext) {
    const ctx = qrCanvas.getContext('2d');
    const N = 9, cell = 12;
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, N * cell, N * cell);
    ctx.fillStyle = '#14263c';

    // deterministic pseudo-random modules
    let seed = 42;
    const rand = () => { seed = (seed * 1103515245 + 12345) & 0x7fffffff; return seed / 0x7fffffff; };

    const finder = (fx, fy) => {
      ctx.fillRect(fx * cell, fy * cell, 3 * cell, 3 * cell);
      ctx.fillStyle = '#fff';
      ctx.fillRect((fx + .5) * cell, (fy + .5) * cell, 2 * cell, 2 * cell);
      ctx.fillStyle = '#14263c';
      ctx.fillRect((fx + 1) * cell, (fy + 1) * cell, cell, cell);
    };
    finder(0, 0); finder(6, 0); finder(0, 6);
    for (let y = 0; y < N; y++) {
      for (let x = 0; x < N; x++) {
        const inFinder = (x < 4 && y < 4) || (x > 4 && y < 4) || (x < 4 && y > 4);
        if (!inFinder && rand() > .45) ctx.fillRect(x * cell, y * cell, cell, cell);
      }
    }
  }
})();
</script>
</body>
</html>