<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("database/db.php");
include("admin_header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
</head>
<body>
<style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #fff; color: #111; }
    h2 { font-size: 28px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 30px; text-align: center; }

    /* 1. Hero Banner */
    .hero-banner {
        position: relative; width: 100%; min-height: 550px; 
        display: flex; align-items: flex-end; padding-bottom: 80px; 
        overflow: hidden; background: black;
    }
    .banner-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; }
    .banner-content { position: relative; z-index: 3; margin-left: 10%; color: white; }

    /* 2. Featured Products */
    .featured-products { padding: 50px 5%; background: #f9f9f9; text-align: center; }
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        gap: 20px;
        margin-bottom: 40px;
    }

    .product-card {
        background: white;
        transition: 0.3s;
        text-align: left;
        border: 1px solid #eee;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }

   
    .product-img-container {
        width: 100%;
        aspect-ratio: 1 / 1;  
        background: #f6f6f6;     
        display: flex;
        align-items: center;    
        justify-content: center; 
        padding: 15px;            
        box-sizing: border-box;
        overflow: hidden;
    }

    
    .product-card img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;   
        display: block;
        transition: transform 0.4s ease; 
    }

    .product-info { padding: 15px; }
    .product-card h4 { font-size: 16px; margin: 0 0 5px; font-weight: 600; color: #111; }
    .product-descrip { font-size: 13px; color: #777; margin-bottom: 10px; height: 36px; overflow: hidden; }
    .product-price { font-size: 16px; font-weight: 800; color: #000; margin: 0; }

    .shop-btn {
        display: inline-block; padding: 15px 45px; background: #000; color: #fff;
        text-decoration: none; font-weight: bold; text-transform: uppercase; transition: 0.3s;
    }
    .shop-btn:hover { background: #333; }
</style>

<section class="hero-banner">
    <img src="../uploads/banner/banner1(1).jpg" alt="Sneakers Banner" class="banner-img">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.3); z-index: 2;"></div>
    <div class="banner-content">
        <div style="background: white; color: black; padding: 8px 14px; font-weight: bold; display: inline-block; margin-bottom: 15px;">NEW COLLECTION</div>
        <h1 style="font-size: 52px; margin: 0 0 10px 0; font-weight: 800; text-transform: uppercase; line-height: 1.1;">MID-SEASON SALE<br>20% OFF</h1>
        <p style="font-size: 18px; margin-bottom: 25px; opacity: 0.8;">Discover the latest sneaker styles.</p>
        <a href="admin/product_list.php" class="shop-btn" style="background: white; color: black;" onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='white'">PRODUCT</a>
    </div>
</section>

<section class="featured-products">
    <h2>Trending Sneakers</h2>
    <div class="product-grid">
        <?php
        try {
            $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM product p JOIN category c ON p.category_id = c.category_id ORDER BY p.product_id ASC LIMIT 8");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($products){
                foreach($products as $product){
                    $photo = !empty($product['product_photo']) ? "uploads/shoes/".$product['product_photo'] : "../uploads/shoes/placeholder.jpg";
                    
                    echo '<div class="product-card">';
                    echo '<div class="product-img-container">';
                    echo '<img src="'.$photo.'" alt="'.htmlspecialchars($product['product_name']).'">';
                    echo '</div>';
                    echo '<div class="product-info">';
                    echo '<h4>'.htmlspecialchars($product['product_name']).'</h4>';
                    echo '<p class="product-descrip">'.htmlspecialchars($product['product_descrip']).'</p>';
                    echo '<p class="product-price">RM '.number_format($product['product_price'],2).'</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else { echo '<p>No products available.</p>'; }
        } catch(PDOException $e){ echo "<p>Database Error: ".$e->getMessage()."</p>"; }
        ?>
    </div> 
    <div class="shop-btn-container">
        <a href="admin/product_list.php" class="shop-btn">View All</a>
    </div>
</section>

<?php include("footer.php"); ?>