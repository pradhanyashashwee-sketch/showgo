<?php
// movies-manage.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin-login.php");
    exit();
}
require_once "db.php";
$conn = getDB();

$result = $conn->query("SELECT * FROM movies ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Movies - ShowGO</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include "navbar.php"; ?>
<div style="max-width:1000px;margin:20px auto;">
  <h2>Movies</h2>
  <a href="movie-add.php" class="btn">+ Add Movie</a>
  <table border="1" cellpadding="8" cellspacing="0" width="100%" style="margin-top:12px;">
    <tr><th>ID</th><th>Title</th><th>Description</th><th>Actions</th></tr>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['description']); ?></td>
        <td>
          <a href="edit-movie.php?id=<?php echo $row['id']; ?>">Edit</a> |
          <a href="delete-movie.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>