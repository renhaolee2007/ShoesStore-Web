<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../lib/base.php';

/* =========================
   Check if user is logged in
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../member/member_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected = $_POST['selected'] ?? [];

/* =========================
   Basic validation
========================= */
if (empty($selected)) {
    header("Location: ../cart/view.php?error=noselect");
    exit;
}

/* =========================
   Prepare placeholders for SQL IN clause
========================= */
$placeholders = implode(',', array_fill(0, count($selected), '?'));

/* =========================
   Retrieve selected cart items
========================= */
$stmt = db()->prepare("
    SELECT 
        ci.id,
        ci.cart_id,
        ci.product_id,
        ci.size_id,
        ci.quantity,
        p.product_price, 
        p.product_name,
        ps.stock
    FROM cart c
    JOIN cart_items ci ON c.cart_id = ci.cart_id
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN product_size ps ON ci.size_id = ps.size_id
    WHERE ci.id IN ($placeholders) 
      AND c.user_id = ?
");

$stmt->execute(array_merge($selected, [$user_id]));
$items = $stmt->fetchAll();

/* =========================
   Redirect if no valid items found
========================= */
if (empty($items)) {
    header("Location: ../cart/view.php?error=invalid");
    exit;
}

/* =========================
   Check stock availability
========================= */
$total = 0;
$stock_errors = [];

foreach ($items as $item) {
    if ($item['stock'] === null || $item['quantity'] > $item['stock']) {
        $stock_errors[] = $item['product_name'] . " (stock not enough)";
    }

    $total += $item['quantity'] * $item['product_price'];
}

/* =========================
   Redirect if stock is not enough
========================= */
if (!empty($stock_errors)) {
    $error_msg = "Stock not enough: " . implode(", ", $stock_errors);
    header("Location: ../cart/view.php?error=stock&msg=" . urlencode($error_msg));
    exit;
}

/* =========================
   Start transaction
========================= */
db()->beginTransaction();

try {
    /* Create new order */
    $stmt = db()->prepare("
        INSERT INTO orders (user_id, total_amount, status, created_at) 
        VALUES (?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$user_id, $total]);
    $order_id = db()->lastInsertId();

    /* Insert order items + deduct stock */
    foreach ($items as $item) {
        db()->prepare("
            INSERT INTO order_items 
            (order_id, product_id, size_id, quantity, price)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $order_id,
            $item['product_id'],
            $item['size_id'],
            $item['quantity'],
            $item['product_price']
        ]);

        db()->prepare("
            UPDATE product_size 
            SET stock = stock - ? 
            WHERE size_id = ?
        ")->execute([
            $item['quantity'],
            $item['size_id']
        ]);
    }

    /* Remove checked-out items from cart_items */
    $stmt = db()->prepare("
        DELETE ci
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE ci.id IN ($placeholders)
          AND c.user_id = ?
    ");
    $stmt->execute(array_merge($selected, [$user_id]));

    db()->commit();

} catch (Exception $e) {
    db()->rollBack();
    die("Order failed: " . $e->getMessage());
}

/* =========================
   Redirect to success page
========================= */
header("Location: ../order/order_success.php?order_id=$order_id");
exit;