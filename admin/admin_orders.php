<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$allowed_status = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

/* =========================
   HANDLE UPDATE / DELETE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_orders'])) {

    $selected_orders = array_map('intval', $_POST['selected_orders']);

    if (!empty($selected_orders)) {

        $placeholders = implode(',', array_fill(0, count($selected_orders), '?'));

        /* UPDATE */
        if (isset($_POST['update_order']) && isset($_POST['new_status'])) {

            $new_status = trim($_POST['new_status']);

            if (in_array($new_status, $allowed_status)) {

                $sql = "UPDATE orders
                        SET status = ?
                        WHERE order_id IN ($placeholders)
                        AND status NOT IN ('Delivered', 'Cancelled')";

                $stmt = $pdo->prepare($sql);

                $params = array_merge([$new_status], $selected_orders);
                $stmt->execute($params);

                $affected = $stmt->rowCount();

                if ($affected > 0) {
                    header("Location: admin_orders.php?msg=updated");
                } else {
                    header("Location: admin_orders.php?msg=locked");
                }
                exit();
            }
        }

       /* DELETE */
       if (isset($_POST['delete_order'])) {

        // 1. Delete order items first
        $sql = "DELETE FROM order_items WHERE order_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($selected_orders);

        // 2. Then delete orders
        $sql = "DELETE FROM orders WHERE order_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($selected_orders);

        header("Location: admin_orders.php?msg=deleted");
        exit();
        }
    }
}

/* =========================
   SEARCH
========================= */
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT 
        o.order_id,
        o.created_at,
        oi.quantity,
        o.status,
        u.user_id,
        u.username,
        p.product_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN product p ON oi.product_id = p.product_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE 
            u.user_id LIKE ? OR
            u.username LIKE ? OR
            o.order_id LIKE ? OR
            p.product_name LIKE ?
    ";

    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$sql .= " ORDER BY o.order_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll();
$total_orders = count($orders);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Order Management</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    <link rel="stylesheet" href="../css/orders.css">
</head>
<body class="profile-page">

<?php include __DIR__ . '/../admin_header.php'; ?>

<div class="admin-order-wrapper">
    <div class="admin-order-card">

        <h2 class="admin-order-title">Admin Order Management</h2>

        <!-- Search -->
        <form method="get" class="admin-search-form">
            <input type="text" name="search"
                   class="admin-search-input"
                   placeholder="Search..."
                   value="<?= htmlspecialchars($search); ?>">

            <button type="submit" class="admin-search-btn">Search</button>
            <a href="admin_orders.php" class="admin-clear-btn">Clear</a>

            <div class="admin-total-orders">
                Total Orders: <?= $total_orders; ?>
            </div>
        </form>

        <!-- MULTI ACTION -->
        <form method="post" onsubmit="return validateAdminMultiAction(event);">

            <div class="admin-order-header">
                <div><input type="checkbox" id="checkAll" onclick="toggleAllOrders(this)"></div>
                <div>User ID</div>
                <div>User Name</div>
                <div>Order ID</div>
                <div>Order Name</div>
                <div>Quantity</div>
                <div>Order Date</div>
                <div>Status</div>
            </div>

            <div class="admin-order-body">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $row): ?>
                        <?php $isLocked = ($row['status'] === 'Delivered' || $row['status'] === 'Cancelled'); ?>

                        <div class="admin-order-row">

                            <div>
                                <input type="checkbox"
                                       name="selected_orders[]"
                                       value="<?= $row['order_id']; ?>"
                                       class="admin-order-checkbox"
                                       <?= $isLocked ? 'disabled' : ''; ?>>
                            </div>

                            <div><?= $row['user_id']; ?></div>
                            <div><?= htmlspecialchars($row['username']); ?></div>
                            <div><?= $row['order_id']; ?></div>
                            <div><?= htmlspecialchars($row['product_name']); ?></div>
                            <div><?= $row['quantity']; ?></div>
                            <div><?= date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></div>

                            <div>
                                <span class="status-badge <?= strtolower($row['status']); ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="order-no-data">NO ORDER FOUND!</div>
                <?php endif; ?>
            </div>

            <div class="admin-order-bottom">

                <select name="new_status" class="admin-status-select-bottom">
                    <option value="">-- Select Status --</option>
                    <?php foreach ($allowed_status as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="update_order" class="admin-update-btn-bottom">
                    Update Selected
                </button>

                <button type="submit" name="delete_order" class="admin-delete-btn-bottom">
                    Delete Selected
                </button>

                <a href="../mainpage.php" class="order-back-btn">Back to Home</a>

            </div>

        </form>

    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<script>
    <?php if ($_GET['msg'] == 'updated'): ?>
        alert("Selected orders updated successfully!");
    <?php elseif ($_GET['msg'] == 'deleted'): ?>
        alert("Selected orders deleted successfully!");
    <?php elseif ($_GET['msg'] == 'locked'): ?>
        alert("Delivered or Cancelled orders cannot be updated.");
    <?php endif; ?>
</script>
<?php endif; ?>

<?php include __DIR__ . '/../footer.php'; ?>

</body>
</html>