<?php
include_once __DIR__ . '/../includes/init.php';
include_once __DIR__ . '/../includes/db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$uid    = (int)$_SESSION['user_id'];
$errors = [];
$success = '';
$active_tab = $_GET['tab'] ?? 'profile';

/* ============================================================
   FETCH USER
============================================================ */
$u_stmt = mysqli_prepare($conn, "SELECT id, name, email, profile_image, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $uid);
mysqli_stmt_execute($u_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($u_stmt));

if (!$user) { header("Location: ../logout.php"); exit; }

/* ============================================================
   FETCH STATS
============================================================ */
// Total orders + spent
$stats_stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS total_spent
     FROM orders WHERE user_id = ?"
);
mysqli_stmt_bind_param($stats_stmt, "i", $uid);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

// Total reviews written
$rev_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM reviews WHERE user_id = ?");
mysqli_stmt_bind_param($rev_stmt, "i", $uid);
mysqli_stmt_execute($rev_stmt);
$rev_count = mysqli_fetch_assoc(mysqli_stmt_get_result($rev_stmt))['cnt'];

// Recent 5 orders
$recent_stmt = mysqli_prepare($conn,
    "SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
);
mysqli_stmt_bind_param($recent_stmt, "i", $uid);
mysqli_stmt_execute($recent_stmt);
$recent_orders = mysqli_fetch_all(mysqli_stmt_get_result($recent_stmt), MYSQLI_ASSOC);

/* ============================================================
   HANDLE PROFILE UPDATE
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $active_tab = 'profile';
    $new_name  = trim($_POST['name']  ?? '');
    $new_email = trim($_POST['email'] ?? '');

    if (strlen($new_name) < 2)                         $errors[] = "Name must be at least 2 characters.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";

    // Check email not taken by another user
    // Check email not taken by another user
    if (empty($errors)) {
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($chk, "si", $new_email, $uid);
        mysqli_stmt_execute($chk);
        if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0)
            $errors[] = "That email address is already registered to another account.";
    }

    // Handle profile image upload
    $new_profile_image = $user['profile_image'];
    if (empty($errors) && !empty($_FILES['profile_image']['name'])) {
        $ext      = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Profile image must be JPG, PNG, WebP or GIF.";
        } else {
            $img_filename = 'u_' . $uid . '_' . uniqid() . '.' . $ext;
            $dest_dir = __DIR__ . '/../assets/images/profiles/';
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
            $dest = $dest_dir . $img_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                // Delete old image if it exists
                if ($user['profile_image']) {
                    @unlink($dest_dir . $user['profile_image']);
                }
                $new_profile_image = $img_filename;
            } else {
                $errors[] = "Failed to upload profile image.";
            }
        }
    }

    if (empty($errors)) {
        $upd = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "sssi", $new_name, $new_email, $new_profile_image, $uid);
        mysqli_stmt_execute($upd);
        $success = "Profile updated successfully.";
        // Refresh user data
        $user['name']  = $new_name;
        $user['email'] = $new_email;
        $user['profile_image'] = $new_profile_image;
    }
}

/* ============================================================
   HANDLE PASSWORD CHANGE
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $active_tab   = 'security';
    $current_pw   = $_POST['current_password'] ?? '';
    $new_pw       = $_POST['new_password']      ?? '';
    $confirm_pw   = $_POST['confirm_password']  ?? '';

    // Fetch stored hash
    $pw_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($pw_stmt, "i", $uid);
    mysqli_stmt_execute($pw_stmt);
    $stored_hash = mysqli_fetch_assoc(mysqli_stmt_get_result($pw_stmt))['password'];

    if (!password_verify($current_pw, $stored_hash)) $errors[] = "Current password is incorrect.";
    if (strlen($new_pw) < 8)                          $errors[] = "New password must be at least 8 characters.";
    if ($new_pw !== $confirm_pw)                      $errors[] = "New passwords do not match.";

    if (empty($errors)) {
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $pw_upd   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($pw_upd, "si", $new_hash, $uid);
        mysqli_stmt_execute($pw_upd);
        $success = "Password changed successfully.";
    }
}

/* helpers */
$initials = '';
$parts    = explode(' ', trim($user['name']));
$initials = strtoupper(substr($parts[0], 0, 1));
if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));

function acct_badge(string $s): string {
    $m = [
        'Pending'    => 'badge-pending',
        'Processing' => 'badge-processing',
        'Shipped'    => 'badge-shipped',
        'Delivered'  => 'badge-delivered',
        'Cancelled'  => 'badge-cancelled',
    ];
    return '<span class="order-badge ' . ($m[$s] ?? 'badge-pending') . '">' . htmlspecialchars($s) . '</span>';
}

$member_since = date('F Y', strtotime($user['created_at']));
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">
  <div class="container">

    <!-- ── HERO BANNER ── -->
    <div class="acct-hero">
      <div class="acct-avatar-wrap">
        <?php if ($user['profile_image']): ?>
          <img src="../assets/images/profiles/<?= htmlspecialchars($user['profile_image']) ?>" class="acct-avatar-img" alt="">
        <?php else: ?>
          <div class="acct-avatar"><?= htmlspecialchars($initials) ?></div>
        <?php endif; ?>
      </div>
      <div class="acct-hero-info">
        <h1 class="acct-hero-name"><?= htmlspecialchars($user['name']) ?></h1>
        <p class="acct-hero-meta">
          <?= htmlspecialchars($user['email']) ?>
          <span class="acct-dot">·</span>
          Member since <?= $member_since ?>
          <span class="acct-dot">·</span>
          <span class="acct-role-pill"><?= ucfirst($user['role']) ?></span>
        </p>
      </div>
    </div>

    <!-- ── STATS ROW ── -->
    <div class="acct-stats">
      <div class="acct-stat">
        <span class="acct-stat-value"><?= (int)$stats['total_orders'] ?></span>
        <span class="acct-stat-label">Orders Placed</span>
      </div>
      <div class="acct-stat">
        <span class="acct-stat-value">£<?= number_format((float)$stats['total_spent'], 2) ?></span>
        <span class="acct-stat-label">Total Spent</span>
      </div>
      <div class="acct-stat">
        <span class="acct-stat-value"><?= (int)$rev_count ?></span>
        <span class="acct-stat-label">Reviews Written</span>
      </div>
    </div>

    <!-- ── TWO COLUMN ── -->
    <div class="acct-layout">

      <!-- LEFT: Tab nav -->
      <nav class="acct-nav">
        <a href="?tab=profile"  class="acct-nav-item <?= $active_tab === 'profile'  ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Profile Details
        </a>
        <a href="?tab=security" class="acct-nav-item <?= $active_tab === 'security' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Password & Security
        </a>
        <a href="?tab=orders"   class="acct-nav-item <?= $active_tab === 'orders'   ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Recent Orders
        </a>
        <div class="acct-nav-divider"></div>
        <a href="../logout.php" class="acct-nav-item acct-nav-item--danger">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </a>
      </nav>

      <!-- RIGHT: Tab content -->
      <div class="acct-content">

        <!-- Flash messages -->
        <?php if (!empty($errors)): ?>
        <div class="acct-alert acct-alert--error">
          <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="acct-alert acct-alert--success">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- ════ PROFILE TAB ════ -->
        <?php if ($active_tab === 'profile'): ?>
        <div class="acct-card">
          <h2 class="acct-card-title">Profile Details</h2>
          <p class="acct-card-sub">Update your display name and email address.</p>

          <form method="POST" action="?tab=profile" enctype="multipart/form-data" class="acct-form">
            <div class="acct-form-group">
              <label>Profile Picture</label>
              <div class="acct-profile-upload">
                <?php if ($user['profile_image']): ?>
                  <img src="../assets/images/profiles/<?= htmlspecialchars($user['profile_image']) ?>" class="acct-profile-preview" id="profilePreview">
                <?php else: ?>
                  <div class="acct-profile-initials" id="profileInitials"><?= htmlspecialchars($initials) ?></div>
                <?php endif; ?>
                <div class="acct-upload-controls">
                  <label for="profile_image" class="btn-ghost" style="cursor:pointer;font-size:12px;padding:8px 12px;">Change Photo</label>
                  <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                  <p class="acct-form-hint">JPG, PNG or WebP. Max 2MB.</p>
                </div>
              </div>
            </div>
            <div class="acct-form-group">
              <label for="acct_name">Full Name</label>
              <input type="text" id="acct_name" name="name"
                     value="<?= htmlspecialchars($user['name']) ?>"
                     placeholder="Your full name" required>
            </div>
            <div class="acct-form-group">
              <label for="acct_email">Email Address</label>
              <input type="email" id="acct_email" name="email"
                     value="<?= htmlspecialchars($user['email']) ?>"
                     placeholder="you@example.com" required>
            </div>
            <div class="acct-form-group acct-form-group--readonly">
              <label>Account Role</label>
              <div class="acct-readonly-field">
                <span class="acct-role-pill"><?= ucfirst($user['role']) ?></span>
                <span class="acct-readonly-hint">Role can only be changed by an admin</span>
              </div>
            </div>
            <div class="acct-form-group acct-form-group--readonly">
              <label>Member Since</label>
              <div class="acct-readonly-field"><?= $member_since ?></div>
            </div>
            <div class="acct-form-actions">
              <button type="submit" name="update_profile" class="btn-primary acct-save-btn">
                Save Changes
              </button>
            </div>
          </form>
        </div>

        <!-- ════ SECURITY TAB ════ -->
        <?php elseif ($active_tab === 'security'): ?>
        <div class="acct-card">
          <h2 class="acct-card-title">Password & Security</h2>
          <p class="acct-card-sub">Choose a strong password you don't use elsewhere.</p>

          <form method="POST" action="?tab=security" class="acct-form">
            <div class="acct-form-group">
              <label for="current_password">Current Password</label>
              <div class="acct-pw-wrap">
                <input type="password" id="current_password" name="current_password"
                       placeholder="Enter your current password" autocomplete="current-password" required>
                <button type="button" class="acct-pw-toggle" data-target="current_password" aria-label="Show password">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>
            <div class="acct-form-group">
              <label for="new_password">New Password</label>
              <div class="acct-pw-wrap">
                <input type="password" id="new_password" name="new_password"
                       placeholder="At least 8 characters" autocomplete="new-password" required>
                <button type="button" class="acct-pw-toggle" data-target="new_password" aria-label="Show password">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <!-- Strength meter -->
              <div class="pw-strength-wrap" id="pwStrengthWrap" style="display:none;">
                <div class="pw-strength-bar"><div class="pw-strength-fill" id="pwFill"></div></div>
                <span class="pw-strength-label" id="pwLabel"></span>
              </div>
            </div>
            <div class="acct-form-group">
              <label for="confirm_password">Confirm New Password</label>
              <div class="acct-pw-wrap">
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Repeat new password" autocomplete="new-password" required>
                <button type="button" class="acct-pw-toggle" data-target="confirm_password" aria-label="Show password">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <!-- Security tips -->
            <div class="acct-security-tips">
              <p class="acct-tip"><span class="tip-dot tip-dot--ok">✓</span> At least 8 characters</p>
              <p class="acct-tip"><span class="tip-dot tip-dot--ok">✓</span> Mix of upper &amp; lowercase letters</p>
              <p class="acct-tip"><span class="tip-dot tip-dot--ok">✓</span> Include numbers or symbols</p>
            </div>

            <div class="acct-form-actions">
              <button type="submit" name="change_password" class="btn-primary acct-save-btn">
                Update Password
              </button>
            </div>
          </form>
        </div>

        <!-- ════ ORDERS TAB ════ -->
        <?php elseif ($active_tab === 'orders'): ?>
        <div class="acct-card">
          <div class="acct-card-title-row">
            <h2 class="acct-card-title" style="margin-bottom:0;">Recent Orders</h2>
            <a href="orders.php" class="acct-view-all">View all →</a>
          </div>

          <?php if (empty($recent_orders)): ?>
          <div class="acct-empty-orders">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            <p>You haven't placed any orders yet.</p>
            <a href="../shop.php" class="btn-primary" style="margin-top:8px;font-size:13px;padding:10px 20px;">Start Shopping</a>
          </div>
          <?php else: ?>
          <div class="acct-orders-table-wrap">
            <table class="acct-orders-table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_orders as $ro): ?>
                <tr>
                  <td class="order-id-cell">#<?= $ro['id'] ?></td>
                  <td><?= date('d M Y', strtotime($ro['created_at'])) ?></td>
                  <td class="order-total-cell">£<?= number_format((float)$ro['total'], 2) ?></td>
                  <td><?= acct_badge($ro['status']) ?></td>
                  <td><a href="order-detail.php?id=<?= $ro['id'] ?>" class="acct-view-order">View →</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <a href="orders.php" class="btn-ghost" style="margin-top:16px;display:inline-block;">View All Orders →</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</main>

<script>
/* ---- Password show/hide toggle ---- */
document.querySelectorAll('.acct-pw-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var inp = document.getElementById(this.dataset.target);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.classList.toggle('is-visible');
  });
});

/* ---- Password strength meter ---- */
var newPw = document.getElementById('new_password');
if (newPw) {
  newPw.addEventListener('input', function () {
    var val  = this.value;
    var wrap = document.getElementById('pwStrengthWrap');
    var fill = document.getElementById('pwFill');
    var lbl  = document.getElementById('pwLabel');
    if (!val) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';

    var score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))    score++;

    var levels = [
      { label: 'Weak',      color: '#e57373', pct: '25%'  },
      { label: 'Fair',      color: '#e6a84a', pct: '50%'  },
      { label: 'Good',      color: '#6aa3f5', pct: '75%'  },
      { label: 'Strong',    color: '#7fbf9a', pct: '100%' },
    ];
    var lvl = levels[Math.max(0, score - 1)];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    lbl.textContent       = lvl.label;
    lbl.style.color       = lvl.color;
  });
}

/* ---- Image Preview ---- */
function previewImage(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('profilePreview');
      var initials = document.getElementById('profileInitials');
      if (preview) {
        preview.src = e.target.result;
      } else if (initials) {
        // Switch from initials to image
        var img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'acct-profile-preview';
        img.id = 'profilePreview';
        initials.parentNode.replaceChild(img, initials);
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
