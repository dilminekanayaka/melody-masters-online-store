<?php
require_once __DIR__ . '/auth.php';   // ensures admin session

header('Content-Type: application/json');

$since = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-60 seconds'));


if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
    $since = date('Y-m-d H:i:s', strtotime('-60 seconds'));
}

$out = ['orders' => [], 'customers' => [], 'server_time' => date('Y-m-d H:i:s')];

/* New orders since $since */
$stmt = mysqli_prepare($conn,
    "SELECT o.id, u.name AS customer, o.total, o.status, o.created_at
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE o.created_at > ?
     ORDER BY o.created_at DESC
     LIMIT 20"
);
mysqli_stmt_bind_param($stmt, 's', $since);
mysqli_stmt_execute($stmt);
$out['orders'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

/* New customers since */
$stmt2 = mysqli_prepare($conn,
    "SELECT name, email, created_at
     FROM users
     WHERE role = 'customer' AND created_at > ?
     ORDER BY created_at DESC
     LIMIT 10"
);
mysqli_stmt_bind_param($stmt2, 's', $since);
mysqli_stmt_execute($stmt2);
$out['customers'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

echo json_encode($out);
