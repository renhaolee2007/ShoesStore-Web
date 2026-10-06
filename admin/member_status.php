<?php
session_start();
require_once '../database/db.php';

// Validate the ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: member_maintenance.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    // Prepare SQL to toggle status (active & blocked)
    // Only affects users with 'member' role
    $sql = "UPDATE users 
            SET status = IF(status = 'active', 'blocked', 'active') 
            WHERE user_id = ? AND role = 'member'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        // Success: Redirect with success message
        header("Location: member_maintenance.php?msg=success");
    } else {
        // No change: User ID not found or role not 'member'
        header("Location: member_maintenance.php?msg=not_found");
    }
    exit();
    
} catch (PDOException $e) {
    // Error: Redirect with error message
    header("Location: member_maintenance.php?msg=error");
    exit();
}
?>