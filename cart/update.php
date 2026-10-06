<?php
require_once '../lib/base.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   Check if user is logged in
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../member/member_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   Get data from form
========================= */
$item_id = $_POST['cart_id'] ?? null;   

/* =========================
   Basic validation
========================= */
if (!$item_id || !$action) {
    header("Location: view.php");
    exit;
}

/* =========================
   Get current quantity from cart_items
   Make sure this item belongs to the current user
========================= */
$stmt = db()->prepare("
    SELECT ci.id, ci.quantity
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.cart_id
    WHERE ci.id = ? AND c.user_id = ?
");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch();

/* =========================
   Redirect if item not found
========================= */
if (!$item) {
    header("Location: view.php");
    exit;
}

$quantity = (int)$item['quantity'];

/* =========================
   Update quantity based on action
========================= */
if ($action === 'increase') {
    $quantity++;

} elseif ($action === 'decrease') {
    $quantity--;

    if ($quantity <= 0) {
        $stmt = db()->prepare("
            DELETE ci
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE ci.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$item_id, $user_id]);

        header("Location: view.php");
        exit;
    }
} else {
    header("Location: view.php");
    exit;
}

/* =========================
   Update new quantity in cart_items
========================= */
$stmt = db()->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE id = ?
");
$stmt->execute([$quantity, $item_id]);

/* =========================
   Redirect back to cart page
========================= */
header("Location: view.php");
exit;