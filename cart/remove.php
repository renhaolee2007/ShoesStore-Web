<?php
session_start();
require_once '../lib/base.php';

/* =========================
   Check if user is logged in
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../member/member_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   Get cart item ID from URL
========================= */
$item_id = $_GET['cart_id'] ?? null;

/* =========================
   Redirect if no cart item selected
========================= */
if (!$item_id) {
    header("Location: view.php");
    exit;
}

/* =========================
   Delete selected item from cart_items
   Make sure the item belongs to this user
========================= */
$stmt = db()->prepare("
    DELETE ci
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.cart_id
    WHERE ci.id = ? AND c.user_id = ?
");
$stmt->execute([$item_id, $user_id]);

/* =========================
   Redirect back to cart page
========================= */
header("Location: view.php");
exit;