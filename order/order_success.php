<?php
// start session if not started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../lib/base.php';

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/login.php");
    exit;
}

// get order id from URL
$order_id = $_GET['order_id'] ?? 0;

// validate order id
if (empty($order_id)) {
    die("Invalid Order ID");
}

/* =========================
   get order details from database
   ========================= */
$stmt = db()->prepare("
    SELECT * FROM orders 
    WHERE order_id = ? AND user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

// if order not found
if (!$order) {
    die("Order not found.");
}

/* =========================
   get all items in this order
   join product + size tables
   ========================= */
$stmt = db()->prepare("
    SELECT 
        oi.*, 
        p.product_name, 
        p.product_photo,
        ps.size
    FROM order_items oi
    JOIN product p ON oi.product_id = p.product_id
    LEFT JOIN product_size ps 
        ON oi.product_id = ps.product_id 
        AND oi.size_id = ps.size_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Successful</title>

    <style>
        body { 
            font-family: Arial; 
            text-align: center; 
            padding: 60px; 
            background: #f8f9fa; 
        }

        .success-box {
            background: white;
            max-width: 700px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .icon { 
            font-size: 80px; 
            color: #28a745; 
        }

        h1 { 
            color: #28a745; 
        }

        .item {
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        .summary {
            margin-top: 25px;
            font-size: 18px;
        }

        a {
            display: inline-block;
            margin: 10px;
            padding: 12px 25px;
            background: black;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .small {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="success-box">

    <!-- success icon -->
    <div class="icon">✅</div>

    <h1>Order Placed Successfully!</h1>
    <p>Thank you for your purchase.</p>

    <!-- display order id -->
    <h2>Order #<?= htmlspecialchars($order['order_id']) ?></h2>

    <!-- display order date -->
    <p class="small">
        Date: <?= htmlspecialchars($order['created_at']) ?>
    </p>

    <!-- =========================
         display order items
         ========================= -->
    <?php foreach ($items as $item): ?>

        <div class="item">

            <img src="../uploads/shoes/<?= $item['product_photo'] ?>">

            <div>

                <b><?= htmlspecialchars($item['product_name']) ?></b>

                <p>Size: <?= $item['size'] ?? '-' ?></p>

                <p>Qty: <?= $item['quantity'] ?></p>

                <p>
                    Subtotal: RM <?= number_format($item['quantity'] * $item['price'], 2) ?>
                </p>

            </div>

        </div>

    <?php endforeach; ?>

    <!-- order total -->
    <div class="summary">
        <p><strong>Total Paid:</strong> RM <?= number_format($order['total_amount'], 2) ?></p>
    </div>

    <br>

    <!-- navigation buttons -->
    <a href="order_history.php">View My Orders</a>
    <a href="../product/list.php">Continue Shopping</a>

</div>

</body>
</html>