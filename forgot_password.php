<?php
session_start();
require_once 'database/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['email'] ?? '');

    if (empty($input)) {
        $error = "Please enter your username.";
    } else {
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "No account found with this username.";
            } else {
                $success = "A password reset link has been sent to your email address.";
            }
        } catch (PDOException $e) {
            $error = "System error: " . htmlspecialchars($e->getMessage());
            error_log("Forgot Password Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | StepStyle</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .forgot-container {
            max-width: 420px;
            margin: 40px auto;
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        .forgot-container h1 {
            margin: 0 0 25px 0;
            font-size: 26px;
            color: #333;
        }
        .error-box {
            background: #ffe5e5;
            color: #d32f2f;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 100%;
            padding: 14px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #000;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #222;
        }
        .back-link {
            margin-top: 25px;
            display: inline-block;
            color: #555;
            text-decoration: none;
        }
        .back-link:hover {
            color: #000;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

    <div class="forgot-container">
        <h1>Forgot Password</h1>

        <?php if ($error): ?>
            <div class="error-box"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" 
                   name="email" 
                   placeholder="USERNAME" 
                   required 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <button type="submit" class="btn-login">SEND RESET LINK</button>
        </form>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>

<?php include 'footer.php'; ?>

</body>
</html>