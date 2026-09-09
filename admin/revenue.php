<?php
// ============================================================
// AeroGlide — admin/revenue.php
// Revenue Report
// ============================================================
require_once __DIR__ . '/guard.php';

$db = ag_db();

$totalRev    = (float)$db->query("SELECT COALESCE(SUM(grand_total),0) FROM bookings WHERE status='confirmed'")->fetchColumn();
$totalDisc   = (float)$db->query("SELECT COALESCE(SUM(discount_num),0) FROM bookings")->fetchColumn();
$avgBooking  = (float)$db->query("SELECT COALESCE(AVG(grand_total),0) FROM bookings WHERE status='confirmed'")->fetchColumn();
$cancelRate  = 0;
$tot         = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$cancelled   = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();
if ($tot > 0) $cancelRate = round($cancelled / $tot * 100, 1);

// Monthly revenue (last 12 months)
$monthly = $db->query("
    SELECT DATE_FORMAT(booking_date,'%Y-%m') AS month,
           COUNT(*) AS cnt,
           SUM(grand_total) AS rev,
           SUM(discount_num) AS disc
    FROM bookings
    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month ASC
")->fetchAll();

// Top destinations
$topDest = $db->query("
    SELECT city, COUNT(*) AS cnt, SUM(grand_total) AS rev
    FROM bookings WHERE status='confirmed'
    GROUP BY city ORDER BY rev DESC LIMIT 8
")->fetchAll();

// Revenue by mode
$byMode = $db->query("
    SELECT mode, COUNT(*) AS cnt, SUM(grand_total) AS rev
    FROM bookings WHERE status='confirmed'
    GROUP BY mode
")->fetchAll();

// Coupon usage
$couponUsage = $db->query("
    SELECT promo_code, COUNT(*) AS used, SUM(discount_num) AS total_disc
    FROM bookings WHERE promo_code IS NOT NULL AND promo_code != ''
    GROUP BY promo_code ORDER BY used DESC
")->fetchAll();

admin_log('Viewed revenue report');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revenue — AeroGlide Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:1.5rem;}
@media(max-width:900px){.grid-3{grid-template-columns:1fr;}}
.chart-bar-h{display:flex;flex-direction:column;gap:.6rem;padding:1.2rem 1.5rem;}
.bar-row{display:flex;align-items:center;gap:.75rem;}
.bar-label{width:130px;font-size:.8rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0;}
.bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;}
.bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--blue),var(--teal));}
.bar-val{font-size:.78rem;font-weight:700;color:var(--muted);white-space:nowrap;}
.monthly-chart{padding:1.4rem 1.5rem;}
.monthly-bars{display:flex;align-items:flex-end;gap:6px;height:120px;border-bottom:2px solid #f1f5f9;margin-bottom:.5rem;}
.m-bar{flex:1;border-radius:5px 5px 0 0;background:linear-gradient(180deg,var(--blue),var(--teal));cursor:default;transition:opacity .2s;min-width:18px;}
.m-bar:hover{opacity:.8;}
.monthly-labels{display:flex;gap:6px;}
.m-label{flex:1;font-size:.65rem;color:var(--muted);text-align:center;min-width:18px;}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Revenue Report</div>
  </header>

  <div class="content">
    <div class="page-header">
      <h1 class="page-title">Revenue Overview</h1>
      <p class="page-subtitle">All-time revenue analysis for AeroGlide bookings.</p>
    </div>

    <!-- Top Stats -->
    <div class="stats-grid">
      <div class="stat-card green">
        <div class="stat-icon green">💰</div>
        <div class="stat-value">₱<?= number_format($totalRev,0) ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-icon blue">📊</div>
        <div class="stat-value">₱<?= number_format($avgBooking,0) ?></div>
        <div class="stat-label">Avg. Booking Value</div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-icon yellow">🏷️</div>
        <div class="stat-value">₱<?= number_format($totalDisc,0) ?></div>
        <div class="stat-label">Total Discounts Given</div>
      </div>
      <div class="stat-card red">
        <div class="stat-icon red">❌</div>
        <div class="stat-value"><?= $cancelRate ?>%</div>
        <div class="stat-label">Cancellation Rate</div>
      </div>
    </div>

    <!-- Monthly chart + Mode breakdown -->
    <div class="grid-2">
      <!-- Monthly revenue chart -->
      <div class="section-card">
        <div class="section-head"><span class="section-head-title">Monthly Revenue (Last 12 Months)</span></div>
        <?php if (empty($monthly)): ?>
          <div class="empty-state" style="padding:2rem;"><div class="empty-state-icon">📊</div><p>No data yet.</p></div>
        <?php else:
          $maxRev = max(array_column($monthly,'rev')) ?: 1;
        ?>
        <div class="monthly-chart">
          <div class="monthly-bars">
            <?php foreach ($monthly as $m):
              $h = max(4, round(($m['rev'] / $maxRev) * 110));
            ?>
              <div class="m-bar" style="height:<?= $h ?>px;" title="<?= $m['month'] ?>: ₱<?= number_format($m['rev'],0) ?>"></div>
            <?php endforeach; ?>
          </div>
          <div class="monthly-labels">
            <?php foreach ($monthly as $m): ?>
              <div class="m-label"><?= substr($m['month'],5) ?></div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.4rem;">
            <?php foreach (array_slice($monthly,-3) as $m): ?>
              <div style="display:flex;justify-content:space-between;font-size:.8rem;">
                <span style="color:var(--muted);"><?= $m['month'] ?></span>
                <span style="font-weight:700;">₱<?= number_format($m['rev'],0) ?> <span style="color:var(--muted);">(<?= $m['cnt'] ?> bookings)</span></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Revenue by mode -->
      <div class="section-card">
        <div class="section-head"><span class="section-head-title">Revenue by Mode</span></div>
        <div class="chart-bar-h">
          <?php
          $modeMax = max(array_column($byMode,'rev') ?: [1]);
          $modeColors=['flights'=>'var(--blue)','hotels'=>'var(--green)','packages'=>'var(--purple)'];
          foreach ($byMode as $m):
            $pct = $modeMax ? round($m['rev']/$modeMax*100) : 0;
          ?>
          <div class="bar-row">
            <div class="bar-label" style="text-transform:capitalize;"><?= htmlspecialchars($m['mode']) ?> <span style="font-weight:500;color:var(--muted);">(<?= $m['cnt'] ?>)</span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $modeColors[$m['mode']] ?? 'var(--blue)' ?>;"></div></div>
            <div class="bar-val">₱<?= number_format($m['rev'],0) ?></div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($byMode)): ?><div class="empty-state"><p>No data yet.</p></div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top Destinations -->
    <div class="grid-2">
      <div class="section-card">
        <div class="section-head"><span class="section-head-title">Top Destinations by Revenue</span></div>
        <div class="chart-bar-h">
          <?php
          $destMax = max(array_column($topDest,'rev') ?: [1]);
          foreach ($topDest as $d):
            $pct = $destMax ? round($d['rev']/$destMax*100) : 0;
          ?>
          <div class="bar-row">
            <div class="bar-label" title="<?= htmlspecialchars($d['city']) ?>"><?= htmlspecialchars($d['city']) ?> <span style="font-weight:500;color:var(--muted);">(<?= $d['cnt'] ?>)</span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div>
            <div class="bar-val">₱<?= number_format($d['rev'],0) ?></div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($topDest)): ?><div class="empty-state"><p>No data yet.</p></div><?php endif; ?>
        </div>
      </div>

      <!-- Coupon Usage -->
      <div class="section-card">
        <div class="section-head"><span class="section-head-title">Promo Code Usage</span></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Code</th><th>Times Used</th><th>Discounts Given</th></tr></thead>
            <tbody>
              <?php if(empty($couponUsage)): ?>
                <tr><td colspan="3"><div class="empty-state"><p>No promo codes used yet.</p></div></td></tr>
              <?php else: ?>
                <?php foreach($couponUsage as $c): ?>
                <tr>
                  <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:6px;"><?= htmlspecialchars($c['promo_code']) ?></code></td>
                  <td style="text-align:center;font-weight:700;"><?= $c['used'] ?></td>
                  <td style="font-weight:700;color:var(--red);">−₱<?= number_format($c['total_disc'],0) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
