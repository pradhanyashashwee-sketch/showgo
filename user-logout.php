<?php
session_start();
session_unset();
session_destroy();
// clear cookie if session cookie exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
header("Location: user-login.php");
exit();
?>