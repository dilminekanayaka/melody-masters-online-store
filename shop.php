<?php
include 'includes/init.php';
include 'includes/db.php';

/* ============================================================
   INPUT SANITISATION
============================================================ */
$search      = trim($_GET['q']     ?? '');
$cat_filter  = trim($_GET['cat']   ?? '');
$type_filter = in_array($_GET['type'] ?? '', ['physical','digital']) ? $_GET['type'] : '';
$sort        = trim($_GET['sort']  ?? 'newest');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 12;
$offset      = ($page - 1) * $per_page;

$price_min  = isset($_GET['pmin']) && is_numeric($_GET['pmin']) ? (float)$_GET['pmin'] : 0;
$price_max  = isset($_GET['pmax']) && is_numeric($_GET['pmax']) ? (float)$_GET['pmax'] : 9999999;

/* ============================================================
   FETCH ALL CATEGORIES (for sidebar)
============================================================ */
$cats_res  = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$all_cats  = mysqli_fetch_all($cats_res, MYSQLI_ASSOC);

// Resolve cat filter name → id
$cat_id = null;
foreach ($all_cats as $c) {
    if (strtolower(str_replace(' ', '-', $c['name'])) === strtolower($cat_filter)
        || (string)$c['id'] === $cat_filter) {
        $cat_id = (int)$c['id'];
        break;
    }
}

/* ============================================================
   PRICE RANGE for slider bounds (full table)
============================================================ */
$pr = mysqli_query($conn, "SELECT MIN(price) as mn, MAX(price) as mx FROM products WHERE stock > 0");
$pr_row = mysqli_fetch_assoc($pr);
$abs_min = (float)($pr_row['mn'] ?? 0);
$abs_max = (float)($pr_row['mx'] ?? 9999);
if ($price_max === 9999999) $price_max = $abs_max;

/* ============================================================
   BUILD QUERY
============================================================ */
$where_parts = ["p.stock > 0"];
$bind_types  = "";
$bind_vals   = [];

if ($search !== '') {
    $where_parts[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $bind_types   .= "ss";
    $bind_vals[]   = "%$search%";
    $bind_vals[]   = "%$search%";
}

if ($cat_id !== null) {
    $where_parts[] = "p.category_id = ?";
    $bind_types   .= "i";
    $bind_vals[]   = $cat_id;
}

if ($type_filter !== '') {
    $where_parts[] = "p.type = ?";
    $bind_types   .= "s";
    $bind_vals[]   = $type_filter;
}

if ($price_min > 0) {
    $where_parts[] = "p.price >= ?";
    $bind_types   .= "d";
    $bind_vals[]   = $price_min;
}

if ($price_max < $abs_max) {
    $where_parts[] = "p.price <= ?";
    $bind_types   .= "d";
    $bind_vals[]   = $price_max;
}

$where_sql = implode(" AND ", $where_parts);

switch ($sort) {
    case 'price_asc':   $order_sql = "p.price ASC";                       break;
    case 'price_desc':  $order_sql = "p.price DESC";                      break;
    case 'bestsellers': $order_sql = "total_sold DESC, p.created_at DESC"; break;
    case 'name_asc':    $order_sql = "p.name ASC";                        break;
    default:            $order_sql = "p.created_at DESC";                 break;
}

// Count query
$count_sql = "
    SELECT COUNT(DISTINCT p.id)
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE $where_sql
";
$cs = mysqli_prepare($conn, $count_sql);
if ($bind_types) mysqli_stmt_bind_param($cs, $bind_types, ...$bind_vals);
mysqli_stmt_execute($cs);
$total_products = (int)mysqli_fetch_row(mysqli_stmt_get_result($cs))[0];
$total_pages    = (int)ceil($total_products / $per_page);

// Main query
$main_sql = "
    SELECT
        p.id, p.name, p.price, p.image, p.stock,
        c.name AS category,
        COALESCE(SUM(oi.quantity), 0) AS total_sold,
        COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
        COALESCE(COUNT(DISTINCT r.id), 0) AS review_count
    FROM products p
    LEFT JOIN categories  c  ON c.id  = p.category_id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN reviews     r  ON r.product_id  = p.id
    WHERE $where_sql
    GROUP BY p.id, p.name, p.price, p.image, p.stock, c.name
    ORDER BY $order_sql
    LIMIT ? OFFSET ?
";
$ms = mysqli_prepare($conn, $main_sql);
$types_with_paging = $bind_types . "ii";
$vals_with_paging  = array_merge($bind_vals, [$per_page, $offset]);
mysqli_stmt_bind_param($ms, $types_with_paging, ...$vals_with_paging);
mysqli_stmt_execute($ms);
$products = mysqli_fetch_all(mysqli_stmt_get_result($ms), MYSQLI_ASSOC);

/* ============================================================
   BUILD PAGINATION URL HELPER
============================================================ */
function page_url(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return 'shop.php?' . http_build_query($params);
}

function filter_url(array $new): string {
    $params = array_merge($_GET, $new);
    unset($params['page']);
    return 'shop.php?' . http_build_query($params);
}

/* ============================================================
   ACTIVE CATEGORY LABEL
============================================================ */
$active_cat_name = '';
if ($cat_id !== null) {
    foreach ($all_cats as $c) {
        if ($c['id'] == $cat_id) {
            $active_cat_name = $c['name'];
            break;
        }
    }
}

$page_title = $search ? "Search: \"$search\"" : ($active_cat_name ?: "All Products");
?>
<?php include 'includes/header.php'; ?>

<main class="shop-page">
  <div class="container">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span class="bc-sep">›</span>
      <a href="shop.php">Shop</a>
      <?php if ($active_cat_name): ?>
        <span class="bc-sep">›</span>
        <span><?= htmlspecialchars($active_cat_name) ?></span>
      <?php elseif ($search): ?>
        <span class="bc-sep">›</span>
        <span>Search: "<?= htmlspecialchars($search) ?>"</span>
      <?php endif; ?>
    </nav>

    <div class="shop-layout">

      <!-- ===================== SIDEBAR ===================== -->
      <aside class="shop-sidebar">

        <!-- Search -->
        <form class="sidebar-search" action="shop.php" method="GET">
          <?php if ($cat_filter): ?>
            <input type="hidden" name="cat" value="<?= htmlspecialchars($cat_filter) ?>">
          <?php endif; ?>
          <input type="text" name="q" placeholder="Search products…"
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" aria-label="Search">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          </button>
        </form>

        <!-- Categories -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Categories</h3>
          <ul class="sidebar-cat-list">
            <li>
              <a href="<?= filter_url(['cat' => '', 'page' => 1]) ?>"
                 class="sidebar-cat-link <?= !$cat_filter ? 'is-active' : '' ?>">
                All Products
              </a>
            </li>
            <?php foreach ($all_cats as $c):
              $slug = strtolower(str_replace(' ', '-', $c['name']));
            ?>
            <li>
              <a href="<?= filter_url(['cat' => $slug, 'page' => 1]) ?>"
                 class="sidebar-cat-link <?= $c['id'] == $cat_id ? 'is-active' : '' ?>">
                <?= htmlspecialchars($c['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Price Range -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Price Range</h3>
          <form method="GET" action="shop.php" class="price-filter-form" id="priceForm">
            <?php if ($search):   ?><input type="hidden" name="q"    value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <?php if ($cat_filter): ?><input type="hidden" name="cat"  value="<?= htmlspecialchars($cat_filter) ?>"><?php endif; ?>
            <?php if ($sort):     ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>

            <div class="price-inputs">
              <div class="price-input-wrap">
                <label for="pmin">Min (£)</label>
                <input type="number" id="pmin" name="pmin"
                       value="<?= $price_min > $abs_min ? $price_min : '' ?>"
                       min="0" step="1" placeholder="<?= (int)$abs_min ?>">
              </div>
              <span class="price-dash">—</span>
              <div class="price-input-wrap">
                <label for="pmax">Max (£)</label>
                <input type="number" id="pmax" name="pmax"
                       value="<?= $price_max < $abs_max ? $price_max : '' ?>"
                       min="0" step="1" placeholder="<?= (int)$abs_max ?>">
              </div>
            </div>
            <button type="submit" class="price-apply-btn">Apply</button>
            <?php if ($price_min > $abs_min || $price_max < $abs_max): ?>
              <a href="<?= filter_url(['pmin' => '', 'pmax' => '']) ?>" class="price-clear-link">Clear price filter</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- Sort (also as sidebar on mobile) -->
        <div class="sidebar-block">
          <h3 class="sidebar-title">Sort By</h3>
          <ul class="sidebar-sort-list">
            <?php
            $sort_options = [
                'newest'      => 'Newest First',
                'bestsellers' => 'Best Sellers',
                'price_asc'   => 'Price: Low → High',
                'price_desc'  => 'Price: High → Low',
                'name_asc'    => 'Name A–Z',
            ];
            foreach ($sort_options as $val => $label): ?>
            <li>
              <a href="<?= filter_url(['sort' => $val]) ?>"
                 class="sidebar-sort-link <?= $sort === $val ? 'is-active' : '' ?>">
                <?= $label ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

      </aside><!-- /sidebar -->

      <!-- ===================== MAIN CONTENT ===================== -->
      <div class="shop-main">

        <!-- Toolbar -->
        <div class="shop-toolbar">
          <div class="shop-results-count">
            <?php if ($total_products > 0): ?>
              Showing
              <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_products) ?></strong>
              of <strong><?= $total_products ?></strong> products
              <?php if ($active_cat_name): ?>
                in <strong><?= htmlspecialchars($active_cat_name) ?></strong>
              <?php endif; ?>
            <?php else: ?>
              No products found
            <?php endif; ?>
          </div>

          <!-- Sort dropdown (desktop toolbar) -->
          <div class="sort-select-wrap">
            <label for="sort-select" class="sort-label">Sort:</label>
            <select id="sort-select" class="sort-select"
                    onchange="window.location.href=this.value">
              <?php foreach ($sort_options as $val => $label):
                $url = filter_url(['sort' => $val]);
              ?>
              <option value="<?= $url ?>" <?= $sort === $val ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Active filters chips -->
        <?php if ($search || $cat_id || $price_min > $abs_min || $price_max < $abs_max): ?>
        <div class="active-filters">
          <span class="active-filter-label">Active filters:</span>
          <?php if ($search): ?>
            <a href="<?= filter_url(['q' => '']) ?>" class="filter-chip">
              "<?= htmlspecialchars($search) ?>" ✕
            </a>
          <?php endif; ?>
          <?php if ($active_cat_name): ?>
            <a href="<?= filter_url(['cat' => '']) ?>" class="filter-chip">
              <?= htmlspecialchars($active_cat_name) ?> ✕
            </a>
          <?php endif; ?>
          <?php if ($price_min > $abs_min || $price_max < $abs_max): ?>
            <a href="<?= filter_url(['pmin' => '', 'pmax' => '']) ?>" class="filter-chip">
              £<?= (int)$price_min ?> – £<?= (int)$price_max ?> ✕
            </a>
          <?php endif; ?>
          <a href="shop.php" class="filter-clear-all">Clear all</a>
        </div>
        <?php endif; ?>

        <!-- PRODUCT GRID -->
        <?php if (!empty($products)): ?>
        <div class="product-grid shop-grid">
          <?php foreach ($products as $p):
            $img_src  = $p['image']
              ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($p['image'])
              : '/melody-masters-online-store/assets/images/placeholder.png';
            $stars    = $p['avg_rating'] > 0
              ? str_repeat('★', round($p['avg_rating'])) . str_repeat('☆', 5 - round($p['avg_rating']))
              : null;
          ?>
          <div class="product-card">
            <?php if ($p['stock'] <= 5 && $p['stock'] > 0): ?>
              <div class="stock-warn-badge">Only <?= $p['stock'] ?> left</div>
            <?php endif; ?>

            <a href="product.php?id=<?= $p['id'] ?>" class="product-img-link">
              <div class="product-img-wrap">
                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     loading="lazy">
              </div>
            </a>

            <div class="product-details">
              <div class="product-meta">
                <span class="product-brand"><?= htmlspecialchars($p['category'] ?? '') ?></span>
                <?php if ((int)$p['total_sold'] > 0): ?>
                  <span class="bs-sold-badge"><?= (int)$p['total_sold'] ?> sold</span>
                <?php endif; ?>
              </div>

              <a href="product.php?id=<?= $p['id'] ?>">
                <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
              </a>

              <?php if ($stars): ?>
              <div class="product-rating">
                <span class="stars"><?= $stars ?></span>
                <span class="review-count">(<?= (int)$p['review_count'] ?>)</span>
              </div>
              <?php endif; ?>

              <div class="product-footer">
                <span class="product-price">£<?= number_format((float)$p['price'], 2) ?></span>
                <a href="cart.php?add=<?= $p['id'] ?>" class="btn-add-cart">Add to Cart</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Product pages">
          <?php if ($page > 1): ?>
            <a href="<?= page_url($page - 1) ?>" class="page-btn" aria-label="Previous page">‹</a>
          <?php endif; ?>

          <?php
          // Show pages smartly: always show first, last, and a window around current
          $window = 2;
          $shown  = [];
          for ($i = 1; $i <= $total_pages; $i++) {
              if ($i === 1 || $i === $total_pages || abs($i - $page) <= $window) {
                  $shown[] = $i;
              }
          }
          $prev = null;
          foreach ($shown as $p_num):
              if ($prev !== null && $p_num - $prev > 1): ?>
                <span class="page-ellipsis">…</span>
          <?php  endif; ?>
            <a href="<?= page_url($p_num) ?>"
               class="page-btn <?= $p_num === $page ? 'is-current' : '' ?>"
               aria-current="<?= $p_num === $page ? 'page' : 'false' ?>">
              <?= $p_num ?>
            </a>
          <?php   $prev = $p_num;
          endforeach; ?>

          <?php if ($page < $total_pages): ?>
            <a href="<?= page_url($page + 1) ?>" class="page-btn" aria-label="Next page">›</a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <!-- EMPTY STATE -->
        <div class="shop-empty">
          <svg width="56" height="56" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="color:var(--muted)"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <h2>No products found</h2>
          <?php if ($search): ?>
            <p>Try a different search term, or <a href="shop.php">browse all products</a>.</p>
          <?php elseif ($cat_id): ?>
            <p>No products in this category yet. <a href="shop.php">View all products</a>.</p>
          <?php else: ?>
            <p>No products match your filters. <a href="shop.php">Clear all filters</a>.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      </div><!-- /shop-main -->
    </div><!-- /shop-layout -->
  </div>
</main>

<?php include 'includes/footer.php'; ?>
