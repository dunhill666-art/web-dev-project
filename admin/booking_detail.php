<?php
// ============================================================
// AeroGlide — admin/booking_detail.php
// Full booking detail + status management
// ============================================================
require_once __DIR__ . '/guard.php';

$db = ag_db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: bookings.php'); exit; }

$stmt = $db->prepare("SELECT b.*, u.username, u.name AS user_name, u.email AS user_email, u.created_at AS user_joined FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.id=:id LIMIT 1");
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();
if (!$b) { header('Location: bookings.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ns = in_array($_POST['status'] ?? '',['confirmed','pending','cancelled']) ? $_POST['status'] : $b['status'];
    $db->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$ns,':id'=>$id]);
    admin_log("Updated booking #{$id} status to {$ns}","booking:{$id}");
    header("Location: booking_detail.php?id={$id}&saved=1"); exit;
}

admin_log("Viewed booking #{$id} detail","booking:{$id}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking <?= htmlspecialchars($b['booking_ref']) ?> — AeroGlide Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;}
@media(max-width:900px){.detail-grid{grid-template-columns:1fr;}}
.detail-section{padding:1.4rem 1.6rem;}
.detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:.6rem 0;border-bottom:1px solid #f1f5f9;}
.detail-row:last-child{border-bottom:none;}
.detail-key{font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;flex-shrink:0;width:140px;}
.detail-val{font-size:.88rem;font-weight:600;color:var(--ink);text-align:right;flex:1;}
.ticket-strip{background:linear-gradient(135deg,var(--navy),#1e3a5f);border-radius:16px;padding:1.8rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden;}
.ticket-strip::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.04);}
.ticket-ref{font-family:'Montserrat',sans-serif;font-size:1.6rem;font-weight:900;letter-spacing:.02em;}
.ticket-city{font-size:1rem;opacity:.75;margin-top:.25rem;}
.ticket-price{font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:#60aeff;margin-top:.75rem;}
.status-form select{padding:.6rem .9rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit;font-size:.88rem;width:100%;margin-bottom:.75rem;outline:none;}
.status-form select:focus{border-color:var(--blue);}
.status-save{width:100%;padding:.75rem;background:var(--blue);color:#fff;border:none;border-radius:10px;font-family:inherit;font-weight:800;font-size:.9rem;cursor:pointer;}
.status-save:hover{background:var(--blue2);}
.user-detail-card{background:#f8fafc;border-radius:12px;padding:1rem 1.2rem;margin-bottom:1rem;}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <a href="bookings.php" style="text-decoration:none;color:var(--muted);font-weight:600;font-size:.85rem;">← All Bookings</a>
      <span style="margin:0 .5rem;color:var(--line);">/</span>
      <?= htmlspecialchars($b['booking_ref']) ?>
    </div>
  </header>

  <div class="content">
    <?php if (isset($_GET['saved'])): ?>
      <div class="alert-success">✅ Booking status updated successfully.</div>
    <?php endif; ?>

    <div class="detail-grid">
      <!-- Left: Booking Info -->
      <div>
        <!-- Ticket header -->
        <div class="ticket-strip">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;opacity:.6;margin-bottom:.35rem;">Booking Reference</div>
              <div class="ticket-ref"><?= htmlspecialchars($b['booking_ref']) ?></div>
              <div class="ticket-city"><?= htmlspecialchars($b['city']) ?></div>
            </div>
            <div style="text-align:right;">
              <span class="badge badge-<?= $b['mode'] ?>" style="font-size:.8rem;"><?= ucfirst($b['mode']) ?></span>
              <div class="ticket-price">₱<?= number_format($b['grand_total'], 2) ?></div>
            </div>
          </div>
        </div>

        <!-- Traveler Info -->
        <div class="section-card">
          <div class="section-head"><span class="section-head-title">Traveler Information</span></div>
          <div class="detail-section">
            <?php
            $details = [
              'Traveler Name' => $b['traveler_name'],
              'Email' => $b['traveler_email'],
              'Phone' => $b['traveler_phone'] ?: '—',
              'Booking Date' => date('F j, Y g:i A', strtotime($b['booking_date'])),
            ];
            foreach ($details as $k => $v): ?>
            <div class="detail-row">
              <span class="detail-key"><?= $k ?></span>
              <span class="detail-val"><?= htmlspecialchars($v) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Flight/Trip Info -->
        <div class="section-card">
          <div class="section-head"><span class="section-head-title">Trip Details</span></div>
          <div class="detail-section">
            <?php
            $trip = [
              'Mode' => ucfirst($b['mode']),
              'Destination' => $b['city'],
              'Guests / Adults' => $b['adults'],
              'Cabin Class' => $b['cabin'],
            ];
            if ($b['fare_name'])       $trip['Fare']     = $b['fare_name'] . ' — ' . ($b['fare_desc'] ?? '');
            if ($b['flight_schedule']) $trip['Flight']   = $b['flight_schedule'];
            if ($b['hotel_name'])      $trip['Hotel']    = $b['hotel_name'] . ' (' . $b['nights'] . ' night' . ($b['nights']!=1?'s':'') . ')';
            if ($b['car_name'])        $trip['Car']      = $b['car_name'] . ' (' . $b['days'] . ' day' . ($b['days']!=1?'s':'') . ')';
            foreach ($trip as $k => $v): ?>
            <div class="detail-row">
              <span class="detail-key"><?= $k ?></span>
              <span class="detail-val"><?= htmlspecialchars((string)$v) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Pricing -->
        <div class="section-card">
          <div class="section-head"><span class="section-head-title">Pricing Breakdown</span></div>
          <div class="detail-section">
            <div class="detail-row"><span class="detail-key">Subtotal</span><span class="detail-val">₱<?= number_format($b['subtotal'], 2) ?></span></div>
            <?php if ($b['discount_num'] > 0): ?>
            <div class="detail-row">
              <span class="detail-key">Discount</span>
              <span class="detail-val" style="color:var(--red);">−₱<?= number_format($b['discount_num'], 2) ?> <?= $b['discount_label'] ? '('.htmlspecialchars($b['discount_label']).')' : '' ?></span>
            </div>
            <?php endif; ?>
            <?php if ($b['promo_code']): ?>
            <div class="detail-row"><span class="detail-key">Promo Code</span><span class="detail-val"><code style="background:#f1f5f9;padding:2px 8px;border-radius:6px;"><?= htmlspecialchars($b['promo_code']) ?></code></span></div>
            <?php endif; ?>
            <div class="detail-row">
              <span class="detail-key" style="color:var(--ink);">Grand Total</span>
              <span class="detail-val" style="font-size:1.1rem;font-weight:900;color:var(--green);">₱<?= number_format($b['grand_total'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Status + User -->
      <div>
        <!-- Status update -->
        <div class="section-card" style="margin-bottom:1.5rem;">
          <div class="section-head"><span class="section-head-title">Booking Status</span></div>
          <div style="padding:1.2rem 1.4rem;">
            <div style="margin-bottom:1rem;">
              <span class="badge badge-<?= $b['status'] ?>" style="font-size:.9rem;padding:5px 14px;"><?= ucfirst($b['status']) ?></span>
            </div>
            <form method="POST" class="status-form">
              <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.06em;">Change Status</label>
              <select name="status">
                <option value="confirmed" <?= $b['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="pending"   <?= $b['status']==='pending'  ?'selected':'' ?>>Pending</option>
                <option value="cancelled" <?= $b['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
              </select>
              <button type="submit" class="status-save">Save Status</button>
            </form>
          </div>
        </div>

        <!-- Account Info -->
        <div class="section-card">
          <div class="section-head"><span class="section-head-title">Account Info</span></div>
          <div style="padding:1.2rem 1.4rem;">
            <div class="user-detail-card">
              <div class="user-cell">
                <div class="user-avatar-sm" style="width:42px;height:42px;font-size:1rem;"><?= strtoupper(substr($b['user_name'],0,1)) ?></div>
                <div>
                  <div style="font-weight:700;"><?= htmlspecialchars($b['user_name']) ?></div>
                  <div style="font-size:.78rem;color:var(--muted);">@<?= htmlspecialchars($b['username']) ?></div>
                  <div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($b['user_email']) ?></div>
                </div>
              </div>
            </div>
            <div class="detail-row">
              <span class="detail-key">Joined</span>
              <span class="detail-val"><?= date('M j, Y', strtotime($b['user_joined'])) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
