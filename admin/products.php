<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/* ── HANDLE DELETE ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Unlink order items + reviews before deleting product
    mysqli_query($conn, "DELETE FROM reviews WHERE product_id = $del_id");
    mysqli_query($conn, "DELETE FROM order_items WHERE product_id = $del_id");
    mysqli_query($conn, "DELETE FROM digital_products WHERE product_id = $del_id");
    $dp = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($dp, "i", $del_id);
    mysqli_stmt_execute($dp);
    header("Location: products.php?deleted=1"); exit;
}

/* ── FILTERS ── */
$search   = trim($_GET['q']   ?? '');
$cat_f    = (int)($_GET['cat'] ?? 0);
$stock_f  = $_GET['stock'] ?? '';
$page     = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
$types  = "";
if ($search)  { $where .= " AND p.name LIKE ?"; $params[] = "%$search%"; $types .= "s"; }
if ($cat_f)   { $where .= " AND p.category_id = ?"; $params[] = $cat_f; $types .= "i"; }
if ($stock_f === 'low')  { $where .= " AND p.stock > 0 AND p.stock <= 5"; }
if ($stock_f === 'out')  { $where .= " AND p.stock = 0"; }
if ($stock_f === 'good') { $where .= " AND p.stock > 5"; }

$cnt_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products p $where");
if ($types) mysqli_stmt_bind_param($cnt_stmt, $types, ...$params);
mysqli_stmt_execute($cnt_stmt);
$total_rows  = mysqli_fetch_row(mysqli_stmt_get_result($cnt_stmt))[0];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$lst_stmt = mysqli_prepare($conn,
    "SELECT p.id, p.name, p.price, p.stock, p.type, p.image, c.name AS category
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     $where
     ORDER BY p.id DESC
     LIMIT $per_page OFFSET $offset"
);
if ($types) mysqli_stmt_bind_param($lst_stmt, $types, ...$params);
mysqli_stmt_execute($lst_stmt);
$products = mysqli_fetch_all(mysqli_stmt_get_result($lst_stmt), MYSQLI_ASSOC);

$categories = mysqli_fetch_all(mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name"), MYSQLI_ASSOC);

adminHead('Products', 'products');
?>

<!-- Flash -->
<?php if (isset($_GET['deleted'])): ?>
<div class="admin-flash admin-flash--ok">✓ Product deleted.</div>
<?php elseif (isset($_GET['saved'])): ?>
<div class="admin-flash admin-flash--ok">✓ Product saved.</div>
<?php endif; ?>

<!-- ── FILTER BAR ── -->
<div class="admin-filter-bar">
  <form method="GET" action="products.php" class="admin-filter-form">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
           placeholder="Search products…" class="admin-input">
    <select name="cat" class="admin-select">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $cat_f === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="stock" class="admin-select">
      <option value="">All Stock</option>
      <option value="good" <?= $stock_f==='good'?'selected':'' ?>>Good (>5)</option>
      <option value="low"  <?= $stock_f==='low' ?'selected':'' ?>>Low (1-5)</option>
      <option value="out"  <?= $stock_f==='out' ?'selected':'' ?>>Out of Stock</option>
    </select>
    <button type="submit" class="admin-btn admin-btn--primary">Filter</button>
    <?php if ($search || $cat_f || $stock_f): ?>
    <a href="products.php" class="admin-btn admin-btn--ghost">Clear</a>
    <?php endif; ?>
  </form>
  <div style="display:flex;align-items:center;gap:12px;">
    <span class="admin-result-count"><?= number_format($total_rows) ?> product<?= $total_rows !== 1 ? 's' : '' ?></span>
    <a href="add_product.php" class="admin-btn admin-btn--primary">+ Add Product</a>
  </div>
</div>

<!-- ── PRODUCTS TABLE ── -->
<div class="admin-panel">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Type</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $prod):
          $img = $prod['image']
            ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($prod['image'])
            : '/melody-masters-online-store/assets/images/placeholder.png';
          $stock_cls = $prod['stock'] == 0 ? 'admin-stock--out' : ($prod['stock'] <= 5 ? 'admin-stock--low' : 'admin-stock--ok');
        ?>
        <tr>
          <td>
            <div class="admin-prod-thumb">
              <img src="<?= $img ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
            </div>
          </td>
          <td class="admin-prod-name"><?= htmlspecialchars($prod['name']) ?></td>
          <td class="admin-muted"><?= htmlspecialchars($prod['category'] ?? '—') ?></td>
          <td class="admin-amount">£<?= number_format((float)$prod['price'], 2) ?></td>
          <td><span class="admin-stock <?= $stock_cls ?>"><?= $prod['stock'] ?></span></td>
          <td>
            <?php if ($prod['type'] === 'digital'): ?>
              <span class="admin-type-digital">Digital</span>
            <?php else: ?>
              <span class="admin-type-physical">Physical</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="admin-row-actions">
              <a href="edit_product.php?id=<?= $prod['id'] ?>" class="admin-row-action">Edit</a>
              <a href="products.php?delete=<?= $prod['id'] ?>"
                 class="admin-row-action admin-row-action--danger"
                 onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($prod['name'])) ?>\'?')">Delete</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <tr><td colspan="7" class="admin-empty-row">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
  <div class="admin-pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $cat_f ?>&stock=<?= $stock_f ?>"
       class="admin-page-btn <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php adminFoot(); ?>
