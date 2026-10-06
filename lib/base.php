<?php
// -------------------------------------------------------
// 1. Basic PHP Configuration
// -------------------------------------------------------
date_default_timezone_set('Asia/Kuala_Lumpur');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// 2. Request Helper Functions
// -------------------------------------------------------

// Check if request is GET
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// Check if request is POST
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Get value from $_GET safely
function get($key, $default = null) {
    $value = $_GET[$key] ?? $default;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Get value from $_POST safely
function post($key, $default = null) {
    $value = $_POST[$key] ?? $default;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Get value from $_REQUEST safely
function req($key, $default = null) {
    $value = $_REQUEST[$key] ?? $default;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// -------------------------------------------------------
// 3. Redirect & Flash Message System
// -------------------------------------------------------

// Redirect to another page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Temporary flash message (one-time session message)
function temp($key, $value = null) {
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    } else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// -------------------------------------------------------
// 4. File Upload Helpers
// -------------------------------------------------------

// Get uploaded file safely
function get_file($key) {
    $f = $_FILES[$key] ?? null;
    if ($f && $f['error'] === UPLOAD_ERR_OK) {
        return (object)$f;
    }
    return null;
}

// Save uploaded image file
function save_photo($file, $folder = '../uploads') {
    if (!$file) return null;

    // Create folder if not exists
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $filename = uniqid() . '.jpg';
    $destination = $folder . '/' . $filename;

    if (move_uploaded_file($file->tmp_name, $destination)) {
        return $filename;
    }

    return null;
}

// -------------------------------------------------------
// 5. HTML Helpers
// -------------------------------------------------------

// Escape HTML output (prevent XSS)
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Display validation error message
function err($key) {
    global $_err;
    if (isset($_err[$key]) && $_err[$key] !== '') {
        echo '<span class="err">' . h($_err[$key]) . '</span>';
    }
}

// -------------------------------------------------------
// 6. Password Security Helpers
// -------------------------------------------------------

// Hash password securely
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// -------------------------------------------------------
// 7. Database Connection (PDO)
// -------------------------------------------------------

// Get database connection (singleton)
function db() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=localhost;dbname=shoes_shop;charset=utf8mb4';

            $pdo = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

        } catch (PDOException $e) {
            die('Database Connection Failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

// -------------------------------------------------------
// 8. Authentication Functions
// -------------------------------------------------------

// Require user to be logged in
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        temp('error', 'Please login first.');
        redirect('../php/login.php');
    }
}

// Allow only admin users
function require_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        temp('error', 'Admin only access.');
        redirect('../php/login.php');
    }
}

// Allow only member users
function require_member() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
        temp('error', 'Member only access.');
        redirect('../php/login.php');
    }
}

// -------------------------------------------------------
// 9. Login & Logout Functions
// -------------------------------------------------------

// Login user and set session
function login_user($user) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['username'];
}

// Logout user
function logout() {
    session_destroy();
    redirect('../php/login.php');
}

// -------------------------------------------------------
// 10. Global Error Array
// -------------------------------------------------------
global $_err;
$_err = [];

// -------------------------------------------------------
// 11. Role Checking Helpers
// -------------------------------------------------------

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is member
function is_member() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

?>