<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$user_id = $_SESSION['user_id'] ?? 1;

/* cancel order */
if (isset($_POST['cancel_order_id'])) {
    $order_id = (int)$_POST['cancel_order_id'];

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'Cancelled'
        WHERE order_id = ? AND user_id = ? AND status IN ('Pending', 'Processing')
    ");
    $stmt->execute([$order_id, $user_id]);

    header("Location: order_history.php?msg=cancel_success");
    exit();
}

/* get total orders */
$totalStmt = $pdo->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE user_id = ?");
$totalStmt->execute([$user_id]);
$totalData = $totalStmt->fetch();
$totalOrders = $totalData['total_orders'];

/* get order history with product name + status + date */
$stmt = $pdo->prepare("
    SELECT 
        o.order_id,
        o.created_at,
        oi.quantity,
        o.status,
        p.product_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN product p ON oi.product_id = p.product_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order History</title>
    <link rel="stylesheet" href="/css/orders.css">
</head>
<body class="profile-page">

<?php include "../header.php"; ?>

<div class="order-history-wrapper">
    <div class="order-history-card">

        <h2 class="order-title">Order History</h2>

        <div class="order-table-header order-table-header-6">
            <div>Order ID</div>
            <div>Order Name</div>
            <div>Quantity</div>
            <div>Order Date</div>
            <div>Shipping Status</div>
            <div>Action</div>
        </div>

        <div class="order-table-body ">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <?php $statusClass = strtolower($order['status']); ?>
                    <div class="order-table-row order-table-row-6">
                        <div><?php echo $order['order_id']; ?></div>
                        <div><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div><?php echo $order['quantity']; ?></div>
                        <div><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></div>
                        <div>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                        <div>
                            <?php if ($order['status'] == 'Pending' || $order['status'] == 'Processing'): ?>
                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="cancel_order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" class="cancel-order-btn">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span class="action-disabled">--</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="order-no-data">NO ORDER!</div>
            <?php endif; ?>
        </div>

        <div class="order-bottom">
            <div class="total-order-box">
                Total Order: <?php echo $totalOrders; ?>
            </div>

            <a href="/mainpage.php" class="order-back-btn">Back to Home</a>
        </div>

    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancel_success'): ?>
<script>
    alert("Order cancelled successfully!");
</script>
<?php endif; ?>

<?php include "../footer.php"; ?>

</body>
</html>