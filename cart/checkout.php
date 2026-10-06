<?php
session_start();
require_once '../lib/base.php';

/* =========================
   Check if user is logged in
   ========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   Get selected cart items
   ========================= */
$selected = $_POST['selected'] ?? [];

/* =========================
   Redirect if no item selected
   ========================= */
if (empty($selected)) {
    header("Location: ../cart/view.php?error=select");
    exit;
}

/* =========================
   Prepare placeholders for SQL IN clause
   ========================= */
$placeholders = implode(',', array_fill(0, count($selected), '?'));

/* =========================
   Retrieve cart items with product and size details
   ========================= */
$stmt = db()->prepare("
SELECT 
    ci.id,
    ci.quantity,
    ci.size_id,
    p.product_name, 
    p.product_price, 
    p.product_photo,
    ps.size
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
   Calculate total amount
   ========================= */
$total = 0;
foreach ($items as $item) {
    $total += $item['quantity'] * $item['product_price'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
        }

        .container {
            display: flex;
            gap: 40px;
            padding: 40px;
        }

        .left {
            flex: 2;
        }

        .right {
            flex: 1;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            height: fit-content;
        }

        .item {
            display: flex;
            gap: 20px;
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }

        .item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: black;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }

        .edit-btn {
            display: block;
            text-align: center;
            padding: 12px;
            border: 1px solid black;
            text-decoration: none;
            color: black;
            font-weight: bold;
            margin-top: 10px;
        }

        .edit-btn:hover {
            background: #eee;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/..//header.php'; ?>

<div class="container">

    <!-- LEFT: Display selected items -->
    <div class="left">

        <h1>Order Review</h1>

        <?php foreach ($items as $item): ?>

            <div class="item">

                <!-- Product image -->
                <img src="../uploads/shoes/<?= $item['product_photo'] ?>">

                <div>
                    <!-- Product name -->
                    <h3><?= htmlspecialchars($item['product_name']) ?></h3>

                    <!-- Display selected size -->
                    <p>Size: <?= $item['size'] ?? '-' ?></p>

                    <!-- Quantity -->
                    <p>Qty: <?= $item['quantity'] ?></p>

                    <!-- Price per item -->
                    <p>Price: RM <?= number_format($item['product_price'],2) ?></p>

                    <!-- Subtotal for this item -->
                    <p><b>
                        Subtotal: RM <?= number_format($item['quantity'] * $item['product_price'],2) ?>
                    </b></p>
                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <!-- RIGHT: Order summary -->
    <div class="right">

        <h2>Summary</h2>

        <!-- Total price -->
        <p>Total: <b>RM <?= number_format($total,2) ?></b></p>

        <!-- Go back to edit cart -->
        <a href="../cart/view.php" class="edit-btn">
            Edit Cart
        </a>

        <!-- Confirm order form -->
        <form method="POST" action="place_order.php">

            <!-- Pass selected cart IDs -->
            <?php foreach ($selected as $id): ?>
                <input type="hidden" name="selected[]" value="<?= $id ?>">
            <?php endforeach; ?>

            <button type="submit" class="checkout-btn">
                Confirm Order
            </button>

        </form>

    </div>

</div>

</body>
</html>