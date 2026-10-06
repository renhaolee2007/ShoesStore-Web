<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . "/database/db.php");
include($_SERVER['DOCUMENT_ROOT'] . "/admin_header.php");
?>

<h2 class="page-title" style="text-align:center; margin-top:50px; font-family: 'Georgia', serif; color:#333; font-size:32px;">Product List (Admin)</h2>

<?php
if(isset($_SESSION['success_msg'])){
    echo '<div class="success-msg" style="text-align:center; color:green;">'.htmlspecialchars($_SESSION['success_msg']).'</div>';
    unset($_SESSION['success_msg']); 
}
if(isset($_SESSION['error_msg'])){
    echo '<div class="error-msg" style="text-align:center; color:red;">'.htmlspecialchars($_SESSION['error_msg']).'</div>';
    unset($_SESSION['error_msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Maintenance</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
</head>
<body>
<form method="GET" action="" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:center; margin-bottom:20px;">
    <input type="text" name="keyword" placeholder="Search product name..." 
           value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" 
           style="padding:6px 10px; border-radius:5px; border:1px solid #ccc; width:200px;">

    <select name="category_id" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc;">
        <option value="">All Categories</option>
        <?php
        $stmt_cat = $pdo->query("SELECT * FROM category ORDER BY category_name ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
        foreach($categories as $cat){
            $selected = ((string)($_GET['category_id'] ?? '') === (string)$cat['category_id']) ? 'selected' : '';
            echo "<option value='".htmlspecialchars($cat['category_id'])."' $selected>".htmlspecialchars($cat['category_name'])."</option>";
        }
        ?>
    </select>

    <input type="number" name="price_min" placeholder="Min Price" value="<?php echo htmlspecialchars($_GET['price_min'] ?? ''); ?>" style="padding:6px 10px; width:100px;">
    <input type="number" name="price_max" placeholder="Max Price" value="<?php echo htmlspecialchars($_GET['price_max'] ?? ''); ?>" style="padding:6px 10px; width:100px;">

    <select name="sort_by" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc;">
        <option value="">Default</option>
        <option value="price_asc" <?php if(($_GET['sort_by']??'')=='price_asc') echo 'selected'; ?>>Price ↓</option>
        <option value="price_desc" <?php if(($_GET['sort_by']??'')=='price_desc') echo 'selected'; ?>>Price ↑</option>
        <option value="name_asc" <?php if(($_GET['sort_by']??'')=='name_asc') echo 'selected'; ?>>Name ↑</option>
        <option value="name_desc" <?php if(($_GET['sort_by']??'')=='name_desc') echo 'selected'; ?>>Name ↓</option>
    </select>

    <button type="submit" style="padding:6px 12px; background:#4CAF50; color:#fff; border:none; border-radius:4px; cursor:pointer;">Search</button>
    
    <a href="product_list.php" style="padding:7px 13px; background:#007bff; color:#fff; border-radius:4px; text-decoration:none; font-size:13px;  vertical-align:middle; ">Reload</a>
</form>

<form method="POST" action="product_delete.php" onsubmit="return confirm('Delete selected products?');">
    <div style="width:90%; margin:0 auto 10px auto; text-align:right;">
        <a href="product_add.php" class="create-btn" style="padding:6px 12px; background:#4CAF50; color:white; border-radius:4px; text-decoration:none; margin-right:10px;">+ Create Product</a>
        <button type="submit" style="background:#f44336;color:white;padding:6px 12px;border:none;border-radius:4px; cursor:pointer;">
            Delete Selected
        </button>
    </div>

    <table class="product-table" style="width:90%; margin:0 auto; border-collapse:collapse; text-align:center; border: 1px solid #ddd;">
        <thead>
            <tr style="background-color:#333; color:#fff; text-transform:uppercase; font-family:'Georgia', serif;">
                <th><input type="checkbox" id="select-all"></th>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Sizes Available</th>
                <th>Price</th>
                <th>Total Stock</th>
                <th>Category</th>
                <th>Photo</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        try {
            // Use SUM(ps.stock) to calculate the total inventory in real time, and use GROUP_CONCAT to display sizes 
            $sql = "SELECT p.*, c.category_name, 
                           GROUP_CONCAT(DISTINCT ps.size ORDER BY ps.size ASC SEPARATOR ', ') AS sizes,
                           IFNULL(SUM(ps.stock), 0) AS total_stock
                    FROM product p
                    JOIN category c ON p.category_id = c.category_id
                    JOIN product_size ps ON p.product_id = ps.product_id
                    WHERE p.status = 1 AND ps.stock> 0 ";
            $params = [];

            if (!empty($_GET['keyword'])) {
                $sql .= " AND p.product_name LIKE :keyword";
                $params[':keyword'] = "%".$_GET['keyword']."%";
            }
            if (!empty($_GET['category_id'])) {
                $sql .= " AND p.category_id = :category_id";
                $params[':category_id'] = $_GET['category_id'];
            }
            if (!empty($_GET['price_min'])) {
                $sql .= " AND p.product_price >= :price_min";
                $params[':price_min'] = $_GET['price_min'];
            }
            if (!empty($_GET['price_max'])) {
                $sql .= " AND p.product_price <= :price_max";
                $params[':price_max'] = $_GET['price_max'];
            }

            $sql .= " GROUP BY p.product_id";

            // sorting
            $order_by = " ORDER BY p.product_id ASC";
            if(!empty($_GET['sort_by'])) {
                switch($_GET['sort_by']) {
                    case 'price_asc': $order_by = " ORDER BY p.product_price ASC"; break;
                    case 'price_desc': $order_by = " ORDER BY p.product_price DESC"; break;
                    case 'name_asc': $order_by = " ORDER BY p.product_name ASC"; break;
                    case 'name_desc': $order_by = " ORDER BY p.product_name DESC"; break;
                }
            }
            $sql .= $order_by;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($products) {
                foreach ($products as $product) {
                    echo "<tr style='border-bottom: 1px solid #ddd;'>";
                    echo "<td><input type='checkbox' name='delete_ids[]' value='".htmlspecialchars($product['product_id'])."'></td>";
                    echo "<td>".htmlspecialchars($product['product_id'])."</td>";
                    echo "<td>".htmlspecialchars($product['product_name'])."</td>";
                    echo "<td>".htmlspecialchars($product['product_descrip'])."</td>";
                    echo "<td>".htmlspecialchars($product['sizes'] ?? 'No sizes')."</td>";
                    echo "<td>RM ".htmlspecialchars($product['product_price'])."</td>";

                   
                    $stock = (int)($product['total_stock'] ?? 0);
                    if($stock <= 8){
                        echo "<td style='background:#ffdddd; color:#d8000c; font-weight:bold;'>$stock (Low Stock)</td>";
                    } else {
                        echo "<td>$stock</td>";
                    }

                    echo "<td>".htmlspecialchars($product['category_name'])."</td>";
                    
                    if(!empty($product['product_photo'])) {
                        echo "<td><img src='../uploads/shoes/".htmlspecialchars($product['product_photo'])."' width='60' style='border-radius:5px;'></td>";
                    } else {
                        echo "<td>No image</td>";
                    }

echo "<td> 
        <a href='product_edit.php?id=".htmlspecialchars($product['product_id'])."' 
           style='padding:6px 12px; background:#4CAF50; color:#fff; border-radius:4px; text-decoration:none !important; display:inline-block; margin:2px; font-size:14px;'>Edit</a> 
        
        <a href='product_delete.php?id=".htmlspecialchars($product['product_id'])."' 
           onclick=\"return confirm('Are you sure?')\" 
           style='padding:6px 12px; background:#f44336; color:#fff; border-radius:4px; text-decoration:none !important; display:inline-block; margin:2px; font-size:14px;'>Delete</a>
        </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='padding:20px;'>No products found.</td></tr>";
            }

        } catch(PDOException $e) {
            echo "<tr><td colspan='10' style='color:red; padding:20px;'>Error: ".htmlspecialchars($e->getMessage())."</td></tr>";
        }
        ?>
        </tbody>
    </table>
</form>

<script>

const selectAllCheckbox = document.getElementById("select-all");
if(selectAllCheckbox){
    selectAllCheckbox.addEventListener("click", function() {
        const checkboxes = document.querySelectorAll("input[name='delete_ids[]']");
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
}
if(performance.navigation.type === 1){
}
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . "/footer.php"); ?>