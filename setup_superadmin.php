<?php
/**
 * ONE-TIME Super Admin Setup Script
 * Visit: http://localhost/melody-masters-online-store/setup_superadmin.php
 * DELETE THIS FILE after running it.
 */
include_once __DIR__ . '/includes/init.php';
include_once __DIR__ . '/includes/db.php';

$SA_NAME     = 'Super Admin';
$SA_EMAIL    = 'admin@melodymasters.com';
$SA_PASSWORD = 'Admin@2024';         // ← shown in plain on this page; change after first login

$message = '';
$success = false;

// Check if already exists
$chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($chk, "s", $SA_EMAIL);
mysqli_stmt_execute($chk);
$exists = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;

if (!$exists) {
    // Make sure the ENUM is already migrated; try to INSERT with role='superadmin'
    $hash = password_hash($SA_PASSWORD, PASSWORD_DEFAULT);
    $ins  = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'superadmin')");
    mysqli_stmt_bind_param($ins, "sss", $SA_NAME, $SA_EMAIL, $hash);
    if (mysqli_stmt_execute($ins)) {
        $success = true;
        $message = 'Super Admin account created successfully!';
    } else {
        $message = 'DB error: ' . mysqli_error($conn)
                 . '<br>Did you run <code>database/migrate_superadmin.sql</code> first?';
    }
} else {
    $message = 'Super Admin already exists in the database.';
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Setup</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: 'Inter', sans-serif; background: #0d0d0d; color: #e2e2e2; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .card { background: #161616; border: 1px solid #2a2a2a; border-radius: 12px; padding: 40px; max-width: 480px; width: 100%; }
    h1 { font-size: 20px; font-weight: 700; margin: 0 0 6px; }
    .sub { font-size: 13px; color: #666; margin-bottom: 28px; }
    .alert { padding: 13px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 24px; }
    .alert--ok  { background: rgba(46,125,79,0.12); border: 1px solid rgba(46,125,79,0.3); color: #7fbf9a; }
    .alert--err { background: rgba(192,57,43,0.12); border: 1px solid rgba(192,57,43,0.3); color: #e57373; }
    .creds { background: #0d0d0d; border: 1px solid #2a2a2a; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
    .creds h2 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #555; margin: 0 0 12px; }
    .cred-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; padding: 6px 0; border-bottom: 1px solid #1e1e1e; }
    .cred-row:last-child { border: none; }
    .cred-label { color: #666; }
    .cred-value { font-weight: 600; color: #c8973c; font-family: monospace; }
    .warn { font-size: 12px; color: #e6a84a; background: rgba(200,151,60,0.08); border: 1px solid rgba(200,151,60,0.2); border-radius: 6px; padding: 10px 14px; margin-bottom: 20px; }
    a.btn { display: inline-block; padding: 11px 24px; background: #c8973c; color: #000; font-weight: 700; border-radius: 7px; font-size: 14px; text-decoration: none; }
    a.btn:hover { background: #d4a835; }
    .del { font-size: 12px; color: #555; margin-top: 20px; }
  </style>
</head>
<body>
<div class="card">
  <h1>🎵 Melody Masters — Super Admin Setup</h1>
  <p class="sub">One-time initialisation script</p>

  <div class="alert <?= $success ? 'alert--ok' : 'alert--err' ?>">
    <?= $message ?>
  </div>

  <?php if ($success): ?>
  <div class="creds">
    <h2>Login Credentials</h2>
    <div class="cred-row">
      <span class="cred-label">Email</span>
      <span class="cred-value"><?= htmlspecialchars($SA_EMAIL) ?></span>
    </div>
    <div class="cred-row">
      <span class="cred-label">Password</span>
      <span class="cred-value"><?= htmlspecialchars($SA_PASSWORD) ?></span>
    </div>
    <div class="cred-row">
      <span class="cred-label">Role</span>
      <span class="cred-value">superadmin</span>
    </div>
  </div>

  <div class="warn">
    ⚠ <strong>Change your password</strong> after first login. Then <strong>delete this file</strong>
    (<code>setup_superadmin.php</code>) from your server.
  </div>

  <a href="/melody-masters-online-store/login.php" class="btn">Go to Login →</a>
  <?php endif; ?>

  <p class="del">After confirming login works, delete <code>setup_superadmin.php</code> from your project root.</p>
</div>
</body>
</html>
