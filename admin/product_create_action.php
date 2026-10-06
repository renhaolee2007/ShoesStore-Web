<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . "/database/db.php");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    try {
        // Collect main table data
        $product_name    = trim($_POST['product_name'] ?? '');
        $product_descrip = trim($_POST['product_descrip'] ?? '');
        $product_price   = floatval($_POST['product_price'] ?? 0);
        $category_id     = intval($_POST['category_id'] ?? 0); 
        $size_stocks     = $_POST['size_stock'] ?? []; 

        // Upload images
        $photo_name = null;
        if(isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] == 0){
            $ext = strtolower(pathinfo($_FILES['product_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg','jpeg','png','gif'];
            if(in_array($ext, $allowed_ext)){
                $photo_name = 'product_'.time().'_'.rand(1000,9999).'.'.$ext; 
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shoes/';
                if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                move_uploaded_file($_FILES['product_photo']['tmp_name'], $upload_dir.$photo_name);
            } else {
                throw new Exception("Invalid image format. Only JPG, PNG, GIF are allowed.");
            }
        }

        // Calculate new product_id
        $stmt = $pdo->query("SELECT MAX(product_id) AS max_id FROM product");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

        // Start database transaction
        $pdo->beginTransaction();

        $sql = "INSERT INTO product 
                (product_id, product_name, product_descrip, product_price, category_id, product_photo)
                VALUES 
                (:product_id, :product_name, :product_descrip, :product_price, :category_id, :product_photo)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':product_id'      => $new_id,
            ':product_name'    => $product_name,
            ':product_descrip' => $product_descrip,
            ':product_price'   => $product_price,
            ':category_id'     => $category_id,
            ':product_photo'   => $photo_name
        ]);

        // Insert into product_size table
        $stmt_stock = $pdo->prepare("INSERT INTO product_size (product_id, `size`, stock) VALUES (?, ?, ?)");
        
        foreach($size_stocks as $size => $qty){
            // Only insert if a number is provided，Skip if it's an empty string.
            if($qty !== ''){
                $stmt_stock->execute([$new_id, $size, intval($qty)]);
            }
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success_msg'] = "Product '$product_name' created successfully!";
        header("Location: product_list.php");
        exit;

    } catch(Exception $e){
        // Roll back all operations if an error occurs
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        // if($photo_name) @unlink($upload_dir.$photo_name);

        $_SESSION['error_msg'] = $e->getMessage();
        header("Location: product_list.php");
        exit;
    }

} else {
    $_SESSION['error_msg'] = "Invalid request method.";
    header("Location: product_list.php");
    exit;
}