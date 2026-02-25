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
    if ($admin_role === 'superadmin')  { $role_label = 'Super Admin'; }
    elseif ($admin_role === 'staff')   { $role_label = 'Staff'; }
    else                               { $role_label = 'Admin'; }

    // Seed last-hour notifications for initial drawer population
    $seed_orders = mysqli_fetch_all(mysqli_query($conn,
        "SELECT o.id, u.name AS customer, o.total, o.status, o.created_at
         FROM orders o JOIN users u ON u.id = o.user_id
         WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
         ORDER BY o.created_at DESC LIMIT 15"), MYSQLI_ASSOC);

    $seed_customers = mysqli_fetch_all(mysqli_query($conn,
        "SELECT name, email, created_at FROM users
         WHERE role = 'customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
         ORDER BY created_at DESC LIMIT 10"), MYSQLI_ASSOC);

    $seed_json_orders    = json_encode($seed_orders);
    $seed_json_customers = json_encode($seed_customers);
    $seed_json_now       = json_encode(date('Y-m-d H:i:s'));
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
  <style>
  /* ─── Notification Bell ─── */
  .notif-wrap {
    position: relative;
  }
  .notif-btn {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.08);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.55);
    cursor: pointer;
    transition: background .15s, color .15s;
    flex-shrink: 0;
    position: relative;
  }
  .notif-btn:hover { background: rgba(255,255,255,.13); color: #fff; }

  .notif-count {
    position: absolute;
    top: -5px; right: -5px;
    min-width: 17px; height: 17px;
    padding: 0 4px;
    background: #ef4444;
    border: 2px solid #0f0f18;
    border-radius: 9px;
    font-size: 10px; font-weight: 700; color: #fff;
    display: none; align-items: center; justify-content: center;
    line-height: 1;
  }
  .notif-count.visible { display: flex; }

  /* ─── Drawer ─── */
  .notif-drawer {
    position: fixed;
    top: 58px;           /* sits just below the topbar (58px tall) */
    right: 16px;
    width: 340px;
    max-height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    background: #1a1a27;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,.7);
    z-index: 10000;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-8px) scale(.98);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
  }
  .notif-drawer.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
  }

  /* List must scroll inside the fixed drawer */
  .notif-list {
    max-height: 360px;
    overflow-y: auto;
    flex: 1;
  }

  /* Mobile: shrink topbar to 52px so adjust top */
  @media (max-width: 768px) {
    .notif-drawer {
      top: 52px;
      right: 12px;
      width: min(320px, calc(100vw - 24px));
    }
    .notif-list { max-height: calc(100vh - 160px); }
  }

  /* Small phone: fill almost full width */
  @media (max-width: 540px) {
    .notif-drawer {
      top: 50px;
      left: 8px;
      right: 8px;
      width: auto;
      border-radius: 10px;
      max-height: calc(100vh - 70px);
    }
    .notif-list { max-height: calc(100vh - 160px); }
  }

  /* 390px: edge-to-edge */
  @media (max-width: 420px) {
    .notif-drawer {
      top: 48px;
      left: 6px;
      right: 6px;
      width: auto;
      border-radius: 8px;
    }
  }

  .notif-drawer-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px 12px;
    border-bottom: 1px solid rgba(255,255,255,.07);
  }
  .notif-drawer-title {
    font-size: 13px; font-weight: 700; color: #f0eff7;
    display: flex; align-items: center; gap: 7px;
  }
  .notif-live-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #10b981;
    animation: ndot 2s ease-in-out infinite;
  }
  @keyframes ndot { 0%,100%{opacity:1} 50%{opacity:.25} }

  .notif-clear-btn {
    font-size: 11px; font-weight: 600; color: #c8973c;
    background: none; border: none; cursor: pointer;
    font-family: inherit; padding: 0;
  }
  .notif-clear-btn:hover { text-decoration: underline; }

  /* ─── Items ─── */
  .notif-list {
    max-height: 360px;
    overflow-y: auto;
  }
  .notif-list::-webkit-scrollbar { width: 4px; }
  .notif-list::-webkit-scrollbar-track { background: transparent; }
  .notif-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

  .notif-item {
    display: flex; align-items: flex-start; gap: 11px;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255,255,255,.045);
    text-decoration: none;
    transition: background .12s;
    cursor: default;
  }
  .notif-item:last-child { border-bottom: none; }
  .notif-item:hover { background: rgba(255,255,255,.04); }
  .notif-item.is-new { background: rgba(16,185,129,.04); }

  .notif-ico {
    width: 32px; height: 32px; border-radius: 8px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .notif-ico--order    { background: rgba(79,142,247,.15); color: #4f8ef7; }
  .notif-ico--customer { background: rgba(16,185,129,.15); color: #10b981; }

  .notif-body { flex: 1; min-width: 0; }
  .notif-item-title {
    font-size: 12px; font-weight: 600; color: #f0eff7;
    display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .notif-item-sub {
    font-size: 11px; color: #6b6a80;
    display: block; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .notif-item-time {
    font-size: 10px; color: #44435a;
    white-space: nowrap; flex-shrink: 0;
    margin-top: 2px;
  }

  .notif-new-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #10b981; flex-shrink: 0; margin-top: 4px;
  }

  .notif-empty {
    padding: 32px 16px;
    text-align: center;
    font-size: 13px; color: #44435a;
  }

  /* ─── Footer ─── */
  .notif-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px;
    border-top: 1px solid rgba(255,255,255,.07);
    font-size: 11px;
  }
  .notif-footer-status { color: #44435a; }
  .notif-footer-link { color: #c8973c; font-weight: 600; }
  .notif-footer-link:hover { text-decoration: underline; }

  /* ─── Topbar avatar group ─── */
  .admin-topbar-user {
    display: flex; align-items: center; gap: 10px;
  }
  .admin-topbar-user-info {
    display: flex; flex-direction: column; align-items: flex-end; gap: 1px;
  }
  .admin-topbar-name {
    font-size: 13px; font-weight: 600; color: #f0eff7;
    line-height: 1.2;
  }
  .admin-topbar-role {
    font-size: 10px; font-weight: 600;
    color: #c8973c;
    text-transform: uppercase; letter-spacing: .5px;
    line-height: 1.2;
  }
  .admin-topbar-role--staff { color: #4f8ef7; }

  /* ─── Toast notifications ─── */
  #notif-toasts {
    position: fixed; bottom: 24px; right: 24px;
    z-index: 99999;
    display: flex; flex-direction: column; gap: 10px;
    pointer-events: none;
  }
  .notif-toast {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 13px 15px;
    border-radius: 10px;
    background: #1a1a27;
    border: 1px solid rgba(255,255,255,.1);
    box-shadow: 0 12px 40px rgba(0,0,0,.6);
    min-width: 270px; max-width: 330px;
    pointer-events: auto;
    animation: toast-in .28s cubic-bezier(.34,1.56,.64,1);
  }
  .notif-toast.exit { animation: toast-out .22s ease forwards; }
  @keyframes toast-in  { from{opacity:0;transform:translateX(40px)} to{opacity:1;transform:translateX(0)} }
  @keyframes toast-out { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(40px)} }
  .notif-toast--order    { border-left: 3px solid #4f8ef7; }
  .notif-toast--customer { border-left: 3px solid #10b981; }
  .notif-toast-ico {
    width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .notif-toast--order    .notif-toast-ico { background: rgba(79,142,247,.2);  color: #4f8ef7; }
  .notif-toast--customer .notif-toast-ico { background: rgba(16,185,129,.2);  color: #10b981; }
  .notif-toast-body { flex: 1; min-width: 0; }
  .notif-toast-title { font-size: 12px; font-weight: 700; color: #f0eff7; display: block; }
  .notif-toast-sub   { font-size: 11px; color: #9997a6; display: block; margin-top: 2px; }
  .notif-toast-x {
    background: none; border: none; color: #44435a; cursor: pointer;
    font-size: 16px; line-height: 1; padding: 0; align-self: flex-start;
  }
  .notif-toast-x:hover { color: #f0eff7; }
  </style>
</head>
<body class="admin-body">

<!-- Toast container -->
<div id="notif-toasts"></div>

<!-- Seed data for JS -->
<script>
window._nSeedOrders = <?= $seed_json_orders ?>;
window._nSeedCusts  = <?= $seed_json_customers ?>;
window._nSeedNow    = <?= $seed_json_now ?>;
</script>

<div class="admin-shell">

  <!-- ── SIDEBAR ── -->
  <aside class="admin-sidebar" id="adminSidebar">

    <div class="admin-sidebar-logo">
      <a href="/melody-masters-online-store/index.php">Melody<span>Masters</span></a>
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

  <!-- Sidebar backdrop (mobile) -->
  <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- ── MAIN ── -->
  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <button class="admin-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1 class="admin-topbar-title"><?= htmlspecialchars($title) ?></h1>

      <div class="admin-topbar-right">

        <!-- Bell -->
        <div class="notif-wrap" id="notifWrap">
          <button class="notif-btn" id="notifBtn" title="Notifications">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-count" id="notifCount">0</span>
          </button>

          <!-- Drawer -->
          <div class="notif-drawer" id="notifDrawer">
            <div class="notif-drawer-head">
              <span class="notif-drawer-title">
                <span class="notif-live-dot"></span>
                Notifications
              </span>
              <button class="notif-clear-btn" id="notifClearBtn">Mark all read</button>
            </div>

            <div class="notif-list" id="notifList">
              <div class="notif-empty" id="notifEmpty">No new activity</div>
            </div>

            <div class="notif-footer">
              <span class="notif-footer-status" id="notifStatus">Loading…</span>
              <a href="/melody-masters-online-store/admin/orders.php" class="notif-footer-link">View orders →</a>
            </div>
          </div>
        </div>

        <!-- User block: name + role -->
        <div class="admin-topbar-user">
          <div class="admin-topbar-user-info">
            <span class="admin-topbar-name"><?= htmlspecialchars($admin_name) ?></span>
            <span class="admin-topbar-role<?= $is_staff ? ' admin-topbar-role--staff' : '' ?>"><?= $role_label ?></span>
          </div>
        </div>

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
/* ── Sidebar toggle with backdrop ── */
(function () {
  var sidebar   = document.getElementById('adminSidebar');
  var toggle    = document.getElementById('sidebarToggle');
  var backdrop  = document.getElementById('sidebarBackdrop');
  if (!toggle || !sidebar) return;

  function openSidebar() {
    sidebar.classList.add('is-open');
    if (backdrop) backdrop.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('is-open');
    if (backdrop) backdrop.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
  });
  if (backdrop) backdrop.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
})();

/* ── Notification System ── */
(function () {
  var unread   = 0;
  var lastPoll = window._nSeedNow || new Date().toISOString().slice(0, 19).replace('T', ' ');
  var seenIds  = new Set();
  var seenEmails = new Set();

  var O_ICO = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
  var C_ICO = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
  var SC    = { Pending:'#f59e0b', Processing:'#4f8ef7', Shipped:'#8b5cf6', Delivered:'#10b981', Cancelled:'#ef4444' };

  function timeAgo(ts) {
    var d    = new Date(ts.replace(' ', 'T'));
    var diff = Math.round((Date.now() - d) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.round(diff / 60) + 'm ago';
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function setStatus(msg) {
    var el = document.getElementById('notifStatus');
    if (el) el.textContent = msg;
  }

  function bumpUnread(n) {
    unread += n;
    var badge = document.getElementById('notifCount');
    if (!badge) return;
    badge.textContent = unread > 99 ? '99+' : String(unread);
    badge.classList.add('visible');
  }

  function buildOrderItem(o, isNew) {
    var col = SC[o.status] || '#9997a6';
    return '<div class="notif-item' + (isNew ? ' is-new' : '') + '">' +
      '<div class="notif-ico notif-ico--order">' + O_ICO + '</div>' +
      '<div class="notif-body">' +
        '<span class="notif-item-title">New Order #' + o.id + ' — £' + parseFloat(o.total).toFixed(2) + '</span>' +
        '<span class="notif-item-sub">' + o.customer + ' · <span style="color:' + col + '">' + o.status + '</span></span>' +
      '</div>' +
      '<span class="notif-item-time">' + timeAgo(o.created_at) + '</span>' +
      (isNew ? '<span class="notif-new-dot"></span>' : '') +
    '</div>';
  }

  function buildCustItem(c, isNew) {
    return '<div class="notif-item' + (isNew ? ' is-new' : '') + '">' +
      '<div class="notif-ico notif-ico--customer">' + C_ICO + '</div>' +
      '<div class="notif-body">' +
        '<span class="notif-item-title">New customer joined</span>' +
        '<span class="notif-item-sub">' + c.name + ' · ' + c.email + '</span>' +
      '</div>' +
      '<span class="notif-item-time">' + timeAgo(c.created_at) + '</span>' +
      (isNew ? '<span class="notif-new-dot"></span>' : '') +
    '</div>';
  }

  function prependItem(html) {
    var list  = document.getElementById('notifList');
    var empty = document.getElementById('notifEmpty');
    if (!list) return;
    if (empty) empty.remove();
    list.insertAdjacentHTML('afterbegin', html);
    // Cap at 30 items
    var items = list.querySelectorAll('.notif-item');
    if (items.length > 30) items[items.length - 1].remove();
  }

  function showToast(type, title, sub) {
    var zone = document.getElementById('notif-toasts');
    if (!zone) return;
    var ico = type === 'order' ? O_ICO : C_ICO;
    var el  = document.createElement('div');
    el.className = 'notif-toast notif-toast--' + type;
    el.innerHTML =
      '<div class="notif-toast-ico">' + ico + '</div>' +
      '<div class="notif-toast-body"><span class="notif-toast-title">' + title + '</span>' +
        '<span class="notif-toast-sub">' + sub + '</span></div>' +
      '<button class="notif-toast-x" onclick="this.parentNode.remove()">×</button>';
    zone.appendChild(el);
    setTimeout(function () {
      el.classList.add('exit');
      setTimeout(function () { el.remove(); }, 250);
    }, 5000);
  }

  /* ── Seed from PHP data ── */
  function seed() {
    var orders = window._nSeedOrders || [];
    var custs  = window._nSeedCusts  || [];
    var items  = [];
    orders.forEach(function (o) { items.push({ t: 'o', d: o, ts: o.created_at }); seenIds.add(o.id); });
    custs.forEach(function (c)  { items.push({ t: 'c', d: c, ts: c.created_at }); seenEmails.add(c.email); });
    items.sort(function (a, b)  { return a.ts < b.ts ? 1 : -1; });
    items.forEach(function (x) {
      prependItem(x.t === 'o' ? buildOrderItem(x.d, false) : buildCustItem(x.d, false));
    });
    setStatus('Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
  }

  /* ── Poll every 30 s ── */
  function poll() {
    fetch('/melody-masters-online-store/admin/api_activity.php?since=' + encodeURIComponent(lastPoll))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        lastPoll = data.server_time;
        var added = 0;

        var newOrders = (data.orders || []).filter(function (o) { return !seenIds.has(o.id); });
        newOrders.reverse().forEach(function (o) {
          seenIds.add(o.id);
          prependItem(buildOrderItem(o, true));
          showToast('order', 'New Order #' + o.id + ' — £' + parseFloat(o.total).toFixed(2), o.customer + ' · ' + o.status);
          added++;
        });

        var newCusts = (data.customers || []).filter(function (c) { return !seenEmails.has(c.email); });
        newCusts.reverse().forEach(function (c) {
          seenEmails.add(c.email);
          prependItem(buildCustItem(c, true));
          showToast('customer', c.name + ' joined', c.email);
          added++;
        });

        if (added > 0) bumpUnread(added);
        setStatus('Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
      })
      .catch(function () { setStatus('Retrying…'); });
  }

  /* ── Bell toggle ── */
  var btn    = document.getElementById('notifBtn');
  var drawer = document.getElementById('notifDrawer');

  if (btn && drawer) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      drawer.classList.toggle('open');
    });
  }

  /* Close on outside click */
  document.addEventListener('click', function (e) {
    var wrap = document.getElementById('notifWrap');
    if (wrap && !wrap.contains(e.target) && drawer) {
      drawer.classList.remove('open');
    }
  });

  /* Mark all read */
  var clearBtn = document.getElementById('notifClearBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      unread = 0;
      var badge = document.getElementById('notifCount');
      if (badge) { badge.textContent = '0'; badge.classList.remove('visible'); }
    });
  }

  /* Init */
  seed();
  setInterval(poll, 30000);
})();
</script>
</body>
</html>
<?php
} // end adminFoot()
