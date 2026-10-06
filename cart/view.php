<?php
// Show error message if exists in URL
if (isset($_GET['error'])) {
    echo "<div style='background:red;color:white;padding:10px;'>";
    echo "Error code: " . htmlspecialchars($_GET['error']);

    if (isset($_GET['msg'])) {
        echo "<br>Message: " . htmlspecialchars($_GET['msg']);
    }

    echo "</div>";
}

session_start();
require_once '../lib/base.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   Get cart items (with size info)
   ========================= */
$stmt = db()->prepare("
    SELECT 
        ci.id,
        ci.quantity,
        ci.size_id,
        p.product_id,
        p.product_name,
        p.product_price,
        p.product_photo,
        ps.size
    FROM cart c
    JOIN cart_items ci ON c.cart_id = ci.cart_id
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN product_size ps ON ci.size_id = ps.size_id
    WHERE c.user_id = ?
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Cart</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
        }

        .cart-container {
            display: flex;
            gap: 40px;
            padding: 40px;
        }

        .cart-left {
            flex: 2;
        }

        .cart-item {
            display: flex;
            gap: 20px;
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }

        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }

        .info h3 {
            margin: 0;
        }

        .qty {
            margin: 10px 0;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid black;
            background: white;
            cursor: pointer;
        }

        .remove-btn {
            color: red;
            text-decoration: none;
            font-size: 14px;
        }

        .cart-right {
            flex: 1;
            background: white;
            padding: 25px;
            border: 1px solid #ddd;
            height: fit-content;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: black;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: #333;
        }

        h1 {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/..//header.php'; ?>

<div class="cart-container">

    <!-- LEFT SIDE CART LIST -->
    <div class="cart-left">

        <h1>YOUR BAG</h1>

        <?php if (empty($items)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>

        <?php foreach ($items as $item): ?>
            <div class="cart-item" data-price="<?= $item['product_price']; ?>">

                <img src="../uploads/shoes/<?= $item['product_photo']; ?>">

                <div class="info">
                    <h3><?= htmlspecialchars($item['product_name']); ?></h3>

                    <p>RM <?= number_format($item['product_price'], 2); ?></p>

                    <!-- Product size -->
                    <p>Size: <?= htmlspecialchars($item['size']); ?></p>

                    <div class="qty">
                        <button class="qty-btn decrease" data-cart-id="<?= $item['id']; ?>">-</button>
                        <span><?= $item['quantity']; ?></span>
                        <button class="qty-btn increase" data-cart-id="<?= $item['id']; ?>">+</button>
                    </div>

                    <!-- Remove item from cart -->
                    <a href="remove.php?cart_id=<?= $item['id']; ?>" class="remove-btn">
                        Remove
                    </a>
                </div>

            </div>
        <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <!-- RIGHT SIDE SUMMARY -->
    <div class="cart-right">

        <h2>ORDER SUMMARY</h2>

        <p>Total Items: <?= count($items) ?></p>

        <h3>RM <span id="total">0.00</span></h3>

        <form method="POST" action="../cart/checkout.php">
            <?php foreach ($items as $item): ?>
                <input type="hidden" name="selected[]" value="<?= $item['id']; ?>">
                <input type="hidden" name="size_id[]" value="<?= $item['size_id']; ?>">
            <?php endforeach; ?>

            <button class="checkout-btn">Checkout</button>
        </form>

    </div>

</div>

<script>

// Calculate total price
function updateTotal() {

    let total = 0;

    document.querySelectorAll('.cart-item').forEach(item => {

        let price = parseFloat(item.dataset.price);
        let qty = parseInt(item.querySelector('.qty span').innerText);

        total += price * qty;
    });

    document.getElementById('total').innerText = total.toFixed(2);
}

// Increase / decrease quantity buttons
document.querySelectorAll('.qty-btn').forEach(btn => {

    btn.onclick = function() {

        let cartId = this.dataset.cartId;
        let action = this.classList.contains('increase') ? 'increase' : 'decrease';

        fetch('update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'cart_id=' + cartId + '&action=' + action
        }).then(() => location.reload());

    };

});

// Run total calculation when page loads
window.onload = updateTotal;

</script>

</body>
</html>