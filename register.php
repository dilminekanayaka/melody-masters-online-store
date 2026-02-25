<?php
include 'includes/init.php';
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$name  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($name) < 2) {
        $error = "Please enter your full name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "An account with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);
            mysqli_stmt_execute($stmt);
            header("Location: login.php?registered=success");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account | Melody Masters</title>
  <link rel="stylesheet" href="/melody-masters-online-store/assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-dark">

<div class="auth-wrapper auth-reverse">

  <!-- LEFT: Form panel -->
  <div class="auth-form">

    <div class="auth-form-inner">
      <a href="index.php" class="auth-back-link auth-back-mobile">← Back to shop</a>

      <h2>Create Account</h2>
      <p class="auth-sub">Join Melody Masters and start exploring professional gear.</p>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" placeholder="John Smith"
                 value="<?= htmlspecialchars($name) ?>" required autofocus>
        </div>

        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
          <label for="password">Password <span class="field-hint">(min. 6 characters)</span></label>
          <input type="password" id="password" name="password" placeholder="Create a password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
        </div>

        <button type="submit" class="btn-primary full-width">Create Account</button>
      </form>

      <div class="auth-links">
        Already have an account? <a href="login.php">Sign in</a>
      </div>
    </div>

  </div>

  <!-- RIGHT: Image panel -->
  <div class="auth-brand auth-bg-register">
    <div class="auth-overlay-content">
      <a href="index.php" class="auth-back-link">← Back to shop</a>
      <h1>Melody <span>Masters</span></h1>
      <p>Professional instruments and audio gear from the world's most trusted brands. Free delivery over £100.</p>
    </div>
  </div>

</div>

</body>
</html>