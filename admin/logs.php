<?php
// ============================================================
// AeroGlide — admin/logs.php
// Admin Activity Log Viewer
// ============================================================
require_once __DIR__ . '/guard.php';

$db = ag_db();
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 30; $offset = ($page-1)*$perPage;

$total = (int)$db->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
$pages = max(1,ceil($total/$perPage));

$logs = $db->prepare("
    SELECT l.*, u.username, u.name AS admin_name
    FROM admin_logs l JOIN users u ON u.id=l.admin_id
    ORDER BY l.created_at DESC
    LIMIT :lim OFFSET :off
");
$logs->bindValue(':lim',$perPage,PDO::PARAM_INT);
$logs->bindValue(':off',$offset,PDO::PARAM_INT);
$logs->execute();
$logs = $logs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs — AeroGlide Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.pagination{display:flex;gap:.5rem;align-items:center;}
.page-btn{padding:.5rem .9rem;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;color:var(--ink);background:#f1f5f9;border:1.5px solid var(--line);}
.page-btn.active{background:var(--blue);color:#fff;border-color:var(--blue);}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Activity Logs</div>
  </header>

  <div class="content">
    <div class="page-header">
      <h1 class="page-title">Activity Logs</h1>
      <p class="page-subtitle"><?= number_format($total) ?> admin actions recorded.</p>
    </div>

    <div class="section-card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th></tr>
          </thead>
          <tbody>
            <?php if(empty($logs)): ?>
              <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📋</div><p>No activity logged yet.</p></div></td></tr>
            <?php else: ?>
              <?php foreach($logs as $l): ?>
              <tr>
                <td style="color:var(--muted);font-size:.78rem;white-space:nowrap;"><?= date('M j, Y g:i A',strtotime($l['created_at'])) ?></td>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-sm"><?= strtoupper(substr($l['admin_name'],0,1)) ?></div>
                    <div>
                      <div style="font-weight:700;font-size:.82rem;"><?= htmlspecialchars($l['admin_name']) ?></div>
                      <div style="font-size:.72rem;color:var(--muted);">@<?= htmlspecialchars($l['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($l['action']) ?></td>
                <td style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($l['target'] ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if($pages>1): ?>
      <div style="padding:1rem 1.5rem;">
        <div class="pagination">
          <?php if($page>1): ?><a class="page-btn" href="?page=<?= $page-1 ?>">← Prev</a><?php endif; ?>
          <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
            <a class="page-btn <?= $p===$page?'active':'' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if($page<$pages): ?><a class="page-btn" href="?page=<?= $page+1 ?>">Next →</a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
