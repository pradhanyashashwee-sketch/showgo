<?php
session_start();
include "db.php";

$conn = getDB();
$error = "";
$success = "";

// basic request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        if (!$conn) {
            $error = "Database connection error.";
        } else {
            // check duplicate email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $error = "Email already exists.";
                    $stmt->close();
                } else {
                    $stmt->close();
                    $ins = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                    if ($ins) {
                        $ins->bind_param("sss", $name, $email, $password);
                        if ($ins->execute()) {
                            $ins->close();
                            // redirect to login for a clean flow
                            header("Location: user-login.php");
                            exit();
                        } else {
                            $error = "Error creating account. Please try again.";
                            $ins->close();
                        }
                    } else {
                        $error = "Server error (prepare failed).";
                    }
                }
            } else {
                $error = "Server error (prepare failed).";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Signup</title>
    <link rel="stylesheet" href="css/style.css">
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
    <form method="POST" class="login-box" novalidate>
        <h2 class="login-title">User Signup</h2>
        <?php if ($error): ?>
            <p class="text-danger"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Enter full name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        <label>Email</label>
        <input type="email" name="email" placeholder="Enter email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Confirm password" required>
        <button type="submit" name="signup" class="login-btn">Sign Up</button>
        <p style="text-align:center; margin-top:10px;">Already have an account? <a href="user-login.php">Login</a></p>
    </form>
</div>
</body>
</html>