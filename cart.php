<?php
include 'includes/init.php';
include 'includes/db.php';


// --- ADD TO CART ---
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $pid  = (int)$_GET['add'];
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, shipping_cost, image, stock FROM products WHERE id = ? AND stock > 0");
    mysqli_stmt_bind_param($stmt, "i", $pid);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row) {
        if (!isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] = [
                'name'     => $row['name'],
                'price'    => $row['price'],
                'shipping' => $row['shipping_cost'],
                'image'    => $row['image'],
                'qty'      => 1,
                'stock'    => $row['stock'],
            ];
        } else {
            // Increment, respect stock limit
            $cur = $_SESSION['cart'][$pid]['qty'];
            if ($cur < $row['stock']) {
                $_SESSION['cart'][$pid]['qty']++;
            }
        }
    }
    header("Location: cart.php");
    exit;
}

// --- REMOVE FROM CART ---
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$pid]);
    header("Location: cart.php");
    exit;
}

// --- UPDATE QUANTITIES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if ($qty < 1) {
            unset($_SESSION['cart'][$pid]);
        } elseif (isset($_SESSION['cart'][$pid])) {
            $max = $_SESSION['cart'][$pid]['stock'] ?? 999;
            $_SESSION['cart'][$pid]['qty'] = min($qty, $max);
        }
    }
    header("Location: cart.php");
    exit;
}

// --- CLEAR CART ---
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}

// --- COMPUTE TOTAL ---
$cart_items       = $_SESSION['cart'] ?? [];
$subtotal         = 0;
$total_prod_ship  = 0;
foreach ($cart_items as $item) {
    $subtotal        += $item['price'] * $item['qty'];
    $total_prod_ship += ($item['shipping'] ?? 0) * $item['qty'];
}
$shipping_threshold = 100;
// Default to 8.99 if no product-specific shipping is set
$base_shipping = ($total_prod_ship > 0) ? $total_prod_ship : 8.99;
$shipping_cost = ($subtotal >= $shipping_threshold || $subtotal == 0) ? 0 : $base_shipping;
$total         = $subtotal + $shipping_cost;
$item_count         = array_sum(array_column($cart_items, 'qty'));
?>
<?php include 'includes/header.php'; ?>

<main class="cart-page">
  <div class="container">

    
    <div class="cart-heading">
      <h1>Your Cart <?php if ($item_count > 0): ?><span class="cart-count-label">(<?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?>)</span><?php endif; ?></h1>
      <a href="shop.php" class="continue-shopping">← Continue Shopping</a>
    </div>

    <?php if (empty($cart_items)): ?>

    <!-- ===== EMPTY CART ===== -->
    <div class="cart-empty">
      <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="color:var(--muted)"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <h2>Your cart is empty</h2>
      <p>Looks like you haven't added anything yet. Browse our range and find something you'll love.</p>
      <a href="shop.php" class="btn-primary">Start Shopping</a>
    </div>

    <?php else: ?>
     <!-- Cart Layout -->
    <div class="cart-layout">

      <!-- LEFT: Items -->
      <div class="cart-items-col">

        <form method="POST" id="cartForm">
          <div class="cart-items-list">

            <?php foreach ($cart_items as $pid => $item):
              $img_src = $item['image']
                ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($item['image'])
                : '/melody-masters-online-store/assets/images/placeholder.png';
              $line_total = $item['price'] * $item['qty'];
            ?>
            <div class="cart-row">

              <!-- Product image -->
              <a href="product.php?id=<?= $pid ?>" class="cart-img-link">
                <div class="cart-img-wrap">
                  <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                </div>
              </a>

              <!-- Product info -->
              <div class="cart-item-info">
                <a href="product.php?id=<?= $pid ?>" class="cart-item-name">
                  <?= htmlspecialchars($item['name']) ?>
                </a>
                <div class="cart-item-price-unit">£<?= number_format($item['price'], 2) ?> each</div>
              </div>

              <!-- Qty stepper -->
              <div class="qty-stepper">
                <button type="button" class="qty-btn qty-dec" data-pid="<?= $pid ?>">−</button>
                <input type="number" name="qty[<?= $pid ?>]" id="qty_<?= $pid ?>"
                       value="<?= $item['qty'] ?>"
                       min="1" max="<?= $item['stock'] ?>"
                       class="qty-input">
                <button type="button" class="qty-btn qty-inc" data-pid="<?= $pid ?>"
                        data-max="<?= $item['stock'] ?>">+</button>
              </div>

              <!-- Line total -->
              <div class="cart-line-total">£<?= number_format($line_total, 2) ?></div>

              <!-- Remove -->
              <a href="cart.php?remove=<?= $pid ?>" class="cart-remove" title="Remove item" aria-label="Remove <?= htmlspecialchars($item['name']) ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </a>

            </div>
            <?php endforeach; ?>

          </div>

          <!-- Cart actions -->
          <div class="cart-actions-bar">
            <a href="cart.php?clear=1" class="btn-ghost-sm"
               onclick="return confirm('Clear your entire cart?')">Clear Cart</a>
            <button type="submit" class="btn-primary">Update Cart</button>
          </div>
        </form>

      </div>

      <!-- Order Summary -->
      <aside class="cart-summary">
        <h2 class="summary-title">Order Summary</h2>

        <div class="summary-rows">
          <div class="summary-row">
            <span>Subtotal (<?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?>)</span>
            <span>£<?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="summary-row">
            <span>Delivery</span>
            <?php if ($shipping_cost == 0): ?>
              <span class="summary-free">Free</span>
            <?php else: ?>
              <span>£<?= number_format($shipping_cost, 2) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($subtotal > 0 && $subtotal < $shipping_threshold): ?>
        <div class="free-shipping-notice">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          Add <strong>£<?= number_format($shipping_threshold - $subtotal, 2) ?></strong> more for free delivery
        </div>
        <?php elseif ($subtotal >= $shipping_threshold): ?>
        <div class="free-shipping-notice free-shipping-notice--achieved">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          You qualify for <strong>free delivery!</strong>
        </div>
        <?php endif; ?>

        <div class="summary-divider"></div>

        <div class="summary-total-row">
          <span>Total</span>
          <span class="summary-total-price">£<?= number_format($total, 2) ?></span>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="checkout.php" class="btn-primary cart-checkout-btn">
            Proceed to Checkout
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        <?php else: ?>
          <a href="login.php" class="btn-primary cart-checkout-btn">
            Sign In to Checkout
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
          <p class="guest-checkout-note">
            <a href="register.php">Create an account</a> to track your orders
          </p>
        <?php endif; ?>

        <!-- Trust signals -->
        <div class="cart-trust">
          <div class="cart-trust-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Secure checkout</span>
          </div>
          <div class="cart-trust-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
            <span>30-day returns</span>
          </div>
          <div class="cart-trust-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>2-year warranty</span>
          </div>
        </div>

      </aside>
    </div><!-- /cart-layout -->
    <?php endif; ?>

  </div>
</main>

<!-- Quantity stepper JS -->
<script>
(function () {
  function getInput(pid) {
    return document.getElementById('qty_' + pid);
  }

  document.querySelectorAll('.qty-dec').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pid   = this.dataset.pid;
      var input = getInput(pid);
      var val   = parseInt(input.value, 10);
      if (val > 1) input.value = val - 1;
    });
  });

  document.querySelectorAll('.qty-inc').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pid   = this.dataset.pid;
      var max   = parseInt(this.dataset.max, 10);
      var input = getInput(pid);
      var val   = parseInt(input.value, 10);
      if (val < max) input.value = val + 1;
    });
  });
})();
</script>

<?php include 'includes/footer.php'; ?>
