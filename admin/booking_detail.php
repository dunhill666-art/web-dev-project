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
    if (isset($_POST['action']) && $_POST['action'] === 'approve_cancellation') {
        $notes = trim($_POST['admin_notes'] ?? 'Cancellation approved by Admin. Refund processed.');
        $res = auth_admin_approve_cancellation((int)$_SESSION['user_id'], $id, $notes);
        header("Location: booking_detail.php?id={$id}&saved=1&msg=" . urlencode($res['message']));
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'reject_cancellation') {
        $notes = trim($_POST['admin_notes'] ?? 'Cancellation request rejected by Admin.');
        $res = auth_admin_reject_cancellation((int)$_SESSION['user_id'], $id, $notes);
        header("Location: booking_detail.php?id={$id}&saved=1&msg=" . urlencode($res['message']));
        exit;
    }

    $ns = in_array($_POST['status'] ?? '', ['confirmed', 'pending', 'cancelled', 'pending_cancellation']) ? $_POST['status'] : $b['status'];
    $db->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$ns, ':id'=>$id]);
    admin_log("Updated booking #{$id} status to {$ns}", "booking:{$id}");
    header("Location: booking_detail.php?id={$id}&saved=1"); 
    exit;
}

admin_log("Viewed booking #{$id} detail","booking:{$id}");
$bStatus = strtolower($b['status'] ?? 'confirmed');
$isPendingCancel = ($bStatus === 'pending_cancellation');
$isCancelled = ($bStatus === 'cancelled');
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
.badge-pending_cancellation {
  background: #fef3c7 !important;
  color: #92400e !important;
  border: 1px solid #fde68a !important;
  font-weight: 800 !important;
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 0.85rem;
  display: inline-block;
}
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
      <div class="alert-success">✅ <?= htmlspecialchars($_GET['msg'] ?? 'Booking status updated successfully.', ENT_QUOTES) ?></div>
    <?php endif; ?>

    <!-- PENDING CANCELLATION ACTION PANEL -->
    <?php if ($isPendingCancel): 
      $fee = min(500, $b['grand_total']);
      $refAmt = max(0, $b['grand_total'] - $fee);
    ?>
      <div style="background:#fffbeb; border:2px solid #fde68a; border-radius:18px; padding:1.5rem 1.75rem; margin-bottom:1.75rem; box-shadow:0 8px 24px rgba(217,119,6,0.12);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
          <span style="font-size:2rem; line-height:1;">⚠️</span>
          <div>
            <h2 style="font-family:'Montserrat',sans-serif; font-size:1.25rem; font-weight:800; color:#92400e; margin:0 0 4px;">
              Cancellation Request Awaiting Administrator Approval
            </h2>
            <p style="margin:0; font-size:0.88rem; color:#b45309;">
              Requested on <strong><?= !empty($b['cancellation_requested_at']) ? date('F j, Y g:i A', strtotime($b['cancellation_requested_at'])) : 'Recent' ?></strong>
            </p>
          </div>
        </div>

        <div style="background:#ffffff; border:1px solid #fef08a; border-radius:12px; padding:14px 16px; margin-bottom:16px;">
          <div style="font-size:0.8rem; font-weight:800; color:#854d0e; text-transform:uppercase; margin-bottom:4px;">Customer's Reason:</div>
          <div style="font-size:0.95rem; color:#0f172a; font-weight:600;"><?= htmlspecialchars($b['cancellation_reason'] ?: 'Customer initiated cancellation via dashboard.') ?></div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; background:#ffffff; border:1px solid #fef08a; border-radius:12px; padding:14px 16px; margin-bottom:18px; font-size:0.9rem;">
          <div><span style="color:#64748b; font-size:0.8rem; display:block;">Total Paid:</span><strong>₱<?= number_format($b['grand_total'], 2) ?></strong></div>
          <div><span style="color:#64748b; font-size:0.8rem; display:block;">Cancellation Fee:</span><strong style="color:#dc2626;">- ₱<?= number_format($fee, 2) ?></strong></div>
          <div><span style="color:#64748b; font-size:0.8rem; display:block;">Refund Credited:</span><strong style="color:#16a34a; font-size:1.1rem;">₱<?= number_format($refAmt, 2) ?></strong></div>
          <div><span style="color:#64748b; font-size:0.8rem; display:block;">Action:</span><strong>Void Boarding Pass</strong></div>
        </div>

        <!-- ACTIONS -->
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
          <!-- Approve Form -->
          <form method="POST" style="display:inline;" onsubmit="return confirm('Approve cancellation and authorize refund of ₱<?= number_format($refAmt, 2) ?>?');">
            <input type="hidden" name="action" value="approve_cancellation">
            <button type="submit" style="background:#10b981; color:#fff; border:none; padding:10px 22px; border-radius:10px; font-weight:800; font-size:0.92rem; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
              ✓ Approve Cancellation &amp; Process Refund
            </button>
          </form>

          <!-- Reject Button toggling rejection section -->
          <button type="button" onclick="document.getElementById('reject-form-box').style.display='block'; this.style.display='none';" style="background:#ef4444; color:#fff; border:none; padding:10px 22px; border-radius:10px; font-weight:800; font-size:0.92rem; cursor:pointer;">
            ✕ Reject / Resolve Request
          </button>
        </div>

        <div id="reject-form-box" style="display:none; margin-top:16px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:16px;">
          <form method="POST">
            <input type="hidden" name="action" value="reject_cancellation">
            <label style="display:block; font-size:0.85rem; font-weight:800; color:#9f1239; margin-bottom:6px;">
              Reason for Rejection / Resolution Note (displayed to traveler):
            </label>
            <textarea name="admin_notes" rows="2" required placeholder="e.g., Non-refundable promotion policy or flight departs in under 2 hours..." style="width:100%; border:1.5px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-family:inherit; font-size:0.88rem; outline:none; margin-bottom:12px; resize:none;"></textarea>
            <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-weight:800; font-size:0.88rem; cursor:pointer;">
              Confirm Rejection &amp; Restore Confirmed Status
            </button>
          </form>
        </div>
      </div>
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

        <!-- Pricing & Cancellation History -->
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

            <?php if ($isCancelled): ?>
              <div style="margin-top:14px; padding-top:14px; border-top:1.5px dashed #fca5a5; background:#fff1f2; border-radius:10px; padding:12px 14px;">
                <div style="font-weight:800; color:#9f1239; font-size:0.82rem; text-transform:uppercase; margin-bottom:6px;">Cancellation &amp; Refund Breakdown</div>
                <div class="detail-row"><span class="detail-key">Cancellation Fee</span><span class="detail-val" style="color:#dc2626;">- ₱<?= number_format($b['cancellation_fee'], 2) ?></span></div>
                <div class="detail-row"><span class="detail-key">Refund Credited</span><span class="detail-val" style="color:#16a34a; font-weight:800;">₱<?= number_format($b['refund_amount'], 2) ?></span></div>
                <div class="detail-row"><span class="detail-key">Cancelled At</span><span class="detail-val"><?= date('M j, Y g:i A', strtotime($b['cancelled_at'])) ?></span></div>
                <?php if (!empty($b['admin_notes'])): ?>
                <div class="detail-row"><span class="detail-key">Admin Notes</span><span class="detail-val"><?= htmlspecialchars($b['admin_notes']) ?></span></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($b['admin_notes']) && !$isCancelled): ?>
              <div style="margin-top:14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:10px 14px; font-size:0.85rem; color:#1e40af;">
                <strong>Admin Resolution Note:</strong> <?= htmlspecialchars($b['admin_notes']) ?>
              </div>
            <?php endif; ?>
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
              <?php if ($isPendingCancel): ?>
                <span class="badge-pending_cancellation">⏳ Pending Cancellation</span>
              <?php else: ?>
                <span class="badge badge-<?= $b['status'] ?>" style="font-size:.9rem;padding:5px 14px;"><?= ucfirst($b['status']) ?></span>
              <?php endif; ?>
            </div>
            <form method="POST" class="status-form">
              <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.06em;">Change Status</label>
              <select name="status">
                <option value="confirmed" <?= $b['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="pending_cancellation" <?= $b['status']==='pending_cancellation'?'selected':'' ?>>Pending Cancellation</option>
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
