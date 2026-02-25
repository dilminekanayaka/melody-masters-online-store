<?php
include 'auth.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    $stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);

    echo "<p style='color:green;text-align:center;'>Category added</p>";
}
?>

<div class="form-container">
  <h2>Add Category</h2>
  <form method="POST">
    <input type="text" name="name" placeholder="Category Name" required>
    <button>Add Category</button>
  </form>
</div>

<?php include '../includes/footer.php'; ?>