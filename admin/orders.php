<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/* ── Handle DELETE (managers only) ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && can_manage()) {
    $del_id = (int)$_GET['delete'];
    // Remove order items first
    $d1 = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($d1, "i", $del_id);
    mysqli_stmt_execute($d1);
    $d2 = mysqli_prepare($conn, "DELETE FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($d2, "i", $del_id);
    mysqli_stmt_execute($d2);
    header("Location: orders.php?deleted=1"); exit;
}

/* ── Handle STATUS UPDATE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $upd_id     = (int)$_POST['order_id'];
    $upd_status = in_array($_POST['status'], ['Pending','Processing','Shipped','Delivered','Cancelled'])
                  ? $_POST['status'] : 'Pending';
    $us = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($us, "si", $upd_status, $upd_id);
    mysqli_stmt_execute($us);
    header("Location: orders.php?updated=1"); exit;
}

/* ── Filters ── */
$filter_status = $_GET['status'] ?? '';
$filter_date   = $_GET['date']   ?? '';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['p'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
$types  = "";
if ($filter_status) { $where .= " AND o.status = ?"; $params[] = $filter_status; $types .= "s"; }
if ($filter_date === 'today') { $where .= " AND DATE(o.created_at) = CURDATE()"; }
if ($search)        { $where .= " AND (u.name LIKE ? OR o.id = ?)";
                      $like = "%$search%";
                      $params[] = $like; $params[] = (int)$search; $types .= "si"; }

$count_stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id $where");
if ($types) { mysqli_stmt_bind_param($count_stmt, $types, ...$params); }
mysqli_stmt_execute($count_stmt);
$total_rows = mysqli_fetch_row(mysqli_stmt_get_result($count_stmt))[0];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$list_stmt = mysqli_prepare($conn,
    "SELECT o.id, o.total, o.shipping_cost, o.status, o.created_at,
            u.name AS customer, u.email,
            COUNT(oi.id) AS item_count
     FROM orders o
     JOIN users u ON u.id = o.user_id
     LEFT JOIN order_items oi ON oi.order_id = o.id
     $where
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT $per_page OFFSET $offset"
);
if ($types) { mysqli_stmt_bind_param($list_stmt, $types, ...$params); }
mysqli_stmt_execute($list_stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($list_stmt), MYSQLI_ASSOC);

/* ── View single order ── */
$view_order = null; $view_items = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $vs  = mysqli_prepare($conn, "SELECT o.*, u.name AS customer, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?");
    mysqli_stmt_bind_param($vs, "i", $vid);
    mysqli_stmt_execute($vs);
    $view_order = mysqli_fetch_assoc(mysqli_stmt_get_result($vs));
    if ($view_order) {
        $vi = mysqli_prepare($conn, "SELECT oi.*, p.name, p.image, p.type FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($vi, "i", $vid);
        mysqli_stmt_execute($vi);
        $view_items = mysqli_fetch_all(mysqli_stmt_get_result($vi), MYSQLI_ASSOC);

        // Detect if digital-only
        $view_order['is_digital_only'] = true;
        foreach ($view_items as $vi_item) {
            if ($vi_item['type'] !== 'digital') {
                $view_order['is_digital_only'] = false;
                break;
            }
        }
    }
}

function o_badge(string $s): string {
    $m = ['Pending'=>'badge-pending','Processing'=>'badge-processing','Shipped'=>'badge-shipped','Delivered'=>'badge-delivered','Cancelled'=>'badge-cancelled'];
    return '<span class="order-badge '.($m[$s]??'badge-pending').'">'.htmlspecialchars($s).'</span>';
}

adminHead('Orders', 'orders');
?>

<!-- Flash messages -->
<?php if (isset($_GET['updated'])): ?>
<div class="admin-flash admin-flash--ok">✓ Order status updated.</div>
<?php elseif (isset($_GET['deleted'])): ?>
<div class="admin-flash admin-flash--ok">✓ Order deleted.</div>
<?php endif; ?>

<!-- ── VIEW ORDER PANEL ── -->
<?php if ($view_order): ?>
<div class="admin-panel" style="margin-bottom:24px;">
  <div class="admin-panel-head">
    <h2 class="admin-panel-title">Order #<?= $view_order['id'] ?> — <?= htmlspecialchars($view_order['customer']) ?></h2>
    <a href="orders.php" class="admin-btn admin-btn--ghost">← Back to List</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <!-- Details -->
    <div>
      <table class="admin-detail-table">
        <tr><th>Customer</th><td><?= htmlspecialchars($view_order['customer']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($view_order['email']) ?></td></tr>
        <tr><th>Date</th><td><?= date('d M Y, g:ia', strtotime($view_order['created_at'])) ?></td></tr>
        <tr><th>Payment</th><td><?= htmlspecialchars($view_order['payment_method'] ?? 'COD') ?></td></tr>
        <tr><th>Shipping</th><td>£<?= number_format((float)$view_order['shipping_cost'], 2) ?></td></tr>
        <tr><th>Total</th><td><strong>£<?= number_format((float)$view_order['total'], 2) ?></strong></td></tr>
        <tr><th>Status</th><td><?= o_badge($view_order['status']) ?></td></tr>
      </table>
    </div>
    <!-- Address -->
    <div>
      <?php if (!empty($view_order['address1'])): ?>
      <p class="admin-detail-label">Delivery Address</p>
      <p class="admin-detail-address">
        <?= htmlspecialchars($view_order['full_name'] ?? $view_order['customer']) ?><br>
        <?= htmlspecialchars($view_order['address1']) ?><br>
        <?php if ($view_order['address2']): ?><?= htmlspecialchars($view_order['address2']) ?><br><?php endif; ?>
        <?= htmlspecialchars($view_order['city'] ?? '') ?>, <?= htmlspecialchars($view_order['postcode'] ?? '') ?><br>
        <?= htmlspecialchars($view_order['country'] ?? '') ?>
      </p>
      <?php endif; ?>
      <!-- Update status inline -->
      <form method="POST" action="orders.php?view=<?= $view_order['id'] ?>" style="margin-top:20px;">
        <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
        <label class="admin-form-label">Update Status</label>
        <div style="display:flex;gap:8px;margin-top:6px;">
          <select name="status" class="admin-select" style="flex:1;">
            <?php 
            $all_steps = ['Pending','Processing','Shipped','Delivered','Cancelled'];
            if ($view_order['is_digital_only']) {
                $all_steps = ['Pending','Delivered','Cancelled'];
            }
            foreach ($all_steps as $st): ?>
            <option value="<?= $st ?>" <?= $view_order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="update_status" class="admin-btn admin-btn--primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Items -->
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr></thead>
      <tbody>
        <?php foreach ($view_items as $vi): ?>
        <tr>
          <td><?= htmlspecialchars($vi['name']) ?></td>
          <td><?= $vi['quantity'] ?></td>
          <td>£<?= number_format((float)$vi['price'], 2) ?></td>
          <td>£<?= number_format($vi['price'] * $vi['quantity'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>

<!-- ── FILTER BAR ── -->
<div class="admin-filter-bar">
  <form method="GET" action="orders.php" class="admin-filter-form">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
           placeholder="Search by name or order #…" class="admin-input">
    <select name="status" class="admin-select">
      <option value="">All Statuses</option>
      <?php foreach (['Pending','Processing','Shipped','Delivered','Cancelled'] as $st): ?>
      <option value="<?= $st ?>" <?= $filter_status === $st ? 'selected' : '' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="admin-btn admin-btn--primary">Filter</button>
    <?php if ($filter_status || $search): ?>
    <a href="orders.php" class="admin-btn admin-btn--ghost">Clear</a>
    <?php endif; ?>
  </form>
  <span class="admin-result-count"><?= number_format($total_rows) ?> order<?= $total_rows !== 1 ? 's' : '' ?></span>
</div>

<!-- ── ORDERS TABLE ── -->
<div class="admin-panel">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Email</th>
          <th>Items</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="admin-id">#<?= $o['id'] ?></td>
          <td><?= htmlspecialchars($o['customer']) ?></td>
          <td class="admin-muted"><?= htmlspecialchars($o['email']) ?></td>
          <td><?= $o['item_count'] ?></td>
          <td class="admin-amount">£<?= number_format((float)$o['total'], 2) ?></td>
          <td><?= o_badge($o['status']) ?></td>
          <td class="admin-date"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          <td>
            <div class="admin-row-actions">
              <a href="orders.php?view=<?= $o['id'] ?>" class="admin-row-action">View</a>
              <?php if (can_manage()): ?>
              <a href="orders.php?delete=<?= $o['id'] ?>"
                 class="admin-row-action admin-row-action--danger"
                 onclick="return confirm('Delete order #<?= $o['id'] ?>?')">Delete</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="admin-empty-row">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="admin-pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?p=<?= $i ?>&status=<?= urlencode($filter_status) ?>&q=<?= urlencode($search) ?>"
       class="admin-page-btn <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php adminFoot(); ?>
