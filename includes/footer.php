<?php
// ============================================================
// AeroGlide — includes/footer.php
// Reusable bottom footer section component
// ============================================================
require_once __DIR__ . '/../auth_helper.php';

$isLoggedIn = auth_is_logged_in();
?>
<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand-block">
      <div class="footer-brand-header">
        <img class="footer-brand-logo" src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo">
        <span class="footer-brand-name">AeroGlide</span>
      </div>
      <p class="footer-brand-desc">The Philippines' premier domestic airline booking platform. Connecting travelers to island paradises with ease.</p>
      <span class="social-title">Follow us</span>
      <div class="social-buttons-list">
        <a href="#" class="social-circle-btn" aria-label="Facebook">f</a>
        <a href="#" class="social-circle-btn" aria-label="Instagram">◎</a>
        <a href="#" class="social-circle-btn" aria-label="Twitter">𝕏</a>
        <a href="#" class="social-circle-btn" aria-label="YouTube">▶</a>
      </div>
    </div>

    <div class="footer-links-grid">
      <div>
        <h3 class="footer-col-title">Philippines Destinations</h3>
        <ul class="footer-nav-list">
          <li><a href="<?= ag_base_url('boracay.php') ?>">Boracay, Aklan</a></li>
          <li><a href="<?= ag_base_url('coron.php') ?>">Coron, Palawan</a></li>
          <li><a href="<?= ag_base_url('siargao.php') ?>">Siargao, Surigao</a></li>
          <li><a href="<?= ag_base_url('albay.php') ?>">Mayon Volcano, Albay</a></li>
          <li><a href="<?= ag_base_url('chocolate-hills.php') ?>">Chocolate Hills, Bohol</a></li>
          <li><a href="<?= ag_base_url('taal-volcano.php') ?>">Taal Volcano, Batangas</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-col-title">Guides &amp; Manuals</h3>
        <ul class="footer-nav-list">
          <li><a href="<?= ag_base_url('help.php#user-manual') ?>">User Manual</a></li>
          <li><a href="<?= ag_base_url('help.php#how-to-book') ?>">How to Book</a></li>
          <li><a href="<?= ag_base_url('help.php#site-works') ?>">How the Site Works</a></li>
          <li><a href="<?= ag_base_url('help.php#baggage') ?>">Baggage &amp; Flight Rules</a></li>
          <li><a href="<?= ag_base_url('help.php#user-manual') ?>">Boarding Pass Guide</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-col-title">Help &amp; Support</h3>
        <ul class="footer-nav-list">
          <li><a href="<?= ag_base_url('help.php#about') ?>">About Us &amp; Reviews</a></li>
          <li><a href="<?= ag_base_url('help.php#faq') ?>">Frequently Asked Questions</a></li>
          <li><a href="<?= ag_base_url('help.php') ?>">Help Center Room</a></li>
          <?php if ($isLoggedIn): ?>
            <li><a href="<?= ag_base_url('my_bookings.php') ?>">My Booked Trips</a></li>
          <?php else: ?>
            <li><a href="<?= ag_base_url('login.php') ?>">Log In / Sign Up</a></li>
          <?php endif; ?>
          <li><a href="<?= ag_base_url('forgot_password.php') ?>">Reset Password</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom-line">
    <p>© <?php echo date('Y'); ?> AeroGlide Philippines Inc. · Book smarter, travel further. All rights reserved.</p>
  </div>
</footer>
