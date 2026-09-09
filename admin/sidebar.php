<?php
require_once dirname(__DIR__) . '/auth_helper.php';

// Shared sidebar for all admin pages
$admin = auth_get_user() ?? ['name' => 'Admin'];
$currentPage = basename($_SERVER['PHP_SELF']);
$nav = function($file, $icon, $label) use ($currentPage) {
    $active = ($currentPage === $file) ? ' active' : '';
    echo "<a href=\"{$file}\" class=\"nav-link{$active}\"><span class=\"nav-icon\">{$icon}</span> {$label}</a>\n";
};
?>
<aside class="sidebar">
  <a href="<?= ag_base_url('index.php') ?>" class="sidebar-brand" style="text-decoration:none;color:inherit;" title="Return to Homepage">
    <img src="<?= htmlspecialchars(asset_find(['logo']), ENT_QUOTES) ?>" alt="Logo">
    <div>
      <span class="brand-text">AeroGlide</span>
      <span class="brand-badge">ADMIN</span>
    </div>
  </a>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <?php $nav('index.php', '📊', 'Dashboard'); ?>
    <?php $nav('bookings.php', '🎫', 'All Bookings'); ?>
    <?php $nav('users.php', '👥', 'Users'); ?>

    <div class="nav-section-label" style="margin-top:.75rem;">Reports</div>
    <?php $nav('revenue.php', '💰', 'Revenue'); ?>
    <?php $nav('logs.php', '📋', 'Activity Logs'); ?>

    <div class="nav-section-label" style="margin-top:.75rem;">Site</div>
    <a href="<?= ag_base_url('index.php') ?>" class="nav-link"><span class="nav-icon">🌐</span> View Website</a>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($admin['name'] ?? 'Admin') ?></div>
        <div class="admin-role">Administrator</div>
      </div>
    </div>
    <a href="<?= ag_base_url('logout.php') ?>">Sign Out</a>
  </div>
</aside>
