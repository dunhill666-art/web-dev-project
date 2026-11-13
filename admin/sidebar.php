<?php
require_once dirname(__DIR__) . '/user/auth_helper.php';

// Shared sidebar for admin & staff pages
$admin = auth_get_user() ?? ['name' => 'User', 'role' => 'user'];
$isStaffOnly = ($admin['role'] ?? '') === 'staff';
$isAdmin = ($admin['role'] ?? '') === 'admin';
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
      <span class="brand-badge" style="<?= $isStaffOnly ? 'background:linear-gradient(135deg,#0284c7,#0369a1);' : '' ?>"><?= $isStaffOnly ? 'STAFF' : 'ADMIN' ?></span>
    </div>
  </a>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Operations</div>
    <?php if ($isAdmin): ?>
      <?php $nav(ag_base_url('admin'), '📊', 'Dashboard'); ?>
    <?php endif; ?>
    <?php $nav('bookings.php', '🎫', 'All Bookings'); ?>
    <?php if ($isAdmin): ?>
      <?php $nav('users.php', '👥', 'Users'); ?>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
      <div class="nav-section-label" style="margin-top:.75rem;">Reports</div>
      <?php $nav('revenue.php', '💰', 'Revenue'); ?>
      <?php $nav('logs.php', '📋', 'Activity Logs'); ?>
    <?php endif; ?>

    <div class="nav-section-label" style="margin-top:.75rem;">Site</div>
    <a href="<?= ag_base_url('index.php') ?>" class="nav-link" target="_blank"><span class="nav-icon">🌐</span> View Website</a>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar" style="<?= $isStaffOnly ? 'background:#0284c7;' : '' ?>"><?= strtoupper(substr($admin['name'] ?? 'S', 0, 1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($admin['name'] ?? ($isStaffOnly ? 'Staff' : 'Admin')) ?></div>
        <div class="admin-role"><?= $isStaffOnly ? 'Staff Member' : 'Administrator' ?></div>
      </div>
    </div>
    <a href="<?= ag_base_url('user/logout.php') ?>">Sign Out</a>
  </div>
</aside>
