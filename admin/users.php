<?php
// ============================================================
// AeroGlide — admin/users.php
// User & Staff Management (create / edit / activate / deactivate / safe delete)
// ============================================================
require_once __DIR__ . '/guard.php';
admin_require_full_admin('Access Denied: Staff accounts do not have permission to manage user accounts.');

$db     = ag_db();
$selfId = (int)($_SESSION['user_id'] ?? 0);

$search       = trim($_GET['q'] ?? '');
$roleFilter   = $_GET['role']   ?? '';
$statusFilter = $_GET['status'] ?? '';
$page         = max(1,(int)($_GET['page'] ?? 1));
$perPage      = 20; $offset = ($page-1)*$perPage;

$allowedRoles    = ['user','staff','admin'];
$allowedStatuses = ['active','inactive'];
if (!in_array($roleFilter, $allowedRoles, true))       { $roleFilter = ''; }
if (!in_array($statusFilter, $allowedStatuses, true))  { $statusFilter = ''; }

$formErrors = [];
$formData   = [];
$showCreate = isset($_GET['new']);
$editId     = (int)($_GET['edit'] ?? 0);

// POST actions (admin-only via guard, all writes use prepared statements)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    // ------------------------------------------------------------
    // CREATE user / staff account
    // ------------------------------------------------------------
    if ($action === 'create_user') {
        $showCreate = true;
        $formData = [
            'name'     => trim($_POST['name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'role'     => $_POST['role'] ?? 'user',
            'password' => $_POST['password'] ?? '',
        ];
        if (!in_array($formData['role'], ['user','staff'], true)) {
            $formData['role'] = 'user';
        }

        $formErrors = array_values(array_filter([
            validateRequired($formData['name'], 'Full name'),
            val_username($formData['username']),
            val_email($formData['email']),
            val_password($formData['password']),
        ]));
        if (mb_strlen($formData['name']) > 120) {
            $formErrors[] = 'Full name must not exceed 120 characters.';
        }

        if (!$formErrors) {
            $chk = $db->prepare("SELECT id FROM users WHERE username=:u OR email=:e LIMIT 1");
            $chk->execute([':u'=>$formData['username'], ':e'=>$formData['email']]);
            if ($chk->fetch()) {
                $formErrors[] = 'That username or email address is already registered.';
            }
        }

        if (!$formErrors) {
            $hash = password_hash($formData['password'], PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO users (username,name,email,password,role,status) VALUES (:u,:n,:e,:p,:r,'active')");
            $ins->execute([
                ':u'=>$formData['username'], ':n'=>$formData['name'],
                ':e'=>$formData['email'],   ':p'=>$hash,
                ':r'=>$formData['role'],
            ]);
            $newId = (int)$db->lastInsertId();
            admin_log("Created {$formData['role']} account #{$newId} ({$formData['username']})", "user:{$newId}");
            header('Location: users.php?saved=1&msg=' . urlencode('Account created successfully.'));
            exit;
        }
    }

    // ------------------------------------------------------------
    // UPDATE existing account
    // ------------------------------------------------------------
    if ($action === 'update_user' && $uid) {
        $editId = $uid;
        $cur = $db->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
        $cur->execute([':id'=>$uid]);
        $existing = $cur->fetch();

        if (!$existing) {
            $formErrors[] = 'Account not found.';
        } else {
            $formData = [
                'name'     => trim($_POST['name'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'email'    => trim($_POST['email'] ?? ''),
                'role'     => $_POST['role'] ?? $existing['role'],
                'status'   => $_POST['status'] ?? $existing['status'],
                'password' => $_POST['password'] ?? '',
            ];
            if (!in_array($formData['role'], $allowedRoles, true)) {
                $formData['role'] = $existing['role'];
            }
            if (!in_array($formData['status'], $allowedStatuses, true)) {
                $formData['status'] = in_array($existing['status'], $allowedStatuses, true) ? $existing['status'] : 'active';
            }

            // Self-protection: an administrator can never demote or lock themselves out.
            if ($uid === $selfId) {
                if ($formData['role'] !== 'admin') {
                    $formErrors[] = 'You cannot change your own administrator role.';
                    $formData['role'] = 'admin';
                }
                if ($formData['status'] !== 'active') {
                    $formErrors[] = 'You cannot deactivate your own account.';
                    $formData['status'] = 'active';
                }
            }

            // Administrator accounts must stay active.
            if ($existing['role'] === 'admin' && $formData['status'] !== 'active') {
                $formErrors[] = 'Administrator accounts cannot be deactivated.';
                $formData['status'] = 'active';
            }

            $formErrors = array_merge($formErrors, array_values(array_filter([
                validateRequired($formData['name'], 'Full name'),
                val_username($formData['username']),
                val_email($formData['email']),
                $formData['password'] !== '' ? val_password($formData['password']) : null,
            ])));
            if (mb_strlen($formData['name']) > 120) {
                $formErrors[] = 'Full name must not exceed 120 characters.';
            }

            if (!$formErrors) {
                $chk = $db->prepare("SELECT id FROM users WHERE (username=:u OR email=:e) AND id<>:id LIMIT 1");
                $chk->execute([':u'=>$formData['username'], ':e'=>$formData['email'], ':id'=>$uid]);
                if ($chk->fetch()) {
                    $formErrors[] = 'That username or email address is already used by another account.';
                }
            }

            if (!$formErrors) {
                $sql    = "UPDATE users SET username=:u, name=:n, email=:e, role=:r, status=:s";
                $params = [
                    ':u'=>$formData['username'], ':n'=>$formData['name'],
                    ':e'=>$formData['email'],    ':r'=>$formData['role'],
                    ':s'=>$formData['status'],   ':id'=>$uid,
                ];
                if ($formData['password'] !== '') {
                    $sql .= ", password=:p";
                    $params[':p'] = password_hash($formData['password'], PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id=:id";
                $db->prepare($sql)->execute($params);
                admin_log("Updated account #{$uid} ({$formData['username']})", "user:{$uid}");
                header('Location: users.php?saved=1&msg=' . urlencode('Account updated successfully.'));
                exit;
            }
        }
    }

    // ------------------------------------------------------------
    // ACTIVATE / DEACTIVATE
    // ------------------------------------------------------------
    if (($action === 'activate' || $action === 'deactivate') && $uid) {
        $cur = $db->prepare("SELECT role FROM users WHERE id=:id LIMIT 1");
        $cur->execute([':id'=>$uid]);
        $targetRole = $cur->fetchColumn();

        if ($targetRole === false) {
            header('Location: users.php?err=' . urlencode('Account not found.'));
            exit;
        }
        if ($uid === $selfId) {
            header('Location: users.php?err=' . urlencode('You cannot change the status of your own account.'));
            exit;
        }
        if ($targetRole === 'admin') {
            header('Location: users.php?err=' . urlencode('Administrator accounts cannot be deactivated from this screen.'));
            exit;
        }

        $newStatus = $action === 'activate' ? 'active' : 'inactive';
        $db->prepare("UPDATE users SET status=:s WHERE id=:id")->execute([':s'=>$newStatus, ':id'=>$uid]);
        $label = $newStatus === 'active' ? 'Activated' : 'Deactivated';
        admin_log("{$label} account #{$uid}", "user:{$uid}");
        header('Location: users.php?saved=1&msg=' . urlencode("Account {$newStatus}."));
        exit;
    }

    // ------------------------------------------------------------
    // LEGACY QUICK ROLE TOGGLE (kept from the original UI)
    // ------------------------------------------------------------
    if ($action === 'toggle_role' && $uid) {
        if ($uid === $selfId) {
            header('Location: users.php?err=' . urlencode('You cannot change your own administrator role.'));
            exit;
        }
        $cur = $db->prepare("SELECT role FROM users WHERE id=:id LIMIT 1");
        $cur->execute([':id'=>$uid]);
        $curRole = $cur->fetchColumn();
        if ($curRole === false) {
            header('Location: users.php?err=' . urlencode('Account not found.'));
            exit;
        }
        $newRole = match($curRole) {
            'user'  => 'staff',
            'staff' => 'admin',
            default => 'user',
        };
        $db->prepare("UPDATE users SET role=:r WHERE id=:id")->execute([':r'=>$newRole, ':id'=>$uid]);
        admin_log("Toggled user #{$uid} role to {$newRole}", "user:{$uid}");
        header('Location: users.php?saved=1&msg=' . urlencode("Role changed to {$newRole}."));
        exit;
    }

    // ------------------------------------------------------------
    // RESET COUPON
    // ------------------------------------------------------------
    if ($action === 'reset_coupon' && $uid) {
        $db->prepare("UPDATE users SET used_coupon=NULL WHERE id=:id")->execute([':id'=>$uid]);
        admin_log("Reset coupon for user #{$uid}", "user:{$uid}");
        header('Location: users.php?saved=1&msg=' . urlencode('Coupon usage reset.'));
        exit;
    }

    // ------------------------------------------------------------
    // SAFE DELETE (accounts with booking history are deactivated)
    // ------------------------------------------------------------
    if ($action === 'delete' && $uid) {
        $cur = $db->prepare("SELECT role, username FROM users WHERE id=:id LIMIT 1");
        $cur->execute([':id'=>$uid]);
        $row = $cur->fetch();

        if (!$row) {
            header('Location: users.php?err=' . urlencode('Account not found.'));
            exit;
        }
        if ($uid === $selfId) {
            header('Location: users.php?err=' . urlencode('You cannot delete your own account.'));
            exit;
        }
        if ($row['role'] === 'admin') {
            header('Location: users.php?err=' . urlencode('Administrator accounts cannot be deleted. Deactivate a user or staff account instead.'));
            exit;
        }

        $bc = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=:id");
        $bc->execute([':id'=>$uid]);
        $bookingCount = (int)$bc->fetchColumn();

        if ($bookingCount > 0) {
            // Preserve bookings and their foreign-key relationships.
            $db->prepare("UPDATE users SET status='inactive' WHERE id=:id")->execute([':id'=>$uid]);
            admin_log("Deactivated account #{$uid} instead of deleting (has {$bookingCount} booking(s))", "user:{$uid}");
            header('Location: users.php?saved=1&msg=' . urlencode("This account has {$bookingCount} booking(s), so it was deactivated instead of deleted to preserve records."));
        } else {
            $db->prepare("DELETE FROM users WHERE id=:id")->execute([':id'=>$uid]);
            admin_log("Deleted account #{$uid} ({$row['username']})", "user:{$uid}");
            header('Location: users.php?deleted=1&msg=' . urlencode('Account deleted.'));
        }
        exit;
    }
}

// Load account being edited (GET) and merge failed POST values for redisplay.
$editUser = null;
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
    $stmt->execute([':id'=>$editId]);
    $editUser = $stmt->fetch() ?: null;
    if ($editUser && $formData) {
        $editUser = array_merge($editUser, $formData);
    }
}

// Flash messages
$flashSaved   = isset($_GET['saved']);
$flashDeleted = isset($_GET['deleted']);
$flashMsg     = trim($_GET['msg'] ?? '');
$flashErr     = trim($_GET['err'] ?? '');

// List filters
$where=[]; $params=[];
if ($search!=='') { $where[]="(username LIKE :s OR name LIKE :s OR email LIKE :s)"; $params[':s']="%{$search}%"; }
if ($roleFilter!=='')   { $where[]="role=:role";     $params[':role']=$roleFilter; }
if ($statusFilter!=='') { $where[]="status=:status"; $params[':status']=$statusFilter; }
$wc = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cStmt = $db->prepare("SELECT COUNT(*) FROM users $wc"); $cStmt->execute($params);
$total = (int)$cStmt->fetchColumn(); $pages = max(1,ceil($total/$perPage));

$stmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) AS booking_count FROM users u $wc ORDER BY u.created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT); $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute(); $users = $stmt->fetchAll();

admin_log('Viewed users list');

// Helper for building list links while preserving current filters.
$listQuery = function(array $overrides = []) use ($search, $roleFilter, $statusFilter, $page): string {
    return http_build_query(array_merge([
        'q' => $search, 'role' => $roleFilter, 'status' => $statusFilter, 'page' => $page,
    ], $overrides));
};
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
.badge-active{background:#dcfce7;color:#15803d;}
.badge-inactive{background:#f1f5f9;color:#64748b;border:1px solid var(--line);}
.account-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem;align-items:end;}
.form-field{display:flex;flex-direction:column;gap:.3rem;}
.form-field label{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.form-field input,.form-field select{padding:.62rem .85rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit;font-size:.85rem;color:var(--ink);background:#fff;outline:none;}
.form-field input:focus,.form-field select:focus{border-color:var(--blue);}
.form-actions{grid-column:1/-1;display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.25rem;}
.form-hint{font-size:.7rem;color:var(--muted);}
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Users &amp; Staff</div>
  </header>

  <div class="content">
    <?php if($flashSaved): ?><div class="alert-success">✅ <?= htmlspecialchars($flashMsg !== '' ? $flashMsg : 'Saved successfully.') ?></div><?php endif; ?>
    <?php if($flashDeleted): ?><div class="alert-warning">🗑 <?= htmlspecialchars($flashMsg !== '' ? $flashMsg : 'Account deleted.') ?></div><?php endif; ?>
    <?php if($flashErr !== ''): ?><div class="alert-error">⚠️ <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="page-title">Users &amp; Staff</h1>
        <p class="page-subtitle"><?= number_format($total) ?> account(s) matching your filters</p>
      </div>
      <a href="users.php?new=1" class="filter-btn filter-btn-primary" style="text-decoration:none;">+ New User / Staff</a>
    </div>

    <?php if ($showCreate || $editUser): ?>
    <?php
      $isEdit = (bool)$editUser;
      $v = $isEdit ? $editUser : $formData;
      $v = array_merge(['name'=>'','username'=>'','email'=>'','role'=>'user','status'=>'active','password'=>''], $v);
    ?>
    <div class="section-card" style="padding:1.5rem;margin-bottom:1.5rem;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:1rem;flex-wrap:wrap;">
        <div>
          <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.05rem;">
            <?= $isEdit ? 'Edit account #' . (int)$v['id'] : 'Create user or staff account' ?>
          </div>
          <div style="font-size:.8rem;color:var(--muted);">
            <?= $isEdit ? 'Update account details, role, status, or set a new password.' : 'New accounts can be created as a regular user or staff member.' ?>
          </div>
        </div>
        <a href="users.php" class="filter-btn filter-btn-reset" style="text-decoration:none;">Cancel</a>
      </div>

      <?php if ($formErrors): ?>
        <div class="alert-error">
          <?php foreach ($formErrors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="account-form" autocomplete="off">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update_user' : 'create_user' ?>">
        <?php if ($isEdit): ?><input type="hidden" name="user_id" value="<?= (int)$v['id'] ?>"><?php endif; ?>

        <div class="form-field">
          <label for="f_name">Full name</label>
          <input id="f_name" type="text" name="name" maxlength="120" required value="<?= htmlspecialchars((string)$v['name']) ?>">
        </div>

        <div class="form-field">
          <label for="f_username">Username</label>
          <input id="f_username" type="text" name="username" maxlength="30" required value="<?= htmlspecialchars((string)$v['username']) ?>" placeholder="letters, numbers, underscore">
        </div>

        <div class="form-field">
          <label for="f_email">Email address</label>
          <input id="f_email" type="email" name="email" maxlength="180" required value="<?= htmlspecialchars((string)$v['email']) ?>">
        </div>

        <div class="form-field">
          <label for="f_role">Role</label>
          <select id="f_role" name="role">
            <option value="user"  <?= $v['role']==='user'  ?'selected':'' ?>>User</option>
            <option value="staff" <?= $v['role']==='staff' ?'selected':'' ?>>Staff</option>
            <?php if ($isEdit): ?>
              <option value="admin" <?= $v['role']==='admin' ?'selected':'' ?>>Admin</option>
            <?php endif; ?>
          </select>
        </div>

        <?php if ($isEdit): ?>
        <div class="form-field">
          <label for="f_status">Status</label>
          <select id="f_status" name="status">
            <option value="active"   <?= $v['status']==='active'   ?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $v['status']==='inactive' ?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <?php endif; ?>

        <div class="form-field">
          <label for="f_password"><?= $isEdit ? 'New password (optional)' : 'Password' ?></label>
          <input id="f_password" type="password" name="password" minlength="6" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password" placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'At least 6 characters' ?>">
          <?php if ($isEdit): ?><span class="form-hint">Only fill this in to reset the account password.</span><?php endif; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="filter-btn filter-btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Account' ?></button>
          <a href="users.php" class="filter-btn filter-btn-reset" style="text-decoration:none;">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Search</label>
        <input class="filter-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Username, name, email…">
      </div>
      <div class="filter-group">
        <label class="filter-label">Role</label>
        <select class="filter-select" name="role">
          <option value="">All Roles</option>
          <option value="user"  <?= $roleFilter==='user' ?'selected':'' ?>>User</option>
          <option value="staff" <?= $roleFilter==='staff'?'selected':'' ?>>Staff</option>
          <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select class="filter-select" name="status">
          <option value="">All Statuses</option>
          <option value="active"   <?= $statusFilter==='active'  ?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <button type="submit" class="filter-btn filter-btn-primary">Search</button>
      <a href="users.php" class="filter-btn filter-btn-reset">Reset</a>
    </form>

    <div class="section-card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Bookings</th><th>Coupon Used</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if(empty($users)): ?>
              <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">👥</div><p>No users found.</p></div></td></tr>
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
                <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= ucfirst(htmlspecialchars($u['role'])) ?></span></td>
                <td><span class="badge badge-<?= ($u['status'] ?? 'active')==='active' ? 'active' : 'inactive' ?>"><?= ($u['status'] ?? 'active')==='active' ? 'Active' : 'Inactive' ?></span></td>
                <td style="text-align:center;">
                  <a href="bookings.php?user_id=<?= (int)$u['id'] ?>" style="font-weight:700;color:var(--blue);text-decoration:none;"><?= (int)$u['booking_count'] ?></a>
                </td>
                <td style="font-size:.78rem;">
                  <?php if($u['used_coupon']): ?>
                    <code style="background:#f1f5f9;padding:2px 6px;border-radius:6px;"><?= htmlspecialchars($u['used_coupon']) ?></code>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="reset_coupon">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" style="font-size:.7rem;background:none;border:none;color:var(--red);cursor:pointer;font-weight:700;margin-left:4px;">Reset</button>
                    </form>
                  <?php else: ?><span style="color:var(--muted);">None</span><?php endif; ?>
                </td>
                <td style="color:var(--muted);font-size:.78rem;"><?= date('M j, Y',strtotime($u['created_at'])) ?></td>
                <td>
                  <div class="actions-cell">
                    <a href="users.php?<?= $listQuery(['edit'=>(int)$u['id'], 'new'=>null]) ?>" class="action-btn" style="background:#dbeafe;color:#1d4ed8;text-decoration:none;">Edit</a>
                    <a href="bookings.php?user_id=<?= (int)$u['id'] ?>" class="action-btn" style="background:#e0f2fe;color:#0369a1;text-decoration:none;">View Bookings</a>
                    <?php if ((int)$u['id'] !== $selfId): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_role">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="action-btn" style="background:#f3e8ff;color:#7c3aed;"><?= $u['role']==='admin'?'Set User':($u['role']==='staff'?'Make Admin':'Make Staff') ?></button>
                      </form>
                      <?php if ($u['role'] !== 'admin'): ?>
                        <?php $isActive = ($u['status'] ?? 'active') === 'active'; ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="<?= $isActive ? 'deactivate' : 'activate' ?>">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <button type="submit" class="action-btn" style="background:<?= $isActive ? '#fef3c7' : '#dcfce7' ?>;color:<?= $isActive ? '#92400e' : '#15803d' ?>;"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm(<?= $u['booking_count'] > 0 ? "'This account has bookings and will be deactivated instead of deleted. Continue?'" : "'Delete this account permanently? This cannot be undone.'" ?>);">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                          <button type="submit" class="action-btn" style="background:#fee2e2;color:#991b1b;">Delete</button>
                        </form>
                      <?php endif; ?>
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
          <?php if($page>1): ?><a class="page-btn" href="?<?= $listQuery(['page'=>$page-1]) ?>">← Prev</a><?php endif; ?>
          <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
            <a class="page-btn <?= $p===$page?'active':'' ?>" href="?<?= $listQuery(['page'=>$p]) ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if($page<$pages): ?><a class="page-btn" href="?<?= $listQuery(['page'=>$page+1]) ?>">Next →</a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
