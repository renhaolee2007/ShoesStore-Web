<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/database/db.php");

try {
    $ids_to_delete = [];

    // Collect all IDs to be deleted
    if (isset($_GET['id'])) {
        $ids_to_delete[] = $_GET['id'];
    } elseif (isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
        $ids_to_delete = $_POST['delete_ids'];
    }

    if (count($ids_to_delete) > 0) {
        $pdo->beginTransaction();

        // Retrieve image paths first(for deleting files from disk)
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
        $stmt_img = $pdo->prepare("SELECT product_photo FROM product WHERE product_id IN ($placeholders)");
        $stmt_img->execute($ids_to_delete);
        $photos = $stmt_img->fetchAll(PDO::FETCH_COLUMN);

        // Delete database records
        // Due to ON DELETE CASCADE,deleting a product will automatically remove its product_size.
        $stmt_del = $pdo->prepare("UPDATE product set `status` = 0  WHERE product_id IN ($placeholders)");
        $stmt_del->execute($ids_to_delete);

        $pdo->commit();

        // 4. After DB deletion succeeds,clean up physical files
        foreach ($photos as $photo) {
            if ($photo) {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shoes/' . $photo;
                if (file_exists($file_path)) {
                    @unlink($file_path); // Use '@' to suppress errors if the file doesn't exist.
                }
            }
        }

        $_SESSION['success_msg'] = "Product(s) and their images deleted successfully!";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error_msg'] = "Delete failed: " . $e->getMessage();
}

header("Location: product_list.php");
exit;