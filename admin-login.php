<?php
session_start();
include "db.php";
$conn = getDB();

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password_raw = $_POST['password'];
    
    // Use full_name instead of username
    $stmt = $conn->prepare("SELECT id, full_name, email, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // First try direct comparison (for unhashed passwords)
        if ($password_raw === $row['password']) { 
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['full_name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_logged_in'] = true;  // Fixed: changed from 'admins_logged_in'
            
            // Debug: Uncomment to see what's happening
            // echo "Login successful! Redirecting...";
            // exit();
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid Email or Password.";
        }
    } else {
        $error = "Invalid Email or Password.";
    }
    
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/style.css">
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
    <style>
        .error {
            color: red;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-box">
      <h2>Admin Login</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" name="submit" class="login-btn">Login</button>
      </form>
      <p style="text-align:center; margin-top:15px;">
        Don't have an account? <a href="admin-signup.php">Sign Up</a>
      </p>
    </div>
</div>
</body>
</html>