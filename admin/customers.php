<?php
require_once __DIR__ . '/auth.php';
require_manager(); 
require_once __DIR__ . '/layout.php';

$search        = trim($_GET['q']   ?? '');
$filter_joined = $_GET['joined'] ?? '';
$page          = max(1, (int)($_GET['p'] ?? 1));
$per_page      = 20;
$offset        = ($page - 1) * $per_page;

$where  = "WHERE u.role = 'customer'";
$params = [];
$types  = "";
if ($search) { $where .= " AND (u.name LIKE ? OR u.email LIKE ?)"; $like = "%$search%"; $params = [$like,$like]; $types = "ss"; }
if ($filter_joined === 'today') { $where .= " AND DATE(u.created_at) = CURDATE()"; }

$cnt = mysqli_prepare($conn, "SELECT COUNT(*) FROM users u $where");
if ($types) mysqli_stmt_bind_param($cnt, $types, ...$params);
mysqli_stmt_execute($cnt);
$total_rows  = mysqli_fetch_row(mysqli_stmt_get_result($cnt))[0];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$lst = mysqli_prepare($conn,
    "SELECT u.id, u.name, u.email, u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total),0) AS total_spent
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     $where
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT $per_page OFFSET $offset"
);
if ($types) mysqli_stmt_bind_param($lst, $types, ...$params);
mysqli_stmt_execute($lst);
$customers = mysqli_fetch_all(mysqli_stmt_get_result($lst), MYSQLI_ASSOC);

adminHead('Customers', 'customers');
?>

<div class="admin-filter-bar">
  <form method="GET" action="customers.php" class="admin-filter-form">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
           placeholder="Search by name or email…" class="admin-input">
    <button type="submit" class="admin-btn admin-btn--primary">Search</button>
    <?php if ($search): ?><a href="customers.php" class="admin-btn admin-btn--ghost">Clear</a><?php endif; ?>
  </form>
  <span class="admin-result-count"><?= number_format($total_rows) ?> customer<?= $total_rows !== 1 ? 's' : '' ?></span>
</div>

<div class="admin-panel">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Orders</th>
          <th>Total Spent</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td class="admin-id"><?= $c['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="admin-cust-avatar"><?= strtoupper(substr($c['name'],0,1)) ?></div>
              <?= htmlspecialchars($c['name']) ?>
            </div>
          </td>
          <td class="admin-muted"><?= htmlspecialchars($c['email']) ?></td>
          <td><?= $c['order_count'] ?></td>
          <td class="admin-amount">£<?= number_format((float)$c['total_spent'], 2) ?></td>
          <td class="admin-date"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="6" class="admin-empty-row">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
  <div class="admin-pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>"
       class="admin-page-btn <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php adminFoot(); ?>
