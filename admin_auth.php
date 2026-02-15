<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$admin_ok = false;
if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in'])) {
    $admin_ok = true;
} elseif (!empty($_SESSION['admins_id']) && !empty($_SESSION['admins_logged_in'])) {
    $_SESSION['admin_id'] = $_SESSION['admins_id'];
    $_SESSION['admin_logged_in'] = $_SESSION['admins_logged_in'];
    $admin_ok = true;
} elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin' && !empty($_SESSION['user_id']) === false) {
    $admin_ok = true;
}

if (!$admin_ok) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: admin-login.php');
    exit();
}
?>