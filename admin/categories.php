<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/* ── DELETE ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Null-out affected products' category before deleting
    mysqli_query($conn, "UPDATE products SET category_id = NULL WHERE category_id = $did");
    $dc = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($dc, "i", $did);
    mysqli_stmt_execute($dc);
    header("Location: categories.php?deleted=1"); exit;
}

/* ── ADD ── */
$err = ''; $cat_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
    $cat_name = trim($_POST['cat_name'] ?? '');
    $cat_type = in_array($_POST['cat_type'] ?? '', ['physical','digital']) ? $_POST['cat_type'] : 'physical';
    if (!$cat_name) {
        $err = "Category name is required.";
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO categories (name, type) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, "ss", $cat_name, $cat_type);
        mysqli_stmt_execute($ins);
        $cat_success = "Category \"{$cat_name}\" added as " . ucfirst($cat_type) . ".";
    }
}

/* ── FETCH grouped by type ── */
$cats = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.id, c.name, c.type, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.type ASC, c.name ASC"), MYSQLI_ASSOC);

$physical_cats = array_filter($cats, function($c) { return $c['type'] === 'physical'; });
$digital_cats  = array_filter($cats, function($c) { return $c['type'] === 'digital'; });

adminHead('Categories', 'categories');
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="admin-flash admin-flash--ok">✓ Category deleted.</div>
<?php endif; ?>
<?php if ($cat_success): ?>
<div class="admin-flash admin-flash--ok">✓ <?= htmlspecialchars($cat_success) ?></div>
<?php elseif ($err): ?>
<div class="admin-flash admin-flash--err"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

  <!-- ── CATEGORY LIST ── -->
  <div>

    <!-- Physical -->
    <div class="admin-panel" style="margin-bottom:20px;">
      <div class="admin-panel-head">
        <h2 class="admin-panel-title">
          <span class="admin-type-physical" style="margin-right:8px;">Physical</span>
          Categories
        </h2>
        <span class="admin-result-count"><?= count($physical_cats) ?> categories</span>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Products</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($physical_cats as $cat): ?>
            <tr>
              <td class="admin-prod-name"><?= htmlspecialchars($cat['name']) ?></td>
              <td><?= $cat['product_count'] ?></td>
              <td>
                <a href="categories.php?delete=<?= $cat['id'] ?>"
                   class="admin-row-action admin-row-action--danger"
                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($cat['name'])) ?>\'?')">Delete</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($physical_cats)): ?>
            <tr><td colspan="3" class="admin-empty-row">No physical categories yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Digital -->
    <div class="admin-panel">
      <div class="admin-panel-head">
        <h2 class="admin-panel-title">
          <span class="admin-type-digital" style="margin-right:8px;">Digital</span>
          Categories
        </h2>
        <span class="admin-result-count"><?= count($digital_cats) ?> categories</span>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Products</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($digital_cats as $cat): ?>
            <tr>
              <td class="admin-prod-name"><?= htmlspecialchars($cat['name']) ?></td>
              <td><?= $cat['product_count'] ?></td>
              <td>
                <a href="categories.php?delete=<?= $cat['id'] ?>"
                   class="admin-row-action admin-row-action--danger"
                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($cat['name'])) ?>\'?')">Delete</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($digital_cats)): ?>
            <tr><td colspan="3" class="admin-empty-row">No digital categories yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ── ADD CATEGORY FORM ── -->
  <div class="admin-panel">
    <div class="admin-panel-head">
      <h2 class="admin-panel-title">Add Category</h2>
    </div>
    <form method="POST" action="categories.php" class="admin-form" style="gap:16px;">

      <div class="admin-form-group">
        <label class="admin-form-label">Category Name *</label>
        <input type="text" name="cat_name" class="admin-input"
               placeholder="e.g. Guitars" required
               value="<?= htmlspecialchars($_POST['cat_name'] ?? '') ?>">
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label">Product Type *</label>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <label class="admin-type-radio <?= ($_POST['cat_type']??'physical')==='physical' ? 'is-selected' : '' ?>" id="lbl-phys">
            <input type="radio" name="cat_type" value="physical"
                   <?= ($_POST['cat_type']??'physical')==='physical' ? 'checked' : '' ?>>
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            Physical
          </label>
          <label class="admin-type-radio <?= ($_POST['cat_type']??'')==='digital' ? 'is-selected' : '' ?>" id="lbl-dig">
            <input type="radio" name="cat_type" value="digital"
                   <?= ($_POST['cat_type']??'')==='digital' ? 'checked' : '' ?>>
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Digital
          </label>
        </div>
        <span style="font-size:11px;color:var(--dim);margin-top:6px;display:block;">
          Physical = shipped goods &nbsp;·&nbsp; Digital = downloadable files
        </span>
      </div>

      <div class="admin-form-actions" style="padding-top:0;">
        <button type="submit" name="add_cat" class="admin-btn admin-btn--primary" style="width:100%;">
          Add Category
        </button>
      </div>

    </form>
  </div>

</div>

<style>
.admin-type-radio {
  display:flex;align-items:center;gap:7px;
  padding:8px 14px;border-radius:8px;cursor:pointer;
  border:1px solid var(--line-md);color:var(--mid);
  font-size:12px;font-weight:500;transition:all .15s;
  user-select:none;flex:1;justify-content:center;
}
.admin-type-radio input[type="radio"] { display:none; }
.admin-type-radio:hover { border-color:var(--gold);color:var(--white); }
.admin-type-radio.is-selected {
  border-color:var(--gold);
  background:rgba(200,151,60,.08);
  color:var(--gold);
}
</style>
<script>
(function(){
  var radios = document.querySelectorAll('input[name="cat_type"]');
  var lblP   = document.getElementById('lbl-phys');
  var lblD   = document.getElementById('lbl-dig');
  radios.forEach(function(r){
    r.addEventListener('change', function(){
      lblP.classList.toggle('is-selected', this.value === 'physical');
      lblD.classList.toggle('is-selected', this.value === 'digital');
    });
  });
})();
</script>

<?php adminFoot(); ?>
