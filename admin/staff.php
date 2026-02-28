<?php
require_once __DIR__ . '/auth.php';
require_manager(); 

require_once __DIR__ . '/layout.php';

$errors  = [];
$success = '';

/* ── DELETE STAFF ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // Can only delete staff rows
    $chk = mysqli_prepare($conn,
        "SELECT id FROM users WHERE id = ? AND role = 'staff'");
    mysqli_stmt_bind_param($chk, "i", $del_id);
    mysqli_stmt_execute($chk);
    if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
        $ds = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($ds, "i", $del_id);
        mysqli_stmt_execute($ds);
        $success = "Staff account deleted.";
    } else {
        $errors[] = "Staff member not found or you don't have permission.";
    }
}

/* ── ADD STAFF ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $new_name  = trim($_POST['name']     ?? '');
    $new_email = trim($_POST['email']    ?? '');
    $new_pass  = trim($_POST['password'] ?? '');

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
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'staff')");
            mysqli_stmt_bind_param($ins, "sss", $new_name, $new_email, $hash);
            mysqli_stmt_execute($ins);
            $success = "Staff account for \"{$new_name}\" created successfully.";

            // Clear POST fields
            $_POST = [];
        }
    }
}

/* ── FETCH STAFF ── */
$staff_list = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, name, email, created_at
     FROM users
     WHERE role = 'staff'
     ORDER BY created_at ASC"), MYSQLI_ASSOC);

adminHead('Manage Staff', 'staff');
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

  <!-- ── STAFF LIST ── -->
  <div class="admin-panel">
    <div class="admin-panel-head">
      <h2 class="admin-panel-title">Staff Members</h2>
      <span class="admin-result-count"><?= count($staff_list) ?> member<?= count($staff_list) !== 1 ? 's' : '' ?></span>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staff_list as $s): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="admin-cust-avatar" style="background:rgba(79,142,247,0.1);color:#4f8ef7;">
                  <?= strtoupper(substr($s['name'],0,1)) ?>
                </div>
                <div>
                  <?= htmlspecialchars($s['name']) ?>
                </div>
              </div>
            </td>
            <td class="admin-muted"><?= htmlspecialchars($s['email']) ?></td>
            <td class="admin-date"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td>
              <a href="staff.php?delete=<?= $s['id'] ?>"
                 class="admin-row-action admin-row-action--danger"
                 onclick="return confirm('Remove staff access for \'<?= htmlspecialchars(addslashes($s['name'])) ?>\'?')">
                Remove
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($staff_list)): ?>
          <tr><td colspan="4" class="admin-empty-row">No staff accounts found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── ADD STAFF FORM ── -->
  <div class="admin-panel">
    <div class="admin-panel-head">
      <h2 class="admin-panel-title">Add New Staff</h2>
    </div>
    <form method="POST" action="staff.php" class="admin-form" style="gap:16px;">

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
          <input type="password" name="password" id="newStaffPass" class="admin-input"
                 placeholder="Secure password" style="width:100%;padding-right:42px;" required minlength="8">
          <button type="button" class="admin-pw-toggle" onclick="togglePw()" aria-label="Show password">
            <svg id="eyeIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="admin-form-group" style="background:rgba(255,255,255,0.03);padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,0.05);">
        <span style="font-size:12px;color:var(--dim);line-height:1.4;display:block;">
          <strong style="color:var(--white);">Staff permissions:</strong><br>
          Can manage products, categories, and view orders. Cannot manage customers or administration settings.
        </span>
      </div>

      <div class="admin-form-actions" style="padding-top:4px;">
        <button type="submit" name="add_staff" class="admin-btn admin-btn--primary" style="width:100%;">
          Create Staff Account
        </button>
      </div>
    </form>
  </div>

</div><!-- /grid -->

<script>
function togglePw() {
  var inp  = document.getElementById('newStaffPass');
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
