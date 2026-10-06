<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>

<?php
if ($_SESSION['role'] === 'admin') {
    include __DIR__ . '/../admin_header.php';
} else {
    include __DIR__ . '/../header.php';
}
?>

<body class="profile-page">

<div class="profile-container">

    <form class="password-card" action="update_password.php" method="POST">

        <h3>Change Password</h3>

        <div class="input-box">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>

        <div class="input-box">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>

        <button type="submit" class="update-btn">Update Password</button>
        <a href="../../profile.php" class="back-btn">Back to Profile</a>

    </form>

</div>

<?php include "../footer.php"; ?>

</body>
</html>