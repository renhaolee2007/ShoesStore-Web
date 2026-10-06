<?php
session_start();
require_once 'database/db.php'; 

$error = [];
$success = '';

// Initialize variables as empty strings
$username = '';
$email = '';
$telephone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and trim inputs
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $telephone  = trim($_POST['telephone'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    // Simple validation
    if (empty($username))   $error['username'] = "Username is required";
    if (empty($email))      $error['email'] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error['email'] = "Invalid email format";
    if (empty($password))   $error['password'] = "Password is required";
    if (strlen($password) < 6) $error['password'] = "Password must be at least 6 characters";
    if ($password !== $confirm) $error['confirm_password'] = "Passwords do not match";
    if (empty($telephone))  $error['telephone'] = "Telephone is required";
    if (empty($address))    $error['address'] = "Address is required";

    // Check duplicate email
    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error['email'] = "Email already exists";
        }
    }

    // Photo Upload
    $photo_name = 'default-avatar.jpg';
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $size = $_FILES['photo']['size'];

        if (!in_array($ext, $allowed)) {
            $error['photo'] = "Only JPG, JPEG and PNG files are allowed";
        } elseif ($size > 5 * 1024 * 1024) {
            $error['photo'] = "Photo size cannot exceed 5MB";
        } else {
            // Give unique name
            $photo_name = "member_" . time() . "." . $ext;
            $upload_path = "../uploads" . $photo_name;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $error['photo'] = "Failed to upload photo";
            }
        }
    }

    // Insert data
    if (empty($error)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, user_email, user_password, user_telephone, user_address, profile_photo, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'member', 'active', NOW())
        ");

        $result = $stmt->execute([$username, $email, $hashed, $telephone, $address, $photo_name]);

        if ($result) {
            $success = "Registration successful! Redirecting to login...";
        } else {
            $error['general'] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<div style="max-width:600px; margin:80px auto; background:white; padding:60px; border-radius:12px; box-shadow:0 4px 25px rgba(0,0,0,0.08);">

    <h1 style="text-align:center; margin-bottom:30px;">Create Account</h1>

    <?php if ($success): ?>
        <p style="color:green; text-align:center; font-weight:bold;"><?= $success ?></p>
    <?php endif; ?>

    <?php if (!empty($error['general'])): ?>
        <p style="color:red; text-align:center;"><?= $error['general'] ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($username) ?>" 
               autocomplete="off" style="width:100%; padding:12px; margin-bottom:5px;">
        <small style="color:red;"><?= $error['username'] ?? '' ?></small>

        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" 
               style="width:100%; padding:12px; margin:15px 0 5px;">
        <small style="color:red;"><?= $error['email'] ?? '' ?></small>

        <input type="text" name="telephone" placeholder="Telephone" value="<?= htmlspecialchars($telephone) ?>" 
               style="width:100%; padding:12px; margin:15px 0 5px;">
        <small style="color:red;"><?= $error['telephone'] ?? '' ?></small>

        <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($address) ?>" 
               style="width:100%; padding:12px; margin:15px 0 5px;">
        <small style="color:red;"><?= $error['address'] ?? '' ?></small>

        <input type="password" name="password" placeholder="Password" 
               style="width:100%; padding:12px; margin:15px 0 5px;">
        <small style="color:red;"><?= $error['password'] ?? '' ?></small>

        <input type="password" name="confirm_password" placeholder="Confirm Password" 
               style="width:100%; padding:12px; margin:15px 0 5px;">
        <small style="color:red;"><?= $error['confirm_password'] ?? '' ?></small>

        <div style="margin:25px 0 15px;">
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:16px;">
                Profile Photo (Optional)
            </label>
            
            <div style="display:flex; align-items:center; gap:12px; border:1px solid #ccc; background:#f8f8f8; padding:8px 12px; border-radius:6px;">
                <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" style="display:none;">
                
                <label for="photo" style="background:#0061ff; color:white; padding:8px 18px; border-radius:4px; cursor:pointer; font-weight:600; font-size:14px; white-space:nowrap;">
                    Choose File
                </label>

                <span id="file-name" style="margin-left:12px; color:#666; font-size:14px; flex:1;">
                    No file chosen
                </span>
            </div>

            <small style="color:#718096; font-size:14px; margin-top:6px; display:block;">
                JPG or PNG only, max 5MB
            </small>
        </div>

        <button type="submit" style="width:100%; padding:14px; background:black; color:white; border:none; font-size:16px; margin-top:20px; cursor:pointer;">
            CREATE ACCOUNT
        </button>

    </form>

    <div style="text-align:center; margin-top:25px;">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

</div>

<?php include 'footer.php'; ?>

<?php if ($success): ?>
<script>
    setTimeout(function() {
        window.location.href = "login.php";
    }, 1500);
</script>
<?php endif; ?>

<script>
    document.getElementById('photo').addEventListener('change', function() {
        if (this.files[0]) {
            document.getElementById('file-name').textContent = this.files[0].name;
        } else {
            document.getElementById('file-name').textContent = 'No file chosen';
        }
    });
</script>
</body>
</html>