<?php
include_once __DIR__ . '/../includes/init.php';
include_once __DIR__ . '/../includes/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$uid      = (int)$_SESSION['user_id'];
$order_id = (int)$_GET['id'];

/* ============================================================
   FETCH ORDER (belongs to the logged-in user only)
============================================================ */
$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $uid);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: orders.php");
    exit;
}

/* ============================================================
   FETCH ORDER ITEMS
============================================================ */
$items_stmt = mysqli_prepare($conn,
    "SELECT oi.quantity, oi.price, oi.download_count, 
            p.id AS product_id, p.name, p.image, p.type,
            dp.download_limit
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     LEFT JOIN digital_products dp ON dp.product_id = p.id
     WHERE oi.order_id = ?"
);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);

/* ============================================================
   HELPERS
============================================================ */
function od_status_badge(string $status): string {
    $map = [
        'Pending'    => ['icon' => '⏳', 'class' => 'badge-pending'],
        'Processing' => ['icon' => '⚙', 'class' => 'badge-processing'],
        'Shipped'    => ['icon' => '🚚', 'class' => 'badge-shipped'],
        'Delivered'  => ['icon' => '✓', 'class' => 'badge-delivered'],
        'Cancelled'  => ['icon' => '✕', 'class' => 'badge-cancelled'],
    ];
    $cfg = $map[$status] ?? ['icon' => '•', 'class' => 'badge-pending'];
    return '<span class="order-badge ' . $cfg['class'] . '">'
         . $cfg['icon'] . ' ' . htmlspecialchars($status)
         . '</span>';
}

$date_fmt  = date('d M Y, g:ia', strtotime($order['created_at']));
$subtotal  = (float)$order['total'] - (float)$order['shipping_cost'];

// Determine if this is a digital-only order
$is_digital_only = true;
foreach ($items as $item) {
    if ($item['type'] !== 'digital') {
        $is_digital_only = false;
        break;
    }
}

$steps    = $is_digital_only ? ['Pending', 'Delivered'] : ['Pending', 'Processing', 'Shipped', 'Delivered'];
$step_idx = array_search($order['status'], $steps);
if ($step_idx === false) $step_idx = -1; // Cancelled or unknown
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="order-detail-page">
  <div class="container">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <span class="bc-sep">›</span>
      <a href="orders.php">My Orders</a>
      <span class="bc-sep">›</span>
      <span>Order #<?= $order_id ?></span>
    </nav>

    <!-- PAGE HEADER -->
    <div class="od-header">
      <div>
        <h1 class="orders-heading">Order #<?= $order_id ?></h1>
        <span class="order-date" style="margin-top:4px;display:block;">Placed on <?= $date_fmt ?></span>
      </div>
      <?= od_status_badge($order['status']) ?>
    </div>

    <!-- PROGRESS TRACKER (only shown for non-cancelled) -->
    <?php if ($order['status'] !== 'Cancelled'): ?>
    <div class="od-tracker">
      <?php foreach ($steps as $i => $step):
        $is_done   = $i < $step_idx;
        $is_active = $i === $step_idx;
        $cls       = $is_active ? 'tracker-step--active' : ($is_done ? 'tracker-step--done' : '');
      ?>
      <div class="tracker-step <?= $cls ?>">
        <div class="tracker-dot">
          <?php if ($is_done): ?>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?><?= $i + 1 ?><?php endif; ?>
        </div>
        <span class="tracker-label"><?= $step ?></span>
      </div>
      <?php if ($i < count($steps) - 1): ?>
      <div class="tracker-line <?= $i < $step_idx ? 'tracker-line--done' : '' ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- TWO COLUMN LAYOUT -->
    <div class="od-layout">

      <!-- LEFT: Items -->
      <div class="od-items-col">

        <!-- ── DEDICATED DIGITAL DOWNLOADS PANEL ── -->
        <?php 
        $digital_items = array_filter($items, function($i){ return $i['type'] === 'digital'; });
        if (!empty($digital_items)): 
        ?>
        <div class="od-section od-section--primary digital-downloads-hero">
            <div>
                <h2 class="od-section-title" style="margin-bottom:4px;">Your Digital Content</h2>
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                    <?php if ($order['status'] === 'Delivered'): ?>
                        Thank you for your purchase! You can download your files below.
                    <?php else: ?>
                        Your files will be available for download here once the order is approved.
                    <?php endif; ?>
                </p>
                
                <div class="hero-download-list">
                    <?php foreach ($digital_items as $di): ?>
                    <div class="hero-dl-item">
                        <?php if ($order['status'] === 'Delivered'): ?>
                        <a href="../download.php?oid=<?= $order_id ?>&pid=<?= $di['product_id'] ?>" 
                           class="btn-primary hero-dl-btn">
                           Download Now
                        </a>
                        <?php else: ?>
                        <span class="dl-badge-pending">Pending Approval</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="od-section">
          <h2 class="od-section-title">Items Ordered</h2>
          <div class="od-items-list">
            <?php foreach ($items as $item):
              $img = $item['image']
                ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($item['image'])
                : '/melody-masters-online-store/assets/images/placeholder.png';
              $line_total = $item['price'] * $item['quantity'];
            ?>
            <div class="od-item">
              <a href="../product.php?id=<?= $item['product_id'] ?>" class="od-item-img-wrap">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              </a>
              <div class="od-item-info">
                <a href="../product.php?id=<?= $item['product_id'] ?>" class="od-item-name">
                  <?= htmlspecialchars($item['name']) ?>
                </a>
                <span class="od-item-unit">£<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></span>
              </div>
              <span class="od-item-total">£<?= number_format($line_total, 2) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Shipping address -->
        <?php if ($order['address1']): ?>
        <div class="od-section">
          <h2 class="od-section-title">Delivery Address</h2>
          <div class="od-address">
            <strong><?= htmlspecialchars($order['full_name'] ?? '') ?></strong><br>
            <?= htmlspecialchars($order['address1']) ?><br>
            <?php if ($order['address2']): ?><?= htmlspecialchars($order['address2']) ?><br><?php endif; ?>
            <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['postcode']) ?><br>
            <?= htmlspecialchars($order['country']) ?><br>
            <?php if ($order['phone']): ?>
            <span style="color:var(--muted);font-size:13px;">📞 <?= htmlspecialchars($order['phone']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Order notes -->
        <?php if (!empty($order['notes'])): ?>
        <div class="od-section">
          <h2 class="od-section-title">Order Notes</h2>
          <p style="font-size:14px;color:var(--mid);"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Summary -->
      <aside class="od-summary">

        <div class="od-section">
          <h2 class="od-section-title">Order Summary</h2>

          <div class="summary-rows">
            <div class="summary-row">
              <span>Subtotal</span>
              <span>£<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
              <span>Delivery</span>
              <?php if ((float)$order['shipping_cost'] == 0): ?>
                <span class="summary-free">Free</span>
              <?php else: ?>
                <span>£<?= number_format((float)$order['shipping_cost'], 2) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-total-row" style="margin-bottom:0;">
            <span>Total</span>
            <span class="summary-total-price">£<?= number_format((float)$order['total'], 2) ?></span>
          </div>
        </div>

        <!-- Payment info -->
        <div class="od-section">
          <h2 class="od-section-title">Payment</h2>
          <div class="od-payment-info">
            <?php if (($order['payment_method'] ?? 'cod') === 'card'): ?>
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              <span>Credit / Debit Card</span>
            <?php else: ?>
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
              <span>Cash on Delivery</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="od-actions">
          <a href="orders.php" class="btn-ghost" style="width:100%;text-align:center;">← Back to Orders</a>
          <?php if ($order['status'] === 'Delivered'): ?>
          <a href="../shop.php" class="btn-primary" style="width:100%;text-align:center;margin-top:10px;">
            Reorder / Buy Again
          </a>
          <?php endif; ?>
        </div>

      </aside>

    </div><!-- /od-layout -->

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
