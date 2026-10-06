<?php
// lib/security.php
// Security functions for your assignment (follow practical conventions)

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && $_SESSION['role'] === 'admin';
}

function is_member_logged_in() {
    return isset($_SESSION['member_id']) && $_SESSION['role'] === 'member';
}

// Protect pages - call this at the top of protected files
function require_login($role = 'member') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($role === 'admin' && !is_admin_logged_in()) {
        header('Location: ../public/login.php?error=unauthorized');
        exit;
    }
    
    if ($role === 'member' && !is_member_logged_in()) {
        header('Location: ../public/login.php?error=unauthorized');
        exit;
    }
}

// Safe output helper (reuse from practical)
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Get database connection (you should already have this in base.php or db.php)
function db() {
    // Change these details according to your XAMPP setup
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=csv_db_12', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>