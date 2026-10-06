<?php
session_start();
require_once '../database/db.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: admin_maintenance.php");
    exit();
}

// Fetch admin record to display
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        die("Admin not found.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Details | StepStyle</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    <style>
        /* CSS Variables */
        :root {
            --bg-body: #f4f7fe;
            --card-bg: #ffffff;
            --text-dark: #2d3748;
            --text-muted: #718096;
            --primary-blue: #0061ff;
            --primary-hover: #004ecc;
            --border-color: #e2e8f0;
            --danger-red: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-container {
            flex: 1;
            max-width: 1200px;
            margin: 40px auto;
            width: 95%;
            display: flex;
            gap: 30px;
            align-items: stretch;
        }

        /* Left Side Card */
        .profile-side {
            flex: 0 0 400px;
            display: flex;
        }

        .profile-card-static {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .avatar-container {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden; 
            margin: 0 auto 25px;
            padding: 0; 
            border: 3px solid var(--primary-blue);
            background: #fff;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-card-static h2 {
            margin: 15px 0 10px;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .role-badge {
            background: #eef2ff;
            color: var(--primary-blue);
            padding: 8px 20px;
            border-radius: 100px;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 30px;
        }

        .info-list {
            text-align: left;
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
        }
        
        .info-list p { margin: 12px 0; }
        .info-list i { width: 25px; font-size: 20px; color: var(--primary-blue); }

        .delete-btn-wrapper { margin-top: auto; padding-top: 20px; }

        /* Right Side Form */
        .form-side {
            flex: 1;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--bg-body);
            padding-bottom: 15px;
        }

        .form-header h3 { margin: 0; font-size: 24px; font-weight: 800; }

        .back-link { 
            text-decoration: none; 
            color: var(--text-muted); 
            font-weight: 700; 
            font-size: 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-link:hover { color: var(--primary-blue); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 700; font-size: 16px; color: var(--text-dark); }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            transition: 0.2s;
        }

        input[type="file"]::file-selector-button {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        input[type="file"]::file-selector-button:hover { background: var(--primary-hover); }

        .security-section {
            border-top: 2px solid var(--bg-body);
            margin-top: 30px;
            padding-top: 30px;
        }

        .security-section h4 {
            margin: 0 0 20px 0;
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .btn-save {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 16px 45px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 17px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-save:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* Delete Modal Styles */
        .modal { 
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.65); 
            align-items: center; justify-content: center; backdrop-filter: blur(2px); 
        }
        .modal-content { 
            background: white; padding: 35px; border-radius: 12px; 
            width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            text-align: left; 
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .close { font-size: 28px; cursor: pointer; color: #64748b; }
        
        .modal-info-box { 
            background: #f8fafc; padding: 20px; border-radius: 12px; 
            margin: 20px 0; border: 1px solid #edf2f7; 
        }
        .modal-btn-cancel { 
            background: #cbd5e0; color: #2d3748; border: none; padding: 14px; 
            border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 700; flex: 1; 
        }
        .modal-btn-delete { 
            background: var(--danger-red); color: white; border: none; padding: 14px; 
            border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 700; 
            text-decoration: none; text-align: center; flex: 1; 
        }
    </style>
</head>
<body>

    <?php include '../admin_header.php'; ?>

    <div class="main-container">
        
        <aside class="profile-side">
            <div class="profile-card-static">
                <div class="avatar-container">
                    <?php
                        $photo = $admin['profile_photo'];
                        $photoSrc = (!empty($photo) && file_exists("../uploads/" . $photo)) 
                                    ? "../uploads/" . $photo 
                                    : "../uploads/default-avatar.jpg";
                    ?>
                    <img src="<?= htmlspecialchars($photoSrc) ?>" class="user-avatar" alt="Admin Photo">
                </div>
                <h2><?= htmlspecialchars($admin['username']) ?></h2>
                <div><span class="role-badge">System Administrator</span></div>
                
                <div class="info-list">
                    <p><i class="fas fa-calendar-alt"></i> Registered: <span style="color: var(--text-dark); font-weight: 600;"><?= date('d M Y', strtotime($admin['created_at'] ?? 'now')) ?></span></p>
                    <p><i class="fas fa-shield-alt"></i> Status: <span style="color: #10b981; font-weight:800;">Active</span></p>
                </div>

                <div class="delete-btn-wrapper">
                    <button onclick="showDeleteModal()" style="width: 100%; background: #fff; color: var(--danger-red); border: 1px solid #fee2e2; padding: 16px; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 15px; letter-spacing: 0.5px;">
                        <i class="fas fa-trash-alt"></i> DELETE ACCOUNT
                    </button>
                </div>
            </div>
        </aside>

        <main class="form-side">
            <div class="form-header">
                <h3>Update Admin Profile</h3>
                <a href="admin_maintenance.php" class="back-link">
                    <i class="fas fa-chevron-left"></i> Back to List
                </a>
            </div>

            <form action="admin_edit_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['user_email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Telephone Number</label>
                        <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($admin['user_telephone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Change Profile Photo</label>
                        <input type="file" name="photo" class="form-control" style="padding: 10px;">
                    </div>

                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="4" style="resize: none;"><?= htmlspecialchars($admin['user_address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="security-section">
                    <h4>Security Settings</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>New Password (Min.6 characters)</label> 
                            <input type="password" name="new_password" class="form-control" minlength="6"> 
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> SAVE CHANGES
                    </button>
                </div>
            </form>
        </main>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: #ef4444; font-size: 28px; font-weight: 800; margin: 0;">Delete Admin</h2>
                <span class="close" onclick="hideDeleteModal()">&times;</span>
            </div>
            
            <p style="color: #4a5568; font-size: 18px; margin: 15px 0;">Are you sure you want to delete this admin account?</p>
            
            <div class="modal-info-box">
                <p style="margin: 0 0 10px 0; font-size: 16px;">
                    <strong style="color: #2d3748; width: 60px; display: inline-block;">Name:</strong> 
                    <span style="color: #4a5568;"><?= htmlspecialchars($admin['username']) ?></span>
                </p>
                <p style="margin: 0; font-size: 16px;">
                    <strong style="color: #2d3748; width: 60px; display: inline-block;">Email:</strong> 
                    <span style="color: #4a5568;"><?= htmlspecialchars($admin['user_email']) ?></span>
                </p>
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="button" onclick="hideDeleteModal()" class="modal-btn-cancel">Cancel</button>
                <a href="admin_delete_process.php?id=<?= $admin['user_id'] ?>" class="modal-btn-delete">Delete</a>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        function showDeleteModal() { document.getElementById('deleteModal').style.display = 'flex'; }
        function hideDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }
        window.onclick = function(e) { if(e.target.className === 'modal') hideDeleteModal(); }
    </script>
</body>
</html>