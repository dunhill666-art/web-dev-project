<?php
// ============================================================
// AeroGlide — admin/users.php
// User Management
// ============================================================
require_once __DIR__ . '/guard.php';

$db = ag_db();
$search = trim($_GET['q'] ?? '');
$role   = $_GET['role']   ?? '';
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage = 20; $offset = ($page-1)*$perPage;

// POST actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($_POST['action']==='toggle_role' && $uid) {
        $cur = $db->prepare("SELECT role FROM users WHERE id=:id")->execute([':id'=>$uid]) ? $db->prepare("SELECT role FROM users WHERE id=:id") : null;
        $cur = $db->prepare("SELECT role FROM users WHERE id=:id"); $cur->execute([':id'=>$uid]);
        $curRole = $cur->fetchColumn();
        $newRole = $curRole==='admin' ? 'user' : 'admin';
        $db->prepare("UPDATE users SET role=:r WHERE id=:id")->execute([':r'=>$newRole,':id'=>$uid]);
        admin_log("Toggled user #{$uid} role to {$newRole}","user:{$uid}");
        header('Location: users.php?saved=1'); exit;
    }
    if ($_POST['action']==='reset_coupon' && $uid) {
        $db->prepare("UPDATE users SET used_coupon=NULL WHERE id=:id")->execute([':id'=>$uid]);
        admin_log("Reset coupon for user #{$uid}","user:{$uid}");
        header('Location: users.php?saved=1'); exit;
    }
    if ($_POST['action']==='delete' && $uid) {
        $db->prepare("DELETE FROM users WHERE id=:id AND role!='admin'")->execute([':id'=>$uid]);
        admin_log("Deleted user #{$uid}","user:{$uid}");
        header('Location: users.php?deleted=1'); exit;
    }
}

$where=[]; $params=[];
if ($search!=='') { $where[]="(username LIKE :s OR name LIKE :s OR email LIKE :s)"; $params[':s']="%{$search}%"; }
if ($role!=='')   { $where[]="role=:role"; $params[':role']=$role; }
$wc = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cStmt = $db->prepare("SELECT COUNT(*) FROM users $wc"); $cStmt->execute($params);
$total = (int)$cStmt->fetchColumn(); $pages = max(1,ceil($total/$perPage));

$stmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) AS booking_count FROM users u $wc ORDER BY u.created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT); $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute(); $users = $stmt->fetchAll();

admin_log('Viewed users list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — AeroGlide Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
<style>
.filter-bar{display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;}
.filter-group{display:flex;flex-direction:column;gap:.25rem;}
.filter-label{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.filter-input,.filter-select{padding:.6rem .85rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit;font-size:.85rem;color:var(--ink);background:#fff;outline:none;}
.filter-input{min-width:240px;}
.filter-btn{padding:.65rem 1.25rem;border-radius:10px;border:none;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;}
.filter-btn-primary{background:var(--blue);color:#fff;}
.filter-btn-reset{background:#f1f5f9;color:var(--ink);text-decoration:none;display:inline-flex;align-items:center;}
.pagination{display:flex;gap:.5rem;align-items:center;}
.page-btn{padding:.5rem .9rem;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;color:var(--ink);background:#f1f5f9;border:1.5px solid var(--line);}
.page-btn.active{background:var(--blue);color:#fff;border-color:var(--blue);}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Users</div>
  </header>

  <div class="content">
    <?php if(isset($_GET['saved'])): ?><div class="alert-success">✅ User updated.</div><?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?><div class="alert-warning">🗑 User deleted.</div><?php endif; ?>

    <div class="page-header">
      <h1 class="page-title">Users</h1>
      <p class="page-subtitle"><?= number_format($total) ?> account(s) registered</p>
    </div>

    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Search</label>
        <input class="filter-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Username, name, email…">
      </div>
      <div class="filter-group">
        <label class="filter-label">Role</label>
        <select class="filter-select" name="role">
          <option value="">All Roles</option>
          <option value="user"  <?= $role==='user' ?'selected':'' ?>>User</option>
          <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <button type="submit" class="filter-btn filter-btn-primary">Search</button>
      <a href="users.php" class="filter-btn filter-btn-reset">Reset</a>
    </form>

    <div class="section-card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Bookings</th><th>Coupon Used</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if(empty($users)): ?>
              <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">👥</div><p>No users found.</p></div></td></tr>
            <?php else: ?>
              <?php foreach($users as $i=>$u): ?>
              <tr>
                <td style="color:var(--muted);font-size:.75rem;"><?= $offset+$i+1 ?></td>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                    <div>
                      <div style="font-weight:700;font-size:.83rem;"><?= htmlspecialchars($u['name']) ?></div>
                      <div style="font-size:.72rem;color:var(--muted);">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td style="text-align:center;">
                  <a href="bookings.php?q=<?= urlencode($u['username']) ?>" style="font-weight:700;color:var(--blue);text-decoration:none;"><?= $u['booking_count'] ?></a>
                </td>
                <td style="font-size:.78rem;">
                  <?php if($u['used_coupon']): ?>
                    <code style="background:#f1f5f9;padding:2px 6px;border-radius:6px;"><?= htmlspecialchars($u['used_coupon']) ?></code>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="reset_coupon">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" style="font-size:.7rem;background:none;border:none;color:var(--red);cursor:pointer;font-weight:700;margin-left:4px;">Reset</button>
                    </form>
                  <?php else: ?><span style="color:var(--muted);">None</span><?php endif; ?>
                </td>
                <td style="color:var(--muted);font-size:.78rem;"><?= date('M j, Y',strtotime($u['created_at'])) ?></td>
                <td>
                  <div class="actions-cell">
                    <a href="bookings.php?user_id=<?= $u['id'] ?>" class="action-btn" style="background:#e0f2fe;color:#0369a1;text-decoration:none;">View Bookings</a>
                    <?php if ($u['role'] !== 'admin' || $u['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_role">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="action-btn" style="background:#f3e8ff;color:#7c3aed;"><?= $u['role']==='admin'?'Demote':'Promote' ?></button>
                    </form>
                    <?php endif; ?>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user and all their bookings?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="action-btn" style="background:#fee2e2;color:#991b1b;">Delete</button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if($pages>1): ?>
      <div style="padding:1rem 1.5rem;">
        <div class="pagination">
          <?php if($page>1): ?><a class="page-btn" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">← Prev</a><?php endif; ?>
          <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
            <a class="page-btn <?= $p===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if($page<$pages): ?><a class="page-btn" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">Next →</a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
