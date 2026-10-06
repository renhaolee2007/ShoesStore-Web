<?php
session_start();
require_once __DIR__ . '/../database/db.php';

/* temporary testing */
$user_id = $_SESSION['user_id'] ?? 1;
$_SESSION['role'] = $user['role'] ?? 'member';
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';

/* get current password */
$stmt = $pdo->prepare("SELECT user_password FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

/* verify password */
if ($user && password_verify($current, $user['user_password'])) {

    $hashedPassword = password_hash($new, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
    $stmt->execute([$hashedPassword, $user_id]);

    header("Location: ../../profile.php?msg=password_success");
    exit();

} else {

    header("Location: ../../profile.php?msg=password_error");
    exit();
}
?>