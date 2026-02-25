<?php
require_once __DIR__ . '/auth.php';
require_manager(); // Staff cannot access this page

// Only superadmin can access this page
if (!IS_SUPERADMIN) {
    header("Location: /melody-masters-online-store/admin/dashboard.php");
    exit;
}

require_once __DIR__ . '/layout.php';

$errors  = [];
$success = '';

/* ── DELETE ADMIN ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // Cannot delete yourself
    if ($del_id === (int)$_SESSION['user_id']) {
        $errors[] = "You cannot delete your own account.";
    } else {
        // Can only delete admin/superadmin rows (not customers)
        $chk = mysqli_prepare($conn,
            "SELECT id FROM users WHERE id = ? AND role IN ('admin','superadmin')");
        mysqli_stmt_bind_param($chk, "i", $del_id);
        mysqli_stmt_execute($chk);
        if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
            $ds = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($ds, "i", $del_id);
            mysqli_stmt_execute($ds);
            $success = "Admin account deleted.";
        } else {
            $errors[] = "Admin not found or you don't have permission.";
        }
    }
}

/* ── ADD ADMIN ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $new_name  = trim($_POST['name']     ?? '');
    $new_email = trim($_POST['email']    ?? '');
    $new_pass  = trim($_POST['password'] ?? '');
    $new_role  = in_array($_POST['role'] ?? '', ['admin','superadmin','staff']) ? $_POST['role'] : 'admin';

    if (!$new_name)                        $errors[] = "Name is required.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (strlen($new_pass) < 8)             $errors[] = "Password must be at least 8 characters.";

    if (empty($errors)) {
        // Check email not already taken
        $ec = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($ec, "s", $new_email);
        mysqli_stmt_execute($ec);
        if (mysqli_num_rows(mysqli_stmt_get_result($ec)) > 0) {
            $errors[] = "That email address is already registered.";
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $ins  = mysqli_prepare($conn,
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "ssss", $new_name, $new_email, $hash, $new_role);
            mysqli_stmt_execute($ins);
            $success = "Admin account for \"{$new_name}\" created successfully.";

            // Clear POST fields
            $_POST = [];
        }
    }
}

/* ── FETCH ADMINS ── */
$admins = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, name, email, role, created_at
     FROM users
     WHERE role IN ('admin','superadmin')
     ORDER BY role DESC, created_at ASC"), MYSQLI_ASSOC);

adminHead('Manage Admins', 'users');
?>

<!-- Flash -->
<?php if ($success): ?>
<div class="admin-flash admin-flash--ok">✓ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
<div class="admin-flash admin-flash--err">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

  <!-- ── ADMIN LIST ── -->
  <div class="admin-panel">
    <div class="admin-panel-head">
      <h2 class="admin-panel-title">Admin Accounts</h2>
      <span class="admin-result-count"><?= count($admins) ?> admin<?= count($admins) !== 1 ? 's' : '' ?></span>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $a):
            $is_self = ((int)$a['id'] === (int)$_SESSION['user_id']);
            $is_sa   = ($a['role'] === 'superadmin');
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="admin-cust-avatar" style="<?= $is_sa ? 'background:rgba(200,151,60,0.18);color:#c8973c;' : '' ?>">
                  <?= strtoupper(substr($a['name'],0,1)) ?>
                </div>
                <div>
                  <?= htmlspecialchars($a['name']) ?>
                  <?php if ($is_self): ?>
                  <span style="font-size:10px;color:var(--muted);margin-left:6px;">(you)</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td class="admin-muted"><?= htmlspecialchars($a['email']) ?></td>
            <td>
              <?php if ($is_sa): ?>
              <span class="admin-role-badge admin-role-badge--super">Super Admin</span>
              <?php else: ?>
              <span class="admin-role-badge admin-role-badge--admin">Admin</span>
              <?php endif; ?>
            </td>
            <td class="admin-date"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
            <td>
              <?php if (!$is_self): ?>
              <a href="users.php?delete=<?= $a['id'] ?>"
                 class="admin-row-action admin-row-action--danger"
                 onclick="return confirm('Remove admin access for \'<?= htmlspecialchars(addslashes($a['name'])) ?>\'?')">
                Remove
              </a>
              <?php else: ?>
              <span style="font-size:12px;color:var(--dim);">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($admins)): ?>
          <tr><td colspan="5" class="admin-empty-row">No admin accounts found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── ADD ADMIN FORM ── -->
  <div class="admin-panel">
    <div class="admin-panel-head">
      <h2 class="admin-panel-title">Add New Admin</h2>
    </div>
    <form method="POST" action="users.php" class="admin-form" style="gap:16px;">

      <div class="admin-form-group">
        <label class="admin-form-label">Full Name *</label>
        <input type="text" name="name" class="admin-input"
               placeholder="Jane Smith" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label">Email Address *</label>
        <input type="email" name="email" class="admin-input"
               placeholder="jane@melodymasters.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label">Password * <span style="color:var(--dim);font-weight:400;">(min 8 chars)</span></label>
        <div style="position:relative;">
          <input type="password" name="password" id="newAdminPass" class="admin-input"
                 placeholder="Secure password" style="width:100%;padding-right:42px;" required minlength="8">
          <button type="button" class="admin-pw-toggle" onclick="togglePw()" aria-label="Show password">
            <svg id="eyeIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label">Role</label>
        <select name="role" class="admin-select">
          <option value="staff"    <?= ($_POST['role']??'staff')==='staff'?'selected':'' ?>>Staff</option>
          <option value="admin"    <?= ($_POST['role']??'')==='admin'?'selected':'' ?>>Admin</option>
          <option value="superadmin" <?= ($_POST['role']??'')==='superadmin'?'selected':'' ?>>Super Admin</option>
        </select>
        <span style="font-size:11px;color:var(--dim);margin-top:4px;">
          Staff: products &amp; orders only. Admin: full access. Super Admin: can manage admins.
        </span>
      </div>

      <div class="admin-form-actions" style="padding-top:4px;">
        <button type="submit" name="add_admin" class="admin-btn admin-btn--primary" style="width:100%;">
          Create Admin Account
        </button>
      </div>
    </form>
  </div>

</div><!-- /grid -->

<script>
function togglePw() {
  var inp  = document.getElementById('newAdminPass');
  var icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>

<?php adminFoot(); ?>
