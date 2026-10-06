<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database/db.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['g-recaptcha-response'])) {
        $error = "Please complete the captcha.";
    } else {
        // --- reCAPTCHA  ---
        $secret_key = "6LeZsJ0sAAAAAF2urahbpEkwYFMAZzMk4BQc9BMo";
        $recaptcha = $_POST['g-recaptcha-response'];
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha");
        $captcha = json_decode($verify);

        if (!$captcha || !$captcha->success) {
            $error = "Captcha failed. Please try again.";
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            // 查找用户
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "Invalid username or password.";
            } 
            
            elseif ($user['status'] === 'blocked') {
                $error = "Your account has been blocked. Please contact support.";
            } 
            
            elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                $diff = strtotime($user['locked_until']) - time();
                $error = "Account locked. Try again in " . ceil($diff / 60) . " minutes.";
            } 
            
            elseif (password_verify($password, $user['user_password'])) {
               
                $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['username'];

                
                if ($user['role'] === 'admin') {
                    header("Location: ../admin_dash.php");
                } else {
                    header("Location: ../mainpage.php");
                }
                exit();
            } 
           
            else {
                $attempts = $user['login_attempts'] + 1;
                if ($attempts >= 3) {
                    $locked_time = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE user_id = ?");
                    $stmt->execute([$attempts, $locked_time, $user['user_id']]);
                    $error = "Too many attempts. Account locked for 5 minutes.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = ? WHERE user_id = ?");
                    $stmt->execute([$attempts, $user['user_id']]);
                    $error = "Invalid username or password. (Attempt $attempts/3)";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login | StepStyle</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body { 
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; 
            background: #ffffff; 
            margin: 0; 
            color: #000;
        }
        .login-container {
            max-width: 450px;
            margin: 100px auto;
            padding: 0 20px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header h1 {
            font-size: 32px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .error-box {
            background: #000;
            color: #fff;
            padding: 12px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #000;
            box-sizing: border-box;
            font-size: 16px;
            outline: none;
        }
        .input-group input:focus {
            background: #f9f9f9;
        }
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .btn-login {
            width: 100%;
            padding: 18px;
            background: #000;
            color: #fff;
            border: none;
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #333;
        }
        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }
        .login-footer a {
            color: #000;
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/header.php'; ?>

<div style="width:100%; background:#f5f5f5; padding:40px 0; text-align:center; border-bottom:1px solid #eee;">
    <span style="font-size:14px; font-weight:700; color:#666; text-transform:uppercase; letter-spacing:2px;">Welcome Back</span>
</div>

<div class="login-container">
    <div class="login-header">
        <h1>Login</h1>
    </div>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <input type="text" name="username" placeholder="USERNAME" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="PASSWORD" required>
        </div>

        <div class="captcha-wrapper">
            <div class="g-recaptcha" data-sitekey="6LeZsJ0sAAAAADhjdtIcRGGmxYybX7XAz9s6cRPp"></div>
        </div>

        <label style="display:flex; align-items:center; gap:10px; font-size:13px; margin-bottom:20px; cursor:pointer;">
            <input type="checkbox" name="remember_me" style="width:18px; height:18px; accent-color:#000;">
            KEEP ME LOGGED IN
        </label>

        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="login-footer">
        <p><a href="forgot_password.php">Forgot your password?</a></p>
        <p style="margin-top:15px; color:#666;">
            New to StepStyle? <a href="register.php">Join us now</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>>

</body>
</html>