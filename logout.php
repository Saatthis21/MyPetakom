<?php
session_start();

$_SESSION = [];

// kill the session cookie too
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

//destroy the session
session_destroy();

//login with a success message
header("Location: LoginPage.php?logged_out=1");
exit;
?>
