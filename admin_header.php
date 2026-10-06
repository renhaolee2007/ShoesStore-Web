<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}
?>
<!-- Header -->
<div class="header-area">
    <div class="main-nav">
        <!-- Logo -->
        <a href="/admin_dash.php" class="logo">StepStyle</a>

        <!-- Navigation -->
        <ul class="nav">
            <li><a href="/admin_dash.php">Dashboard</a></li>
            <li><a href="/admin/member_maintenance.php">User Management</a></li>
            <li><a href="/admin/admin_maintenance.php">Admin Management</a></li>
            <li><a href="/admin/product_list.php">Product Management</a></li>
            <li><a href="/admin/admin_orders.php">Order Management</a></li>
            <li><a href="/admin/data_chart.php">Reports</a></li>
        </ul>

        <!-- Right-side buttons -->
        <div class="header-buttons">
            <?php if (is_logged_in()): ?>
                <a href="/profile.php" class="header-btn">Admin Profile</a>
                <a href="/logout.php" class="header-btn logout-style">Logout</a>
            <?php else: ?>
                <a href="login.php" class="header-btn">Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>