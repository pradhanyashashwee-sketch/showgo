<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin-login.php");
    exit();
}
require_once "db.php";
$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $stmt = $conn->prepare("INSERT INTO movies (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $desc);
    $stmt->execute();
    header("Location: movies-manage.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Add Movie</title></head>
<body>
<?php include "navbar.php"; ?>
<div style="max-width:600px;margin:20px auto;">
  <h2>Add Movie</h2>
  <form method="POST">
    <label>Title</label><br>
    <input type="text" name="title" required><br>
    <label>Description</label><br>
    <textarea name="description" required></textarea><br>
    <button type="submit">Add</button>
  </form>
</div>
</body>
</html>