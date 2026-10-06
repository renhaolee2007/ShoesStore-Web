<?php
session_start();
require_once '../database/db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null ; 

// Get category filter and search keywords
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // 1. Base SQL Query
    $sql = "SELECT p.*, SUM(s.stock) as total_stock 
            FROM product p 
            LEFT JOIN product_size s ON p.product_id = s.product_id 
            WHERE 1=1";

    $params = [];

    // 2. Dynamic Search Condition
    if (!empty($search)) {
        $sql .= " AND p.product_name LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    // 3. Dynamic Category Condition
    if ($category_id) {
        $sql .= " AND p.category_id = :cat_id";
        $params['cat_id'] = $category_id;
    }

    // 4. Grouping and Ordering
    $sql .= " GROUP BY p.product_id ORDER BY p.product_id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); 
    $products = $stmt->fetchAll();

    // Retrieve IDs already in the current user's wishlist
    $wishlist_ids = [];
    if ($user_id) {
        $wish_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $wish_stmt->execute([$user_id]); 
        $wishlist_ids = $wish_stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Listing | StepStyle</title>

    <style>
        :root {
            --adidas-black: #000000;
            --text-gray: #666666;
            --bg-gray: #f5f5f5;
            --stock-green: #008a00;
        }

        body { 
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; 
            background: #ffffff; 
            margin: 0; 
            color: #000;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 60px;
        }

        h1 {font-size: 36px;font-weight:900 ; text-transform: uppercase; margin-bottom: 30px; letter-spacing: 1px; }
        .search-container { margin-bottom: 30px; display: flex; max-width: 400px; }
        .search-input { flex: 1; padding: 12px; border: 1px solid #000; border-right: none; outline: none; }
        .search-btn { padding: 0 20px; background: #000; color: #fff; border: 1px solid #000; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        /* Pure CSS Search Icon */
        .icon-search { width: 14px; height: 14px; border: 2px solid #fff; border-radius: 50%; position: relative; display: inline-block; }
        .icon-search::after { content: ''; position: absolute; width: 2px; height: 8px; background: #fff; bottom: -5px; right: -3px; transform: rotate(-45deg); }

        .filter { margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .filter a { margin-right: 25px; font-size: 14px; font-weight: 700; text-decoration: none; text-transform: uppercase; color: var(--text-gray); transition: 0.2s ;display: inline-block; }
        .filter a.active { color: #000; box-shadow: 0 2px 0 #000; }

        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
        .card { background: white; display: flex; flex-direction: column; }
        .img-wrapper { width: 100%; aspect-ratio: 1 / 1; background: var(--bg-gray); position: relative; overflow: hidden; border: 1px solid #eee; }
        .card img { width: 100%; height: 100%; object-fit: contain; transition: transform 0.4s ease; padding: 10px; }
        .card:hover img { transform: scale(1.05); }

        .card-wishlist-btn { position: absolute; top: 10px; right: 10px; background: #fff; border: 1px solid #000; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; border-radius: 4px; font-size: 20px; }
        .card-wishlist-btn.active { color: #e74c3c; border-color: #e74c3c; }

        .card-info { padding: 15px 0; }
        .product-name { font-size: 17px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; min-height: 40px; }
        .price { font-size: 16px; font-weight: 800; margin-bottom: 12px; }

        .btn-group { display: flex; flex-direction: column; gap: 8px; }
        .btn-step { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; font-size: 13px; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #000; transition: 0.3s; cursor: pointer; }
        .btn-black { background: #000; color: #fff; border: none; }
        .btn-white { background: #fff; color: #000; }
        .btn-white:hover { background: #000; color: #fff; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #fff; width: 400px; padding: 30px; position: relative; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-modal { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 24px; color: #999; }
        
        .size-selector { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 15px 0; }
        .size-item { border: 1px solid #ccc; padding: 10px; text-align: center; cursor: pointer; font-weight: 700; }
        .size-item.selected { background: #000; color: #fff; border-color: #000; }

        .qty-box { display: flex; align-items: center; border: 1px solid #000; width: fit-content; margin-bottom: 25px; }
        .qty-btn { width: 40px; height: 40px; background: none; border: none; cursor: pointer; font-size: 18px; font-weight: bold; }
        .qty-input { width: 50px; height: 40px; border: none; border-left: 1px solid #000; border-right: 1px solid #000; text-align: center; font-weight: 700; outline: none; }

        @media (max-width: 800px) { .products-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="main-container">
        <h1>All Products</h1>

        <div class="search-container">
            <form action="list.php" method="GET" style="display: flex; width: 100%;">
                <?php if($category_id): ?>
                    <input type="hidden" name="category" value="<?= $category_id ?>">
                <?php endif; ?>
                <input type="text" name="search" class="search-input" placeholder="Search for shoes..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><span class="icon-search"></span></button>
            </form>
        </div>

        <div class="filter">
            <a href="list.php" <?= !$category_id ? 'class="active"' : '' ?>>All</a>
            <a href="list.php?category=1" <?= $category_id==1 ? 'class="active"' : '' ?>>Sneakers</a>
            <a href="list.php?category=2" <?= $category_id==2 ? 'class="active"' : '' ?>>Running</a>
            <a href="list.php?category=3" <?= $category_id==3 ? 'class="active"' : '' ?>>Basketball</a>
            <a href="list.php?category=4" <?= $category_id==4 ? 'class="active"' : '' ?>>Lifestyle</a>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <?php 
                    $is_fav = in_array($p['product_id'], $wishlist_ids); 
                    // Fetch available sizes for the current product
                    $sz_stmt = $pdo->prepare("SELECT size_id, size FROM product_size WHERE product_id = ? AND stock > 0");
                    $sz_stmt->execute([$p['product_id']]);
                    $p_sizes = json_encode($sz_stmt->fetchAll(PDO::FETCH_ASSOC));
                ?>
                <div class="card">
                    <div class="img-wrapper">
                        <button onclick="toggleWishlist(<?= $p['product_id'] ?>)" class="card-wishlist-btn <?= $is_fav ? 'active' : '' ?>">
                            <?= $is_fav ? '❤' : '♡' ?>
                        </button>
                        <a href="detail.php?id=<?= $p['product_id'] ?>">
                            <img src="../../uploads/shoes/<?= htmlspecialchars($p['product_photo']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
                        </a>
                    </div>

                    <div class="card-info">
                        <h3 class="product-name"><?= htmlspecialchars($p['product_name']) ?></h3>
                        <div class="price">RM <?= number_format($p['product_price'], 2) ?></div>

                        <div class="btn-group">
                            <a href="detail.php?id=<?= $p['product_id'] ?>" class="btn-step btn-white">View Detail <span>→</span></a>
                            
                            <?php if ($p['total_stock'] > 0): ?>
                                <button onclick='openQuickModal(<?= $p['product_id'] ?>, "<?= addslashes($p['product_name']) ?>", <?= $p['product_price'] ?>, <?= $p_sizes ?>)' class="btn-step btn-black">
                                    Add To Bag <span>🛍</span>
                                </button>
                            <?php else: ?>
                                <button class="btn-step" disabled style="background:#f5f5f5; color:#ccc; border-color:#eee; cursor:not-allowed;">Sold Out</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal-overlay" id="quickModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="m-name" style="text-transform:uppercase; font-weight:900; margin:0 0 10px 0;"></h2>
            <p id="m-price" style="font-weight:800; color:#666; font-size: 18px; margin: 0;"></p>

            <p style="font-size: 11px; font-weight: 800; margin-top:25px; text-transform:uppercase;">Select Size</p>
            <div class="size-selector" id="m-sizes"></div>

            <p style="font-size: 11px; font-weight: 800; margin-top:20px; text-transform:uppercase;">Quantity</p>
            <div class="qty-box">
                <button class="qty-btn" onclick="changeQty(-1)">-</button>
                <input type="number" id="m-qty" class="qty-input" value="1" readonly>
                <button class="qty-btn" onclick="changeQty(1)">+</button>
            </div>

            <button onclick="confirmAdd()" class="btn-step btn-black" style="width: 100%; border: none; justify-content: center;">Confirm Add To Bag</button>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
    let currentId = null, selectedSizeId = null;

    function openQuickModal(id, name, price, sizes) {
        currentId = id; 
        selectedSizeId = null;
        document.getElementById('m-name').innerText = name;
        document.getElementById('m-price').innerText = 'RM ' + price.toFixed(2);
        document.getElementById('m-qty').value = 1;
        
        let html = '';
        sizes.forEach(s => {
            html += `<div class="size-item" onclick="selectSize(this, '${s.size_id}')">${s.size}</div>`;
        });
        document.getElementById('m-sizes').innerHTML = html;
        document.getElementById('quickModal').style.display = 'flex';
    }

    function closeModal() { document.getElementById('quickModal').style.display = 'none'; }

    function selectSize(el, s_id) {
        document.querySelectorAll('.size-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        selectedSizeId = s_id;
    }

    function changeQty(v) {
        let q = document.getElementById('m-qty');
        let n = parseInt(q.value) + v;
        if(n >= 1) q.value = n;
    }

    function confirmAdd() {
        if(!selectedSizeId) { alert("Please select a size!"); return; }
        let qty = document.getElementById('m-qty').value;

        fetch('../cart/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${currentId}&size_id=${selectedSizeId}&quantity=${qty}`
        })
        .then(response => response.text())
        .then(data => {
            // Logic to handle authentication status returned from backend
            if (data.trim() === 'not_logged_in') {
                alert("Please login first to add items to your bag.");
                window.location.href = "../login.php"; 
            } else {
                alert("Success! Added to bag.");
                closeModal();
                location.reload(); 
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Failed to add to bag.");
        });
    }

    function toggleWishlist(id) { 
        window.location.href = '../wishlist/wishlist_action.php?product_id=' + id; 
    }

    window.onclick = function(event) {
        let modal = document.getElementById('quickModal');
        if (event.target == modal) closeModal();
    }
    </script>
</body>
</html>