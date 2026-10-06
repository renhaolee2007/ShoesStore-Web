<?php
session_start();
require_once '../database/db.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Member ID");
}

$member_id = (int)$_GET['id'];

// Get member data
try {
    $sql = "SELECT * FROM users WHERE user_id = ? AND role = 'member'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        die("Member not found!");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Detail - <?= htmlspecialchars($member['username']) ?></title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    <style>
        :root {
            --primary-blue: #0061ff;
            --bg-body: #f4f7fe;
            --text-main: #2d3748;
            --border: #e2e8f0;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-body); 
            font-size: 20px; 
            margin: 0;
            color: var(--text-main);
        }

        .main-wrapper { 
            padding: 30px 40px 180px 40px;
            max-width: 720px;         
            margin: 0 auto;             
            width: 95%; 
        }

        .page-title {
            font-size: 35px;
            color: var(--text-main);
            margin-bottom: 25px;
            font-weight: 700;
            text-align: center;         
        }

        .avatar-container {
            text-align: center;         
            margin-bottom: 30px;
        }

        .big-avatar {
            width: 180px;             
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: #eee; /* 如果图片加载失败的底色 */
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }
        
        .label {
            width: 160px;           
            font-weight: 600;
            color: #1e2937;
            flex-shrink: 0;
        }
        
        .input-box {
            flex: 1;
            max-width: 450px;          
            padding: 12px 15px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 18px;
            min-height: 35px;
            display: flex;
            align-items: center;
        }

        .btn-secondary { 
            display: inline-block; /* 确保 padding 有效 */
            background: var(--primary-blue); 
            color: white; 
            padding: 13px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #004ecc;
        }
    </style>
</head>
<body>

    <?php include '../admin_header.php'; ?>

    <div class="main-wrapper">
        <h1 class="page-title">Member Detail</h1>

        <div class="avatar-container">
            <?php 
            // Profile photo path logic
            $photo_file = !empty($member['profile_photo']) ? $member['profile_photo'] : 'default-avatar.jpg';
            ?>
            <img src="../../uploads/<?= htmlspecialchars($photo_file) ?>" 
                 class="big-avatar" alt="Profile Photo">
        </div>

        <div class="form-row">
            <div class="label">Member ID</div>
            <div class="input-box">
                <?= str_pad($member['user_id'], 3, '0', STR_PAD_LEFT) ?>
            </div>
        </div>

        <div class="form-row">
            <div class="label">Name</div>
            <div class="input-box">
                <?= htmlspecialchars($member['username']) ?>
            </div>
        </div>

        <div class="form-row">
            <div class="label">Email</div>
            <div class="input-box">
                <?= htmlspecialchars($member['user_email']) ?>
            </div>
        </div>

        <div class="form-row">
            <div class="label">Telephone</div>
            <div class="input-box">
                <?= htmlspecialchars($member['user_telephone'] ?? 'N/A') ?>
            </div>
        </div>

        <div class="form-row">
            <div class="label">Address</div>
            <div class="input-box" style="min-height: 70px; align-items: flex-start; padding-top: 12px;">
                <?= nl2br(htmlspecialchars($member['user_address'] ?? 'N/A')) ?>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <a href="../admin/member_maintenance.php" class="btn-secondary">Back to Member Maintenance</a>
        </div>
    </div>

    <?php include '../footer.php'; ?>

</body>
</html>