<?php
if (!defined('INIT_LOADED')) include_once __DIR__ . '/init.php';
if (!isset($conn))           include_once __DIR__ . '/db.php';

// Cart count from session
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  $cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
}

// Fetch logged-in user's name
$user_name   = '';
$user_initials = '';
if (isset($_SESSION['user_id'])) {
  $stmt = mysqli_prepare($conn, "SELECT name, profile_image FROM users WHERE id = ?");
  mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $user_name, $user_profile_img);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  // Build initials (up to 2 letters)
  $parts = explode(' ', trim($user_name));
  $user_initials = strtoupper(substr($parts[0], 0, 1));
  if (isset($parts[1])) {
    $user_initials .= strtoupper(substr($parts[1], 0, 1));
  }

  // First name only for display
  $user_first = $parts[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Melody Masters — Professional music instruments, audio gear & accessories. Free delivery over £100.">
  <title>Melody Masters | Professional Music Gear</title>
  <link rel="stylesheet" href="/melody-masters-online-store/assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php if (isset($_GET['signedout'])): ?>
<!-- ===== SIGNED-OUT TOAST ===== -->
<div class="signout-toast" id="signoutToast" role="alert" aria-live="polite">
  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  <span>You've been signed out. See you next time!</span>
  <button class="signout-toast-close" id="signoutToastClose" aria-label="Dismiss">
    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
</div>
<script>
  (function () {
    var toast = document.getElementById('signoutToast');
    var close = document.getElementById('signoutToastClose');
    function dismiss() {
      toast.classList.add('is-hiding');
      setTimeout(function () { if (toast) toast.remove(); }, 350);
    }
    if (close) close.addEventListener('click', dismiss);
    setTimeout(dismiss, 4000); // auto-dismiss after 4 s
  })();
</script>
<?php endif; ?>

<!-- ===== HEADER ===== -->
<header class="site-header">
  <div class="header-main">
    <div class="header-main-inner">

      <!-- Logo -->
      <a href="/melody-masters-online-store/index.php" class="site-logo">
        Melody<span>Masters</span>
      </a>

      <!-- Search bar — visible on desktop, hidden on mobile until icon tap -->
      <form class="search-bar" id="siteSearchBar" action="/melody-masters-online-store/shop.php" method="GET">
        <input type="text" name="q" placeholder="Search for instruments, brands, gear…" autocomplete="off">
        <button type="submit" class="search-btn" aria-label="Search">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>
      </form>

      <!-- Right actions — always aligned to the right -->
      <div class="header-actions">

        <!-- Search icon (mobile only — toggles the search bar dropdown) -->
        <button class="header-icon-btn header-search-toggle" id="mobileSearchBtn" aria-label="Search" aria-expanded="false">
          <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>

        <?php if (isset($_SESSION['user_id']) && $user_name): ?>
          <!-- Logged-in user avatar + dropdown -->
          <div class="user-menu" id="userMenu">
            <button class="user-menu-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false">
              <?php if (!empty($user_profile_img)): ?>
                <img src="/melody-masters-online-store/assets/images/profiles/<?= htmlspecialchars($user_profile_img) ?>" class="user-avatar-img" alt="">
              <?php else: ?>
                <div class="user-avatar"><?= htmlspecialchars($user_initials) ?></div>
              <?php endif; ?>
              <span class="user-name-label"><?= htmlspecialchars($user_first) ?></span>
              <svg class="user-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div class="user-dropdown" id="userDropdown" role="menu">
              <div class="user-dropdown-header">
                <?php if (!empty($user_profile_img)): ?>
                  <img src="/melody-masters-online-store/assets/images/profiles/<?= htmlspecialchars($user_profile_img) ?>" class="user-avatar-img user-avatar-img--lg" alt="">
                <?php else: ?>
                  <div class="user-avatar user-avatar--lg"><?= htmlspecialchars($user_initials) ?></div>
                <?php endif; ?>
                <div>
                  <div class="dropdown-name"><?= htmlspecialchars($user_name) ?></div>
                  <div class="dropdown-role"><?= ucfirst($_SESSION['role']) ?></div>
                </div>
              </div>
              <div class="user-dropdown-divider"></div>
              <?php if (in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'])): ?>
                <a href="/melody-masters-online-store/admin/dashboard.php" class="dropdown-item" role="menuitem">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                  Admin Dashboard
                </a>
              <?php else: ?>
                <a href="/melody-masters-online-store/customer/account.php" class="dropdown-item" role="menuitem">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  My Account
                </a>
                <a href="/melody-masters-online-store/customer/orders.php" class="dropdown-item" role="menuitem">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  My Orders
                </a>
              <?php endif; ?>
              <div class="user-dropdown-divider"></div>
              <a href="/melody-masters-online-store/logout.php" class="dropdown-item dropdown-item--danger" role="menuitem">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
              </a>
            </div>
          </div>

        <?php else: ?>
          <!-- Guest links -->
          <a href="/melody-masters-online-store/login.php" class="header-text-btn">Sign In</a>
          <a href="/melody-masters-online-store/register.php" class="btn-header-register">Register</a>
        <?php endif; ?>

        <!-- Cart -->
        <a href="/melody-masters-online-store/cart.php" class="cart-btn" title="Cart">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <?php if ($cart_count > 0): ?>
            <span class="cart-badge"><?= $cart_count ?></span>
          <?php endif; ?>
        </a>

      </div><!-- /header-actions -->
    </div>
  </div>

  <!-- Category Nav -->
  <?php
  $current_script  = basename($_SERVER['PHP_SELF']);
  $current_cat     = trim($_GET['cat']  ?? '');
  $current_type    = trim($_GET['type'] ?? '');
  $is_shop         = ($current_script === 'shop.php');

  $nav_cats = mysqli_fetch_all(
      mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC"),
      MYSQLI_ASSOC
  );
  ?>
  <nav class="header-nav">
    <div class="header-nav-inner">

      <?php
      $all_active = ($is_shop && $current_cat === '') ? ' is-active' : '';
      ?>
      <a href="/melody-masters-online-store/shop.php"
         class="header-nav-link<?= $all_active ?>">All Products</a>

      <?php foreach ($nav_cats as $nc):
        $cat_active = ($is_shop && (string)$current_cat === (string)$nc['id']) ? ' is-active' : '';
      ?>
      <a href="/melody-masters-online-store/shop.php?cat=<?= (int)$nc['id'] ?>"
         class="header-nav-link<?= $cat_active ?>">
        <?= htmlspecialchars($nc['name']) ?>
      </a>
      <?php endforeach; ?>

    </div>
  </nav>
</header>

<!-- ===== MOBILE SEARCH OVERLAY ===== -->
<div class="mobile-search-overlay" id="mobileSearchOverlay">
  <form class="mobile-search-form" action="/melody-masters-online-store/shop.php" method="GET">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--muted);flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <input type="text" name="q" id="mobileSearchInput" placeholder="Search instruments, brands, gear…" autocomplete="off">
    <button type="button" class="mobile-search-close" id="mobileSearchClose" aria-label="Close search">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </form>
</div>

<!-- Header JS -->
<script>
(function() {
  /* ── User dropdown ── */
  var uBtn  = document.getElementById('userMenuBtn');
  var uDrp  = document.getElementById('userDropdown');
  var uMenu = document.getElementById('userMenu');
  if (uBtn && uDrp) {
    uBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      var open = uDrp.classList.toggle('is-open');
      uBtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', function(e) {
      if (uMenu && !uMenu.contains(e.target)) {
        uDrp.classList.remove('is-open');
        uBtn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        uDrp.classList.remove('is-open');
        uBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ── Mobile search toggle ── */
  var mBtn    = document.getElementById('mobileSearchBtn');
  var overlay = document.getElementById('mobileSearchOverlay');
  var mInput  = document.getElementById('mobileSearchInput');
  var mClose  = document.getElementById('mobileSearchClose');

  function openMobileSearch() {
    if (!overlay) return;
    overlay.classList.add('is-open');
    if (mBtn) mBtn.setAttribute('aria-expanded', 'true');
    setTimeout(function(){ if (mInput) mInput.focus(); }, 80);
  }
  function closeMobileSearch() {
    if (!overlay) return;
    overlay.classList.remove('is-open');
    if (mBtn) mBtn.setAttribute('aria-expanded', 'false');
  }

  if (mBtn && overlay) {
    mBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      overlay.classList.contains('is-open') ? closeMobileSearch() : openMobileSearch();
    });
  }
  if (mClose) {
    mClose.addEventListener('click', closeMobileSearch);
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMobileSearch();
  });
})();
</script>
