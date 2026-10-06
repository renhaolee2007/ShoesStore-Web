<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once($_SERVER['DOCUMENT_ROOT'] . "../database/db.php");
include($_SERVER['DOCUMENT_ROOT'] . "../admin_header.php");
?>

<link rel="stylesheet" href="../css/admin_dash.css">
<link rel="stylesheet" href="../css/admin_product.css">
<h2 class="page-title">Create New Product</h2>

<div class="create-product-container">
    <div class="photo-preview-section">
        <img id="preview-img" src="" alt="Product Preview" class="preview-img" style="display:none;">
        <p id="no-photo-text">No photo selected</p>
    </div>

    <div class="create-product-form">
        <?php
        if(isset($_SESSION['success_msg'])){
            echo '<div class="success-msg">'.$_SESSION['success_msg'].'</div>';
            unset($_SESSION['success_msg']);
        }
        if(isset($_SESSION['error_msg'])){
            echo '<div class="error-msg">'.$_SESSION['error_msg'].'</div>';
            unset($_SESSION['error_msg']);
        }
        ?>

        <form action="product_create_action.php" method="POST" enctype="multipart/form-data">
            <label for="product_name">Product Name</label>
            <input type="text" name="product_name" id="product_name" required>

            <label for="product_descrip">Description</label>
            <textarea name="product_descrip" id="product_descrip" required></textarea>

            <label for="product_price">Price (RM)</label>
            <input type="number" step="0.01" name="product_price" id="product_price" required>

            <label>Inventory per Size (38 - 42)</label>
            <div class="size-input-grid">
                <?php 
                $sizes = [38, 39, 40, 41, 42];
                foreach($sizes as $s): ?>
                    <div class="size-item">
                        <span class="size-label">Size <?php echo $s; ?></span>
                        <input type="number" name="size_stock[<?php echo $s; ?>]" min="0" placeholder="0">
                    </div>
                <?php endforeach; ?>
            </div>

            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" required>
                <option value="">Select Category</option>
                <?php
                $stmt = $pdo->query("SELECT * FROM category ORDER BY category_name ASC");
                while($cat = $stmt->fetch(PDO::FETCH_ASSOC)){
                    echo "<option value='".$cat['category_id']."'>".$cat['category_name']."</option>";
                }
                ?>
            </select>

            <label for="product_photo">Product Photo</label>
            <input type="file" name="product_photo" id="product_photo" accept="image/*" onchange="previewImage(event)">

            <div class="form-buttons">
                <button type="submit" class="submit-btn">Create Product</button>
                <button type="button" class="clear-btn" onclick="clearForm()">Clear All</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(event){
    const input = event.target;
    const preview = document.getElementById('preview-img');
    const noPhotoText = document.getElementById('no-photo-text');

    if(input.files && input.files[0]){
        preview.src = URL.createObjectURL(input.files[0]);
        preview.style.display = 'block';
        noPhotoText.style.display = 'none';
    } else {
        preview.src = '';
        preview.style.display = 'none';
        noPhotoText.style.display = 'block';
    }
}

function clearForm(){
    const form = document.querySelector('.create-product-form form');
    form.reset();
    document.getElementById('preview-img').style.display = 'none';
    document.getElementById('no-photo-text').style.display = 'block';
}
</script>

<?php include("../footer.php"); ?>