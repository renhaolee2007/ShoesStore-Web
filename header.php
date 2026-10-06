<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

$current_page = basename($_SERVER['PHP_SELF']); 

if (($current_page === 'member_login.php' || $current_page === 'member_register.php') && !is_logged_in()) {
    return; 
}
?>

<header>
<link rel="stylesheet" href="/css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="header-area">
    <div class="header-container">
        
        <div class="logo">
            <a href="/mainpage.php">StepStyle</a>
        </div>

        <ul class="nav">
            <li><a href="/product/list.php">Shop All</a></li>
            <li class="dropdown">
               <a href="#">Categories <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i></a>
               <ul class="dropdown-menu">
                  <li><a href="/product/list.php?category=1">Sneakers</a></li>
                  <li><a href="/product/list.php?category=2">Running</a></li>
                  <li><a href="/product/list.php?category=3">Basketball</a></li>
                  <li><a href="/product/list.php?category=4">Lifestyle</a></li>
                </ul>
            </li>
        </ul>

        <div class="header-buttons" style="display: flex; align-items: center;">
            <?php if (is_logged_in()): ?>
                <a href="/cart/view.php" class="icon-btn" title="Cart" style="
                    text-decoration: none; 
                    color: white; 
                    margin-right: 5px; 
                    display: inline-flex;
                    align-items: center;
                ">
                    <i class="fa-solid fa-cart-shopping" style="font-size: 22px;"></i>
                </a>
                <a href="/wishlist/wishlist.php" class="header-btn">Wishlist</a>
                <a href="/order/order_history.php" class="header-btn">Order History</a>
                <a href="/profile.php" class="header-btn">Profile</a>
                <a href="/logout.php" class="header-btn logout-style">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="header-btn btn-login">Login</a>
                <a href="/register.php" class="header-btn btn-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</header>