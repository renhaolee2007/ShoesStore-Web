<?php
session_start();

// clear session array
$_SESSION = [];

// delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// delete session
session_destroy();

// delete remember me cookie
setcookie("remember_me", "", time() - 3600, "/");

// redirect
header("Location: mainpage.php");
exit();