<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';

/* temporary testing */
$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$_SESSION['role'] = $user['role'] ?? 'member';
/* profile photo path */
$photoSrc = '../../image/ProfileIcon.jpg';

if (!empty($user['profile_photo'])) {
    if (file_exists(__DIR__ . '/../uploads/' . $user['profile_photo'])) {
        $photoSrc = '/../uploads/' . $user['profile_photo'];
    } elseif (file_exists(__DIR__ . '/../image/' . $user['profile_photo'])) {
        $photoSrc = '/../image/' . $user['profile_photo'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="profile-page">

     

<?php
if ($_SESSION['role'] === 'admin') {
    include __DIR__ . '/../admin_header.php';
} else {
    include __DIR__ . '/../header.php';
}
?>

<div class="profile-container">

    <form class="profile-card" action="../profile/update_profile.php" method="POST" enctype="multipart/form-data">

        <div class="image-upload">
            <label for="fileInput" class="image-label">
                <img id="preview" src="<?= htmlspecialchars($photoSrc); ?>" alt="Profile Image">
                <span class="edit-photo-text">Click to change photo</span>
            </label>
            <input type="file" name="profile_pic" id="fileInput" accept="image/*" hidden>
        </div>

        <div class="input-box">
            <label>Name</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? ''); ?>" required>
        </div>

        <div class="input-box">
            <label>Email Address</label>
            <input type="email" name="user_email" value="<?= htmlspecialchars($user['user_email'] ?? ''); ?>" required>
        </div>

        <div class="input-box">
            <label>Phone Number</label>
            <input type="text" name="user_telephone" value="<?= htmlspecialchars($user['user_telephone'] ?? ''); ?>" required>
        </div>

        <div class="input-box">
            <label>Address</label>
            <input type="text" name="user_address" value="<?= htmlspecialchars($user['user_address'] ?? ''); ?>" required>
        </div>

        <button type="submit" class="update-btn">Save Changes</button>
        <a href="../../profile.php" class="back-btn">Back to Profile</a>
    </form>

</div>

<?php include "../footer.php"; ?>

<script src="../../js/profile.js"></script>

</body>
</html>