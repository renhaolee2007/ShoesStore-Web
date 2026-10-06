<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    
    $sql = "SELECT w.*, p.product_id, p.product_name, p.product_price, p.product_photo, 
                   IFNULL(SUM(s.stock), 0) as total_stock 
            FROM wishlist w 
            JOIN product p ON w.product_id = p.product_id 
            LEFT JOIN product_size s ON p.product_id = s.product_id
            WHERE w.user_id = ? 
            GROUP BY p.product_id
            ORDER BY w.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist | StepStyle</title>
    <style>
        :root {
            --adidas-black: #000000;
            --text-gray: #666666;
            --bg-gray: #f5f5f5;
        }

        body { 
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; 
            background: #ffffff; 
            margin: 0; 
            color: #000;
        }

        .main-wrapper { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 50px 60px; 
        }

        .page-header {
            text-align: left;
            margin-bottom: 40px;
        }
        .page-header h1 {
            font-size: 36px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .item-count {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: var(--adidas-black);
            padding: 4px 16px;
            border-radius: 50px;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); 
            gap: 25px;
        }

        .wish-card {
            background: #fff;
            position: relative;
        }

        .img-container {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: var(--bg-gray);
            overflow: hidden;
            position: relative;
            border: 1px solid #eee;
        }

        .stock-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 800;
            color: #008a00;
            z-index: 5;
        }

        .wish-card img {
            width: 100%;
            height: 100%;
            object-fit: contain; 
            padding: 15px;
            box-sizing: border-box;
        }

        .remove-x {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fff;
            border: 1px solid #000;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            font-weight: bold;
        }

        .card-body {
            padding: 15px 0;
            text-align: left;
        }

        .product-name {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            min-height: 40px;
        }

        .price {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .btn-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-adidas {
            display: flex;
            align-items: center;
            justify-content: center; 
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid #000;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-black { background: #000; color: #fff; border: none; }
        .btn-white { background: #fff; color: #000; }
        .btn-white:hover { background: #000; color: #fff; }

        .btn-remove-link {
            background: none;
            border: none;
            padding: 5px 0;
            font-size: 12px;
            text-decoration: underline;
            color: var(--text-gray);
            cursor: pointer;
            text-align: left;
        }

        @media (max-width: 1100px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 800px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-wrapper">
        <div class="page-header">
            <h1>MY WISHLIST <span class="item-count"><?= count($items) ?> ITEMS</span></h1>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <h2 style="font-weight:900;">YOUR WISHLIST IS EMPTY</h2>
                <p style="color: #666; margin-bottom: 20px;">Once you add something to your wishlist, it will appear here.</p>
                <a href="../product/list.php" class="btn-adidas btn-black" style="width:200px;">
                    SHOP NOW
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($items as $item): ?>
                <div class="wish-card">
                    <div class="remove-x" onclick="removeFromWishlist(<?= $item['product_id'] ?>)">✕</div>

                    <div class="img-container">
                        <?php if ($item['total_stock'] > 0): ?>
                            <div class="stock-badge">● IN STOCK</div>
                        <?php endif; ?>
                        
                        <?php 
                            
                            $photoPath = "../../uploads/shoes/" . $item['product_photo'];
                        ?>
                        <a href="../../product/detail.php?id=<?= $item['product_id'] ?>">
                            <img src="<?= $photoPath ?>" alt="Product" onerror="this.src='https://via.placeholder.com/300?text=No+Image'">
                        </a>
                    </div>

                    <div class="card-body">
                        <h3 class="product-name"><?= htmlspecialchars($item['product_name']) ?></h3>
                        <div class="price">RM <?= number_format($item['product_price'], 2) ?></div>

                        <div class="btn-stack">
                            <?php if ($item['total_stock'] > 0): ?>
                                <button class="btn-adidas btn-black" onclick="addToBag(<?= $item['product_id'] ?>)">
                                    Add to bag
                                </button>
                            <?php else: ?>
                                <button class="btn-adidas" style="background:#f5f5f5; color:#ccc; border-color:#eee; cursor:not-allowed;" disabled>Sold Out</button>
                            <?php endif; ?>

                            <a href="../../product/detail.php?id=<?= $item['product_id'] ?>" class="btn-adidas btn-white">
                                View Detail
                            </a>

                            <button class="btn-remove-link" onclick="removeFromWishlist(<?= $item['product_id'] ?>)">
                                Remove from Wishlist
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>

    <script>
    function removeFromWishlist(productId) {
        if (confirm("Remove this item from your wishlist?")) {

            window.location.href = "wishlist_action.php?product_id=" + productId;
        }
    }

    function addToBag(productId) {

        alert("Please select your size in the product details page.");
        window.location.href = "/../product/detail.php?id=" + productId;
    }
    </script>
</body>
</html>