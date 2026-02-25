<?php
include 'includes/init.php';
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'staff'])) {
        header("Location: /melody-masters-online-store/admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, name, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id, $user_name, $db_password, $role);
    mysqli_stmt_fetch($stmt);

    if ($db_password && password_verify($password, $db_password)) {
        $_SESSION['user_id']   = $user_id;
        $_SESSION['role']      = $role;
        $_SESSION['user_name'] = $user_name;

        // Send admins and staff straight to the dashboard
        if (in_array($role, ['admin', 'superadmin', 'staff'])) {
            header("Location: /melody-masters-online-store/admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Melody Masters</title>
  <link rel="stylesheet" href="/melody-masters-online-store/assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-dark">

<div class="auth-wrapper">

  <!-- LEFT: Image panel -->
  <div class="auth-brand auth-bg-login">
    <div class="auth-overlay-content">
      <a href="index.php" class="auth-back-link">← Back to shop</a>
      <h1>Melody <span>Masters</span></h1>
      <p>Sign in to access your orders, saved items, and exclusive member offers.</p>
    </div>
  </div>

  <!-- RIGHT: Form panel -->
  <div class="auth-form">

    <div class="auth-form-inner">
      <a href="index.php" class="auth-back-link auth-back-mobile">← Back to shop</a>

      <h2>Sign In</h2>
      <p class="auth-sub">Welcome back — good to see you again.</p>

      <?php if (isset($_GET['registered'])): ?>
        <div class="auth-success">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Account created successfully. You can now sign in.
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Your password" required>
        </div>

        <button type="submit" class="btn-primary full-width">Sign In</button>
      </form>

      <div class="auth-links">
        Don't have an account? <a href="register.php">Create one</a>
      </div>
    </div>

  </div>

</div>

</body>
</html>