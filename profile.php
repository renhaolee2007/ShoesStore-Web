<?php
session_start();
require_once __DIR__ . '/database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: member/member_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// role
$_SESSION['role'] = $user['role'] ?? 'member';

// pohoto
$photoSrc = 'image/ProfileIcon.jpg';

if (!empty($user['profile_photo'])) {
    if (file_exists(__DIR__ . '/uploads/' . $user['profile_photo'])) {
        $photoSrc = '/uploads/' . $user['profile_photo'];
    } elseif (file_exists(__DIR__ . '/image/' . $user['profile_photo'])) {
        $photoSrc = 'image/' . $user['profile_photo'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/profile.css" >
    <title>My Profile</title>
    <?php
    if ($_SESSION['role'] === 'admin') {
        echo '<link rel="stylesheet" href="css/admin_dash.css">';
    } else {
        echo '<link rel="stylesheet" href="css/style.css">';
    }
    ?>
</head>
<body class="profile-page">

<?php
if ($_SESSION['role'] === 'admin') {
    include __DIR__ . '/admin_header.php';
} else {
    include __DIR__ . '/header.php';
}
?>

<main class="profile-main">
    <div class="profile-container">
        <div class="profile-card">

            <div class="image-upload">
                <img id="preview" src="<?= htmlspecialchars($photoSrc); ?>" alt="Profile Image">
            </div>

            <div class="profile-line">
                <strong>Name:</strong> <?= htmlspecialchars($user['username']); ?>
            </div>

            <div class="profile-line">
                <strong>Email Address:</strong> <?= htmlspecialchars($user['user_email']); ?>
            </div>

            <div class="profile-line">
                <strong>Phone Number:</strong> <?= htmlspecialchars($user['user_telephone']); ?>
            </div>

            <div class="profile-line">
                <strong>Address:</strong> <?= htmlspecialchars($user['user_address']); ?>
            </div>

            <a href="profile/edit_profile.php" class="update-btn">Edit Profile</a>
            <a href="profile/change_password.php" class="update-btn">Update Password</a>
            <?php
            $homeLink = ($_SESSION['role'] === 'admin') 
                ? '/admin_dash.php' 
                : '/mainpage.php';
            ?>

            <a href="<?= $homeLink ?>" class="back-btn">Back to Home</a>

        </div>
    </div>
</main>

<?php include "footer.php"; ?>

<?php if (isset($_GET['msg'])): ?>
<script>
<?php if ($_GET['msg'] == "profile_success"): ?>
    alert("Profile updated successfully!");
<?php elseif ($_GET['msg'] == "password_success"): ?>
    alert("Password updated successfully!");
<?php elseif ($_GET['msg'] == "password_error"): ?>
    alert("Wrong current password!");
<?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>