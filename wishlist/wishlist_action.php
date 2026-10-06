<?php
// php/shopping_cart/wishlist_action.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first!'); window.location.href='../login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id']; 
$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    $fallback = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../../product/list.php';
    header("Location: " . $fallback);
    exit();
}

try {
    $check = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, (int)$product_id]);
    
    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, (int)$product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, (int)$product_id]);
    }

    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: wishlist.php"); 
    }
    exit();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}