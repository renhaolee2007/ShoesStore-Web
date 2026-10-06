<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$user_id = $_SESSION['user_id'] ?? 1;

$username = $_POST['username'] ?? '';
$user_email = $_POST['user_email'] ?? '';
$user_telephone = $_POST['user_telephone'] ?? '';
$user_address = $_POST['user_address'] ?? '';

/* get current profile photo */
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$profile_photo = $user['profile_photo'] ?? '';
$uploadDir = __DIR__ . '/../uploads/';

/* if user selected a new image */
if (isset($_FILES['profile_pic']) && !empty($_FILES['profile_pic']['name'])) {
    $fileName = time() . "_" . basename($_FILES['profile_pic']['name']);

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $targetPath = $uploadDir . $fileName; // ✅ 移到这里

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
        $profile_photo = $fileName;
    }
}

/* update profile */
$stmt = $pdo->prepare("
    UPDATE users SET 
        username = ?,
        user_email = ?,
        user_telephone = ?,
        user_address = ?,
        profile_photo = ?
    WHERE user_id = ?
");

$stmt->execute([
    $username,
    $user_email,
    $user_telephone,
    $user_address,
    $profile_photo,
    $user_id
]);

header("Location: ../../profile.php?msg=profile_success");
exit();
?>