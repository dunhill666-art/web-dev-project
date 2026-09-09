<?php
// ============================================================
// AeroGlide — admin/index.php
// Main Admin Dashboard
// ============================================================
require_once __DIR__ . '/guard.php';

$admin = auth_get_user();
$db    = ag_db();

// Stats
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalBookings = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue  = (float)$db->query("SELECT COALESCE(SUM(grand_total),0) FROM bookings WHERE status='confirmed'")->fetchColumn();
$monthBookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE MONTH(booking_date)=MONTH(NOW()) AND YEAR(booking_date)=YEAR(NOW())")->fetchColumn();

// Recent bookings
$recentBookings = $db->query("
    SELECT b.*, u.username, u.email AS user_email
    FROM bookings b JOIN users u ON u.id=b.user_id
    ORDER BY b.booking_date DESC LIMIT 10
")->fetchAll();

// Mode breakdown
$modeBreakdown = $db->query("SELECT mode, COUNT(*) as cnt, SUM(grand_total) as rev FROM bookings GROUP BY mode")->fetchAll();

// Recent users
$newUsers = $db->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 6")->fetchAll();

admin_log('Viewed dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — AeroGlide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.mode-list{padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:.85rem;}
.mode-row{display:flex;align-items:center;gap:.75rem;}
.mode-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.mode-bar-wrap{flex:1;height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;}
.mode-bar{height:100%;border-radius:99px;}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Dashboard</div>
    <div class="topbar-right">
      <span style="font-size:.82rem;color:var(--muted);">📅 <?= date('l, F j, Y') ?></span>
      <a href="bookings.php" class="topbar-btn btn-primary">View All Bookings</a>
    </div>
  </header>

  <div class="content">
    <div class="page-header">
      <h1 class="page-title">Welcome back, <?= htmlspecialchars($admin['name']) ?>.</h1>
      <p class="page-subtitle">Here's what's happening with AeroGlide today.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon blue">👥</div>
        <div class="stat-value"><?= number_format($totalUsers) ?></div>
        <div class="stat-label">Registered Users</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon green">🎫</div>
        <div class="stat-value"><?= number_format($totalBookings) ?></div>
        <div class="stat-label">Total Bookings</div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-icon yellow">💰</div>
        <div class="stat-value">₱<?= number_format($totalRevenue, 0) ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon purple">📅</div>
        <div class="stat-value"><?= number_format($monthBookings) ?></div>
        <div class="stat-label">Bookings This Month</div>
      </div>
    </div>

    <!-- Mode breakdown + Recent Signups -->
    <div class="grid-2">
      <div class="section-card">
        <div class="section-head"><span class="section-head-title">Booking Mode Breakdown</span></div>
        <?php
        $modeTotal  = array_sum(array_column($modeBreakdown, 'cnt')) ?: 1;
        $modeColors = ['flights'=>'#3b82f6','hotels'=>'#22c55e','packages'=>'#a855f7'];
        $modeIcons  = ['flights'=>'✈️','hotels'=>'🏨','packages'=>'🎁'];
        ?>
        <div class="mode-list">
          <?php if (empty($modeBreakdown)): ?>
            <div class="empty-state"><div class="empty-state-icon">📭</div><p>No bookings yet.</p></div>
          <?php else: ?>
            <?php foreach ($modeBreakdown as $m):
              $pct = round($m['cnt'] / $modeTotal * 100);
              $c   = $modeColors[$m['mode']] ?? '#64748b';
            ?>
            <div class="mode-row">
              <div class="mode-icon" style="background:<?= $c ?>22;"><?= $modeIcons[$m['mode']] ?? '📦' ?></div>
              <div style="flex:1;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                  <span style="font-size:.85rem;font-weight:700;text-transform:capitalize;"><?= htmlspecialchars($m['mode']) ?></span>
                  <span style="font-size:.82rem;color:var(--muted);"><?= $m['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="mode-bar-wrap"><div class="mode-bar" style="width:<?= $pct ?>%;background:<?= $c ?>;"></div></div>
                <div style="font-size:.75rem;color:var(--muted);margin-top:3px;">₱<?= number_format($m['rev'], 0) ?> revenue</div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="section-card">
        <div class="section-head">
          <span class="section-head-title">Recent Signups</span>
          <a href="users.php">View All</a>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>User</th><th>Joined</th><th>Role</th></tr></thead>
            <tbody>
              <?php if (empty($newUsers)): ?>
                <tr><td colspan="3"><div class="empty-state"><p>No users yet.</p></div></td></tr>
              <?php else: ?>
                <?php foreach ($newUsers as $u): ?>
                <tr>
                  <td>
                    <div class="user-cell">
                      <div class="user-avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                      <div>
                        <div style="font-weight:700;font-size:.83rem;"><?= htmlspecialchars($u['name']) ?></div>
                        <div style="font-size:.72rem;color:var(--muted);">@<?= htmlspecialchars($u['username']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="color:var(--muted);font-size:.78rem;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="section-card">
      <div class="section-head">
        <span class="section-head-title">Recent Bookings</span>
        <a href="bookings.php">View All</a>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>Ref</th><th>Traveler</th><th>Destination</th><th>Mode</th><th>Total</th><th>Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (empty($recentBookings)): ?>
              <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🎫</div><p>No bookings yet.</p></div></td></tr>
            <?php else: ?>
              <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><a href="booking_detail.php?id=<?= $b['id'] ?>" style="text-decoration:none;"><code style="font-size:.78rem;background:#f1f5f9;padding:2px 6px;border-radius:6px;color:var(--blue);"><?= htmlspecialchars($b['booking_ref']) ?></code></a></td>
                <td>
                  <div style="font-weight:700;font-size:.83rem;"><?= htmlspecialchars($b['traveler_name']) ?></div>
                  <div style="font-size:.72rem;color:var(--muted);">@<?= htmlspecialchars($b['username']) ?></div>
                </td>
                <td style="font-weight:600;"><?= htmlspecialchars($b['city']) ?></td>
                <td><span class="badge badge-<?= $b['mode'] ?>"><?= ucfirst($b['mode']) ?></span></td>
                <td style="font-weight:800;color:var(--green);">₱<?= number_format($b['grand_total'], 0) ?></td>
                <td style="color:var(--muted);font-size:.78rem;"><?= date('M j, Y', strtotime($b['booking_date'])) ?></td>
                <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
