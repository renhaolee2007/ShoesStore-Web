<?php
require_once __DIR__ . '/../database/db.php';

$category_sql = "
    SELECT 
        c.category_name, 
        COUNT(DISTINCT o.order_id) AS total_orders
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    INNER JOIN product p ON oi.product_id = p.product_id
    INNER JOIN category c ON p.category_id = c.category_id
    WHERE o.status = 'Delivered'
    GROUP BY c.category_id, c.category_name
    ORDER BY total_orders DESC
";
$category_stmt = $pdo->prepare($category_sql);
$category_stmt->execute();
$category_result = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

$category_labels = [];
$category_data = [];

foreach ($category_result as $row) {
    $category_labels[] = $row['category_name'];
    $category_data[] = (int)$row['total_orders'];
}

$total_delivered_orders = array_sum($category_data);

$month_sql = "
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') AS month,
        COUNT(DISTINCT o.order_id) AS total_orders,
        SUM(oi.price * oi.quantity) AS total_revenue
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.status = 'Delivered'
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
";
$month_stmt = $pdo->prepare($month_sql);
$month_stmt->execute();
$month_result = $month_stmt->fetchAll(PDO::FETCH_ASSOC);

$month_labels = [];
$month_data = [];
$month_revenue = [];

foreach ($month_result as $row) {
    $month_labels[] = $row['month'];
    $month_data[] = (int)$row['total_orders'];
    $month_revenue[] = (float)$row['total_revenue'];
}

$total_revenue_sql = "
    SELECT SUM(oi.price * oi.quantity) AS total_revenue
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.status = 'Delivered'
";
$total_revenue_stmt = $pdo->prepare($total_revenue_sql);
$total_revenue_stmt->execute();
$total_revenue_result = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC);

$total_delivered_revenue = (float)($total_revenue_result['total_revenue'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Charts</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    <link rel="stylesheet" href="/css/data_chart.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>

<?php include __DIR__ . '/../admin_header.php'; ?>

<main class="chart-page">
    <h1 class="page-title">Data Charts</h1>

    <div class="summary-container">
        <div class="summary-card">
            <span class="summary-label">Total Delivered Orders</span>
            <span class="summary-value"><?= $total_delivered_orders ?></span>
        </div>

        <div class="summary-card">
            <span class="summary-label">Total Delivered Revenue</span>
            <span class="summary-value">RM <?= number_format($total_delivered_revenue, 2) ?></span>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-card">
            <div class="chart-header">
                <h2>Total Orders by Category</h2>
                <div class="chart-summary">
                    Total Delivered Orders: <strong><?= $total_delivered_orders ?></strong>
                </div>
            </div>
            <div class="chart-box">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="chart-card large-card">
            <div class="chart-header">
                <h2>Monthly Orders & Revenue</h2>
                <div class="chart-summary">
                    Delivered Orders + Revenue Trend
                </div>
            </div>
            <div class="chart-box">
                <canvas id="monthChart"></canvas>
            </div>
        </div>
    </div>
</main>

<script>
const categoryLabels = <?= json_encode($category_labels) ?>;
const categoryData = <?= json_encode($category_data) ?>;
const totalDeliveredOrders = <?= json_encode($total_delivered_orders) ?>;

const monthLabels = <?= json_encode($month_labels) ?>;
const monthData = <?= json_encode($month_data) ?>;
const monthRevenue = <?= json_encode($month_revenue) ?>;
</script>

<script src="/js/data_chart.js"></script>

<?php include __DIR__ . '/../footer.php'; ?>

</body>
</html>