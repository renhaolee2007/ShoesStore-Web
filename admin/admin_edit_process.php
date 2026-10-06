<?php
session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $address = $_POST['address'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Fetch current admin data (to handle old photo)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_admin = $stmt->fetch();

        if (!$current_admin) {
            die("User not found.");
        }

        // Image upload
        $photo_name = $current_admin['profile_photo']; // Use old photo as default
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $target_dir = "../uploads/";
            $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            
            // Rename file with timestamp to avoid name conflicts
            $photo_name = "admin_" . $user_id . "_" . time() . "." . $file_ext; 
            $target_file = $target_dir . $photo_name;

            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                // Optional: Delete old photo from folder here if needed
            }
        }

        // Construct the base SQL update statement
        $sql = "UPDATE users SET 
                username = ?, 
                user_email = ?, 
                user_telephone = ?, 
                user_address = ?, 
                profile_photo = ?";
        
        $params = [$username, $email, $telephone, $address, $photo_name];

        // Handle password update if a new password is provided
        if (!empty($new_password)) {
            // Validate password length (Min 6 chars)
            if (strlen($new_password) < 6) {
                echo "<script>alert('New password must be at least 6 characters!'); window.history.back();</script>";
                exit();
            }
            
            // Check if passwords match
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $sql .= ", user_password = ?";
                $params[] = $hashed_password;
            } else {
                echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
                exit();
            }
        }

        $sql .= " WHERE user_id = ?";
        $params[] = $user_id;

        // Execute final update
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Success redirect back to details page
        echo "<script>
                alert('Profile updated successfully!');
                window.location.href = 'admin_detail.php?id=$user_id';
              </script>";

    } catch (PDOException $e) {
        die("Error updating profile: " . $e->getMessage());
    }
} else {
    header("Location: admin_maintenance.php");
}
?>