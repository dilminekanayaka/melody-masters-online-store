<?php
include 'includes/init.php';
include 'includes/db.php';

/* ============================================================
   GUARDS
============================================================ */
// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cart must not be empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

/* ============================================================
   PRE-FILL USER INFO
============================================================ */
$user_stmt = mysqli_prepare($conn,
    "SELECT name, email FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

/* ============================================================
   CART TOTALS
============================================================ */
$cart_items         = $_SESSION['cart'];
$subtotal           = 0;
$total_prod_ship    = 0;
foreach ($cart_items as $item) {
    $subtotal        += $item['price'] * $item['qty'];
    $total_prod_ship += ($item['shipping'] ?? 0) * $item['qty'];
}
$shipping_threshold = 100;
$base_shipping      = ($total_prod_ship > 0) ? $total_prod_ship : 8.99;
$shipping_cost      = ($subtotal >= $shipping_threshold) ? 0 : $base_shipping;
$total              = $subtotal + $shipping_cost;

/* ============================================================
   STICKY FORM VALUES (on validation failure)
============================================================ */
$v = [
    'full_name' => $user['name']  ?? '',
    'email'     => $user['email'] ?? '',
    'phone'     => '',
    'address1'  => '',
    'address2'  => '',
    'city'      => '',
    'postcode'  => '',
    'country'   => 'United Kingdom',
    'payment'   => 'cod',
    'notes'     => '',
];

$errors  = [];
$success = false;
$order_id = null;
$order_has_digital = false;

/* ============================================================
   PROCESS ORDER (POST)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    // Collect & sanitise
    $v['full_name'] = trim($_POST['full_name'] ?? '');
    $v['email']     = trim($_POST['email']     ?? '');
    $v['phone']     = trim($_POST['phone']      ?? '');
    $v['address1']  = trim($_POST['address1']   ?? '');
    $v['address2']  = trim($_POST['address2']   ?? '');
    $v['city']      = trim($_POST['city']       ?? '');
    $v['postcode']  = trim($_POST['postcode']   ?? '');
    $v['country']   = trim($_POST['country']    ?? '');
    $v['payment']   = in_array($_POST['payment'] ?? '', ['cod', 'card']) ? $_POST['payment'] : 'cod';
    $v['notes']     = trim($_POST['notes']      ?? '');

    // Validate
    if (!$v['full_name'])                   $errors[] = "Full name is required.";
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (!$v['address1'])                    $errors[] = "Address line 1 is required.";
    if (!$v['city'])                        $errors[] = "City is required.";
    if (!$v['postcode'])                    $errors[] = "Postcode is required.";
    if (!$v['country'])                     $errors[] = "Country is required.";

    // Card placeholder validation
    if ($v['payment'] === 'card') {
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_expiry = trim($_POST['card_expiry'] ?? '');
        $card_cvv    = trim($_POST['card_cvv']    ?? '');
        if (strlen($card_number) < 13) $errors[] = "Enter a valid card number.";
        if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) $errors[] = "Enter card expiry as MM/YY.";
        if (!preg_match('/^\d{3,4}$/', $card_cvv))         $errors[] = "Enter a valid CVV.";
    }

    if (empty($errors)) {
        // Re-calculate totals server-side (never trust client)
        $subtotal_final = 0;
        $total_prod_ship_final = 0;
        foreach ($cart_items as $c) {
            $subtotal_final += $c['price'] * $c['qty'];
            $total_prod_ship_final += ($c['shipping'] ?? 0) * $c['qty'];
        }
        $base_shipping_final = ($total_prod_ship_final > 0) ? $total_prod_ship_final : 8.99;
        $shipping_final = ($subtotal_final >= $shipping_threshold) ? 0.00 : $base_shipping_final;
        $total_final    = $subtotal_final + $shipping_final;

        // Insert order
        $ins_order = mysqli_prepare($conn,
            "INSERT INTO orders
              (user_id, full_name, email, phone, address1, address2, city, postcode, country,
               payment_method, notes, total, shipping_cost, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Pending')"
        );
        mysqli_stmt_bind_param($ins_order, "issssssssssdd",
            $_SESSION['user_id'],
            $v['full_name'], $v['email'], $v['phone'],
            $v['address1'],  $v['address2'], $v['city'],
            $v['postcode'],  $v['country'],
            $v['payment'],   $v['notes'],
            $total_final,    $shipping_final
        );
        mysqli_stmt_execute($ins_order);
        $order_id = mysqli_insert_id($conn);

        // Insert order items + decrement stock
        $ins_item = mysqli_prepare($conn,
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)"
        );
        $upd_stock = mysqli_prepare($conn,
            "UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?"
        );

        foreach ($cart_items as $pid => $c) {
            $pid = (int)$pid;
            
            // Track if digital
            if (!$order_has_digital) {
                $check_stmt = mysqli_prepare($conn, "SELECT type FROM products WHERE id = ?");
                mysqli_stmt_bind_param($check_stmt, "i", $pid);
                mysqli_stmt_execute($check_stmt);
                $p_type = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt))['type'] ?? 'physical';
                if ($p_type === 'digital') $order_has_digital = true;
            }

            mysqli_stmt_bind_param($ins_item, "iiid",
                $order_id, $pid, $c['qty'], $c['price']
            );
            mysqli_stmt_execute($ins_item);

            mysqli_stmt_bind_param($upd_stock, "ii", $c['qty'], $pid);
            mysqli_stmt_execute($upd_stock);
        }

        // Clear cart
        unset($_SESSION['cart']);
        $success = true;
    }
}
?>
<?php include 'includes/header.php'; ?>

<main class="checkout-page">
  <div class="container">

    <?php if ($success): ?>
    <!-- ===== ORDER CONFIRMATION ===== -->
    <div class="order-success">
      <div class="order-success-icon">
        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h1>Order Placed — Thank You!</h1>
      <p>Your order <strong>#<?= $order_id ?></strong> has been received and is being processed.</p>
      
      <?php if ($order_has_digital): ?>
      <div class="order-success-notice">
         <p><strong>Buying Digital Products?</strong> You can download your files immediately from your order details page once the payment is confirmed.</p>
      </div>
      <?php endif; ?>

      <p class="order-success-sub">A confirmation has been sent to <strong><?= htmlspecialchars($v['email']) ?></strong>.</p>
      <div class="order-success-actions">
        <a href="customer/order-detail.php?id=<?= $order_id ?>" class="btn-primary">
            <?= $order_has_digital ? 'View Order &amp; Downloads' : 'View Order Details' ?>
        </a>
        <a href="shop.php" class="btn-ghost">Continue Shopping</a>
      </div>
    </div>

    <?php else: ?>
    <!-- ===== CHECKOUT FORM ===== -->

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span class="bc-sep">›</span>
      <a href="cart.php">Cart</a>
      <span class="bc-sep">›</span>
      <span>Checkout</span>
    </nav>

    <h1 class="checkout-heading">Checkout</h1>

    <?php if (!empty($errors)): ?>
    <div class="checkout-errors">
      <strong>Please fix the following:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="checkout-layout" id="checkoutForm">

      <!-- LEFT: FORM -->
      <div class="checkout-form-col">

        <!-- ── SHIPPING DETAILS ── -->
        <div class="checkout-section">
          <h2 class="checkout-section-title">
            <span class="co-step">1</span> Shipping Information
          </h2>

          <div class="form-row-2">
            <div class="form-group">
              <label for="full_name">Full Name *</label>
              <input type="text" id="full_name" name="full_name"
                     value="<?= htmlspecialchars($v['full_name']) ?>"
                     placeholder="John Smith" required>
            </div>
            <div class="form-group">
              <label for="email">Email Address *</label>
              <input type="email" id="email" name="email"
                     value="<?= htmlspecialchars($v['email']) ?>"
                     placeholder="you@example.com" required>
            </div>
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($v['phone']) ?>"
                   placeholder="+44 7700 900000">
          </div>

          <div class="form-group">
            <label for="address1">Address Line 1 *</label>
            <input type="text" id="address1" name="address1"
                   value="<?= htmlspecialchars($v['address1']) ?>"
                   placeholder="House number and street name" required>
          </div>

          <div class="form-group">
            <label for="address2">Address Line 2 <span class="optional">(optional)</span></label>
            <input type="text" id="address2" name="address2"
                   value="<?= htmlspecialchars($v['address2']) ?>"
                   placeholder="Apartment, suite, unit, etc.">
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label for="city">City *</label>
              <input type="text" id="city" name="city"
                     value="<?= htmlspecialchars($v['city']) ?>"
                     placeholder="London" required>
            </div>
            <div class="form-group">
              <label for="postcode">Postcode *</label>
              <input type="text" id="postcode" name="postcode"
                     value="<?= htmlspecialchars($v['postcode']) ?>"
                     placeholder="SW1A 1AA" required>
            </div>
            <div class="form-group">
              <label for="country">Country *</label>
              <select id="country" name="country" required>
                <?php
                $countries = [
                  "United Kingdom", "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan",
                  "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi",
                  "Cabo Verde", "Cambodia", "Cameroon", "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo, Democratic Republic of the", "Congo, Republic of the", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czech Republic",
                  "Denmark", "Djibouti", "Dominica", "Dominican Republic",
                  "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini", "Ethiopia",
                  "Fiji", "Finland", "France",
                  "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
                  "Haiti", "Honduras", "Hungary",
                  "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Ivory Coast",
                  "Jamaica", "Japan", "Jordan",
                  "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kosovo", "Kuwait", "Kyrgyzstan",
                  "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg",
                  "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar",
                  "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "North Macedonia", "Norway",
                  "Oman",
                  "Pakistan", "Palau", "Palestine", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal",
                  "Qatar",
                  "Romania", "Russia", "Rwanda",
                  "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", "Suriname", "Sweden", "Switzerland", "Syria",
                  "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu",
                  "Uganda", "Ukraine", "United Arab Emirates", "United States", "Uruguay", "Uzbekistan",
                  "Vanuatu", "Vatican City", "Venezuela", "Vietnam",
                  "Yemen",
                  "Zambia", "Zimbabwe"
                ];
                sort($countries); // Keep it alphabetical
                // Ensure UK is first as it's the primary market
                $countries = array_diff($countries, ["United Kingdom"]);
                array_unshift($countries, "United Kingdom");
                foreach ($countries as $co):
                ?>
                <option value="<?= $co ?>" <?= $v['country'] === $co ? 'selected' : '' ?>>
                  <?= $co ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="notes">Order Notes <span class="optional">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Any special instructions for your order or delivery…"><?= htmlspecialchars($v['notes']) ?></textarea>
          </div>
        </div>

        <!-- ── PAYMENT ── -->
        <div class="checkout-section">
          <h2 class="checkout-section-title">
            <span class="co-step">2</span> Payment Method
          </h2>

          <div class="payment-options">

            <!-- COD option -->
            <div class="payment-option <?= $v['payment'] === 'cod' ? 'is-selected' : '' ?>" id="label-cod" onclick="selectPayment('cod')">
              <input type="radio" name="payment" value="cod"
                     <?= $v['payment'] === 'cod' ? 'checked' : '' ?>
                     class="payment-radio" id="payCod">
              <div class="payment-option-content">
                <div class="payment-option-header">
                  <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                  <span>Cash on Delivery</span>
                  <span class="payment-badge">Most Popular</span>
                </div>
                <p class="payment-desc">Pay in cash when your order is delivered. No online payment needed.</p>
              </div>
            </div>

            <!-- Card option -->
            <div class="payment-option <?= $v['payment'] === 'card' ? 'is-selected' : '' ?>" id="label-card" onclick="selectPayment('card')">
              <input type="radio" name="payment" value="card"
                     <?= $v['payment'] === 'card' ? 'checked' : '' ?>
                     class="payment-radio" id="payCard">
              <div class="payment-option-content">
                <div class="payment-option-header">
                  <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                  <span>Credit / Debit Card</span>
                  <div class="card-icons">
                    <span class="card-pill">VISA</span>
                    <span class="card-pill">MC</span>
                    <span class="card-pill">AMEX</span>
                  </div>
                </div>
                <p class="payment-desc">Secure card payment. Your details are encrypted and never stored.</p>
              </div>
            </div>

          </div><!-- /payment-options -->

          <!-- Card details panel (shown only when card selected) -->
          <div class="card-fields" id="cardFields"
               style="<?= $v['payment'] === 'card' ? '' : 'display:none' ?>">
            <div class="card-fields-inner">
              <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number"
                       placeholder="1234 5678 9012 3456"
                       maxlength="19" autocomplete="cc-number">
              </div>
              <div class="form-row-2">
                <div class="form-group">
                  <label for="card_expiry">Expiry (MM/YY)</label>
                  <input type="text" id="card_expiry" name="card_expiry"
                         placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                </div>
                <div class="form-group">
                  <label for="card_cvv">CVV</label>
                  <input type="text" id="card_cvv" name="card_cvv"
                         placeholder="123" maxlength="4" autocomplete="cc-csc">
                </div>
              </div>
              <div class="card-secure-note">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                256-bit SSL encryption — your payment info is secure
              </div>
            </div>
          </div>

        </div><!-- /checkout-section -->

      </div><!-- /checkout-form-col -->

      <!-- RIGHT: ORDER SUMMARY -->
      <aside class="checkout-summary">
        <h2 class="summary-title">Order Summary</h2>

        <!-- Item list -->
        <div class="co-item-list">
          <?php foreach ($cart_items as $pid => $item):
            $co_img = $item['image']
              ? '/melody-masters-online-store/assets/images/' . htmlspecialchars($item['image'])
              : '/melody-masters-online-store/assets/images/placeholder.png';
          ?>
          <div class="co-item">
            <div class="co-item-img-wrap">
              <img src="<?= $co_img ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <span class="co-item-qty"><?= (int)$item['qty'] ?></span>
            </div>
            <div class="co-item-info">
              <p class="co-item-name"><?= htmlspecialchars($item['name']) ?></p>
            </div>
            <span class="co-item-total">
              £<?= number_format($item['price'] * $item['qty'], 2) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-rows">
          <div class="summary-row">
            <span>Subtotal</span>
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

        <div class="summary-divider"></div>

        <div class="summary-total-row">
          <span>Total</span>
          <span class="summary-total-price">£<?= number_format($total, 2) ?></span>
        </div>

        <!-- Place order CTA -->
        <button type="submit" name="place_order" class="btn-primary co-place-btn">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Place Order · £<?= number_format($total, 2) ?>
        </button>

        <p class="co-terms-note">
          By placing your order you agree to our
          <a href="#">Terms &amp; Conditions</a> and
          <a href="#">Privacy Policy</a>.
        </p>

        <!-- Trust -->
        <div class="cart-trust" style="margin-top:20px;">
          <div class="cart-trust-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Secure &amp; encrypted checkout</span>
          </div>
          <div class="cart-trust-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
            <span>Free 30-day returns</span>
          </div>
          <div class="cart-trust-item">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Free delivery on orders over £100</span>
          </div>
        </div>

      </aside><!-- /checkout-summary -->

    </form>
    <?php endif; ?>

  </div>
</main>

<script>
/* ---- Payment method selector ---- */
function selectPayment(method) {
  // Check the correct radio
  var radio = document.getElementById(method === 'cod' ? 'payCod' : 'payCard');
  if (radio) radio.checked = true;

  // Update selected state on option panels
  document.querySelectorAll('.payment-option').forEach(function (el) {
    el.classList.remove('is-selected');
  });
  var panel = document.getElementById('label-' + method);
  if (panel) panel.classList.add('is-selected');

  // Show/hide card fields
  var cardFields = document.getElementById('cardFields');
  if (cardFields) {
    cardFields.style.display = method === 'card' ? 'block' : 'none';
    // Toggle required on card inputs when hidden
    var cardInputs = cardFields.querySelectorAll('input');
    cardInputs.forEach(function (inp) {
      inp.required = (method === 'card');
    });
  }
}

// Initialise on page load based on current selection
document.addEventListener('DOMContentLoaded', function () {
  var checked = document.querySelector('.payment-radio:checked');
  if (checked) selectPayment(checked.value);

  /* ---- Card number auto-spacing ---- */
  var cn = document.getElementById('card_number');
  if (cn) {
    cn.addEventListener('input', function () {
      var v = this.value.replace(/\D/g, '').slice(0, 16);
      this.value = v.replace(/(.{4})/g, '$1 ').trim();
    });
  }

  /* ---- Expiry auto-slash ---- */
  var exp = document.getElementById('card_expiry');
  if (exp) {
    exp.addEventListener('input', function () {
      var v = this.value.replace(/\D/g, '').slice(0, 4);
      if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
      this.value = v;
    });
  }
});
</script>

<?php include 'includes/footer.php'; ?>
