<?php
session_start();
if (isset($_GET['movie_id']) && isset($_GET['movie_title']) && isset($_GET['show_time'])) {
    $_SESSION['selected_movie_id'] = intval($_GET['movie_id']);
    $_SESSION['selected_movie_title'] = urldecode($_GET['movie_title']);
    $_SESSION['selected_show_time'] = $_GET['show_time'];
}
include "db.php";
$conn = getDB();
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password_raw = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($password_raw === $row['password']) { 
            $_SESSION['users_id'] = $row['id'];
            $_SESSION['users_name'] = $row['full_name'];
            $_SESSION['users_logged_in'] = true;
            
            // Check if user came from a movie selection
            if (isset($_SESSION['selected_movie_id']) && isset($_SESSION['selected_movie_title']) && isset($_SESSION['selected_show_time'])) {
                // Redirect to book seats for the selected movie
                header("Location: book_seats.php");
                exit();
            } else {
                // No movie selected, redirect to booking page to browse movies
                header("Location: booking.php");
                exit();
            }
        } else {
            $error = "Invalid Email or Password.";
        }
    } else {
        $error = "Invalid Email or Password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .movie-info-prompt {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo-link">
        <div class="logo"><strong>ShowGO</strong></div>
    </a>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="booking.php">Movies</a></li>
            <li><a href="ticket_rates.php">Ticket Rate</a></li>
            <li><a href="user-login.php">Login</a></li>
            <li><a href="admin-login.php">Admin</a></li>
        </ul>
    </nav>
</header>

<div class="login-container">
    <div class="login-box">
      <h2>User Login</h2>
      <?php if (!empty($error)) echo "<p style='color:red;text-align:center;'>$error</p>"; ?>
      <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" name="submit" class="login-btn">Login</button>
      </form>
      <p>Don’t have an account? <a href="user-signup.php">Sign Up</a></p>
    </div>
  </div>
</body>
</html>