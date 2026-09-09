<?php
// ============================================================
// AeroGlide — help.php
// Help Center, About Us, Reviews, User Manuals & FAQs Room.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';

$aboutImgSrc = asset_find(['banaue', 'rice'], ['palawan', 'sunset']);
?>
$pageTitle = 'Help Center, About Us & User Manual — AeroGlide';
$activeNav = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="aeroglide-page">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- ============ HERO SECTION ============ -->
  <section class="help-hero-section">
    <div class="help-hero-container">
      <span class="help-badge-pill">AeroGlide Support, About &amp; Knowledge Base</span>
      <h1 class="help-hero-title">Help Center &amp; User Manual Room</h1>
      <p class="help-hero-subtitle">Everything you need to know about booking domestic flights, managing packages, claiming coupons, reading traveler reviews, and accessing your Boarding Pass Tickets.</p>
    </div>
  </section>

  <!-- ============ ROOM NAVIGATION TABS ============ -->
  <section class="help-room-nav-section">
    <div class="help-room-container">
      <div class="help-tabs-bar" role="tablist" aria-label="Help Rooms">
        <button type="button" class="help-tab-btn active" data-room="about" role="tab" aria-selected="true">
          About Us &amp; Reviews
        </button>
        <button type="button" class="help-tab-btn" data-room="user-manual" role="tab" aria-selected="false">
          User Manual
        </button>
        <button type="button" class="help-tab-btn" data-room="how-to-book" role="tab" aria-selected="false">
          How to Book
        </button>
        <button type="button" class="help-tab-btn" data-room="site-works" role="tab" aria-selected="false">
          How the Site Works
        </button>
        <button type="button" class="help-tab-btn" data-room="faq" role="tab" aria-selected="false">
          FAQs &amp; Support
        </button>
        <button type="button" class="help-tab-btn" data-room="baggage" role="tab" aria-selected="false">
          Baggage &amp; Rules
        </button>
      </div>

      <!-- ================= ROOM 1: ABOUT US & REVIEWS ================= -->
      <div class="help-room-panel active" id="room-about" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">✈️</div>
          <div>
            <h2 class="panel-title">About AeroGlide &amp; Traveler Reviews</h2>
            <p class="panel-subtitle">Learn about our mission connecting the Philippine archipelago and read authentic traveler reviews.</p>
          </div>
        </div>

        <!-- ABOUT SECTION -->
        <div class="about-section" style="padding:2rem 0;">
          <div class="about-container" style="max-width:1000px; margin:0 auto; padding:0;">
            <div class="about-copy">
              <h2 class="about-title">Making the Islands<br>Easier to Reach</h2>
              <p class="about-paragraph">
                Since 2012, AeroGlide has connected travelers to the Philippines' most
                breathtaking destinations — from the white sands of Boracay to the
                limestone cliffs of Coron and the surf breaks of Siargao.
              </p>
              <p class="about-paragraph">
                With honest peso pricing, flexible rebooking, and 24/7 Filipino customer
                support, we make island-hopping as effortless as the breeze.
              </p>
              <ul class="about-points">
                <li>✔ Best-price guarantee on every domestic booking</li>
                <li>✔ Free 24-hour cancellation on flexible fares</li>
                <li>✔ Support in English &amp; Filipino, day or night</li>
              </ul>
              <a href="index.php#booking" class="btn-book-now">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/></svg>
                Plan your island trip
              </a>
            </div>
            <div class="about-arch-frame">
              <img class="about-arch-img" src="<?= htmlspecialchars($aboutImgSrc, ENT_QUOTES) ?>" alt="Banaue Rice Terraces, Ifugao, Philippines">
            </div>
          </div>
        </div>

        <!-- REVIEWS SECTION -->
        <div class="clients-section" style="padding:3rem 0 1rem; border-top:1px dashed #e2e8f0; margin-top:2rem;">
          <div class="section-header" style="text-align:center; margin-bottom:2.5rem;">
            <h2 class="section-main-title">What Our Travelers Say</h2>
            <p class="section-tagline">Real stories from real island-hoppers across the Philippines.</p>
          </div>

          <div class="clients-cards-grid">
            <article class="client-card">
              <div class="client-stars">★★★★★</div>
              <p class="client-quote">"Flew Manila to Caticlan for less than a bus ticket to the provinces.
              The Smart Saver fare with baggage included was the best deal I found anywhere."</p>
              <div class="client-author">
                <img class="client-avatar" src="<?= htmlspecialchars(asset_find(['avatar', '1'], ['vigan']), ENT_QUOTES) ?>" alt="Maria Santos">
                <div>
                  <div class="client-name">Maria Santos</div>
                  <div class="client-role">Frequent Flyer · Quezon City</div>
                </div>
              </div>
            </article>

            <article class="client-card">
              <div class="client-stars">★★★★★</div>
              <p class="client-quote">"Our barkada used the 5 People Deal for Coron — five of us flew for
              the price of four. The whole booking took ten minutes on a phone."</p>
              <div class="client-author">
                <img class="client-avatar" src="<?= htmlspecialchars(asset_find(['avatar', '2'], ['siargao']), ENT_QUOTES) ?>" alt="Paolo Reyes">
                <div>
                  <div class="client-name">Paolo Reyes</div>
                  <div class="client-role">Barkada Trip Organizer · Cebu City</div>
                </div>
              </div>
            </article>

            <article class="client-card">
              <div class="client-stars">★★★★★</div>
              <p class="client-quote">"A typhoon re-routed my Siargao flight and support rebooked me
              overnight with zero fees. That's the kind of alaga you hope for."</p>
              <div class="client-author">
                <img class="client-avatar" src="<?= htmlspecialchars(asset_find(['avatar', '3'], ['aurora']), ENT_QUOTES) ?>" alt="Liza Dela Cruz">
                <div>
                  <div class="client-name">Liza Dela Cruz</div>
                  <div class="client-role">Solo Traveler · Davao</div>
                </div>
              </div>
            </article>
          </div>
        </div>

      </div>

      <!-- ================= ROOM 2: USER MANUAL ================= -->
      <div class="help-room-panel" id="room-user-manual" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">📘</div>
          <div>
            <h2 class="panel-title">Official AeroGlide User Manual</h2>
            <p class="panel-subtitle">Comprehensive guide for new and returning travelers using the AeroGlide platform.</p>
          </div>
        </div>

        <div class="manual-steps-grid">
          
          <div class="manual-step-card">
            <div class="step-badge">Step 1</div>
            <h3 class="step-card-title">Account Creation &amp; Authentication</h3>
            <p class="step-card-desc">Only registered users can finalize trip bookings and access saved Boarding Pass Tickets. Guests can browse destinations and flight schedules freely.</p>
            <ul class="step-checklist">
              <li>Click <strong>Sign Up</strong> on the top navigation bar to create a free account.</li>
              <li>Existing users can click <strong>Log In</strong> with email/username and password.</li>
              <li>Forgotten passwords can be recovered via the <strong>Reset Password</strong> link.</li>
            </ul>
          </div>

          <div class="manual-step-card">
            <div class="step-badge">Step 2</div>
            <h3 class="step-card-title">Selecting Destinations &amp; Packages</h3>
            <p class="step-card-desc">Choose from top Philippine paradises including Boracay, Coron, Siargao, Mayon Volcano, Chocolate Hills, Bukidnon, and Malapascua.</p>
            <ul class="step-checklist">
              <li>Use the homepage flight search box or click any <strong>Destination Card</strong>.</li>
              <li>Filter by <strong>Flights Only</strong>, <strong>Hotels Only</strong>, or <strong>Full Vacation Packages</strong>.</li>
              <li>Select your preferred flight schedule time slot on the destination page.</li>
            </ul>
          </div>

          <div class="manual-step-card">
            <div class="step-badge">Step 3</div>
            <h3 class="step-card-title">Guest Counter &amp; Flexible Add-ons</h3>
            <p class="step-card-desc">Customize hotel stays and car rentals according to your trip itinerary. All add-ons are completely optional.</p>
            <ul class="step-checklist">
              <li>Adjust the <strong>Guest Counter</strong> (1 to 9 guests). Fares multiply accurately per guest.</li>
              <li>Set hotel stays to <strong>0 nights</strong> or car rentals to <strong>0 days</strong> if you only need a flight ticket!</li>
              <li>Setting guests to 5+ unlocks the exclusive <code>5people2026</code> 30% group discount code!</li>
            </ul>
          </div>

          <div class="manual-step-card">
            <div class="step-badge">Step 4</div>
            <h3 class="step-card-title">Claiming Coupon Codes &amp; Checkout</h3>
            <p class="step-card-desc">Apply valid AeroGlide discount codes on the checkout page before reviewing your final trip summary.</p>
            <ul class="step-checklist">
              <li>Enter valid codes: <code>5people2026</code> (30% off for 5+ guests), <code>septdeal2026</code> (₱1,500 off), or <code>berfly1000</code> (₱1,000 off).</li>
              <li><strong>Account Limit Rule:</strong> Each account is limited to <strong>1 claimed coupon code</strong> per account. Logging into another account with a different email resets availability back to 3 coupons!</li>
              <li>Input passenger details and select payment method (GCash, Maya, Card).</li>
            </ul>
          </div>

          <div class="manual-step-card">
            <div class="step-badge">Step 5</div>
            <h3 class="step-card-title">Boarding Pass &amp; My Trips Summary</h3>
            <p class="step-card-desc">After completing checkout, your official Airline Boarding Pass Ticket is generated instantly.</p>
            <ul class="step-checklist">
              <li>View your <strong>Boarding Pass Ticket</strong> formatted with passenger details, barcode, gate, and seat.</li>
              <li>Click <strong>Print Confirmation</strong> or view it anytime in the <strong>My Trips</strong> navbar menu.</li>
              <li>My Trips is accessible exclusively to logged-in travelers.</li>
            </ul>
          </div>

        </div>
      </div>

      <!-- ================= ROOM 3: HOW TO BOOK ================= -->
      <div class="help-room-panel" id="room-how-to-book" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">✈️</div>
          <div>
            <h2 class="panel-title">Visual Step-by-Step Booking Guide</h2>
            <p class="panel-subtitle">How to search, customize, and confirm your flight tickets in 4 simple actions.</p>
          </div>
        </div>

        <div class="booking-flow-grid">
          
          <div class="flow-card">
            <div class="flow-number">01</div>
            <div class="flow-content">
              <h3>Search &amp; Pick Flight</h3>
              <p>Type your destination (e.g., Boracay, Coron) or choose from featured island packages on the homepage.</p>
              <div class="flow-pill">Select One-way or Roundtrip</div>
            </div>
          </div>

          <div class="flow-card">
            <div class="flow-number">02</div>
            <div class="flow-content">
              <h3>Customize Add-ons &amp; Guests</h3>
              <p>Choose flight schedules, select hotel options, and adjust nights or car rental days as desired.</p>
              <div class="flow-pill">Hotel &amp; Car optional (0 days available)</div>
            </div>
          </div>

          <div class="flow-card">
            <div class="flow-number">03</div>
            <div class="flow-content">
              <h3>Enter Promo Code &amp; Checkout</h3>
              <p>Log in to your account, enter passenger info, and type your coupon code to apply instant savings.</p>
              <div class="flow-pill">1 Coupon limit per registered account</div>
            </div>
          </div>

          <div class="flow-card">
            <div class="flow-number">04</div>
            <div class="flow-content">
              <h3>Get Boarding Pass Ticket</h3>
              <p>Receive your navy-blue official Airline Boarding Pass Ticket complete with flight reference and barcode!</p>
              <div class="flow-pill">Saved in My Trips room</div>
            </div>
          </div>

        </div>
      </div>

      <!-- ================= ROOM 4: HOW THE SITE WORKS ================= -->
      <div class="help-room-panel" id="room-site-works" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">⚙️</div>
          <div>
            <h2 class="panel-title">How AeroGlide Works Behind the Scenes</h2>
            <p class="panel-subtitle">Key operational rules, coupon mechanics, and account restrictions explained.</p>
          </div>
        </div>

        <div class="system-rules-grid">
          
          <div class="system-rule-box">
            <div class="rule-icon">🔒</div>
            <h3 class="rule-title">Account Requirement for Booking</h3>
            <p>Browsing island destinations, flight schedules, and price estimates is open to everyone. However, to maintain verified passenger manifests and issue official airline tickets, users must log in before checking out.</p>
          </div>

          <div class="system-rule-box">
            <div class="rule-icon">🏷️</div>
            <h3 class="rule-title">1 Coupon Deal per Account Mechanics</h3>
            <p>To ensure fair promo distribution, each registered account is allowed to claim <strong>1 coupon discount</strong> across all bookings. Once claimed, that account is locked from adding another coupon code. Switch to a new account or sign up with a different email to get 3 fresh coupons again!</p>
          </div>

          <div class="system-rule-box">
            <div class="rule-icon">🎟️</div>
            <h3 class="rule-title">"My Trips" Passenger Dashboard</h3>
            <p>Logged-in users get access to the dedicated <strong>My Trips</strong> portal located on the main navigation header. Here, travelers can review their complete trip summaries, booking reference codes, total paid amounts, and reprint Boarding Pass Tickets anytime.</p>
          </div>

          <div class="system-rule-box">
            <div class="rule-icon">📊</div>
            <h3 class="rule-title">Real-Time Dynamic Fare Calculation</h3>
            <p>Prices update dynamically based on guest numbers, selected cabin tier (Economy, Premium Economy, Business, First Class), and optional hotel/car days. Zero-day options are respected so you never pay for unwanted add-ons.</p>
          </div>

        </div>
      </div>

      <!-- ================= ROOM 5: FAQ ACCORDION ================= -->
      <div class="help-room-panel" id="room-faq" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">❓</div>
          <div>
            <h2 class="panel-title">Frequently Asked Questions</h2>
            <p class="panel-subtitle">Click any question below to expand the detailed answer.</p>
          </div>
        </div>

        <div class="faq-accordion-list" id="faq-list">
          
          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>Why do I need to log in before checking out?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>AeroGlide requires user authentication during checkout to attach your booking reference (e.g. AG-63E1F300) directly to your account. This enables you to view your trip summary, claim coupons, and access your Boarding Pass Ticket under <strong>My Trips</strong>.</p>
            </div>
          </div>

          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>How does the 1-coupon per account rule work?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>Each registered traveler account can claim <strong>1 coupon code</strong> (out of <code>5people2026</code>, <code>septdeal2026</code>, or <code>berfly1000</code>). When you log out and sign into another account registered with a different email, coupon availability resets back to 3 for that new account!</p>
            </div>
          </div>

          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>What coupon codes are currently valid?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>The active AeroGlide promo codes are:</p>
              <ul>
                <li><code>5people2026</code> — 30% OFF flat discount (requires 5 or more guests in search).</li>
                <li><code>septdeal2026</code> — ₱1,500 flat discount on any vacation package or flight.</li>
                <li><code>berfly1000</code> — ₱1,000 flat discount on domestic flights.</li>
              </ul>
            </div>
          </div>

          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>Can I book a flight without selecting a hotel or car rental?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>Yes! Hotel stays and car rentals are 100% optional. You can keep hotel nights set to <strong>0 nights</strong> and car rental days set to <strong>0 days</strong> to purchase flight tickets only.</p>
            </div>
          </div>

          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>Where can I see my official Boarding Pass Ticket?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>Your Boarding Pass Ticket appears immediately after booking confirmation on the checkout page. You can also view or print it anytime by clicking <strong>My Trips</strong> in the top menu while logged in.</p>
            </div>
          </div>

          <div class="faq-item">
            <button type="button" class="faq-question-btn">
              <span>What payment methods are supported?</span>
              <span class="faq-toggle-icon">+</span>
            </button>
            <div class="faq-answer-content">
              <p>We support all major Philippine digital wallets and bank cards including GCash, Maya, BDO Online Banking, BPI Express, and Visa/Mastercard credit or debit cards.</p>
            </div>
          </div>

        </div>
      </div>

      <!-- ================= ROOM 6: BAGGAGE & RULES ================= -->
      <div class="help-room-panel" id="room-baggage" role="tabpanel">
        <div class="room-panel-header">
          <div class="panel-icon-circle">🧳</div>
          <div>
            <h2 class="panel-title">Baggage Allowances &amp; Travel Rules</h2>
            <p class="panel-subtitle">Domestic flight rules for travel within the Philippine archipelago.</p>
          </div>
        </div>

        <div class="baggage-rules-grid">
          <div class="baggage-card">
            <h3>🎒 Carry-on Baggage</h3>
            <p>1 cabin piece up to 7 kg (dimensions max 56cm x 36cm x 23cm) plus 1 small personal item (handbag or laptop bag).</p>
          </div>
          <div class="baggage-card">
            <h3>🧳 Checked Baggage</h3>
            <p>Included with Smart Saver (20 kg) and Flexi Fare (30 kg). Additional baggage can be purchased at airport counters.</p>
          </div>
          <div class="baggage-card">
            <h3>🆔 Required Identification</h3>
            <p>1 valid Philippine Government-issued ID (Driver's License, Passport, UMID, Postal ID, SSS, or National ID) is required at check-in.</p>
          </div>
        </div>
      </div>

    </div>
  </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function() {
  'use strict';

  // Room tab switching logic
  const tabBtns = document.querySelectorAll('.help-tab-btn');
  const panels  = document.querySelectorAll('.help-room-panel');

  function switchRoom(targetRoomId) {
    tabBtns.forEach(btn => {
      const isMatch = btn.dataset.room === targetRoomId;
      btn.classList.toggle('active', isMatch);
      btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
    });

    panels.forEach(panel => {
      const isMatch = panel.id === `room-${targetRoomId}`;
      panel.classList.toggle('active', isMatch);
    });
  }

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      switchRoom(btn.dataset.room);
    });
  });

  // Check URL hash on page load (e.g. help.php#about or help.php#faq)
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    if (['about', 'user-manual', 'how-to-book', 'site-works', 'faq', 'baggage'].includes(hash)) {
      switchRoom(hash);
    }
  }

  // FAQ Accordion toggles
  document.querySelectorAll('.faq-question-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      item.classList.toggle('is-open');
      const icon = btn.querySelector('.faq-toggle-icon');
      if (icon) {
        icon.textContent = item.classList.contains('is-open') ? '−' : '+';
      }
    });
  });

  // Search filtering
  const searchInput = document.getElementById('help-search-input');
  const searchBtn   = document.getElementById('btn-help-search');

  function performSearch() {
    const q = (searchInput?.value || '').trim().toLowerCase();
    if (!q) return;

    if (q.includes('about') || q.includes('review') || q.includes('story')) {
      switchRoom('about');
      document.getElementById('room-about')?.scrollIntoView({ behavior: 'smooth' });
      return;
    }

    // Expand FAQs that match
    document.querySelectorAll('.faq-item').forEach(item => {
      const text = item.textContent.toLowerCase();
      if (text.includes(q)) {
        item.classList.add('is-open');
        const icon = item.querySelector('.faq-toggle-icon');
        if (icon) icon.textContent = '−';
      }
    });

    // Auto-switch to FAQ tab
    switchRoom('faq');
    document.getElementById('room-faq')?.scrollIntoView({ behavior: 'smooth' });
  }

  searchBtn?.addEventListener('click', performSearch);
  searchInput?.addEventListener('keydown', e => { if (e.key === 'Enter') performSearch(); });

})();
</script>

</body>
</html>
