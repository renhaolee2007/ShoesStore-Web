<?php
session_start();
require_once '../database/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); 
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = $_POST['address'];

    try {
        if (strlen($password) < 6) {
            echo "<script>alert('Password must be at least 6 characters.'); window.history.back();</script>";
            exit();
        }

        if ($password !== $confirm_password) {
            echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
            exit();
        }

        // Check if username already exists in database
        $check_sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$username]);
        if ($check_stmt->fetchColumn() > 0) {
            echo "<script>alert('Error: The username \'$username\' is already taken. Please use a different name.'); window.history.back();</script>";
            exit();
        }

        // Profile photo upload
        $photo_name = 'default-avatar.jpg'; 
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $target_dir = "../../photos/"; 
            $file_ext = pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
            $photo_name = "admin_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $photo_name;
            
            move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file);
        }

        // Hash the password 
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert new admin data into database
        $sql = "INSERT INTO users (username, user_email, user_telephone, user_password, user_address, profile_photo, role) 
                VALUES (?, ?, ?, ?, ?, ?, 'admin')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $email, $telephone, $hashed_password, $address, $photo_name]);

        // Success message
        $_SESSION['success'] = "New admin created successfully!";
        header("Location: admin_maintenance.php");
        exit();

    } catch (PDOException $e) {
        die("Error adding admin: " . $e->getMessage());
    }
} else {
    header("Location: admin_maintenance.php");
}
?>