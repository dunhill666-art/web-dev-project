<?php
// ============================================================
// AeroGlide — admin/bookings.php
// All Bookings Management with search/filter/pagination
// ============================================================
require_once __DIR__ . '/guard.php';

$admin = auth_get_user();
$db    = ag_db();

// Filters
$search       = trim($_GET['q']        ?? '');
$mode         = $_GET['mode']          ?? '';
$status       = $_GET['status']        ?? '';
$userIdFilter = (int)($_GET['user_id'] ?? 0);
$dateFrom     = $_GET['date_from']     ?? '';
$dateTo       = $_GET['date_to']       ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

// Fetch all users for user filter dropdown
$allUsersList = $db->query("SELECT id, username, name, email FROM users ORDER BY name ASC")->fetchAll();
$filteredUser = null;
if ($userIdFilter > 0) {
    foreach ($allUsersList as $uOpt) {
        if ((int)$uOpt['id'] === $userIdFilter) {
            $filteredUser = $uOpt;
            break;
        }
    }
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bId = (int)($_POST['booking_id'] ?? 0);
    
    // Admin Approves Cancellation -> Status CANCELLED + Refund calculation + Void ticket
    if ($_POST['action'] === 'approve_cancellation' && $bId) {
        $notes = trim($_POST['admin_notes'] ?? 'Cancellation approved by Admin. Refund processed.');
        $res = auth_admin_approve_cancellation((int)$admin['id'], $bId, $notes);
        header('Location: bookings.php?msg=' . urlencode($res['message']));
        exit;
    }

    // Admin Rejects / Resolves Issue -> Status reverts to CONFIRMED + Admin note
    if ($_POST['action'] === 'reject_cancellation' && $bId) {
        $notes = trim($_POST['admin_notes'] ?? 'Cancellation request rejected by Admin.');
        $res = auth_admin_reject_cancellation((int)$admin['id'], $bId, $notes);
        header('Location: bookings.php?msg=' . urlencode($res['message']));
        exit;
    }

    if ($_POST['action'] === 'status_change' && $bId) {
        $ns = in_array($_POST['new_status'], ['confirmed', 'pending', 'cancelled', 'pending_cancellation']) ? $_POST['new_status'] : 'confirmed';
        $db->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$ns, ':id'=>$bId]);
        admin_log("Changed booking #{$bId} status to {$ns}", "booking:{$bId}");
        header('Location: bookings.php?updated=1'); 
        exit;
    }

    if ($_POST['action'] === 'delete' && $bId) {
        $db->prepare("DELETE FROM bookings WHERE id=:id")->execute([':id'=>$bId]);
        admin_log("Deleted booking #{$bId}", "booking:{$bId}");
        header('Location: bookings.php?deleted=1'); 
        exit;
    }
}

// Count pending cancellations for alert badge
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending_cancellation'")->fetchColumn();

// Query builder
$where = []; $params = [];
if ($search !== '') {
    $where[]      = "(b.booking_ref LIKE :s OR b.traveler_name LIKE :s OR b.traveler_email LIKE :s OR b.city LIKE :s OR u.username LIKE :s)";
    $params[':s'] = "%{$search}%";
}
if ($userIdFilter > 0) {
    $where[]               = "b.user_id = :uid_filter";
    $params[':uid_filter'] = $userIdFilter;
}
if ($mode   !== '') { $where[] = "b.mode=:mode";     $params[':mode']   = $mode; }
if ($status !== '') { $where[] = "b.status=:status"; $params[':status'] = $status; }
if ($dateFrom!=='') { $where[] = "DATE(b.booking_date)>=:df"; $params[':df'] = $dateFrom; }
if ($dateTo  !=='') { $where[] = "DATE(b.booking_date)<=:dt"; $params[':dt'] = $dateTo; }
$wc = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cStmt = $db->prepare("SELECT COUNT(*) FROM bookings b JOIN users u ON u.id=b.user_id $wc");
$cStmt->execute($params); 
$total = (int)$cStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare("SELECT b.*, u.username, u.name AS user_name FROM bookings b JOIN users u ON u.id=b.user_id $wc ORDER BY (b.status = 'pending_cancellation') DESC, b.booking_date DESC LIMIT :lim OFFSET :off");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="aeroglide_bookings_'.date('Ymd').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Ref','Traveler','Email','Phone','Username','City','Mode','Adults','Cabin','Hotel','Nights','Car','Days','SubTotal','Discount','GrandTotal','Promo','Date','Status','CancellationReason','AdminNotes']);
    foreach ($bookings as $b) {
        fputcsv($out, [$b['id'],$b['booking_ref'],$b['traveler_name'],$b['traveler_email'],$b['traveler_phone'],$b['username'],$b['city'],$b['mode'],$b['adults'],$b['cabin'],$b['hotel_name'],$b['nights'],$b['car_name'],$b['days'],$b['subtotal'],$b['discount_num'],$b['grand_total'],$b['promo_code'],$b['booking_date'],$b['status'],$b['cancellation_reason']??'',$b['admin_notes']??'']);
    }
    fclose($out); exit;
}

admin_log('Viewed bookings list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Bookings — AeroGlide Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.filter-bar{display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;}
.filter-group{display:flex;flex-direction:column;gap:.25rem;}
.filter-label{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.filter-input,.filter-select{padding:.6rem .85rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit;font-size:.85rem;color:var(--ink);background:#fff;outline:none;transition:border-color .2s;}
.filter-input:focus,.filter-select:focus{border-color:var(--blue);}
.filter-input{min-width:220px;}
.filter-btn{padding:.65rem 1.25rem;border-radius:10px;border:none;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;}
.filter-btn-primary{background:var(--blue);color:#fff;}
.filter-btn-reset{background:#f1f5f9;color:var(--ink);text-decoration:none;display:inline-flex;align-items:center;}
.pagination{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;}
.page-btn{padding:.5rem .9rem;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;color:var(--ink);background:#f1f5f9;border:1.5px solid var(--line);}
.page-btn.active{background:var(--blue);color:#fff;border-color:var(--blue);}
.page-btn:hover:not(.active){background:#e2e8f0;}

/* Status badges */
.badge-pending_cancellation {
  background: #fef3c7 !important;
  color: #92400e !important;
  border: 1px solid #fde68a !important;
  font-weight: 800 !important;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  display: inline-block;
  white-space: nowrap;
}

/* Modal Overlay */
.admin-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 20, 35, 0.7);
  backdrop-filter: blur(6px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 3000;
}
.admin-modal-overlay.is-active { display: flex; }
.admin-modal-box {
  background: #ffffff;
  border-radius: 18px;
  max-width: 520px;
  width: 100%;
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
  overflow: hidden;
  animation: modalIn .2s ease;
}
@keyframes modalIn {
  from { opacity:0; transform: scale(0.95); }
  to   { opacity:1; transform: scale(1); }
}
.modal-header-box {
  padding: 1.5rem 1.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-title-text {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0 0 4px;
}
.modal-body-box {
  padding: 1.5rem 1.75rem;
}
.admin-action-btn {
  padding: 5px 10px;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  text-decoration: none;
  transition: all 0.15s ease;
}
.btn-approve-cancel {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}
.btn-approve-cancel:hover {
  background: #10b981;
  color: #ffffff;
}
.btn-reject-cancel {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.btn-reject-cancel:hover {
  background: #ef4444;
  color: #ffffff;
}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">All Bookings</div>
    <div class="topbar-right">
      <a href="bookings.php?export=csv&<?= http_build_query(array_filter(['q'=>$search,'mode'=>$mode,'status'=>$status,'date_from'=>$dateFrom,'date_to'=>$dateTo])) ?>" class="topbar-btn btn-outline">⬇ Export CSV</a>
    </div>
  </header>

  <div class="content">
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert-success">✅ <?= htmlspecialchars($_GET['msg'], ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="alert-success">✅ Booking status updated.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert-warning">🗑 Booking deleted.</div><?php endif; ?>

    <div class="page-header">
      <h1 class="page-title">Bookings</h1>
      <p class="page-subtitle"><?= number_format($total) ?> booking(s) found</p>
    </div>

    <!-- PENDING CANCELLATIONS ALERT BANNER -->
    <?php if ($pendingCount > 0): ?>
      <div style="background:#fffbeb; border:1.5px solid #fde68a; border-radius:14px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; box-shadow:0 4px 14px rgba(217,119,6,0.08);">
        <div style="display:flex; align-items:center; gap:12px;">
          <span style="font-size:1.6rem; line-height:1;">⚠️</span>
          <div>
            <div style="font-weight:800; color:#92400e; font-size:0.95rem;">Action Required: <?= $pendingCount ?> Cancellation Request(s) Pending</div>
            <div style="font-size:0.82rem; color:#b45309;">Travelers have requested cancellations requiring admin verification, ₱500 fee calculation, and refund processing.</div>
          </div>
        </div>
        <a href="bookings.php?status=pending_cancellation" style="background:#d97706; color:#ffffff; padding:7px 16px; border-radius:10px; font-size:0.85rem; font-weight:800; text-decoration:none; transition:background 0.2s;">
          Review Pending Requests (<?= $pendingCount ?>) &rarr;
        </a>
      </div>
    <?php endif; ?>

    <?php if ($filteredUser): ?>
      <div class="alert-info" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:10px 16px;border-radius:12px;margin-bottom:1rem;font-weight:700;font-size:0.9rem;">
        👤 Showing bookings exclusively for user: <strong><?= htmlspecialchars($filteredUser['name']) ?></strong> (@<?= htmlspecialchars($filteredUser['username']) ?> &middot; <?= htmlspecialchars($filteredUser['email']) ?>)
        <a href="bookings.php" style="color:#0284c7;margin-left:12px;text-decoration:underline;">Clear Filter</a>
      </div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Search</label>
        <input class="filter-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ref, name, email, city…">
      </div>
      <div class="filter-group">
        <label class="filter-label">User Account</label>
        <select class="filter-select" name="user_id">
          <option value="">All Users</option>
          <?php foreach ($allUsersList as $uOpt): ?>
            <option value="<?= $uOpt['id'] ?>" <?= $userIdFilter===(int)$uOpt['id']?'selected':'' ?>>
              <?= htmlspecialchars($uOpt['name']) ?> (@<?= htmlspecialchars($uOpt['username']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Mode</label>
        <select class="filter-select" name="mode">
          <option value="">All Modes</option>
          <?php foreach(['flights','hotels','packages'] as $m): ?>
            <option value="<?= $m ?>" <?= $mode===$m?'selected':'' ?>><?= ucfirst($m) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select class="filter-select" name="status">
          <option value="">All Statuses</option>
          <option value="confirmed" <?= $status==='confirmed'?'selected':'' ?>>Confirmed</option>
          <option value="pending_cancellation" <?= $status==='pending_cancellation'?'selected':'' ?>>Pending Cancellation <?= $pendingCount > 0 ? "({$pendingCount})" : '' ?></option>
          <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
          <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">From</label>
        <input class="filter-input" style="min-width:auto;" type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
      </div>
      <div class="filter-group">
        <label class="filter-label">To</label>
        <input class="filter-input" style="min-width:auto;" type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
      </div>
      <button type="submit" class="filter-btn filter-btn-primary">Search</button>
      <a href="bookings.php" class="filter-btn filter-btn-reset">Reset</a>
    </form>

    <!-- Table -->
    <div class="section-card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Ref</th><th>Traveler</th><th>Destination</th><th>Mode</th><th>Guests</th><th>Total</th><th>Date</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr><td colspan="10"><div class="empty-state"><div class="empty-state-icon">🎫</div><p>No bookings found.</p></div></td></tr>
            <?php else: ?>
              <?php foreach ($bookings as $i => $b): 
                $bStatus = strtolower($b['status'] ?? 'confirmed');
                $isPendingCancel = ($bStatus === 'pending_cancellation');
              ?>
              <tr style="<?= $isPendingCancel ? 'background:#fffdfa;' : '' ?>">
                <td style="color:var(--muted);font-size:.75rem;"><?= $offset+$i+1 ?></td>
                <td>
                  <a href="booking_detail.php?id=<?= $b['id'] ?>" style="text-decoration:none;">
                    <code style="font-size:.78rem;background:#f1f5f9;padding:2px 6px;border-radius:6px;color:var(--blue);font-weight:700;">
                      <?= htmlspecialchars($b['booking_ref']) ?>
                    </code>
                  </a>
                </td>
                <td>
                  <div style="font-weight:700;font-size:.83rem;"><?= htmlspecialchars($b['traveler_name']) ?></div>
                  <div style="font-size:.72rem;color:var(--muted);">@<?= htmlspecialchars($b['username']) ?></div>
                </td>
                <td style="font-weight:600;max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($b['city']) ?></td>
                <td><span class="badge badge-<?= $b['mode'] ?>"><?= ucfirst($b['mode']) ?></span></td>
                <td style="text-align:center;"><?= $b['adults'] ?></td>
                <td style="font-weight:800;color:var(--green);">₱<?= number_format($b['grand_total'],0) ?></td>
                <td style="color:var(--muted);font-size:.78rem;white-space:nowrap;"><?= date('M j, Y',strtotime($b['booking_date'])) ?></td>
                <td>
                  <?php if ($isPendingCancel): ?>
                    <span class="badge-pending_cancellation">⏳ Pending Cancellation</span>
                  <?php else: ?>
                    <span class="badge badge-<?= $bStatus ?>"><?= ucfirst($bStatus) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="actions-cell" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <?php if ($isPendingCancel): ?>
                      <!-- APPROVE CANCELLATION BUTTON -->
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Approve cancellation for Ref <?= htmlspecialchars($b['booking_ref']) ?>?\n\n- Subtracts ₱500 cancellation fee\n- Credits ₱<?= number_format(max(0, $b['grand_total'] - min(500, $b['grand_total']))) ?> refund\n- Voids ticket');">
                        <input type="hidden" name="action" value="approve_cancellation">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <button type="submit" class="admin-action-btn btn-approve-cancel" title="Approve cancellation and process refund">
                          ✓ Approve
                        </button>
                      </form>

                      <!-- REJECT CANCELLATION BUTTON -->
                      <button type="button" class="admin-action-btn btn-reject-cancel btn-open-reject-modal" 
                              data-id="<?= $b['id'] ?>" 
                              data-ref="<?= htmlspecialchars($b['booking_ref']) ?>"
                              data-reason="<?= htmlspecialchars($b['cancellation_reason'] ?? 'User requested cancellation') ?>"
                              title="Reject cancellation request and restore confirmed status">
                        ✕ Reject
                      </button>
                    <?php endif; ?>

                    <a href="booking_detail.php?id=<?= $b['id'] ?>" class="action-btn" style="background:#dbeafe;color:#1d4ed8;text-decoration:none;">View</a>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this booking permanently?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                      <button type="submit" class="action-btn" style="background:#fee2e2;color:#991b1b;">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div style="padding:1rem 1.5rem;">
        <div class="pagination">
          <?php if ($page>1): ?><a class="page-btn" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">← Prev</a><?php endif; ?>
          <?php for ($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
            <a class="page-btn <?= $p===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page<$pages): ?><a class="page-btn" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">Next →</a><?php endif; ?>
          <span style="font-size:.82rem;color:var(--muted);margin-left:auto;">Page <?= $page ?> of <?= $pages ?></span>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- REJECT CANCELLATION MODAL -->
<div class="admin-modal-overlay" id="reject-modal">
  <div class="admin-modal-box">
    <div class="modal-header-box" style="background:#fff1f2; border-bottom:1px solid #fecdd3;">
      <h3 class="modal-title-text" style="color:#9f1239;">Reject Cancellation Request</h3>
      <p style="margin:0; font-size:0.85rem; color:#be123c;">
        Reverting reservation <strong id="rej-ref-disp">AG-REF</strong> back to <strong>CONFIRMED</strong> status.
      </p>
    </div>
    <div class="modal-body-box">
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:0.85rem;">
        <span style="color:#64748b; font-weight:700; display:block; margin-bottom:2px;">User's Cancellation Reason:</span>
        <span id="rej-user-reason" style="color:#0f172a; font-weight:600;">—</span>
      </div>

      <form method="POST" action="bookings.php">
        <input type="hidden" name="action" value="reject_cancellation">
        <input type="hidden" name="booking_id" id="rej-booking-id" value="">

        <div style="margin-bottom:18px;">
          <label for="rej-admin-notes" style="display:block; font-size:0.82rem; font-weight:700; color:#334155; margin-bottom:6px;">
            Admin Resolution / Rejection Note:
          </label>
          <textarea id="rej-admin-notes" name="admin_notes" rows="3" required placeholder="e.g., Non-refundable promotion policy, cancellation window closed, or itinerary already checked in..." style="width:100%; border:1.5px solid #cbd5e1; border-radius:10px; padding:8px 12px; font-family:inherit; font-size:0.88rem; outline:none; resize:none;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" class="filter-btn filter-btn-reset" id="rej-modal-cancel">Cancel</button>
          <button type="submit" class="filter-btn" style="background:#dc2626; color:#fff;">Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  const rejectModal = document.getElementById('reject-modal');
  const rejIdInput  = document.getElementById('rej-booking-id');
  const rejRefDisp  = document.getElementById('rej-ref-disp');
  const rejReason   = document.getElementById('rej-user-reason');
  const rejCancel   = document.getElementById('rej-modal-cancel');

  document.querySelectorAll('.btn-open-reject-modal').forEach(btn => {
    btn.addEventListener('click', () => {
      if (rejIdInput) rejIdInput.value = btn.dataset.id;
      if (rejRefDisp) rejRefDisp.textContent = btn.dataset.ref;
      if (rejReason)  rejReason.textContent = btn.dataset.reason || 'No specific reason provided';
      if (rejectModal) rejectModal.classList.add('is-active');
    });
  });

  rejCancel?.addEventListener('click', () => {
    if (rejectModal) rejectModal.classList.remove('is-active');
  });

  rejectModal?.addEventListener('click', (e) => {
    if (e.target === rejectModal) rejectModal.classList.remove('is-active');
  });
})();
</script>
</body>
</html>
