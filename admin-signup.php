<?php
session_start();
include "db.php";
$conn = getDB();
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $error = "Email already exists.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            $hashed_password = $password;
            $insert_stmt = $conn->prepare("INSERT INTO admins (full_name, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
            if ($insert_stmt->execute()) {
                $_SESSION['admin_id'] = $insert_stmt->insert_id;
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_logged_in'] = true;
                
                $insert_stmt->close();
                $conn->close();
                
                header("Location: admin-login.php");
                exit();
            } else {
                $error = "Error creating account: " . $conn->error;
            }
            $insert_stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Signup</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error {
            color: red;
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
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
    <form method="POST" class="login-box">
        <h2 class="login-title">Admin Signup</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Enter full name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        <label>Email</label>
        <input type="email" name="email" placeholder="Enter email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label>Password (min. 6 characters)</label>
        <input type="password" name="password" placeholder="Enter password" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Confirm password" required>
        <button type="submit" name="signup" class="login-btn">Sign Up</button>
        <p style="text-align:center; margin-top:15px;">
          Already have an account? <a href="admin-login.php">Login</a>
        </p>
    </form>
</div>
</body>
</html>