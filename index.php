<?php
// ============================================================
// AeroGlide — index.php
// Public homepage: photo hero background, booking, deals,
// destinations, about, clients + Flight Tracker and
// "Discover What's Happening" coupon sections.
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

/* ---- Hero background photo (airplane in the sky) ----
   Primary: any file containing "hero" (e.g. hero-sky.png).
   Fallback: any file containing "plane" (e.g. Airoplane.jpg). */
 $heroBgSrc   = asset_find(['hero'], ['plane']);
 $heroBgIsImg = !str_starts_with($heroBgSrc, 'data:image/svg+xml');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AeroGlide — Book Smarter, Travel Further</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* ==========================================================
   AEROGLIDE — BEAUTIFICATION LAYER (self-contained)
   ========================================================== */

/* ---------- Hero: photo background with seamless edge blend ---------- */
/* Fallback atmosphere (used only when no hero photo is found) */
.hero-section {
  position: relative;
  background:
    radial-gradient(1100px 520px at 86% 10%, rgba(139, 199, 255, .30), transparent 62%),
    radial-gradient(920px 500px at 10% 92%, rgba(255, 255, 255, .55), transparent 66%),
    linear-gradient(168deg, #eef6ff 0%, #dcedfb 46%, #d2e7fa 100%);
  background-size: 92%;
  background-position: center;
  background-repeat: no-repeat;
  overflow: hidden;
}

/* When a photo is set inline, cover it fully + blend every edge
   into the page sky so there is never a visible seam or gap. */
.hero-section::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background:
    /* left + right side blends into the page sky */
    linear-gradient(90deg,
      rgba(236, 246, 255, .95) 0%,
      rgba(236, 246, 255, .60) 24%,
      rgba(236, 246, 255, .06) 56%,
      rgba(222, 239, 252, .30) 84%,
      rgba(212, 234, 250, .72) 100%),
    /* top + bottom blends */
    linear-gradient(180deg,
      rgba(238, 246, 255, .42) 0%,
      rgba(238, 246, 255, 0) 32%,
      rgba(236, 246, 255, 0) 62%,
      rgba(238, 246, 255, .55) 100%);
}

/* bottom fade — melts the photo's lower edge into the booking area sky */
.hero-section::after {
  content: "";
  position: absolute;
  left: 0; right: 0; bottom: 0;
  height: 180px;
  z-index: 0;
  pointer-events: none;
  background: linear-gradient(to bottom, rgba(238, 246, 255, 0), rgba(243, 250, 255, .92));
}

/* Single left-aligned content column — the plane in the photo
   occupies the right side of the background. */
.hero-container {
  grid-template-columns: minmax(0, 660px);
  position: relative;
  z-index: 1;
}

/* Hero title gradient accent */
.hero-title {
  background: linear-gradient(100deg, #12365e 10%, #1e63b5 55%, #2f8de0 90%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* ===================== FLIGHT TRACKER SECTION ===================== */
.tracker-section {
  position: relative;
  padding: 110px 6% 110px;
  background: linear-gradient(180deg, #f3f8fd 0%, #ffffff 100%);
  overflow: hidden;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.tracker-container {
  max-width: 1160px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 70px;
  align-items: center;
}

/* --- phone mockup --- */
.phone-mockup {
  position: relative;
  width: min(330px, 88vw);
  margin: 0 auto;
  border-radius: 44px;
  background: linear-gradient(160deg, #22303f, #0f171f);
  padding: 14px;
  box-shadow:
    0 40px 80px rgba(15, 40, 75,.35),
    0 12px 28px rgba(15, 40, 75,.22);
  transform: rotate(-2deg);
  animation: phoneFloat 8s ease-in-out infinite;
}
@keyframes phoneFloat {
  0%, 100% { transform: rotate(-2deg) translateY(0); }
  50%      { transform: rotate(-1.4deg) translateY(-14px); }
}

.phone-screen {
  background: #f4f7fb;
  border-radius: 32px;
  overflow: hidden;
  padding-bottom: 14px;
}

.phone-notch {
  width: 118px; height: 24px;
  background: #0f171f;
  border-radius: 0 0 16px 16px;
  margin: 0 auto;
}

.phone-statusbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px 4px;
  font-size: 11px;
  font-weight: 700;
  color: #26374a;
}

.phone-app-header {
  padding: 12px 18px 14px;
  background: linear-gradient(135deg, #1e63b5, #2f8de0);
  color: #fff;
}
.phone-app-header .pa-title { font-size: 14px; font-weight: 800; letter-spacing: .2px; }
.phone-app-header .pa-sub   { font-size: 10px; opacity: .85; margin-top: 2px; }

.flight-card {
  margin: 14px 14px 0;
  background: #fff;
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 8px 22px rgba(20,50,90,.12);
}
.fc-route {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.fc-airport { text-align: center; flex: 1; }
.fc-airport .fc-time {
  font-size: 20px; font-weight: 800; color: #14263c;
  font-family: 'Montserrat', sans-serif;
}
.fc-airport .fc-code {
  font-size: 15px; font-weight: 800; color: #1e63b5; margin-top: 2px;
}
.fc-airport .fc-term { font-size: 9.5px; color: #8a99ab; margin-top: 2px; }

.fc-duration {
  flex: 1.2;
  text-align: center;
  position: relative;
  padding-top: 4px;
}
.fc-duration .fc-plane-icon {
  font-size: 14px;
  position: absolute;
  top: -6px; left: 50%;
  transform: translateX(-50%);
  animation: miniPlane 3s ease-in-out infinite;
}
@keyframes miniPlane { 0%,100% { transform: translateX(-50%) translateY(0); } 50% { transform: translateX(-50%) translateY(-4px); } }
.fc-duration .fc-track {
  height: 2px;
  background: #dbe6f2;
  border-radius: 2px;
  margin: 12px 12px 6px;
  position: relative;
  overflow: hidden;
}
.fc-duration .fc-track::after {
  content: "";
  position: absolute;
  left: -40%;
  top: 0; height: 100%; width: 40%;
  background: linear-gradient(90deg, transparent, #2f8de0, transparent);
  animation: trackSweep 2.2s linear infinite;
}
@keyframes trackSweep { to { left: 100%; } }
.fc-duration .fc-dur-text { font-size: 9.5px; color: #8a99ab; }

.fc-gates {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}
.fc-gate-box {
  flex: 1;
  background: #eef4fb;
  border-radius: 10px;
  padding: 8px 6px;
  text-align: center;
}
.fc-gate-box .gb-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .8px; color: #8a99ab; font-weight: 700; }
.fc-gate-box .gb-value { font-size: 13px; font-weight: 800; color: #14263c; margin-top: 2px; }

.fc-status {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 700;
  color: #1e9e63;
}
.fc-status .pulse-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #25c47f;
  box-shadow: 0 0 0 0 rgba(37,196,127,.5);
  animation: pulseDot 1.6s infinite;
}
@keyframes pulseDot {
  70% { box-shadow: 0 0 0 9px rgba(37,196,127,0); }
  100%{ box-shadow: 0 0 0 0 rgba(37,196,127,0); }
}

.phone-alert {
  margin: 12px 14px 0;
  display: flex;
  gap: 10px;
  align-items: center;
  background: #fff7e8;
  border: 1px solid #ffe3ae;
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 10px;
  color: #a06a12;
  font-weight: 600;
}

/* --- tracker text column --- */
.tracker-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #e2eefc;
  color: #1e63b5;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  padding: 8px 16px;
  border-radius: 999px;
}
.tracker-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(28px, 3.6vw, 44px);
  font-weight: 900;
  line-height: 1.12;
  color: #14263c;
  margin: 20px 0 18px;
}
.tracker-paragraph {
  font-size: 15.5px;
  line-height: 1.75;
  color: #5a6b7d;
  max-width: 480px;
}

.tracker-feature-list { margin: 28px 0 0; padding: 0; list-style: none; display: grid; gap: 16px; }
.tracker-feature {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}
.tf-icon {
  flex: 0 0 42px;
  width: 42px; height: 42px;
  border-radius: 12px;
  display: grid; place-items: center;
  color: #fff;
  background: linear-gradient(135deg, #1e63b5, #2f8de0);
  box-shadow: 0 8px 18px rgba(30,99,181,.28);
}
.tf-icon svg { width: 19px; height: 19px; }
.tf-text strong { display: block; font-size: 15px; font-weight: 700; color: #1b2c3f; }
.tf-text span   { display: block; font-size: 13px; color: #7d8c9e; margin-top: 3px; line-height: 1.5; }

.tracker-cta {
  margin-top: 32px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: center;
}
.btn-tracker-primary {
  display: inline-flex; align-items: center; gap: 9px;
  background: linear-gradient(135deg, #1e63b5, #2f8de0);
  color: #fff;
  font-weight: 700; font-size: 14.5px;
  padding: 14px 26px;
  border-radius: 999px;
  text-decoration: none;
  box-shadow: 0 12px 26px rgba(30,99,181,.32);
  transition: transform .25s, box-shadow .25s;
}
.btn-tracker-primary:hover { transform: translateY(-3px); box-shadow: 0 18px 34px rgba(30,99,181,.4); }
.tracker-qr {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 16px;
  padding: 10px 18px 10px 10px;
  box-shadow: 0 10px 24px rgba(15,40,75,.1);
}
.tracker-qr canvas { width: 54px; height: 54px; display: block; }
.tracker-qr .qr-text { font-size: 11px; color: #5a6b7d; line-height: 1.45; font-weight: 600; }
.tracker-qr .qr-text b { color: #14263c; font-size: 12px; }

/* ===================== DISCOVER WHAT'S HAPPENING ===================== */
.promo-section {
  padding: 100px 6% 110px;
  background: linear-gradient(180deg, #ffffff 0%, #eef4fb 100%);
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.promo-container { max-width: 1160px; margin: 0 auto; }

.promo-section-header {
  text-align: center;
  margin-bottom: 44px;
}
.promo-eyebrow {
  display: inline-block;
  font-size: 12px; font-weight: 700;
  letter-spacing: 1.6px; text-transform: uppercase;
  color: #1e63b5;
  background: #e2eefc;
  padding: 8px 18px; border-radius: 999px;
}
.promo-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(26px, 3.4vw, 40px);
  font-weight: 900;
  color: #14263c;
  margin: 16px 0 8px;
}
.promo-subtitle { font-size: 15px; color: #7d8c9e; }

.promo-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 22px;
}

.promo-banner {
  position: relative;
  border-radius: 22px;
  padding: 34px 28px 30px;
  color: #fff;
  overflow: hidden;
  min-height: 260px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  cursor: pointer;
  text-decoration: none;
  transition: transform .3s ease, box-shadow .3s ease;
}
.promo-banner:hover {
  transform: translateY(-8px) scale(1.015);
  box-shadow: 0 26px 52px rgba(15,40,75,.28);
}

/* decorative circles */
.promo-banner::before,
.promo-banner::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.promo-banner::before {
  width: 210px; height: 210px;
  top: -80px; right: -70px;
  background: rgba(255,255,255,.14);
}
.promo-banner::after {
  width: 120px; height: 120px;
  bottom: -50px; left: -40px;
  background: rgba(255,255,255,.1);
}

/* coupon image layer (non-interactive, sits behind content) */
.promo-banner-media {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  z-index: 0;
  pointer-events: none;
  user-select: none;
  opacity: .96;
}
/* tinted gradient scrim: keeps text legible over any photo */
.promo-banner-scrim {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}
.promo-banner-scrim.scrim-blue   { background: linear-gradient(115deg, rgba(13, 59, 133, .88) 0%, rgba(13, 59, 133, .50) 52%, rgba(13, 59, 133, .15) 100%); }
.promo-banner-scrim.scrim-purple { background: linear-gradient(115deg, rgba(64, 34, 148, .88) 0%, rgba(64, 34, 148, .50) 52%, rgba(64, 34, 148, .15) 100%); }
.promo-banner-scrim.scrim-green  { background: linear-gradient(115deg, rgba(7, 84, 52, .88) 0%, rgba(7, 84, 52, .50) 52%, rgba(7, 84, 52, .15) 100%); }

.promo-banner-blue   { background: linear-gradient(140deg, #1a6fe0, #3f9bff); }
.promo-banner-purple { background: linear-gradient(140deg, #6a3ff0, #9a6bff); }
.promo-banner-green  { background: linear-gradient(140deg, #0f9d58, #34c47c); }

.promo-banner-blue:hover   { box-shadow: 0 26px 52px rgba(26,111,224,.4); }
.promo-banner-purple:hover { box-shadow: 0 26px 52px rgba(106,63,240,.4); }
.promo-banner-green:hover  { box-shadow: 0 26px 52px rgba(15,157,88,.4); }

.promo-flag {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 800;
  letter-spacing: 1.2px; text-transform: uppercase;
  background: rgba(255,255,255,.22);
  padding: 7px 13px;
  border-radius: 999px;
  width: fit-content;
  position: relative; z-index: 2;
}
.promo-sale-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(26px, 2.6vw, 34px);
  font-weight: 900;
  line-height: 1.05;
  margin: 18px 0 8px;
  position: relative; z-index: 2;
}
.promo-sale-dates {
  font-size: 14px; font-weight: 600;
  opacity: .92;
  position: relative; z-index: 2;
}
.promo-sale-desc {
  font-size: 13px; opacity: .85; margin-top: 6px;
  position: relative; z-index: 2;
}
.btn-promo-book {
  margin-top: 22px;
  align-self: flex-start;
  display: inline-flex; align-items: center; gap: 8px;
  background: #fff;
  color: #1b2c3f;
  font-weight: 800; font-size: 14px;
  padding: 11px 24px;
  border-radius: 999px;
  text-decoration: none;
  position: relative; z-index: 2;
  transition: transform .2s;
}
.promo-banner:hover .btn-promo-book { transform: translateX(4px); }

/* decorative plane watermark on banners */
.promo-plane-mark {
  position: absolute;
  right: 18px; bottom: 14px;
  width: 92px; height: auto;
  opacity: .18;
  pointer-events: none;
  z-index: 1;
}

/* ===================== POPULAR DESTINATIONS — SIZE BUMP ===================== */
.popular-capsule-grid {
  grid-template-columns: repeat(auto-fit, minmax(235px, 1fr)) !important;
  gap: 28px !important;
}
.capsule-card { min-height: unset; }
.capsule-image-wrap {
  aspect-ratio: 3 / 4 !important;
  min-height: 290px !important;
}
.capsule-image-wrap img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
}
.capsule-info { padding: 16px 4px !important; }
.capsule-city { font-size: 21px !important; }
.capsule-country { font-size: 14px !important; }

/* ---------- responsive ---------- */
@media (max-width: 980px) {
  .tracker-container { grid-template-columns: 1fr; gap: 56px; text-align: center; }
  .tracker-paragraph { margin: 0 auto; }
  .tracker-feature-list { text-align: left; max-width: 440px; margin-left: auto; margin-right: auto; }
  .tracker-cta { justify-content: center; }
  .promo-grid { grid-template-columns: 1fr; }
  .promo-banner { min-height: 220px; }
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
      <a href="index.php" class="nav-item is-active">Home</a>
      <a href="#booking" class="nav-item">Flights</a>
      <a href="#promos" class="nav-item">Deals</a>
      <a href="#tracker" class="nav-item">Track</a>
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
              <div class="fc-code">CDG</div>
              <div class="fc-term">Terminal 2F</div>
            </div>
            <div class="fc-duration">
              <span class="fc-plane-icon">✈</span>
              <div class="fc-track"></div>
              <div class="fc-dur-text">12h 30m · Direct</div>
            </div>
            <div class="fc-airport">
              <div class="fc-time">07:55 AM</div>
              <div class="fc-code">HND</div>
              <div class="fc-term">Terminal 3</div>
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

    <!-- Tracker copy + stats + QR (replicating the app promo layout) -->
    <div class="tracker-text-column">
      <span class="tracker-eyebrow">✈ AeroGlide Live</span>
      <h2 class="tracker-title">Track Your Flight<br>in Real Time</h2>
      <p class="tracker-paragraph">
        Follow flights across the globe with live updates on departure times,
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
            <span>Live position, altitude and speed for every AeroGlide flight worldwide.</span>
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
      <p class="promo-subtitle">Seasonal coupon events — grab them before they fly away.</p>
    </div>

    <div class="promo-grid">

      <!-- Coupon 1: 5 People Deal -->
      <a class="promo-banner promo-banner-blue" href="#booking" aria-label="5 people deal — five people for the price of four">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['people', 'deal'], null), ENT_QUOTES) ?>" alt="AeroGlide 5 people deal" loading="lazy">
        <span class="promo-banner-scrim scrim-blue" aria-hidden="true"></span>

        <span class="promo-flag">👥 5 People Deal</span>
        <h3 class="promo-sale-title">5 People for<br>the Price of 4</h3>
        <p class="promo-sale-dates">Group Getaway · Any Travel Date</p>
        <p class="promo-sale-desc">Coupon code <strong>GROUP5</strong> · book for a group of 5 and the 5th ticket is on us.</p>

        <span class="btn-promo-book">
          Grab the deal
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>

      <!-- Coupon 2: Sept Deal -->
      <a class="promo-banner promo-banner-purple" href="#booking" aria-label="September deal — up to 30 percent off">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['sept', 'deal'], null), ENT_QUOTES) ?>" alt="AeroGlide September deal" loading="lazy">
        <span class="promo-banner-scrim scrim-purple" aria-hidden="true"></span>

        <span class="promo-flag">✈ Sept Deal</span>
        <h3 class="promo-sale-title">September Sale<br>Up to 30% Off</h3>
        <p class="promo-sale-dates">Sept 1 – Sept 30, 2025</p>
        <p class="promo-sale-desc">Coupon code <strong>SEPTDEAL</strong> · discounted fares on all domestic flights.</p>

        <span class="btn-promo-book">
          Grab the deal
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>

      <!-- Coupon 3: Christmas Deal -->
      <a class="promo-banner promo-banner-green" href="#booking" aria-label="Christmas deal — holiday fares from 1,999 pesos">
        <img class="promo-banner-media" src="<?= htmlspecialchars(asset_find(['christmas', 'deal'], null), ENT_QUOTES) ?>" alt="AeroGlide Christmas deal" loading="lazy">
        <span class="promo-banner-scrim scrim-green" aria-hidden="true"></span>

        <span class="promo-flag">🎄 Christmas Deal</span>
        <h3 class="promo-sale-title">Christmas Fares<br>from ₱1,999</h3>
        <p class="promo-sale-dates">Dec 1 – Dec 25, 2025</p>
        <p class="promo-sale-desc">Coupon code <strong>XMASDEAL</strong> · fly home for the holidays for less.</p>

        <span class="btn-promo-book">
          Grab the deal
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </span>

        <svg class="promo-plane-mark" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.3c.4-.2.6-.6.5-1.1z"/></svg>
      </a>
    </div>
  </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="footer-container">
    <div>
      <div class="footer-brand-header">
        <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide logo" class="footer-brand-logo">
        <span class="footer-brand-name">AeroGlide</span>
      </div>
      <span class="social-title">Follow us</span>
      <div class="social-buttons-list">
        <a href="#" class="social-circle-btn" aria-label="AeroGlide on Facebook">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 22v-8h3l.5-4H13V7.5c0-1 .5-1.5 1.5-1.5H17V2h-3c-2.5 0-4 1.8-4 4.5V10H7v4h3v8h3z"/></svg>
        </a>
        <a href="#" class="social-circle-btn" aria-label="AeroGlide on Instagram">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor" stroke="none"/></svg>
        </a>
        <a href="#" class="social-circle-btn" aria-label="AeroGlide on X">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 3h4.5l4 5.5L17.5 3H21l-6.5 8.3L21.5 21H17l-4.3-6-5 6H4l7-8.6L4 3z"/></svg>
        </a>
      </div>
    </div>

    <div class="footer-links-grid">
      <div>
        <h4 class="footer-col-title">Company</h4>
        <ul class="footer-nav-list">
          <li><a href="#destinations">Destinations</a></li>
          <li><a href="#deals">Deals</a></li>
          <li><a href="#promos">What's Happening</a></li>
        </ul>
      </div>
      <div>
        <h4 class="footer-col-title">Support</h4>
        <ul class="footer-nav-list">
          <li><a href="#booking">Book a Flight</a></li>
          <li><a href="#tracker">Track a Flight</a></li>
          <li><a href="login.php">Manage Booking</a></li>
        </ul>
      </div>
      <div>
        <h4 class="footer-col-title">Legal</h4>
        <ul class="footer-nav-list">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Cookie Policy</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>

<!-- Toast Notification -->
<div class="toast-notification" id="toast-notification" role="status" aria-live="polite"></div>

<script>
(function () {
  "use strict";

  var $  = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* ---------- toast ---------- */
  var toastEl = $('#toast-notification'), toastTimer = null;
  function showToast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('is-visible'); }, 2600);
  }

  function goToDestination(city) {
    window.location.href = 'destination.php?city=' + encodeURIComponent(city);
  }

  /* ---------- hero slideshow ---------- */
  var stage = $('#hero-slider-track');
  if (stage) {
    var inner  = $('.hero-slide-track', stage);
    var slides = $$('.hero-slide', stage);
    var indexDisp = $('#slide-index-display');
    var STEP = slides.length ? slides[0].getBoundingClientRect().width + 8 : 92;
    var idx = 0, autoTimer = null;

    function render() {
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
      inner.style.transform = 'translateX(-' + (idx * STEP) + 'px)';
      if (indexDisp) {
        indexDisp.textContent =
          ('0' + (idx + 1)).slice(-2) + ' / ' + ('0' + slides.length).slice(-2);
      }
    }
    function go(n) { idx = (n + slides.length) % slides.length; render(); }
    function restartAuto() {
      clearInterval(autoTimer);
      autoTimer = setInterval(function () { go(idx + 1); }, 5000);
    }

    $('#slide-prev').addEventListener('click', function () { go(idx - 1); restartAuto(); });
    $('#slide-next').addEventListener('click', function () { go(idx + 1); restartAuto(); });
    render();
    restartAuto();
  }

  /* ---------- pill dropdowns (category / passengers / class) ---------- */
  $$('.dropdown-wrapper').forEach(function (wrap) {
    var btn   = $('button', wrap);
    var menu  = $('.dropdown-menu', wrap);
    var label = btn.querySelector('[id^="label-"]');
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var willOpen = menu.hidden;
      $$('.dropdown-menu').forEach(function (m) { m.hidden = true; });
      menu.hidden = !willOpen;
      btn.setAttribute('aria-expanded', String(willOpen));
    });
    $$('.dropdown-option', menu).forEach(function (opt) {
      opt.addEventListener('click', function () {
        $$('.dropdown-option', menu).forEach(function (o) { o.classList.remove('is-selected'); });
        opt.classList.add('is-selected');
        if (label) label.textContent = opt.dataset.val;
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
      });
    });
  });
  document.addEventListener('click', function () {
    $$('.dropdown-menu').forEach(function (m) { m.hidden = true; });
  });

  /* ---------- destination quick menu ---------- */
  var destInput = $('#input-destination');
  var destMenu  = $('#dest-quick-menu');
  if (destInput && destMenu) {
    destInput.addEventListener('focus', function () { destMenu.hidden = false; });
    destInput.addEventListener('input', function () { destMenu.hidden = false; });
    $$('.quick-dest-item', destMenu).forEach(function (item) {
      item.addEventListener('click', function () {
        destInput.value = item.dataset.city;
        destMenu.hidden = true;
      });
    });
    document.addEventListener('click', function (e) {
      if (e.target !== destInput && !destMenu.contains(e.target)) destMenu.hidden = true;
    });
  }

  /* ---------- date fields ---------- */
  [['input-checkin', 'native-checkin'], ['input-checkout', 'native-checkout']].forEach(function (pair) {
    var text   = document.getElementById(pair[0]);
    var native = document.getElementById(pair[1]);
    if (!text || !native) return;
    text.addEventListener('click', function () {
      if (native.showPicker) { try { native.showPicker(); } catch (err) { native.click(); } }
      else { native.click(); }
    });
    native.addEventListener('change', function () {
      if (!native.value) return;
      var d = new Date(native.value + 'T00:00:00');
      text.value = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    });
  });

  /* ---------- guest popover ---------- */
  var guestBtn = $('#btn-guests-modal');
  var guestBox = $('#guest-popover-box');
  if (guestBtn && guestBox) {
    guestBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      guestBox.hidden = !guestBox.hidden;
    });
    guestBox.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { guestBox.hidden = true; });

    function bindCounter(id, min, max) {
      var inc = document.getElementById(id + '-inc');
      var dec = document.getElementById(id + '-dec');
      var val = document.getElementById(id + '-count');
      var n = parseInt(val.textContent, 10) || 0;
      inc.addEventListener('click', function () { n = Math.min(max, n + 1); val.textContent = n; refreshGuests(); });
      dec.addEventListener('click', function () { n = Math.max(min, n - 1); val.textContent = n; refreshGuests(); });
      return function () { return n; };
    }
    var getAdults = bindCounter('adult', 1, 9);
    var getChilds = bindCounter('child', 0, 9);
    function refreshGuests() {
      var a = getAdults(), c = getChilds();
      $('#guest-display-text').textContent =
        a + (a === 1 ? ' Adult' : ' Adults') +
        (c ? ' · ' + c + (c === 1 ? ' Child' : ' Children') : '');
    }
    $('#btn-guest-done').addEventListener('click', function (e) {
      e.stopPropagation();
      guestBox.hidden = true;
    });
  }

  /* ---------- fare option radios ---------- */
  $$('.fare-options-list').forEach(function (list) {
    $$('input[type="radio"]', list).forEach(function (radio) {
      radio.addEventListener('change', function () {
        $$('.fare-option', list).forEach(function (l) { l.classList.remove('is-active'); });
        radio.closest('.fare-option').classList.add('is-active');
      });
    });
  });

  /* ---------- learn more buttons ---------- */
  $$('.btn-learn-more').forEach(function (btn) {
    btn.addEventListener('click', function () { goToDestination(btn.dataset.target); });
  });

  /* ---------- popular destination capsule cards ---------- */
  $$('.capsule-card').forEach(function (card) {
    function go() { goToDestination(card.dataset.destination); }
    card.addEventListener('click', go);
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
    });
  });

  /* ---------- flight search ---------- */
  var searchForm = $('#flight-search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var dest = destInput ? destInput.value.trim() : '';
      if (!dest) {
        showToast('Please choose a destination first ✈');
        if (destInput) destInput.focus();
        return;
      }
      showToast('Searching flights to ' + dest + '…');
      setTimeout(function () { goToDestination(dest); }, 900);
    });
  }

  /* ---------- nav search ---------- */
  var navForm = $('#nav-search-form');
  if (navForm) {
    navForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var q = $('#nav-search-input').value.trim();
      showToast(q ? 'Top results for "' + q + '"' : 'Type a destination to search');
    });
  }

  /* ---------- decorative pseudo-QR on the tracker ---------- */
  var qr = document.getElementById('tracker-qr-canvas');
  if (qr && qr.getContext) {
    var ctx = qr.getContext('2d');
    var S = 108, cells = 21, cs = S / cells;
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, S, S);
    ctx.fillStyle = '#14263c';
    var seed = 42;
    function rand() { seed = (seed * 1103515245 + 12345) % 2147483648; return seed / 2147483648; }
    for (var y = 0; y < cells; y++) {
      for (var x = 0; x < cells; x++) {
        var inFinder = (x < 8 && y < 8) || (x > 12 && y < 8) || (x < 8 && y > 12);
        if (!inFinder && rand() > 0.52) ctx.fillRect(x * cs + 0.5, y * cs + 0.5, cs - 1, cs - 1);
      }
    }
    function finder(fx, fy) {
      ctx.fillStyle = '#14263c'; ctx.fillRect(fx * cs, fy * cs, 7 * cs, 7 * cs);
      ctx.fillStyle = '#fff';    ctx.fillRect((fx + 1) * cs, (fy + 1) * cs, 5 * cs, 5 * cs);
      ctx.fillStyle = '#14263c'; ctx.fillRect((fx + 2) * cs, (fy + 2) * cs, 3 * cs, 3 * cs);
    }
    finder(0, 0); finder(14, 0); finder(0, 14);
  }
})();
</script>
</body>
</html>