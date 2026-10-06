<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // of not login back
    echo "not_logged_in";
    exit;
}

require_once '../lib/base.php';

if (empty($_SESSION['user_id'])) {
    
    http_response_code(401);
    echo "Please login first";
    exit;
}

$user_id = $_SESSION['user_id'];

$product_id = $_POST['product_id'] ?? null;
$quantity   = (int)($_POST['quantity'] ?? 1);
$size_id    = $_POST['size_id'] ?? null;

if (!$product_id) {
    echo "Invalid product";
    exit;
}

if (!$size_id) {
    echo "Please select a size";
    exit;
}

if ($quantity < 1) {
    $quantity = 1;
}

$stmt = db()->prepare("
    SELECT stock
    FROM product_size
    WHERE product_id = ?
      AND size_id = ?
");
$stmt->execute([$product_id, $size_id]);
$size = $stmt->fetch();

if (!$size) {
    echo "Invalid product size";
    exit;
}

if ($quantity > $size['stock']) {
    echo "Not enough stock";
    exit;
}

/* =========================
   Get or create cart
========================= */
$stmt = db()->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

if (!$cart) {
    $stmt = db()->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $cart_id = db()->lastInsertId();
} else {
    $cart_id = $cart['cart_id'];
}

/* =========================
   Check if item already in cart
========================= */
$stmt = db()->prepare("
    SELECT *
    FROM cart_items
    WHERE cart_id = ?
      AND product_id = ?
      AND size_id = ?
");
$stmt->execute([$cart_id, $product_id, $size_id]);
$existing = $stmt->fetch();

/* =========================
   Update or insert into cart_items
========================= */
if ($existing) {
    $newQty = $existing['quantity'] + $quantity;

    if ($newQty > $size['stock']) {
        echo "Not enough stock";
        exit;
    }

    $stmt = db()->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE id = ?
    ");
    $stmt->execute([$newQty, $existing['id']]);

} else {
    $stmt = db()->prepare("
        INSERT INTO cart_items (cart_id, product_id, size_id, quantity)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$cart_id, $product_id, $size_id, $quantity]);
}

/* =========================
   AJAX Response 
========================= */
echo "success";
exit;