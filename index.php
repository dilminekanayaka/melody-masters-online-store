<?php
include 'includes/init.php';
include 'includes/db.php';

// Featured products — 8 newest in-stock items
$fp_res = mysqli_query($conn,
    "SELECT p.id, p.name, p.price, p.image, c.name AS category,
            COALESCE(ROUND(AVG(r.rating),1), 0) AS avg_rating,
            COALESCE(COUNT(DISTINCT r.id), 0) AS review_count
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN reviews r    ON r.product_id = p.id
     WHERE p.stock > 0
     GROUP BY p.id, p.name, p.price, p.image, c.name
     ORDER BY p.created_at DESC
     LIMIT 8"
);
$featured = mysqli_fetch_all($fp_res, MYSQLI_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<!-- ===== HERO BANNER ===== -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="container hero-body">
    <div class="hero-text">

      <h1>Professional Gear<br>for Serious Musicians</h1>
      <p class="hero-desc">Instruments, studio audio, and accessories from the world's leading brands. Free delivery on orders over £100.</p>
      <div class="hero-cta">
        <a href="shop.php" class="btn-primary">Shop All Products</a>
        <a href="shop.php?cat=guitars" class="btn-ghost">View New Arrivals</a>
      </div>
    </div>
  </div>
</section>

<!-- ===== TRUST BAR ===== -->
<div class="trust-bar">
  <div class="container trust-bar-inner">
    <div class="trust-item">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      <span>Free delivery over £100</span>
    </div>
    <div class="trust-item">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span>2-year manufacturer warranty</span>
    </div>
    <div class="trust-item">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
      <span>30-day hassle-free returns</span>
    </div>
    <div class="trust-item">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6.07 6.07l.86-.86a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 21.78 16.92z"/></svg>
      <span>Expert advice — call us today</span>
    </div>
  </div>
</div>

<!-- ===== SHOP BY CATEGORY ===== -->
<section class="section">
  <div class="container">
    <h2 class="section-title">Shop by Category</h2>
    <div class="cat-grid">

      <a href="shop.php?cat=guitars" class="cat-card cat-card--lg">
        <img src="/melody-masters-online-store/assets/images/guitar01.jpg" alt="Guitars & Strings">
        <div class="cat-card-body">
          <span class="cat-name">Guitars &amp; Strings</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

      <a href="shop.php?cat=keyboards" class="cat-card">
        <img src="/melody-masters-online-store/assets/images/keyboard01.png" alt="Keyboards & Synths">
        <div class="cat-card-body">
          <span class="cat-name">Keyboards &amp; Synths</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

      <a href="shop.php?cat=drums" class="cat-card">
        <img src="/melody-masters-online-store/assets/images/drum01.png" alt="Drums & Percussion">
        <div class="cat-card-body">
          <span class="cat-name">Drums &amp; Percussion</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

      <a href="shop.php?cat=audio" class="cat-card">
        <img src="/melody-masters-online-store/assets/images/keyboard02.png" alt="Live & Studio Audio">
        <div class="cat-card-body">
          <span class="cat-name">Live &amp; Studio Audio</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

      <a href="shop.php?cat=strings" class="cat-card">
        <img src="/melody-masters-online-store/assets/images/violin01.png" alt="Strings & Orchestral">
        <div class="cat-card-body">
          <span class="cat-name">Strings &amp; Orchestral</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

      <a href="shop.php?cat=accessories" class="cat-card">
        <img src="/melody-masters-online-store/assets/images/Acce-BrownDrumStick.png" alt="Accessories">
        <div class="cat-card-body">
          <span class="cat-name">Accessories</span>
          <span class="cat-arrow">→</span>
        </div>
      </a>

    </div>
  </div>
</section>

<!-- ===== FEATURED PRODUCTS ===== -->
<section class="section section--alt">
  <div class="container">
    <div class="section-row">
      <h2 class="section-title">Featured Products</h2>
      <a href="shop.php" class="section-link">View all products →</a>
    </div>

    <?php if (!empty($featured)): ?>
    <div class="product-grid">
      <?php foreach ($featured as $fp):
        $fp_img   = $fp['image']
          ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($fp['image'])
          : '/melody-masters-online-store/assets/images/placeholder.png';
        $fp_stars = $fp['avg_rating'] > 0
          ? str_repeat('★', round($fp['avg_rating'])) . str_repeat('☆', 5 - round($fp['avg_rating']))
          : null;
      ?>
      <div class="product-card">
        <a href="product.php?id=<?= $fp['id'] ?>" class="product-img-link">
          <div class="product-img-wrap">
            <img src="<?= $fp_img ?>" alt="<?= htmlspecialchars($fp['name']) ?>" loading="lazy">
          </div>
        </a>
        <div class="product-details">
          <div class="product-meta">
            <span class="product-brand"><?= htmlspecialchars($fp['category'] ?? '') ?></span>
          </div>
          <a href="product.php?id=<?= $fp['id'] ?>"><h3 class="product-name"><?= htmlspecialchars($fp['name']) ?></h3></a>
          <?php if ($fp_stars): ?>
          <div class="product-rating">
            <span class="stars"><?= $fp_stars ?></span>
            <span class="review-count">(<?= (int)$fp['review_count'] ?>)</span>
          </div>
          <?php endif; ?>
          <div class="product-footer">
            <span class="product-price">£<?= number_format((float)$fp['price'], 2) ?></span>
            <a href="cart.php?add=<?= $fp['id'] ?>" class="btn-add-cart">Add to Cart</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bs-empty">
      <p>No products yet. <a href="admin/add_product.php" style="color:var(--gold)">Add your first product →</a></p>
    </div>
    <?php endif; ?>

  </div>
</section>



<!-- ===== BEST SELLERS ===== -->
<?php
// Query top-selling products by total quantity sold from order_items
$bs_sql = "
  SELECT
    p.id,
    p.name,
    p.price,
    p.image,
    c.name AS category,
    COALESCE(SUM(oi.quantity), 0) AS total_sold,
    COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
    COALESCE(COUNT(DISTINCT r.id), 0) AS review_count
  FROM products p
  LEFT JOIN order_items oi ON oi.product_id = p.id
  LEFT JOIN categories  c  ON c.id = p.category_id
  LEFT JOIN reviews     r  ON r.product_id = p.id
  WHERE p.stock > 0
  GROUP BY p.id, p.name, p.price, p.image, c.name
  ORDER BY total_sold DESC, p.created_at DESC
  LIMIT 4
";
$bs_result   = mysqli_query($conn, $bs_sql);
$best_sellers = mysqli_fetch_all($bs_result, MYSQLI_ASSOC);
?>

<section class="section section--alt">
  <div class="container">

    <div class="section-row">
      <div>
        <h2 class="section-title" style="margin-bottom:4px;">Best Sellers</h2>
        <p class="section-subtitle">Our most loved products, chosen by musicians like you.</p>
      </div>
      <a href="shop.php?sort=bestsellers" class="section-link">See all →</a>
    </div>

    <?php if (!empty($best_sellers)): ?>
    <div class="product-grid">
      <?php foreach ($best_sellers as $rank => $p):
        $img      = $p['image'] ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($p['image']) : '/melody-masters-online-store/assets/images/placeholder.png';
        $name_esc = htmlspecialchars($p['name']);
        $cat_esc  = htmlspecialchars($p['category'] ?? '');
        $price    = number_format((float)$p['price'], 2);
        $sold     = (int)$p['total_sold'];
        $rating   = (float)$p['avg_rating'];
        $reviews  = (int)$p['review_count'];
        $stars    = str_repeat('★', round($rating)) . str_repeat('☆', 5 - round($rating));
      ?>
      <div class="product-card">
        <!-- Rank badge -->
        <div class="bs-rank-badge">#<?= $rank + 1 ?></div>

        <a href="product.php?id=<?= $p['id'] ?>" class="product-img-link">
          <div class="product-img-wrap">
            <img src="<?= $img ?>" alt="<?= $name_esc ?>">
          </div>
        </a>

        <div class="product-details">
          <div class="product-meta">
            <span class="product-brand"><?= $cat_esc ?></span>
            <?php if ($sold > 0): ?>
              <span class="bs-sold-badge"><?= $sold ?> sold</span>
            <?php endif; ?>
          </div>

          <a href="product.php?id=<?= $p['id'] ?>">
            <h3 class="product-name"><?= $name_esc ?></h3>
          </a>

          <div class="product-rating">
            <?php if ($reviews > 0): ?>
              <span class="stars"><?= $stars ?></span>
              <span class="review-count"><?= $reviews ?> review<?= $reviews !== 1 ? 's' : '' ?></span>
            <?php else: ?>
              <span class="review-count" style="color:var(--muted)">No reviews yet</span>
            <?php endif; ?>
          </div>

          <div class="product-footer">
            <span class="product-price">£<?= $price ?></span>
            <a href="cart.php?add=<?= $p['id'] ?>" class="btn-add-cart">Add to Cart</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
      <!-- No products yet — friendly empty state -->
      <div class="bs-empty">
        <p>Products will appear here once orders start coming in.</p>
        <a href="shop.php" class="btn-primary" style="margin-top:16px;display:inline-block;">Browse All Products</a>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
