<?php
session_start();
require_once '../database/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Delete the record (ensure role is admin to prevent accidental member deletion)
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
        $result = $stmt->execute([$id]);

        if ($result) {
            // Success, redirect with message
            header("Location: admin_maintenance.php?msg=deleted");
            exit();
        } else {
            die("Failed to delete admin.");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    // Redirect if no ID is found
    header("Location: admin_maintenance.php");
    exit();
}
?>