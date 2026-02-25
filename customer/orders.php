<?php
include_once __DIR__ . '/../includes/init.php';
include_once __DIR__ . '/../includes/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];

/* ============================================================
   FETCH ORDERS (newest first)
============================================================ */
$stmt = mysqli_prepare($conn,
    "SELECT o.id, o.total, o.shipping_cost, o.status, o.created_at,
            COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

/* ============================================================
   FETCH FIRST ITEM IMAGE PER ORDER (for thumbnail strip)
============================================================ */
$thumb_map = [];
if (!empty($orders)) {
    $order_ids   = implode(',', array_column($orders, 'id'));
    $thumb_stmt  = mysqli_query($conn,
        "SELECT oi.order_id, p.image, p.name
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id IN ($order_ids)
         ORDER BY oi.id ASC"
    );
    while ($row = mysqli_fetch_assoc($thumb_stmt)) {
        $oid = $row['order_id'];
        if (!isset($thumb_map[$oid])) $thumb_map[$oid] = [];
        $thumb_map[$oid][] = $row;
    }
}

/* ============================================================
   STATUS CONFIG
============================================================ */
function status_badge(string $status): string {
    $map = [
        'Pending'    => ['label' => 'Pending',    'class' => 'badge-pending'],
        'Processing' => ['label' => 'Processing', 'class' => 'badge-processing'],
        'Shipped'    => ['label' => 'Shipped',    'class' => 'badge-shipped'],
        'Delivered'  => ['label' => 'Delivered',  'class' => 'badge-delivered'],
        'Cancelled'  => ['label' => 'Cancelled',  'class' => 'badge-cancelled'],
    ];
    $cfg = $map[$status] ?? ['label' => $status, 'class' => 'badge-pending'];
    return '<span class="order-badge ' . $cfg['class'] . '">' . htmlspecialchars($cfg['label']) . '</span>';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="orders-page">
  <div class="container">

    <!-- PAGE HEADER -->
    <div class="orders-page-header">
      <div>
        <h1 class="orders-heading">My Orders</h1>
        <p class="orders-sub">Track your past and current orders</p>
      </div>
      <a href="../shop.php" class="btn-ghost">Continue Shopping →</a>
    </div>

    <?php if (empty($orders)): ?>
    <!-- EMPTY STATE -->
    <div class="orders-empty">
      <div class="orders-empty-icon">
        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      </div>
      <h2>No orders yet</h2>
      <p>When you place an order, it will appear here.</p>
      <a href="../shop.php" class="btn-primary" style="margin-top:8px;">Start Shopping</a>
    </div>

    <?php else: ?>
    <!-- ORDER LIST -->
    <div class="orders-list">

      <?php foreach ($orders as $order):
        $thumbs   = $thumb_map[$order['id']] ?? [];
        $date_fmt = date('d M Y', strtotime($order['created_at']));
        $total    = number_format((float)$order['total'], 2);
        $items    = $order['item_count'];
      ?>
      <div class="order-card">

        <!-- TOP ROW: meta + badge -->
        <div class="order-card-top">
          <div class="order-meta">
            <span class="order-number">Order #<?= $order['id'] ?></span>
            <span class="order-date"><?= $date_fmt ?></span>
          </div>
          <?= status_badge($order['status']) ?>
        </div>

        <!-- MIDDLE: thumbnails + item count -->
        <div class="order-card-mid">
          <div class="order-thumbs">
            <?php foreach (array_slice($thumbs, 0, 4) as $t):
              $img = $t['image']
                ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($t['image'])
                : '/melody-masters-online-store/assets/images/placeholder.png';
            ?>
            <div class="order-thumb" title="<?= htmlspecialchars($t['name']) ?>">
              <img src="<?= $img ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>
            <?php endforeach; ?>
            <?php if (count($thumbs) > 4): ?>
            <div class="order-thumb order-thumb--more">+<?= count($thumbs) - 4 ?></div>
            <?php endif; ?>
          </div>
          <p class="order-item-count"><?= $items ?> item<?= $items !== 1 ? 's' : '' ?></p>
        </div>

        <!-- BOTTOM: total + action -->
        <div class="order-card-bottom">
          <div class="order-total-col">
            <span class="order-total-label">Total</span>
            <span class="order-total-amount">£<?= $total ?></span>
          </div>
          <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn-primary order-view-btn">
            View Order →
          </a>
        </div>

      </div>
      <?php endforeach; ?>

    </div><!-- /orders-list -->
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
