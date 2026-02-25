<?php
include 'includes/init.php';
include 'includes/db.php';

/* ============================================================
   VALIDATE PRODUCT ID
============================================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shop.php");
    exit;
}
$product_id = (int)$_GET['id'];

/* ============================================================
   FETCH PRODUCT
============================================================ */
$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.name AS category, c.id AS category_id
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header("Location: shop.php");
    exit;
}

/* ============================================================
   HANDLE ADD TO CART
============================================================ */
if (isset($_GET['add']) && (int)$_GET['add'] === $product_id) {
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    $pid = $product_id;

    if (!isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid] = [
            'name'     => $product['name'],
            'price'    => $product['price'],
            'shipping' => $product['shipping_cost'] ?? 0,
            'image'    => $product['image'],
            'qty'      => min($qty, $product['stock']),
            'stock'    => $product['stock'],
        ];
    } else {
        $new_qty = $_SESSION['cart'][$pid]['qty'] + $qty;
        $_SESSION['cart'][$pid]['qty'] = min($new_qty, $product['stock']);
    }
    header("Location: cart.php");
    exit;
}

/* ============================================================
   HANDLE REVIEW SUBMISSION
============================================================ */
$review_error   = '';
$review_success = '';

/* Check if logged-in user is a verified buyer of this product */
$is_verified_buyer = false;
if (isset($_SESSION['user_id'])) {
    $vb_stmt = mysqli_prepare($conn,
        "SELECT oi.id
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ?
           AND oi.product_id = ?
           AND o.status IN ('completed','delivered')
         LIMIT 1"
    );
    mysqli_stmt_bind_param($vb_stmt, "ii", $_SESSION['user_id'], $product_id);
    mysqli_stmt_execute($vb_stmt);
    mysqli_stmt_store_result($vb_stmt);
    $is_verified_buyer = mysqli_stmt_num_rows($vb_stmt) > 0;
    mysqli_stmt_close($vb_stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = "You must be signed in to leave a review.";
    } elseif (!$is_verified_buyer) {
        $review_error = "Only verified buyers can leave a review.";
    } else {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a rating between 1 and 5 stars.";
        } elseif (strlen($comment) < 10) {
            $review_error = "Please write at least 10 characters in your review.";
        } else {
            // Check if user already reviewed this product
            $dup = mysqli_prepare($conn,
                "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?"
            );
            mysqli_stmt_bind_param($dup, "ii", $_SESSION['user_id'], $product_id);
            mysqli_stmt_execute($dup);
            mysqli_stmt_store_result($dup);

            if (mysqli_stmt_num_rows($dup) > 0) {
                $review_error = "You have already reviewed this product.";
            } else {
                $ins = mysqli_prepare($conn,
                    "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?,?,?,?)"
                );
                mysqli_stmt_bind_param($ins, "iiis",
                    $_SESSION['user_id'], $product_id, $rating, $comment
                );
                mysqli_stmt_execute($ins);
                $review_success = "Thank you — your review has been submitted!";
            }
        }
    }
}

/* ============================================================
   FETCH REVIEWS
============================================================ */
$rev_stmt = mysqli_prepare($conn,
    "SELECT r.rating, r.comment, r.created_at, u.name AS reviewer,
            CASE WHEN EXISTS (
              SELECT 1 FROM order_items oi
              JOIN orders o ON o.id = oi.order_id
              WHERE o.user_id = r.user_id
                AND oi.product_id = r.product_id
                AND o.status IN ('completed','delivered')
            ) THEN 1 ELSE 0 END AS verified_buyer
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ?
     ORDER BY r.created_at DESC"
);
mysqli_stmt_bind_param($rev_stmt, "i", $product_id);
mysqli_stmt_execute($rev_stmt);
$reviews = mysqli_fetch_all(mysqli_stmt_get_result($rev_stmt), MYSQLI_ASSOC);

$avg_rating   = 0;
$review_count = count($reviews);
if ($review_count > 0) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / $review_count, 1);
}

/* ============================================================
   RELATED PRODUCTS (same category, excludes this product)
============================================================ */
$rel_stmt = mysqli_prepare($conn,
    "SELECT p.id, p.name, p.price, p.image, c.name AS category
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id != ? AND p.stock > 0
     ORDER BY RAND()
     LIMIT 4"
);
mysqli_stmt_bind_param($rel_stmt, "ii", $product['category_id'], $product_id);
mysqli_stmt_execute($rel_stmt);
$related = mysqli_fetch_all(mysqli_stmt_get_result($rel_stmt), MYSQLI_ASSOC);

/* ============================================================
   HELPERS
============================================================ */
$img_src  = $product['image']
    ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($product['image'])
    : '/melody-masters-online-store/assets/images/placeholder.png';

$in_stock     = (int)$product['stock'] > 0;
$low_stock    = $in_stock && (int)$product['stock'] <= 5;
$star_full    = $avg_rating > 0 ? str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)) : '☆☆☆☆☆';
$cat_slug     = strtolower(str_replace(' ', '-', $product['category'] ?? ''));
?>
<?php include 'includes/header.php'; ?>

<main class="product-page">
  <div class="container">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span class="bc-sep">›</span>
      <a href="shop.php">Shop</a>
      <?php if ($product['category']): ?>
        <span class="bc-sep">›</span>
        <a href="shop.php?cat=<?= $cat_slug ?>"><?= htmlspecialchars($product['category']) ?></a>
      <?php endif; ?>
      <span class="bc-sep">›</span>
      <span><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <!-- ===== PRODUCT DETAIL ===== -->
    <div class="product-detail">

      <!-- LEFT: Image -->
      <div class="pd-image-col">
        <div class="pd-image-wrap">
          <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['name']) ?>" id="pdMainImage">
        </div>
      </div>

      <!-- RIGHT: Info -->
      <div class="pd-info-col">

        <!-- Category + brand -->
        <div class="pd-meta">
          <span class="pd-category"><?= htmlspecialchars($product['category'] ?? 'Uncategorised') ?></span>
          <span class="pd-type-badge"><?= ucfirst($product['type']) ?></span>
        </div>

        <!-- Name -->
        <h1 class="pd-name"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Rating summary -->
        <?php if ($review_count > 0): ?>
        <div class="pd-rating">
          <span class="pd-stars"><?= $star_full ?></span>
          <span class="pd-rating-avg"><?= $avg_rating ?></span>
          <a href="#reviews" class="pd-review-link"><?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?></a>
        </div>
        <?php else: ?>
        <div class="pd-rating">
          <span class="pd-stars pd-stars--empty">☆☆☆☆☆</span>
          <a href="#reviews" class="pd-review-link">Be the first to review</a>
        </div>
        <?php endif; ?>

        <!-- Price -->
        <div class="pd-price">£<?= number_format((float)$product['price'], 2) ?></div>

        <!-- Description -->
        <?php if ($product['description']): ?>
        <div class="pd-description">
          <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
        <?php endif; ?>

        <!-- Stock status -->
        <div class="pd-stock-status">
          <?php if (!$in_stock): ?>
            <span class="stock-out">✕ Out of stock</span>
          <?php elseif ($low_stock): ?>
            <span class="stock-low">⚠ Only <?= (int)$product['stock'] ?> left in stock — order soon</span>
          <?php else: ?>
            <span class="stock-ok">✓ In stock (<?= (int)$product['stock'] ?> available)</span>
          <?php endif; ?>
        </div>

        <!-- Add to cart form -->
        <?php if ($in_stock): ?>
        <div class="pd-add-to-cart">
          <div class="qty-stepper">
            <button type="button" class="qty-btn qty-dec" data-pid="main" id="mainDec">−</button>
            <input type="number" id="qty_main" class="qty-input"
                   value="1" min="1" max="<?= (int)$product['stock'] ?>">
            <button type="button" class="qty-btn qty-inc" data-pid="main"
                    data-max="<?= (int)$product['stock'] ?>" id="mainInc">+</button>
          </div>
          <a href="#" class="btn-primary pd-cart-btn" id="addToCartBtn"
             data-base="cart.php?add=<?= $product_id ?>">
            Add to Cart
          </a>
        </div>
        <?php else: ?>
        <button class="btn-primary pd-cart-btn" disabled style="opacity:0.4;cursor:not-allowed;">
          Out of Stock
        </button>
        <?php endif; ?>

        <!-- Meta info -->
        <div class="pd-meta-table">
          <div class="pd-meta-row">
            <span>Category</span>
            <a href="shop.php?cat=<?= $cat_slug ?>"><?= htmlspecialchars($product['category'] ?? '—') ?></a>
          </div>
          <div class="pd-meta-row">
            <span>Type</span>
            <span><?= ucfirst($product['type']) ?></span>
          </div>
          <div class="pd-meta-row">
            <span>Stock</span>
            <span><?= $in_stock ? (int)$product['stock'] . ' units' : 'Out of stock' ?></span>
          </div>
        </div>

        <!-- Trust badges -->
        <div class="pd-trust">
          <div class="pd-trust-item">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Free delivery over £100</span>
          </div>
          <div class="pd-trust-item">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>2-year warranty</span>
          </div>
          <div class="pd-trust-item">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
            <span>30-day returns</span>
          </div>
        </div>

      </div><!-- /pd-info-col -->
    </div><!-- /product-detail -->

    <!-- ===== REVIEWS SECTION ===== -->
    <section class="reviews-section" id="reviews">

      <div class="reviews-header">
        <h2 class="reviews-title">Customer Reviews</h2>
        <?php if ($review_count > 0): ?>
          <div class="reviews-avg">
            <span class="reviews-avg-score"><?= $avg_rating ?></span>
            <div>
              <div class="reviews-avg-stars"><?= $star_full ?></div>
              <div class="reviews-avg-label">Based on <?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Review list -->
      <?php if (!empty($reviews)): ?>
      <div class="reviews-list">
        <?php foreach ($reviews as $rv):
          $rv_stars = str_repeat('★', $rv['rating']) . str_repeat('☆', 5 - $rv['rating']);
          $initials = strtoupper(substr($rv['reviewer'], 0, 1));
          $date_fmt = date('d M Y', strtotime($rv['created_at']));
        ?>
        <div class="review-card">
          <div class="review-avatar"><?= $initials ?></div>
          <div class="review-body">
            <div class="review-top">
              <strong class="review-name"><?= htmlspecialchars($rv['reviewer']) ?></strong>
              <?php if ($rv['verified_buyer']): ?>
                <span class="review-verified-badge">✔ Verified Buyer</span>
              <?php endif; ?>
              <span class="review-date"><?= $date_fmt ?></span>
              <span class="review-stars"><?= $rv_stars ?></span>
            </div>
            <p class="review-comment"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="reviews-empty">
        <p>No reviews yet. Be the first to share your experience!</p>
      </div>
      <?php endif; ?>

      <!-- Write a review -->
      <div class="write-review">
        <h3 class="write-review-title">Write a Review</h3>

        <?php if (!isset($_SESSION['user_id'])): ?>
          <div class="review-signin-notice">
            <a href="login.php">Sign in</a> to leave a review.
          </div>

        <?php elseif (!$is_verified_buyer): ?>
          <div class="review-signin-notice">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-3px;margin-right:6px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Only verified buyers can write a review. Purchase this product to share your experience.
          </div>

        <?php else: ?>
          <?php if ($review_error): ?>
            <div class="auth-error" style="margin-bottom:16px;"><?= htmlspecialchars($review_error) ?></div>
          <?php endif; ?>
          <?php if ($review_success): ?>
            <div class="auth-success" style="margin-bottom:16px;"><?= htmlspecialchars($review_success) ?></div>
          <?php endif; ?>

          <form method="POST" class="review-form">
            <input type="hidden" name="submit_review" value="1">

            <!-- Star rating picker -->
            <div class="star-picker" id="starPicker">
              <span class="star-pick-label">Your rating:</span>
              <div class="star-pick-btns" role="group" aria-label="Rate this product">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <button type="button" class="star-pick-btn" data-val="<?= $s ?>"
                          aria-label="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</button>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="ratingInput" value="0">
            </div>

            <div class="form-group" style="margin-top:16px;">
              <label for="review-comment">Your review</label>
              <textarea id="review-comment" name="comment" rows="5"
                        placeholder="What did you think of this product? Share your experience…"
                        style="width:100%;padding:12px;background:var(--bg-alt);border:1px solid var(--line-md);border-radius:var(--r);color:var(--white);font-family:inherit;font-size:14px;resize:vertical;"></textarea>
            </div>

            <button type="submit" class="btn-primary">Submit Review</button>
          </form>
        <?php endif; ?>
      </div>

    </section>

    <!-- ===== RELATED PRODUCTS ===== -->
    <?php if (!empty($related)): ?>
    <section class="related-section">
      <div class="section-row">
        <h2 class="section-title">More from <?= htmlspecialchars($product['category'] ?? 'this category') ?></h2>
        <a href="shop.php?cat=<?= $cat_slug ?>" class="section-link">View all →</a>
      </div>

      <div class="product-grid">
        <?php foreach ($related as $r):
          $r_img = $r['image']
            ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($r['image'])
            : '/melody-masters-online-store/assets/images/placeholder.png';
        ?>
        <div class="product-card">
          <a href="product.php?id=<?= $r['id'] ?>" class="product-img-link">
            <div class="product-img-wrap">
              <img src="<?= $r_img ?>" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
            </div>
          </a>
          <div class="product-details">
            <div class="product-meta">
              <span class="product-brand"><?= htmlspecialchars($r['category'] ?? '') ?></span>
            </div>
            <a href="product.php?id=<?= $r['id'] ?>">
              <h3 class="product-name"><?= htmlspecialchars($r['name']) ?></h3>
            </a>
            <div class="product-footer">
              <span class="product-price">£<?= number_format((float)$r['price'], 2) ?></span>
              <a href="cart.php?add=<?= $r['id'] ?>" class="btn-add-cart">Add to Cart</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>
</main>

<script>
(function () {
  /* ---- Qty stepper ---- */
  var input = document.getElementById('qty_main');
  var dec   = document.getElementById('mainDec');
  var inc   = document.getElementById('mainInc');
  var btn   = document.getElementById('addToCartBtn');

  if (input && dec && inc && btn) {
    var maxQty = parseInt(inc.dataset.max, 10);

    dec.addEventListener('click', function () {
      var v = parseInt(input.value, 10);
      if (v > 1) input.value = v - 1;
      updateCartLink();
    });

    inc.addEventListener('click', function () {
      var v = parseInt(input.value, 10);
      if (v < maxQty) input.value = v + 1;
      updateCartLink();
    });

    input.addEventListener('change', function () {
      var v = Math.max(1, Math.min(parseInt(this.value, 10) || 1, maxQty));
      this.value = v;
      updateCartLink();
    });

    function updateCartLink() {
      btn.href = btn.dataset.base + '&qty=' + input.value;
    }

    updateCartLink();
  }

  /* ---- Interactive star picker ---- */
  var pickerBtns  = document.querySelectorAll('.star-pick-btn');
  var ratingInput = document.getElementById('ratingInput');
  var selected    = 0;

  pickerBtns.forEach(function (btn) {
    btn.addEventListener('mouseover', function () {
      highlightStars(parseInt(this.dataset.val, 10));
    });
    btn.addEventListener('mouseout', function () {
      highlightStars(selected);
    });
    btn.addEventListener('click', function () {
      selected = parseInt(this.dataset.val, 10);
      ratingInput.value = selected;
      highlightStars(selected);
    });
  });

  function highlightStars(n) {
    pickerBtns.forEach(function (b) {
      var v = parseInt(b.dataset.val, 10);
      b.classList.toggle('is-active', v <= n);
    });
  }
})();
</script>

<?php include 'includes/footer.php'; ?>
