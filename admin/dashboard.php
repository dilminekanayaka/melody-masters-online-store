<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/* ── KPI totals ── */
$total_revenue   = (float)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) AS v FROM orders WHERE status!='Cancelled'"))['v']);
$total_orders    = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders"))['v']);
$total_products  = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM products"))['v']);
$total_customers = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM users WHERE role='customer'"))['v']);

/* ── Today ── */
$today_revenue = (float)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) AS v FROM orders WHERE DATE(created_at)=CURDATE() AND status!='Cancelled'"))['v']);
$today_orders  = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders WHERE DATE(created_at)=CURDATE()"))['v']);
$pending_count = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM orders WHERE status='Pending'"))['v']);
$new_customers = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM users WHERE role='customer' AND DATE(created_at)=CURDATE()"))['v']);
$out_of_stock  = (int)  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS v FROM products WHERE stock=0"))['v']);



/* ── Order status ── */
$breakdown  = mysqli_fetch_all(mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status"), MYSQLI_ASSOC);
$status_map = [];
foreach ($breakdown as $b) $status_map[$b['status']] = (int)$b['cnt'];
$total_all  = array_sum($status_map) ?: 1;

/* ── Recent orders ── */
$recent = mysqli_fetch_all(mysqli_query($conn,
    "SELECT o.id, u.name AS customer, o.total, o.status, o.created_at
     FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 7"), MYSQLI_ASSOC);

/* ── Top products ── */
$top_products = mysqli_fetch_all(mysqli_query($conn,
    "SELECT p.id, p.name, p.price, p.stock, p.image,
            COALESCE(SUM(oi.quantity),0) AS sold,
            COALESCE(SUM(oi.quantity*oi.price),0) AS revenue
     FROM products p LEFT JOIN order_items oi ON oi.product_id=p.id
     GROUP BY p.id ORDER BY sold DESC LIMIT 6"), MYSQLI_ASSOC);
$max_sold = max(array_column($top_products, 'sold') ?: [1]);

/* ── Low stock ── */
$low_stock_items = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, name, stock FROM products WHERE stock>0 AND stock<=5 ORDER BY stock ASC LIMIT 6"), MYSQLI_ASSOC);

/* ── Recent customers ── */
$recent_customers = mysqli_fetch_all(mysqli_query($conn,
    "SELECT name, email, created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT 5"), MYSQLI_ASSOC);

$greeting   = date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening');
$first_name = htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'Admin')[0]);

function sb(string $s): string {
    $m = ['Pending'=>'badge-pending','Processing'=>'badge-processing','Shipped'=>'badge-shipped','Delivered'=>'badge-delivered','Cancelled'=>'badge-cancelled'];
    return '<span class="order-badge '.($m[$s]??'badge-pending').'">'.htmlspecialchars($s).'</span>';
}

adminHead('Dashboard', 'dashboard');
?>
<style>
/* ══════════════════════════════════════
   DASHBOARD v3 — self-contained styles
══════════════════════════════════════ */
:root {
  --dc: #111118;
  --db: rgba(255,255,255,0.07);
  --dbh: rgba(255,255,255,0.13);
  --brand-red: #c8973c;
  --dblue: #4f8ef7;
  --dpurp: #8b5cf6;
  --dgrn:  #10b981;
  --dred:  #ef4444;
  --damb:  #f59e0b;
  --dtext: #f0eff7;
  --dsub:  #9997a6;
  --ddim:  #44435a;
  --dr: 10px;
}

/* Welcome */
.dw { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.dw-left h2 { font-size:22px; font-weight:700; color:var(--dtext); letter-spacing:-.4px; margin:0 0 4px; }
.dw-left p  { font-size:13px; color:var(--dsub); margin:0; }
.dw-clock   { font-size:13px; color:var(--dsub); }
.dw-btns    { display:flex; gap:8px; }
.d-btn      { display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; font-family:inherit; transition:.15s; text-decoration:none; }
.d-btn-p    { background:var(--brand-red); color:#000; }
.d-btn-p:hover { background:#d4a835; }
.d-btn-g    { background:rgba(255,255,255,.05); color:var(--dsub); border:1px solid var(--db); }
.d-btn-g:hover{ background:rgba(255,255,255,.09); color:var(--dtext); }

/* Snapshot strip */
.dsnap      { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; }
.dsn        { background:var(--dc); border:1px solid var(--db); border-radius:var(--dr); padding:14px 16px; transition:.2s; }
.dsn:hover  { border-color:var(--dbh); transform:translateY(-1px); }
.dsn--warn  { border-color:rgba(245,158,11,.3); }
.dsn--danger{ border-color:rgba(239,68,68,.3); }
.dsn-ico    { margin-bottom:8px; }
.dsn-lbl    { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--dsub); margin-bottom:4px; }
.dsn-val    { font-size:22px; font-weight:700; color:var(--dtext); letter-spacing:-.5px; line-height:1; }
.dsn-link   { text-decoration:none; display:block; color:inherit; }
.dkc-link   { text-decoration:none; display:block; color:inherit; }
.dsn:hover .dsn-lbl { color:var(--brand-red); }
.dkc:hover .dkc-label { color:var(--brand-red); }


/* KPI grid */
.dkpi-grid  { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.dkc        { background:var(--dc); border:1px solid var(--db); border-radius:var(--dr); padding:20px; position:relative; overflow:hidden; transition:.2s; }
.dkc:hover  { border-color:var(--dbh); transform:translateY(-2px); box-shadow:0 8px 32px rgba(0,0,0,.3); }
.dkc::before{ content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--kc,#c8973c); border-radius:2px 0 0 2px; }
.dkc-icon   { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-bottom:14px; background:var(--ki,rgba(200,151,60,.1)); color:var(--kc,#c8973c); }
.dkc-label  { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--dsub); margin-bottom:6px; }
.dkc-value  { font-size:26px; font-weight:700; letter-spacing:-.8px; color:var(--dtext); line-height:1; margin-bottom:6px; }
.dkc-sub    { font-size:11px; color:var(--dsub); }
.dkc-up     { color:var(--dgrn); }
.dkc-down   { color:var(--dred); }
.dkc-warn   { color:var(--damb); }

/* Chart panel */
.dpanel     { background:var(--dc); border:1px solid var(--db); border-radius:var(--dr); overflow:hidden; }
.dpanel-head{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--db); flex-wrap:wrap; gap:8px; }
.dpanel-title{ font-size:14px; font-weight:600; color:var(--dtext); margin:0; }
.dpanel-sub { font-size:11px; color:var(--dsub); margin:2px 0 0; }
.dpanel-link{ font-size:12px; color:var(--brand-red); font-weight:500; }
.dpanel-link:hover{ text-decoration:underline; }
.dlegend    { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--dsub); }
.dleg-dot   { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.dchart-box { padding:16px 20px 20px; height:270px; position:relative; }
.dchart-box canvas { width:100%!important; height:100%!important; }

/* Two-column row */
.drow       { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start; }
.drow-2col  { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start; }

/* Table */
.dtbl-wrap  { overflow-x:auto; }
.dtbl       { width:100%; border-collapse:collapse; font-size:13px; }
.dtbl th    { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--dsub); text-align:left; border-bottom:1px solid var(--db); background:rgba(0,0,0,.2); white-space:nowrap; }
.dtbl td    { padding:12px 16px; border-bottom:1px solid rgba(255,255,255,.04); color:var(--dsub); vertical-align:middle; }
.dtbl tr:last-child td{ border-bottom:none; }
.dtbl tbody tr:hover td{ background:rgba(255,255,255,.02); }
.dtbl-id    { font-weight:700; color:var(--dtext); font-family:monospace; font-size:12px; }
.dtbl-amt   { font-weight:700; color:var(--dtext); }
.dtbl-act   { font-size:12px; font-weight:600; color:var(--brand-red); }
.dtbl-act:hover{ text-decoration:underline; }
.dtbl-date  { font-size:12px; color:var(--ddim); white-space:nowrap; }
.dtbl-name  { color:var(--dtext); font-weight:500; }
.dblank     { text-align:center; color:var(--ddim); padding:32px!important; font-size:13px; }

/* Status list */
.dstat-list { padding:12px 20px 16px; display:flex; flex-direction:column; gap:10px; }
.dstat-item { display:flex; flex-direction:column; gap:5px; }
.dstat-meta { display:flex; align-items:center; justify-content:space-between; }
.dstat-cnt  { font-size:13px; font-weight:600; color:var(--dtext); }
.dstat-pct  { font-size:11px; color:var(--ddim); margin-left:4px; font-weight:400; }
.dbar       { height:4px; background:rgba(255,255,255,.06); border-radius:2px; overflow:hidden; }
.dbar-fill  { height:100%; border-radius:2px; }

/* Donut */
.ddonut-wrap{ position:relative; display:flex; justify-content:center; padding:16px 0 8px; }
.ddonut-ctr { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; pointer-events:none; }
.ddonut-num { font-size:22px; font-weight:700; color:var(--dtext); line-height:1; display:block; }
.ddonut-lbl { font-size:11px; color:var(--dsub); display:block; margin-top:2px; }

/* Top products */
.dprod-list { padding:4px 20px 16px; display:flex; flex-direction:column; }
.dprod-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.04); }
.dprod-item:last-child{ border-bottom:none; }
.dprod-rank { width:20px; height:20px; border-radius:50%; background:rgba(255,255,255,.05); border:1px solid var(--db); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--ddim); flex-shrink:0; }
.dprod-thumb{ width:36px; height:36px; border-radius:6px; object-fit:contain; background:rgba(255,255,255,.03); border:1px solid var(--db); padding:2px; flex-shrink:0; }
.dprod-info { flex:1; min-width:0; }
.dprod-name { font-size:13px; font-weight:500; color:var(--dtext); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; margin-bottom:3px; }
.dprod-bar  { height:3px; background:rgba(255,255,255,.06); border-radius:2px; overflow:hidden; margin-top:5px; }
.dprod-bfil { height:100%; border-radius:2px; background:var(--brand-red); }
.dprod-meta { font-size:10px; color:var(--ddim); }
.dprod-price{ text-align:right; flex-shrink:0; }
.dprod-amt  { font-size:13px; font-weight:600; color:var(--brand-red); display:block; }
.dprod-sold { font-size:10px; color:var(--ddim); display:block; margin-top:2px; }

/* Low stock */
.dstock-list{ padding:6px 20px 14px; display:flex; flex-direction:column; gap:9px; }
.dstock-item{ display:flex; flex-direction:column; gap:4px; }
.dstock-meta{ display:flex; align-items:center; justify-content:space-between; gap:8px; }
.dstock-name{ font-size:12px; color:var(--dsub); font-weight:500; min-width:0; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dstock-name:hover{ color:var(--brand-red); }
.dstock-qty { font-size:12px; font-weight:700; flex-shrink:0; }
.dstock-bar { height:3px; background:rgba(255,255,255,.06); border-radius:2px; overflow:hidden; }

/* Customers */
.dcust-list { padding:2px 20px 14px; display:flex; flex-direction:column; }
.dcust-row  { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.04); }
.dcust-row:last-child{ border-bottom:none; }
.dcust-av   { width:30px; height:30px; border-radius:50%; background:rgba(200,151,60,.1); color:var(--brand-red); font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dcust-inf  { flex:1; min-width:0; }
.dcust-nm   { font-size:13px; font-weight:500; color:var(--dtext); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dcust-em   { font-size:11px; color:var(--ddim); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dcust-dt   { font-size:11px; color:var(--ddim); white-space:nowrap; }

/* Alert badge */
.dalert     { display:inline-flex; align-items:center; justify-content:center; min-width:17px; height:17px; padding:0 5px; border-radius:9px; background:rgba(239,68,68,.12); color:var(--dred); font-size:10px; font-weight:700; margin-left:6px; border:1px solid rgba(239,68,68,.2); vertical-align:middle; }

/* Empty */
.dempty     { padding:24px 20px; display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--ddim); font-size:13px; text-align:center; }

/* Responsive */
@media(max-width:1300px){ .dkpi-grid{ grid-template-columns:repeat(2,1fr); } .dsnap{grid-template-columns:repeat(3,1fr);} }
@media(max-width:1100px){ .drow,.drow-2col{ grid-template-columns:1fr; } }
@media(max-width:900px) { .dsnap{grid-template-columns:repeat(2,1fr);} .dw{flex-direction:column;align-items:flex-start;} }
@media(max-width:640px) { .dkpi-grid{grid-template-columns:1fr;} .dsnap{grid-template-columns:1fr 1fr;} }
</style>

<!-- ── WELCOME ── -->
<div class="dw">
  <div class="dw-left">
    <h2>Good <?= $greeting ?>, <?= $first_name ?> 👋</h2>
    <p id="dashClock" class="dw-clock"><?= date('l, d F Y') ?></p>
  </div>
  <div class="dw-btns">
    <a href="add_product.php" class="d-btn d-btn-p">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Product
    </a>
    <a href="orders.php" class="d-btn d-btn-g">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Orders
    </a>
    <a href="products.php" class="d-btn d-btn-g">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
      Products
    </a>
  </div>
</div>

<!-- ── SNAPSHOT STRIP ── -->
<div class="dsnap">
  <a href="orders.php?date=today" class="dsn-link">
    <div class="dsn">
      <div class="dsn-ico"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#c8973c" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
      <div class="dsn-lbl">Today's Revenue</div>
      <div class="dsn-val">£<?= number_format($today_revenue, 2) ?></div>
    </div>
  </a>
  <a href="orders.php?date=today" class="dsn-link">
    <div class="dsn">
      <div class="dsn-ico"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4f8ef7" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
      <div class="dsn-lbl">Orders Today</div>
      <div class="dsn-val"><?= $today_orders ?></div>
    </div>
  </a>
  <a href="orders.php?status=Pending" class="dsn-link">
    <div class="dsn <?= $pending_count > 0 ? 'dsn--warn' : '' ?>">
      <div class="dsn-ico"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="<?= $pending_count > 0 ? '#f59e0b' : '#44435a' ?>" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
      <div class="dsn-lbl">Pending Orders</div>
      <div class="dsn-val" <?= $pending_count > 0 ? 'style="color:#f59e0b"' : '' ?>><?= $pending_count ?></div>
    </div>
  </a>
  <a href="customers.php?joined=today" class="dsn-link">
    <div class="dsn">
      <div class="dsn-ico"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
      <div class="dsn-lbl">New Customers</div>
      <div class="dsn-val"><?= $new_customers ?></div>
    </div>
  </a>
  <a href="products.php?stock=out" class="dsn-link">
    <div class="dsn <?= $out_of_stock > 0 ? 'dsn--danger' : '' ?>">
      <div class="dsn-ico"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="<?= $out_of_stock > 0 ? '#ef4444' : '#44435a' ?>" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg></div>
      <div class="dsn-lbl">Out of Stock</div>
      <div class="dsn-val" <?= $out_of_stock > 0 ? 'style="color:#ef4444"' : '' ?>><?= $out_of_stock ?></div>
    </div>
  </a>
</div>

<!-- ── KPI CARDS ── -->
<div class="dkpi-grid">

  <a href="orders.php" class="dkc-link">
    <div class="dkc" style="--kc:#c8973c;--ki:rgba(200,151,60,.1)">
      <div class="dkc-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      </div>
      <div class="dkc-label">Total Revenue</div>
    <div class="dkc-value" data-count="<?= $total_revenue ?>" data-prefix="£">£<?= number_format($total_revenue, 2) ?></div>
    <div class="dkc-sub">Monthly Revenue Total</div>
    </div>
  </a>

  <a href="orders.php" class="dkc-link">
    <div class="dkc" style="--kc:#4f8ef7;--ki:rgba(79,142,247,.1)">
      <div class="dkc-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div class="dkc-label">Total Orders</div>
      <div class="dkc-value" data-count="<?= $total_orders ?>"><?= number_format($total_orders) ?></div>
      <?php if ($pending_count > 0): ?>
      <div class="dkc-sub dkc-warn"><?= $pending_count ?> pending attention</div>
      <?php else: ?><div class="dkc-sub">All orders processed</div><?php endif; ?>
    </div>
  </a>

  <a href="products.php" class="dkc-link">
    <div class="dkc" style="--kc:#8b5cf6;--ki:rgba(139,92,246,.1)">
      <div class="dkc-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      </div>
      <div class="dkc-label">Total Products</div>
      <div class="dkc-value" data-count="<?= $total_products ?>"><?= number_format($total_products) ?></div>
      <?php if (!empty($low_stock_items)): ?>
      <div class="dkc-sub dkc-warn"><?= count($low_stock_items) ?> running low</div>
      <?php else: ?><div class="dkc-sub">Stock levels healthy</div><?php endif; ?>
    </div>
  </a>

  <a href="customers.php" class="dkc-link">
    <div class="dkc" style="--kc:#10b981;--ki:rgba(16,185,129,.1)">
      <div class="dkc-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div class="dkc-label">Total Customers</div>
      <div class="dkc-value" data-count="<?= $total_customers ?>"><?= number_format($total_customers) ?></div>
      <?php if ($new_customers > 0): ?>
      <div class="dkc-sub dkc-up">+<?= $new_customers ?> joined today</div>
      <?php else: ?><div class="dkc-sub">Registered accounts</div><?php endif; ?>
    </div>
  </a>

</div>



<!-- ── MAIN ROW: Orders + Status ── -->
<div class="drow">

  <!-- Recent Orders -->
  <div class="dpanel">
    <div class="dpanel-head">
      <h2 class="dpanel-title">Recent Orders</h2>
      <a href="orders.php" class="dpanel-link">View all →</a>
    </div>
    <div class="dtbl-wrap">
      <table class="dtbl">
        <thead><tr><th>#</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td class="dtbl-id">#<?= $r['id'] ?></td>
            <td class="dtbl-name"><?= htmlspecialchars($r['customer']) ?></td>
            <td class="dtbl-amt">£<?= number_format((float)$r['total'], 2) ?></td>
            <td><?= sb($r['status']) ?></td>
            <td class="dtbl-date"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td><a href="orders.php?view=<?= $r['id'] ?>" class="dtbl-act">View</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recent)): ?><tr><td colspan="6" class="dblank">No orders yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Order Status -->
  <div class="dpanel">
    <div class="dpanel-head">
      <h2 class="dpanel-title">Order Status</h2>
      <a href="orders.php" class="dpanel-link">Manage →</a>
    </div>
    <div class="ddonut-wrap">
      <canvas id="statusDonut" width="160" height="160"></canvas>
      <div class="ddonut-ctr">
        <span class="ddonut-num"><?= $total_orders ?></span>
        <span class="ddonut-lbl">Total</span>
      </div>
    </div>
    <div class="dstat-list">
      <?php
      $statuses = ['Delivered'=>['badge-delivered','#10b981'],'Processing'=>['badge-processing','#4f8ef7'],'Shipped'=>['badge-shipped','#8b5cf6'],'Pending'=>['badge-pending','#f59e0b'],'Cancelled'=>['badge-cancelled','#ef4444']];
      foreach ($statuses as $label => $cfg):
          $cnt = $status_map[$label] ?? 0;
          $pct = round($cnt / $total_all * 100);
      ?>
      <div class="dstat-item">
        <div class="dstat-meta">
          <span class="order-badge <?= $cfg[0] ?>"><?= $label ?></span>
          <span class="dstat-cnt"><?= $cnt ?><span class="dstat-pct">(<?= $pct ?>%)</span></span>
        </div>
        <div class="dbar"><div class="dbar-fill" style="width:<?= $pct ?>%;background:<?= $cfg[1] ?>"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- ── BOTTOM ROW: Products + Alerts/Customers ── -->
<div class="drow-2col">

  <!-- Top Products -->
  <div class="dpanel">
    <div class="dpanel-head">
      <h2 class="dpanel-title">Top Selling Products</h2>
      <a href="products.php" class="dpanel-link">View all →</a>
    </div>
    <div class="dprod-list">
      <?php foreach ($top_products as $i => $tp):
        $img = $tp['image'] ? '/melody-masters-online-store/assets/images/'.htmlspecialchars($tp['image']) : '';
        $bar_w = $max_sold > 0 ? round($tp['sold'] / $max_sold * 100) : 0;
        $sc = $tp['stock'] == 0 ? '#ef4444' : ($tp['stock'] <= 5 ? '#f59e0b' : '#10b981');
      ?>
      <a href="edit_product.php?id=<?= $tp['id'] ?>" class="dkc-link">
        <div class="dprod-item">
          <span class="dprod-rank"><?= $i+1 ?></span>
          <?php if ($img): ?><img src="<?= $img ?>" class="dprod-thumb" alt=""><?php endif; ?>
          <div class="dprod-info">
            <span class="dprod-name"><?= htmlspecialchars($tp['name']) ?></span>
            <div class="dprod-bar"><div class="dprod-bfil" style="width:<?= $bar_w ?>%"></div></div>
            <span class="dprod-meta"><?= $tp['sold'] ?> sold &middot; <span style="color:<?= $sc ?>"><?= $tp['stock'] ?> in stock</span></span>
          </div>
          <div class="dprod-price">
            <span class="dprod-amt">£<?= number_format((float)$tp['price'], 2) ?></span>
            <?php if ($tp['revenue'] > 0): ?><span class="dprod-sold">£<?= number_format((float)$tp['revenue'], 0) ?> rev.</span><?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($top_products)): ?><div class="dempty"><p>No products yet.</p></div><?php endif; ?>
    </div>
  </div>

  <!-- Right: Low Stock + Customers -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Low Stock -->
    <div class="dpanel">
      <div class="dpanel-head">
        <h2 class="dpanel-title">Low Stock<?php if (!empty($low_stock_items)): ?><span class="dalert"><?= count($low_stock_items) ?></span><?php endif; ?></h2>
        <a href="products.php?stock=low" class="dpanel-link">Manage →</a>
      </div>
      <?php if (empty($low_stock_items)): ?>
      <div class="dempty">
        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#44435a" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
        <p>All products well stocked</p>
      </div>
      <?php else: ?>
      <div class="dstock-list">
        <?php foreach ($low_stock_items as $ls):
          $c = $ls['stock'] <= 2 ? '#ef4444' : '#f59e0b';
          $p = min(100, round($ls['stock'] / 5 * 100));
        ?>
        <div class="dstock-item">
          <div class="dstock-meta">
            <a href="edit_product.php?id=<?= $ls['id'] ?>" class="dstock-name"><?= htmlspecialchars($ls['name']) ?></a>
            <span class="dstock-qty" style="color:<?= $c ?>"><?= $ls['stock'] ?> left</span>
          </div>
          <div class="dstock-bar"><div class="dbar-fill" style="width:<?= $p ?>%;background:<?= $c ?>"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Customers -->
    <div class="dpanel">
      <div class="dpanel-head">
        <h2 class="dpanel-title">New Customers</h2>
        <a href="customers.php" class="dpanel-link">View all →</a>
      </div>
      <?php if (empty($recent_customers)): ?>
      <div class="dempty"><p>No customers yet.</p></div>
      <?php else: ?>
      <div class="dcust-list">
        <?php foreach ($recent_customers as $rc): ?>
        <a href="customers.php?q=<?= urlencode($rc['email']) ?>" class="dkc-link">
          <div class="dcust-row">
            <div class="dcust-av"><?= strtoupper(substr($rc['name'],0,1)) ?></div>
            <div class="dcust-inf">
              <span class="dcust-nm"><?= htmlspecialchars($rc['name']) ?></span>
              <span class="dcust-em"><?= htmlspecialchars($rc['email']) ?></span>
            </div>
            <span class="dcust-dt"><?= date('d M', strtotime($rc['created_at'])) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function(){
  /* Live clock */
  function tick(){
    var d=new Date(), el=document.getElementById('dashClock');
    if(!el) return;
    var days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var months=['January','February','March','April','May','June','July','August','September','October','November','December'];
    var h=d.getHours(), m=('0'+d.getMinutes()).slice(-2);
    el.textContent=days[d.getDay()]+', '+d.getDate()+' '+months[d.getMonth()]+' '+d.getFullYear()+' · '+(h>12?h-12:h||12)+':'+m+(h>=12?' PM':' AM');
  }
  tick(); setInterval(tick,10000);

  /* Chart defaults */
  Chart.defaults.color='#9997a6';
  Chart.defaults.font.family="'Inter',sans-serif";
  Chart.defaults.font.size=11;



  /* Donut */
  var sd=document.getElementById('statusDonut');
  if(sd){
    new Chart(sd.getContext('2d'),{type:'doughnut',data:{
      labels:['Delivered','Processing','Shipped','Pending','Cancelled'],
      datasets:[{
        data:[<?= implode(',', [$status_map['Delivered']??0,$status_map['Processing']??0,$status_map['Shipped']??0,$status_map['Pending']??0,$status_map['Cancelled']??0]) ?>],
        backgroundColor:['#10b981','#4f8ef7','#8b5cf6','#f59e0b','#ef4444'],
        borderColor:'#111118',borderWidth:3,hoverOffset:5
      }]
    },options:{
      responsive:false,cutout:'72%',
      plugins:{legend:{display:false},
        tooltip:{backgroundColor:'#16161f',borderColor:'rgba(255,255,255,.1)',borderWidth:1,
          callbacks:{label:function(c){return '  '+c.label+': '+c.parsed;}}}}
    }});
  }

})();
</script>

<?php adminFoot(); ?>