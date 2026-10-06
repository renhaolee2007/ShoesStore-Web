<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . "/database/db.php");
?>
<link rel="stylesheet" href="../css/admin_dash.css">
<link rel="stylesheet" href="../css/admin_product.css">
<?php

include($_SERVER['DOCUMENT_ROOT'] . "/admin_header.php");

$product_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($product_id <= 0) {
    echo "<div style='padding:20px;'><h3>Invalid product ID.</h3></div>";
    include($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'];
    $descrip = $_POST['product_descrip'];
    $price = $_POST['product_price'];
    $category_id = $_POST['category_id'];
    $submitted_stocks = $_POST['size_stock'] ?? []; 

    $new_photo = '';
    if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['product_photo']['name'], PATHINFO_EXTENSION);
        $new_photo = uniqid('prod_') . "." . $ext;
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/product/";
        
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        move_uploaded_file($_FILES['product_photo']['tmp_name'], $upload_dir . $new_photo);
    }

    try {
        $pdo->beginTransaction();
        
        $sql = "UPDATE product SET 
                    product_name=:name,
                    product_descrip=:descrip,
                    product_price=:price,
                    category_id=:category_id
                    " . ($new_photo ? ", product_photo=:photo" : "") . "
                WHERE product_id=:id";
        
        $params = [
            ':name' => $name,
            ':descrip' => $descrip,
            ':price' => $price,
            ':category_id' => $category_id,
            ':id' => $product_id
        ];
        if($new_photo) $params[':photo'] = $new_photo;
        
        $pdo->prepare($sql)->execute($params);

        $stmt_del = $pdo->prepare("DELETE FROM product_size WHERE product_id=:id");
        $stmt_del->execute([':id'=>$product_id]);

        $stmt_ins = $pdo->prepare("INSERT INTO product_size (product_id, `size`, `stock`) VALUES (?, ?, ?)");
        foreach($submitted_stocks as $sz => $qty){
            if($qty !== ''){
                $stmt_ins->execute([$product_id, $sz, intval($qty)]);
            }
        }

        $pdo->commit();
        echo "<script>window.location.href='product_edit.php?id=$product_id&status=updated';</script>";
        exit;

    } catch(PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Update failed: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM product WHERE product_id=:id");
$stmt->execute([':id'=>$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div style='padding:20px;'><h3>Product not found.</h3></div>";
    include($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM category ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$all_sizes = [38, 39, 40, 41, 42, 43]; 
$current_stocks = array_fill_keys($all_sizes, 0);
$stmt_stocks = $pdo->prepare("SELECT `size`, `stock` FROM product_size WHERE product_id=:id");
$stmt_stocks->execute([':id'=>$product_id]);
while($row = $stmt_stocks->fetch(PDO::FETCH_ASSOC)){
    if (array_key_exists($row['size'], $current_stocks)) {
        $current_stocks[$row['size']] = $row['stock'];
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $success_msg = "Product details and stock updated successfully!";
}
?>

<h2 class="page-title">Edit Product Details</h2>

<div class="edit-product-container">
    <div class="photo-section">
        <div id="preview-container" style="width:250px; height:250px; border:2px dashed #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden; margin: 0 auto 20px; background: #eee;">
            <?php 
                $photo_name = $product['product_photo'];
                $img_src = "/uploads/shoes/" . $photo_name;
            ?>
            
            <?php if($img_src): ?>
                <img src="<?php echo $img_src; ?>?t=<?php echo time(); ?>" id="preview-img" style="max-width:100%; max-height:100%; display:block;">
            <?php else: ?>
                <p id="no-img-text">No Image Uploaded</p>
                <img id="preview-img" style="display:none; max-width:100%; max-height:100%;">
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="edit-product-form">
        <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">

        <?php if($success_msg) echo "<p style='color:green; padding:10px; border:1px solid green; background:#f0fff0;'>$success_msg</p>"; ?>
        <?php if($error_msg) echo "<p style='color:red; padding:10px; border:1px solid red; background:#fff0f0;'>$error_msg</p>"; ?>

        <label>Product Name</label>
        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>

        <label>Description</label>
        <textarea name="product_descrip" rows="4" required><?php echo htmlspecialchars($product['product_descrip']); ?></textarea>

        <label>Price (RM)</label>
        <input type="number" step="0.01" name="product_price" value="<?php echo htmlspecialchars($product['product_price']); ?>" required>

        <label>Stock per Size</label>
        <div class="size-input-grid" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; padding:10px; background:#f9f9f9; border-radius:5px;">
            <?php foreach($current_stocks as $size => $qty): ?>
                <div class="size-item">
                    <small>Size <?php echo $size; ?></small>
                    <input type="number" name="size_stock[<?php echo $size; ?>]" value="<?php echo $qty; ?>" min="0" style="width:100%;">
                </div>
            <?php endforeach; ?>
        </div>

        <label style="margin-top:15px; display:block;">Category</label>
        <select name="category_id" required>
            <?php foreach($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label style="margin-top:15px; display:block;">Replace Product Image</label>
        <input type="file" name="product_photo" accept="image/*" onchange="showPreview(event)">

        <div class="form-buttons" style="margin-top:20px; display:flex; gap:10px;">
            <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;">Save Changes</button>
            <button type="button" onclick="window.location.href='product_edit.php?id=<?php echo $product_id; ?>'" style="background: #6c757d; color: white; padding: 10px 20px; border: none; cursor: pointer;">Reset</button>
        </div>
    </form>
</div>

<script>
function showPreview(event){
    const file = event.target.files[0];
    if(file){
        const reader = new FileReader();
        const preview = document.getElementById('preview-img');
        const noText = document.getElementById('no-img-text');
        
        reader.onload = function(e){
            preview.src = e.target.result;
            preview.style.display = 'block';
            if(noText) noText.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/footer.php"); ?>