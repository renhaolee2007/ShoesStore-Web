<?php
// include base file (database connection + helper functions)
require_once '../lib/base.php';

// include header layout
include __DIR__ . '/../header.php';

// category id for basketball products
$category_id = 3;

/* =========================
   get products by category
   ========================= */
$stmt = db()->prepare("SELECT * FROM product WHERE category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll();

/* =========================
   get product sizes for this category
   ========================= */
$sizeStmt = db()->prepare("
    SELECT * 
    FROM product_size 
    WHERE product_id IN (
        SELECT product_id FROM product WHERE category_id = ?
    )
");
$sizeStmt->execute([$category_id]);
$sizes = $sizeStmt->fetchAll();

/* =========================
   group sizes by product_id
   ========================= */
$product_sizes = [];
foreach ($sizes as $s) {
    $product_sizes[$s['product_id']][] = $s;
}
?>

<h1>Basketball</h1>

<!-- product grid layout -->
<div style="
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 30px;
    padding: 40px;
">

<?php foreach ($products as $p): ?>

    <div style="
        border: 1px solid #eee;
        padding: 15px;
        text-align: center;
    ">

        <!-- product image -->
        <img src="../uploads/shoes/<?= $p['product_photo'] ?>" 
             style="width:100%; height:220px; object-fit:cover;">

        <!-- product name -->
        <h3 style="font-size:16px; margin:10px 0;">
            <?= $p['product_name'] ?>
        </h3>

        <!-- product price -->
        <p style="font-weight:bold;">
            RM <?= number_format($p['product_price'],2) ?>
        </p>

        <!-- add to cart form -->
        <form action="../cart/add.php" method="POST">

            <input type="hidden" name="redirect" value="<?= $_SERVER['REQUEST_URI'] ?>">
            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">

            <!-- size selection -->
            <div style="margin:10px 0;">
                <select name="size_id" required style="padding:5px; width:100%;">
                    <option value="">Select Size</option>

                    <?php if (!empty($product_sizes[$p['product_id']])): ?>
                        <?php foreach ($product_sizes[$p['product_id']] as $s): ?>
                            <option value="<?= $s['size_id'] ?>">
                                <?= $s['size'] ?> (Stock: <?= $s['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option disabled>No Size Available</option>
                    <?php endif; ?>

                </select>
            </div>

            <!-- quantity control -->
            <div style="margin:10px 0;">
                <button type="button" onclick="changeQty(this, -1)">-</button>

                <input type="text" name="quantity" value="1" size="2" readonly 
                       style="text-align:center; border:none;">

                <button type="button" onclick="changeQty(this, 1)">+</button>
            </div>

            <!-- add to cart button -->
            <button type="submit" style="
                width:100%;
                padding:10px;
                background:black;
                color:white;
                border:none;
                cursor:pointer;
                font-weight:bold;
            ">
                Add to Cart
            </button>

        </form>

        <!-- product detail link -->
        <a href="../product/detail.php?id=<?= $p['product_id'] ?>" style="
            display:block;
            margin-top:10px;
            padding:10px;
            border:1px solid black;
            text-decoration:none;
            color:black;
            font-weight:bold;
        ">
            Details
        </a>

    </div>

<?php endforeach; ?>

</div>

<script>
// increase or decrease quantity
function changeQty(btn, amount) {
    let input = btn.parentElement.querySelector('input[name="quantity"]');
    let current = parseInt(input.value);

    current += amount;

    // minimum quantity is 1
    if (current < 1) current = 1;

    input.value = current;
}
</script>