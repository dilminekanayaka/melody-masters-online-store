<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: products.php"); exit; }
$pid = (int)$_GET['id'];

$prod_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($prod_stmt, "i", $pid);
mysqli_stmt_execute($prod_stmt);
$prod = mysqli_fetch_assoc(mysqli_stmt_get_result($prod_stmt));
if (!$prod) { header("Location: products.php"); exit; }

// Load existing digital_products record
$dp_stmt = mysqli_prepare($conn, "SELECT * FROM digital_products WHERE product_id = ?");
mysqli_stmt_bind_param($dp_stmt, "i", $pid);
mysqli_stmt_execute($dp_stmt);
$dp = mysqli_fetch_assoc(mysqli_stmt_get_result($dp_stmt)) ?: ['file_path'=>'','download_limit'=>0];

$categories = mysqli_fetch_all(mysqli_query($conn, "SELECT id, name, type FROM categories ORDER BY name"), MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the POST request was truncated by PHP (happens if post_max_size is exceeded)
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_post = ini_get('post_max_size');
        $errors[] = "The file you tried to upload is too large. The server limit is currently $max_post. Please try a smaller file or ask your administrator to increase the limit.";
    }

    $name        = trim($_POST['name']         ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $shipping_cost = (float)($_POST['shipping_cost'] ?? 0);
    $stock       = (int)($_POST['stock']        ?? 0);
    $type        = in_array($_POST['type']??'', ['physical','digital']) ? $_POST['type'] : 'physical';
    
    // Digital products have no shipping and unlimited stock
    if ($type === 'digital') {
        $stock = 999999;
        $shipping_cost = 0;
    }
    
    $description = trim($_POST['description']  ?? '');
    $dl_limit    = max(0, (int)($_POST['download_limit'] ?? 0));

    if (!$name)        $errors[] = "Product name is required.";
    if ($type === 'physical' && !$category_id)
                       $errors[] = "Please select a category.";
    if ($price <= 0)   $errors[] = "Price must be greater than 0.";

    $image_filename = $prod['image']; // keep existing by default
    if (!empty($_FILES['image']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Image must be JPG, PNG, WebP or GIF.";
        } else {
            $new_filename = uniqid('prod_') . '.' . $ext;
            $dest = __DIR__ . '/../assets/images/' . $new_filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                if ($prod['image']) @unlink(__DIR__ . '/../assets/images/' . $prod['image']);
                $image_filename = $new_filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    // Handle new digital file upload
    $new_file_path = $dp['file_path']; // keep existing
    if ($type === 'digital' && !empty($_FILES['digital_file']['name'])) {
        $dl_ext     = strtolower(pathinfo($_FILES['digital_file']['name'], PATHINFO_EXTENSION));
        $dl_allowed = ['pdf','mp3','mp4','zip','epub','wav','flac','png','jpg','jpeg'];
        if (!in_array($dl_ext, $dl_allowed)) {
            $errors[] = "Digital file must be PDF, MP3, MP4, ZIP, EPUB, WAV or FLAC.";
        } else {
            $dl_filename = uniqid('dl_') . '.' . $dl_ext;
            $dl_dest = __DIR__ . '/../assets/downloads/' . $dl_filename;
            if (!is_dir(dirname($dl_dest))) mkdir(dirname($dl_dest), 0755, true);
            if (move_uploaded_file($_FILES['digital_file']['tmp_name'], $dl_dest)) {
                if ($dp['file_path']) @unlink(__DIR__ . '/../assets/downloads/' . $dp['file_path']);
                $new_file_path = $dl_filename;
            } else {
                $errors[] = "Failed to upload digital file.";
            }
        }
    }

    if (empty($errors)) {
        $cat_val = $category_id;
        $upd = mysqli_prepare($conn,
            "UPDATE products SET category_id=?, name=?, price=?, shipping_cost=?, stock=?, type=?, image=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($upd, "isddisssi",
            $cat_val, $name, $price, $shipping_cost, $stock, $type, $image_filename, $description, $pid);
        mysqli_stmt_execute($upd);

        if ($type === 'digital') {
            // Upsert digital_products
            $ex = mysqli_prepare($conn, "SELECT id FROM digital_products WHERE product_id = ?");
            mysqli_stmt_bind_param($ex, "i", $pid);
            mysqli_stmt_execute($ex);
            $exists = mysqli_fetch_row(mysqli_stmt_get_result($ex));
            if ($exists) {
                $du = mysqli_prepare($conn,
                    "UPDATE digital_products SET file_path=?, download_limit=? WHERE product_id=?");
                mysqli_stmt_bind_param($du, "sii", $new_file_path, $dl_limit, $pid);
                mysqli_stmt_execute($du);
            } else {
                $di = mysqli_prepare($conn,
                    "INSERT INTO digital_products (product_id, file_path, download_limit) VALUES (?,?,?)");
                mysqli_stmt_bind_param($di, "isi", $pid, $new_file_path, $dl_limit);
                mysqli_stmt_execute($di);
            }
        } else {
            // Remove from digital_products if switched to physical
            $del = mysqli_prepare($conn, "DELETE FROM digital_products WHERE product_id = ?");
            mysqli_stmt_bind_param($del, "i", $pid);
            mysqli_stmt_execute($del);
        }

        header("Location: products.php?saved=1"); exit;
    }

    // Re-fill from POST on error
    $prod = array_merge($prod, compact('name','category_id','price','shipping_cost','stock','type','description'));
    $dp   = ['file_path' => $new_file_path, 'download_limit' => $dl_limit];
}

adminHead('Edit Product', 'products');
?>

<?php if (!empty($errors)): ?>
<div class="admin-flash admin-flash--err">
  <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="admin-panel admin-form-panel">
  <div class="admin-panel-head">
    <h2 class="admin-panel-title">Edit Product #<?= $pid ?></h2>
    <a href="products.php" class="admin-btn admin-btn--ghost">← Back</a>
  </div>

  <form method="POST" enctype="multipart/form-data" class="admin-form">

    <div class="admin-form-grid">

      <div class="admin-form-group admin-col-2">
        <label class="admin-form-label">Product Name *</label>
        <input type="text" name="name" class="admin-input"
               value="<?= htmlspecialchars($prod['name']) ?>" required>
      </div>

      <!-- Type toggle -->
      <div class="admin-form-group admin-col-2">
        <label class="admin-form-label">Product Type *</label>
        <div style="display:flex;gap:12px;">
          <label class="admin-type-radio <?= $prod['type']==='physical' ? 'is-selected' : '' ?>" id="lbl-physical">
            <input type="radio" name="type" value="physical" <?= $prod['type']==='physical' ? 'checked' : '' ?>>
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            Physical Product
          </label>
          <label class="admin-type-radio <?= $prod['type']==='digital' ? 'is-selected' : '' ?>" id="lbl-digital">
            <input type="radio" name="type" value="digital" <?= $prod['type']==='digital' ? 'checked' : '' ?>>
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Digital Download
          </label>
        </div>
      </div>

      <!-- Category — filtered by selected product type -->
      <div class="admin-form-group" id="row-category">
        <label class="admin-form-label">Category *</label>
        <select name="category_id" class="admin-select" id="catSelect">
          <option value="">— Select category —</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"
                  data-type="<?= htmlspecialchars($c['type']) ?>"
                  <?= (int)$prod['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label">Price (£) *</label>
        <input type="number" name="price" class="admin-input" step="0.01" min="0.01"
               value="<?= htmlspecialchars($prod['price']) ?>" required>
      </div>

      <!-- Stock — physical only -->
      <div class="admin-form-group" id="row-stock">
        <label class="admin-form-label">Stock Quantity</label>
        <input type="number" name="stock" class="admin-input" min="0"
               value="<?= (int)$prod['stock'] ?>">
      </div>

      <!-- Shipping — physical only -->
      <div class="admin-form-group" id="row-shipping">
        <label class="admin-form-label">Shipping Cost (£)</label>
        <input type="number" name="shipping_cost" class="admin-input" step="0.01" min="0" placeholder="0.00"
               value="<?= htmlspecialchars($prod['shipping_cost'] ?? '0.00') ?>">
      </div>

      <!-- Current digital file info -->
      <div class="admin-form-group" id="row-digital-file" style="display:none;">
        <label class="admin-form-label">Digital File <span style="color:var(--dim);font-weight:400;">(PDF, MP3, MP4, ZIP, EPUB, WAV, FLAC…)</span></label>
        <?php if ($dp['file_path']): ?>
        <p style="font-size:12px;color:var(--muted);margin:0 0 8px;">
          Current file: <strong style="color:var(--white);"><?= htmlspecialchars($dp['file_path']) ?></strong>
          — upload a new file below to replace it
        </p>
        <?php endif; ?>
        <div class="admin-file-drop" id="dlFileDrop">
          <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <p><?= $dp['file_path'] ? 'Upload new file to replace' : 'Drag &amp; drop or' ?> <label for="dlFileInput" class="admin-file-label">browse</label></p>
          <span class="admin-file-hint" id="dlFileHint">No file chosen</span>
          <input type="file" name="digital_file" id="dlFileInput" class="admin-file-input"
                 accept=".pdf,.mp3,.mp4,.zip,.epub,.wav,.flac,.png,.jpg,.jpeg">
        </div>
      </div>

      <!-- Download limit — digital only -->
      <div class="admin-form-group" id="row-dl-limit" style="display:none;">
        <label class="admin-form-label">Download Limit <span style="color:var(--dim);font-weight:400;">(0 = unlimited)</span></label>
        <input type="number" name="download_limit" class="admin-input" min="0"
               value="<?= (int)$dp['download_limit'] ?>">
      </div>

      <div class="admin-form-group admin-col-2">
        <label class="admin-form-label">Description</label>
        <textarea name="description" class="admin-textarea" rows="5"><?= htmlspecialchars($prod['description'] ?? '') ?></textarea>
      </div>

      <div class="admin-form-group admin-col-2">
        <label class="admin-form-label">Product Image <span style="color:var(--dim);font-weight:400;">(thumbnail)</span></label>
        <?php if ($prod['image']): ?>
        <div class="admin-current-image">
          <img src="/melody-masters-online-store/assets/images/<?= htmlspecialchars($prod['image']) ?>" alt="">
          <span class="admin-current-label">Current image</span>
        </div>
        <?php endif; ?>
        <div class="admin-file-drop" id="fileDrop" style="margin-top:10px;">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <p><?= $prod['image'] ? 'Upload new image to replace' : 'Drag &amp; drop or' ?> <label for="imageInput" class="admin-file-label">browse</label></p>
          <input type="file" name="image" id="imageInput" accept="image/*" class="admin-file-input">
        </div>
        <div class="admin-image-preview" id="imagePreview" style="display:none;">
          <img id="previewImg" src="" alt="Preview">
          <button type="button" class="admin-image-remove" id="removeImg">✕</button>
        </div>
      </div>

    </div>

    <div class="admin-form-actions">
      <a href="products.php" class="admin-btn admin-btn--ghost">Cancel</a>
      <button type="submit" class="admin-btn admin-btn--primary">Save Changes</button>
    </div>
  </form>
</div>

<style>
.admin-type-radio {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 8px; cursor: pointer;
  border: 1px solid var(--line-md); color: var(--mid);
  font-size: 13px; font-weight: 500; transition: all 0.15s;
  user-select: none;
}
.admin-type-radio input[type="radio"] { display: none; }
.admin-type-radio:hover { border-color: var(--gold); color: var(--white); }
.admin-type-radio.is-selected {
  border-color: var(--gold);
  background: rgba(200,151,60,0.08);
  color: var(--gold);
}
</style>
<script>
var radios    = document.querySelectorAll('input[name="type"]');
var lblPhys   = document.getElementById('lbl-physical');
var lblDig    = document.getElementById('lbl-digital');
var rowCat    = document.getElementById('row-category');
var rowStock  = document.getElementById('row-stock');
var rowShipping = document.getElementById('row-shipping');
var rowDlFile = document.getElementById('row-digital-file');
var rowDlLim  = document.getElementById('row-dl-limit');
var catSel    = document.getElementById('catSelect');

function applyType(val) {
  var isDigital = val === 'digital';
  lblPhys.classList.toggle('is-selected', !isDigital);
  lblDig.classList.toggle('is-selected',   isDigital);
  
  // Keep category visible for both
  rowCat.style.display    = '';
  catSel.required         = true;

  rowStock.style.display  = isDigital ? 'none' : '';
  rowShipping.style.display = isDigital ? 'none' : '';
  rowDlFile.style.display = isDigital ? ''     : 'none';
  rowDlLim.style.display = isDigital ? ''     : 'none';

  // Filter category options to only show those matching the product type
  var opts = catSel.querySelectorAll('option[data-type]');
  opts.forEach(function(o) {
    var match = o.getAttribute('data-type') === val;
    o.style.display = match ? '' : 'none';
    if (!match && o.selected) { catSel.value = ''; }
  });
}
radios.forEach(function(r){ r.addEventListener('change', function(){ applyType(this.value); }); });
applyType(document.querySelector('input[name="type"]:checked').value);

// Image preview
var imgInput  = document.getElementById('imageInput');
var preview   = document.getElementById('imagePreview');
var preImg    = document.getElementById('previewImg');
var drop      = document.getElementById('fileDrop');
var removeBtn = document.getElementById('removeImg');

imgInput.addEventListener('change', function() {
  if (!this.files[0]) return;
  var r = new FileReader();
  r.onload = function(e){ preImg.src=e.target.result; preview.style.display='block'; drop.style.display='none'; };
  r.readAsDataURL(this.files[0]);
});
if (removeBtn) removeBtn.addEventListener('click', function(){
  imgInput.value=''; preview.style.display='none'; drop.style.display='flex';
});

// Digital file name display
document.getElementById('dlFileInput').addEventListener('change', function() {
  document.getElementById('dlFileHint').textContent = this.files[0] ? this.files[0].name : 'No file chosen';
});
</script>

<?php adminFoot(); ?>
