<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php'; 

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; 

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: product_listing.php");
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = ?"); 
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Product not found");
    }

    
    $size_stmt = $pdo->prepare("SELECT size_id, size, stock FROM product_size WHERE product_id = ? AND stock > 0 ORDER BY size ASC");
    $size_stmt->execute([$id]);
    $available_sizes = $size_stmt->fetchAll();

    
    $wish_stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wish_stmt->execute([$user_id, $id]);
    $is_fav = $wish_stmt->fetch();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['product_name']); ?> | StepStyle</title>
    
    <style>
        :root {
            --text-dark: #000000;
            --text-gray: #767677;
            --border-color: #ebedee;
            --accent-gray: #f5f5f5;
        }

        body { 
            font-family: 'Inter', Arial, sans-serif; 
            background: #fff; 
            margin: 0; 
            color: var(--text-dark);
        }

        .main-wrapper { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 60px 20px;
        }

        .product-container {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr; 
            gap: 80px; 
            align-items: start;
        }

        .image-viewer {
            width: 100%;
            background: var(--accent-gray);
            border-radius: 4px;
            overflow: hidden;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-viewer img {
            width: 100%;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .info-panel {
            padding: 0;
            position: relative;
            top: 0;
        }

        .product-name {
            font-size: 48px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 15px 0;
            line-height: 1.1;
        }

        .product-price {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 40px;
            display: block;
        }

        .description-content {
            font-size: 16px;
            line-height: 1.7;
            color: #333;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .size-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 40px;
        }
        .size-item {
            border: 1px solid var(--border-color);
            padding: 15px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .size-item:hover { border-color: #000; }
        .size-item.active {
            background: #000;
            color: #fff;
            border-color: #000;
        }

        .button-group {
            display: flex;
            gap: 15px;
        }

        .btn-add {
            flex: 1;
            background: #000;
            color: #fff;
            padding: 22px;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 16px;
        }
        .btn-add:hover { background: #333; }

        .btn-wish {
            width: 65px;
            background: #fff;
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: 0.2s;
        }
        .btn-wish:hover { border-color: #000; }
        .btn-wish.active { color: #ff4d4d; border-color: #ff4d4d; }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-wrapper">
        <div class="product-container">
            
            <div class="image-viewer">
                <?php $photo = !empty($product['product_photo']) ? $product['product_photo'] : 'default.jpg'; ?>
                <img src="../../uploads/shoes/<?= htmlspecialchars($photo); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
            </div>

            <div class="info-panel">

                <h1 class="product-name"><?= htmlspecialchars($product['product_name']); ?></h1>
                <div class="product-price">RM <?= number_format($product['product_price'], 2); ?></div>

                <div class="description-content">
                    <?= nl2br(htmlspecialchars($product['product_descrip'])); ?>
                </div>

                <div class="section-title">Select Size (UK)</div>
                <div class="size-grid">
                    <?php if (empty($available_sizes)): ?>
                        <div style="grid-column: span 3; color: red;">Out of Stock</div>
                    <?php else: ?>
                        <?php foreach ($available_sizes as $index => $row): ?>
                            <div class="size-item <?= $index === 0 ? 'active' : '' ?>" onclick="selectSize(this, '<?= $row['size_id'] ?>')">
                                <?= htmlspecialchars($row['size']) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="button-group">
                    <form onsubmit="handleAddToBag(event)" style="flex: 1; display: flex;">
                        <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">
                        <input type="hidden" id="selected_size" name="size_id" value="<?= $available_sizes[0]['size_id'] ?? '' ?>">
                        <input type="hidden" name="quantity" value="1">

                        <button type="submit" class="btn-add" <?= empty($available_sizes) ? 'disabled style="background:#ccc;"' : '' ?>>
                            <?= empty($available_sizes) ? 'Sold Out' : 'Add to bag' ?> →
                        </button>
                    </form>

                    <button class="btn-wish <?= $is_fav ? 'active' : '' ?>" id="wishBtn" onclick="toggleWishlist(<?= $product['product_id']; ?>)">
                        <?= $is_fav ? '❤️' : '♡' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        function toggleWishlist(productId) {
            window.location.href = '../wishlist/wishlist_action.php?product_id=' + productId;
        }

        function selectSize(element, sizeId) {
            document.querySelectorAll('.size-item').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('selected_size').value = sizeId;
        }

        function handleAddToBag(event) {
            event.preventDefault(); 

            const sizeId = document.getElementById('selected_size').value;
            if (!sizeId) {
                alert("Please select a size first!");
                return;
            }

            const formData = new FormData(event.target);

            fetch('../cart/add.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                
                if (data.trim() === 'not_logged_in') {
                    alert("Please login first to add items to your bag.");
                    window.location.href = "../login.php"; 
                } else if (data.trim() === 'success') {
                    alert("Added to bag!");
                } else {
                    
                    alert("Added to bag!"); 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Failed to add to bag.");
            });
        }
    </script>
</body>
</html>