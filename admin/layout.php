<?php
// Admin layout helper — included at top of each admin page
// Usage: adminHead('Page Title', 'active_key');
function adminHead(string $title, string $active = ''): void {
    global $conn;
    include_once __DIR__ . '/../includes/init.php';
    include_once __DIR__ . '/../includes/db.php';

    $admin_name    = $_SESSION['user_name'] ?? 'Admin';
    $admin_role    = $_SESSION['role']      ?? 'admin';
    $is_superadmin = ($admin_role === 'superadmin');
    $is_staff      = ($admin_role === 'staff');
    $initials      = strtoupper(substr(trim($admin_name), 0, 1));
    if ($admin_role === 'superadmin')      { $role_label = 'Super Admin'; }
    elseif ($admin_role === 'staff')       { $role_label = 'Staff'; }
    else                                   { $role_label = 'Admin'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> · Melody Masters Admin</title>
  <link rel="stylesheet" href="/melody-masters-online-store/assets/css/style.css">
  <link rel="stylesheet" href="/melody-masters-online-store/assets/css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-shell">

  <!-- ── SIDEBAR ── -->
  <aside class="admin-sidebar" id="adminSidebar">

    <!-- Logo -->
    <div class="admin-sidebar-logo">
      <a href="/melody-masters-online-store/index.php">
        Melody<span>Masters</span>
      </a>
    </div>

    <nav class="admin-nav">

      <div class="admin-nav-section">
        <span class="admin-nav-heading">Overview</span>
        <a href="/melody-masters-online-store/admin/dashboard.php"
           class="admin-nav-link <?= $active === 'dashboard' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
      </div>

      <div class="admin-nav-section">
        <span class="admin-nav-heading">Catalogue</span>
        <a href="/melody-masters-online-store/admin/products.php"
           class="admin-nav-link <?= $active === 'products' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          Products
        </a>
        <a href="/melody-masters-online-store/admin/add_product.php"
           class="admin-nav-link <?= $active === 'add_product' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Product
        </a>
        <a href="/melody-masters-online-store/admin/categories.php"
           class="admin-nav-link <?= $active === 'categories' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
          Categories
        </a>
      </div>

      <div class="admin-nav-section">
        <span class="admin-nav-heading">Sales</span>
        <a href="/melody-masters-online-store/admin/orders.php"
           class="admin-nav-link <?= $active === 'orders' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Orders
        </a>
        <?php if (!$is_staff): ?>
        <a href="/melody-masters-online-store/admin/customers.php"
           class="admin-nav-link <?= $active === 'customers' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Customers
        </a>
        <?php endif; ?>
      </div>

      <!-- Administration (Managers only) -->
      <?php if (!$is_staff): ?>
      <div class="admin-nav-section">
        <span class="admin-nav-heading">Administration</span>
        <?php if ($is_superadmin): ?>
        <a href="/melody-masters-online-store/admin/users.php"
           class="admin-nav-link <?= $active === 'users' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Manage Admins
        </a>
        <?php endif; ?>
        <a href="/melody-masters-online-store/admin/staff.php"
           class="admin-nav-link <?= $active === 'staff' ? 'is-active' : '' ?>">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Manage Staff
        </a>
      </div>
      <?php endif; ?>

    </nav>

    <div class="admin-sidebar-footer">
      <a href="/melody-masters-online-store/index.php" class="admin-nav-link">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        View Store
      </a>
      <a href="/melody-masters-online-store/logout.php" class="admin-nav-link admin-nav-link--danger">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
      </a>
    </div>

  </aside>

  <!-- ── MAIN ── -->
  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <button class="admin-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1 class="admin-topbar-title"><?= htmlspecialchars($title) ?></h1>
      <div class="admin-topbar-right">
        <?php if ($is_superadmin): ?>
        <span class="admin-topbar-badge">Super Admin</span>
        <?php elseif ($is_staff): ?>
        <span class="admin-topbar-badge" style="background:rgba(79,142,247,.1);color:#4f8ef7;border-color:rgba(79,142,247,.22);">Staff</span>
        <?php endif; ?>
        <div class="admin-topbar-avatar"><?= htmlspecialchars($initials) ?></div>
        <span class="admin-topbar-name"><?= htmlspecialchars($admin_name) ?></span>
      </div>
    </header>

    <!-- Page content -->
    <div class="admin-content">
    <?php if (isset($_GET['access_denied'])): ?>
    <div class="admin-flash admin-flash--err">⛔ You don't have permission to access that page.</div>
    <?php endif; ?>
<?php
} // end adminHead()

function adminFoot(): void { ?>
    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<script>
  var sidebar = document.getElementById('adminSidebar');
  var toggle  = document.getElementById('sidebarToggle');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
    });
    // Close on outside click (mobile)
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('is-open') &&
          !sidebar.contains(e.target) && e.target !== toggle) {
        sidebar.classList.remove('is-open');
      }
    });
  }
</script>
</body>
</html>
<?php
} // end adminFoot()
