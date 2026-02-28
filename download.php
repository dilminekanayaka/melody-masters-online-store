<?php

include 'includes/init.php';
include 'includes/db.php';

// Guards
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$oid = (int)($_GET['oid'] ?? 0);
$pid = (int)($_GET['pid'] ?? 0);

if (!$oid || !$pid) {
    die("Invalid request.");
}

/* 1. Verify order belongs to user and is valid */
$stmt = mysqli_prepare($conn, "SELECT status FROM orders WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $oid, $uid);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    die("Order not found or no permission.");
}

/* 
   Only allow downloads if the order is Delivered.
*/
if ($order['status'] !== 'Delivered') {
    die("Download is not available yet. Status must be 'Delivered' (Administrator approval required).");
}

/* 2. Fetch digital product details + tracking count */
$item_stmt = mysqli_prepare($conn, 
    "SELECT dp.file_path, dp.download_limit, oi.download_count, oi.id AS oi_id, p.name
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN digital_products dp ON dp.product_id = oi.product_id
     WHERE oi.order_id = ? AND oi.product_id = ?"
);
mysqli_stmt_bind_param($item_stmt, "ii", $oid, $pid);
mysqli_stmt_execute($item_stmt);
$res = mysqli_fetch_assoc(mysqli_stmt_get_result($item_stmt));

if (!$res) {
    die("Digital product not found in this order.");
}

/* 3. Check download limit (0 = unlimited) */
if ($res['download_limit'] > 0 && $res['download_count'] >= $res['download_limit']) {
    die("Download limit reached for this purchase (" . $res['download_limit'] . " downloads allowed).");
}

/* 4. Validate file on disk */
$path = __DIR__ . '/assets/downloads/' . $res['file_path'];
if (!$res['file_path'] || !file_exists($path)) {
    die("Sorry, the file is currently missing on the server. Please contact support.");
}

/* 5. Increment counter */
$oi_id = (int)$res['oi_id'];
mysqli_query($conn, "UPDATE order_items SET download_count = download_count + 1 WHERE id = $oi_id");

/* 6. Stream File */
// Clean output buffer to prevent corrupted downloads
if (ob_get_level()) ob_end_clean();

$ext = pathinfo($res['file_path'], PATHINFO_EXTENSION);
$safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $res['name']) . '.' . $ext;

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safe_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
