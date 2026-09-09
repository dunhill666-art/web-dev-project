<?php
// ============================================================
// AeroGlide — includes/navbar.php
// Reusable top navigation header bar
// ============================================================
require_once __DIR__ . '/../auth_helper.php';

$isLoggedIn  = auth_is_logged_in();
$currentUser = auth_get_user();
$username    = $isLoggedIn ? ($currentUser['username'] ?? 'User') : '';
$activeNav   = $activeNav ?? '';
?>
<!-- ============ NAVBAR ============ -->
<nav class="main-nav">
  <div class="nav-container">
    <a class="nav-brand" href="<?= ag_base_url('index.php') ?>" aria-label="AeroGlide Home">
      <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="AeroGlide Logo" class="brand-logo-img">
      <span class="brand-text">AeroGlide</span>
    </a>

    <div class="nav-menu">
      <a href="<?= ag_base_url('index.php') ?>" class="nav-item <?= $activeNav === 'home' ? 'is-active' : '' ?>">Home</a>
      <a href="<?= ag_base_url('index.php#booking') ?>" class="nav-item <?= $activeNav === 'flights' ? 'is-active' : '' ?>">Flights</a>
      <a href="<?= ag_base_url('index.php#promos') ?>" class="nav-item <?= $activeNav === 'deals' ? 'is-active' : '' ?>">Deals</a>
      <a href="<?= ag_base_url('index.php#tracker') ?>" class="nav-item <?= $activeNav === 'track' ? 'is-active' : '' ?>">Track</a>
      <a href="<?= ag_base_url('index.php#destinations') ?>" class="nav-item <?= $activeNav === 'destinations' ? 'is-active' : '' ?>">Destinations</a>
      <?php if ($isLoggedIn): ?>
        <a href="<?= ag_base_url('my_bookings.php') ?>" class="nav-item <?= $activeNav === 'trips' ? 'is-active' : '' ?>">My Trips</a>
      <?php endif; ?>
      <a href="<?= ag_base_url('help.php#about') ?>" class="nav-item <?= $activeNav === 'about' ? 'is-active' : '' ?>">About Us</a>
    </div>

    <div class="nav-right">
      <?php if (auth_is_admin()): ?>
        <a class="nav-auth-btn" href="<?= ag_base_url('admin/index.php') ?>" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;margin-right:6px;" title="Admin Dashboard">
          <span>🛡️ Admin Panel</span>
        </a>
      <?php endif; ?>

      <?php if ($isLoggedIn): ?>
        <span class="nav-user-greeting">Hi, <?= htmlspecialchars($username) ?></span>
        <a class="nav-auth-btn" href="<?= ag_base_url('logout.php') ?>" title="Logout">
          <span>Logout</span>
        </a>
      <?php else: ?>
        <a class="nav-auth-btn" href="<?= ag_base_url('login.php') ?>">
          <svg class="auth-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Sign up / Log in</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
