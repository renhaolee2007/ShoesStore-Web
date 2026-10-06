<?php
session_start();
require_once '../database/db.php'; 

// GET PARAMETERS (Filtering & Sorting)
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'username'; 
$order = $_GET['order'] ?? 'ASC';    

// Sorting Whitelist
$allowed_columns = ['user_id', 'username', 'user_email'];
$sort_by = in_array($sort_by, $allowed_columns) ? $sort_by : 'username';
$order = (strtoupper($order) === 'DESC') ? 'DESC' : 'ASC';

try {
    // Build Filtered Query
    $where_sql = "role = 'admin'";
    $params = [];
    if (!empty($search)) {
        $where_sql .= " AND (username LIKE ? OR user_email LIKE ?)";
        $params[] = "%$search%"; 
        $params[] = "%$search%";
    }

    // Calculate Dynamic Total 
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_sql");
    $count_stmt->execute($params);
    $total_display_count = $count_stmt->fetchColumn(); 

    // Fetch Admin Data 
    $sql = "SELECT *, 
            LPAD(ROW_NUMBER() OVER (ORDER BY user_id ASC), 3, '0') as display_id
            FROM users 
            WHERE $where_sql 
            ORDER BY $sort_by $order";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $admins = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper to maintain URL state
function buildQuery($overrides) {
    return http_build_query(array_merge($_GET, $overrides));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Maintenance</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    
<style>
    :root {
        --primary-blue: #3b82f6;
        --bg-body: #f8fafc;
        --text-main: #1e2937;
        --text-secondary: #64748b;
        --border: #e2e8f0;
        --card-bg: #ffffff;
        --success-green: #10b981;
        --danger-red: #ef4444;
        --hover-blue: #2563eb;
        --action-black: #1f2937;
    }

    .main-wrapper { 
        flex: 1; 
        padding: 32px; 
        max-width: 1420px; 
        margin: 0 auto; 
        width: 95%; 
    }
    
    #success-msg { 
        background-color: #ecfdf5; 
        color: var(--success-green);            
        padding: 16px 22px;
        border-radius: 10px; 
        border: 1px solid #a7f3d0;
        margin-bottom: 24px;       
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 10px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.12);
    }

    .toolbar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin: 28px 0; 
        flex-wrap: wrap; 
        gap: 18px; 
    }

    .search-area { 
        background: var(--card-bg); 
        border: 1px solid var(--border); 
        padding: 7px 18px; 
        border-radius: 10px; 
        width: 460px; 
        display: flex; 
        align-items: center; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    
    .search-area input { 
        border: none; 
        outline: none; 
        margin-left: 12px; 
        width: 100%; 
        font-size: 16.5px; 
        padding: 10px 0; 
        color: var(--text-main);
    }
    
    .search-btn { 
        background: var(--primary-blue); 
        color: white; 
        border: none; 
        padding: 10px 22px; 
        border-radius: 8px; 
        cursor: pointer; 
        font-size: 15px; 
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .search-btn:hover { background: var(--hover-blue); }

    .clear-btn { 
        background: #f1f5f9; 
        color: var(--text-secondary); 
        border: none; 
        padding: 10px 22px; 
        border-radius: 8px; 
        cursor: pointer; 
        font-size: 15px; 
        margin-left: 8px; 
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .clear-btn:hover { background: #e2e8f0; }

    .btn-add { 
        background: var(--primary-blue); 
        color: white; 
        padding: 13px 24px; 
        font-size: 15.5px; 
        border-radius: 8px; 
        text-decoration: none; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.20);
        transition: all 0.25s ease;
    }
    .btn-add:hover { 
        background: var(--hover-blue); 
        transform: translateY(-2px);
    }

    .card { 
        background: var(--card-bg); 
        border-radius: 14px; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.07); 
        overflow-x: auto;
    }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        min-width: 1200px; 
    }
    
    th { 
        background: #f8fafc; 
        padding: 18px 16px; 
        text-align: left; 
        color: var(--text-secondary); 
        font-size: 13.5px; 
        text-transform: uppercase; 
        font-weight: 600;
        letter-spacing: 0.4px;
    }
    
    th a { 
        color: inherit; 
        text-decoration: none; 
        display: flex; 
        align-items: center; 
        gap: 6px; 
    }
    
    td { 
        padding: 17px 16px; 
        border-top: 1px solid var(--border); 
        vertical-align: middle; 
        color: var(--text-main); 
        font-size: 16.5px; 
    }

    .user-avatar { 
        width: 42px; 
        height: 42px; 
        border-radius: 50%; 
        margin-right: 14px; 
        object-fit: cover; 
        border: 2px solid #fff; 
        box-shadow: 0 3px 10px rgba(0,0,0,0.08); 
    }

    .modal { 
        display: none; 
        position: fixed; 
        z-index: 2000; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(15, 23, 42, 0.72); 
        align-items: center; 
        justify-content: center; 
        backdrop-filter: blur(5px); 
    }
    
    .modal-content { 
        background: white; 
        padding: 38px; 
        border-radius: 16px; 
        width: 92%; 
        max-width: 520px; 
        box-shadow: 0 20px 70px rgba(0,0,0,0.25); 
    }
    
    .modal-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 24px; 
    }
    
    .close { 
        font-size: 30px; 
        cursor: pointer; 
        color: #64748b; 
    }
    .close:hover { color: #334155; }
    
    .form-group { margin-bottom: 18px; }
    
    .form-group label { 
        display: block; 
        margin-bottom: 9px; 
        font-weight: 600; 
        color: #334155; 
        font-size: 15px; 
    }
    
    .form-group input, 
    .form-group textarea { 
        width: 100%; 
        padding: 13px 16px; 
        border: 1px solid var(--border); 
        border-radius: 10px; 
        box-sizing: border-box; 
        font-size: 16px; 
        color: var(--text-main); 
        outline: none; 
        transition: all 0.2s ease;
    }
    
    .form-group input:focus, 
    .form-group textarea:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .modal-btn-cancel { 
        background: #e2e8f0; 
        color: #1e2937; 
        border: none; 
        padding: 13px 32px; 
        border-radius: 10px; 
        cursor: pointer; 
        font-size: 16px; 
        font-weight: 600; 
        margin-right: 14px; 
        transition: all 0.2s ease;
    }
    .modal-btn-cancel:hover { background: #cbd5e1; }

    .modal-btn-create { 
        background: var(--primary-blue); 
        color: white; 
        border: none; 
        padding: 13px 32px; 
        border-radius: 10px; 
        cursor: pointer; 
        font-size: 16px; 
        font-weight: 600; 
        transition: all 0.2s ease;
    }
    .modal-btn-create:hover { background: var(--hover-blue); }

    #admin_photo_input::file-selector-button { 
        background: var(--primary-blue);
        color: white; 
        border: none; 
        padding: 10px 18px; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 600; 
        margin-right: 12px; 
        transition: all 0.2s ease;
    }
    #admin_photo_input::file-selector-button:hover {
        background: var(--hover-blue);
    }
    </style>
</head>
<body>

    <?php include '../admin_header.php'; ?>

    <div class="main-wrapper">
        <?php if (isset($_SESSION['success'])): ?>
            <div id="success-msg">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1 onclick="window.location.href='admin_maintenance.php'">Admin Maintenance</h1>
        </div>

        <div class="toolbar">
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="search-area">
                    <form action="admin_maintenance.php" method="GET" style="display: flex; width: 100%; align-items: center;">
                        <i class="fas fa-search" style="color: #a0aec0;"></i>
                        <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="admin_maintenance.php" class="clear-btn">Clear</a>
                        <?php endif; ?>
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                    </form>
                </div>
                <a href="#" onclick="openAddModal()" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Admin
                </a>
            </div>
            
            <div style="color: #2d3748; font-weight: 700; font-size: 18px;">
                Total Admins: <?= $total_display_count ?>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No.</th>
                        <th>
                            <a href="?<?= buildQuery(['sort' => 'username', 'order' => ($sort_by == 'username' && $order == 'ASC') ? 'DESC' : 'ASC']) ?>">
                                Name <i class="fas fa-sort"></i>
                            </a>
                        </th>
                        <th>Email</th>
                        <th>Telephone</th> 
                        <th>Address</th> 
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($admins)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 50px; color: #a0aec0;">No admins found.</td></tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td style="font-weight: 600; color: #718096;">
                            <?= $admin['display_id'] ?>
                        </td>
                        <td>
                            <a href="admin_detail.php?id=<?= $admin['user_id'] ?>" style="text-decoration: none; color: inherit;">
                                <div style="display: flex; align-items: center;">
                                    <?php $photo = !empty($admin['profile_photo']) ? $admin['profile_photo'] : 'default-avatar.jpg'; ?>
                                    <img src="../uploads/<?= htmlspecialchars($photo) ?>" class="user-avatar">
                                    <strong><?= htmlspecialchars($admin['username']) ?></strong>
                                </div>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($admin['user_email']) ?></td>
                        <td><?= htmlspecialchars($admin['user_telephone'] ?? 'N/A') ?></td>
                        <td><?= mb_strimwidth(htmlspecialchars($admin['user_address'] ?? 'N/A'), 0, 40, "...") ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 style="font-weight: 800; color: var(--text-main); margin: 0;">Add New Admin</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            
            <form id="addAdminForm" action="admin_add_process.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Username <span style="color:red">*</span></label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Email Address <span style="color:red">*</span></label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Telephone</label>
                    <input type="text" name="telephone">
                </div>

                <div class="form-group">
                    <label>Password (Min.6 characters)<span style="color:red">*</span></label>
                    <input type="password" name="password" id="admin_password" required>
                    <div id="password_requirement_msg" style="color: #ef4444; font-size: 14px; margin-top: 5px; display: none; font-weight: 500;">
                        <i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters long!
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password <span style="color:red">*</span></label>
                    <input type="password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <label>Profile Photo</label>
                    <div style="position: relative;">
                        <input type="file" name="profile_photo" id="admin_photo_input" accept="image/*" 
                               style="font-family: 'Inter', sans-serif; font-size: 14px;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2" style="font-family: 'Inter', sans-serif; resize: none;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <button type="button" onclick="closeAddModal()" class="modal-btn-cancel" style="flex:1">Cancel</button>
                    <button type="submit" class="modal-btn-create" style="flex:1">Create Admin</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        };

        window.onload = function() {
            const msg = document.getElementById('success-msg');
            if (msg) {
                setTimeout(() => {
                    msg.style.transition = "opacity 0.5s";
                    msg.style.opacity = "0";
                    setTimeout(() => { msg.style.display = "none"; }, 500);
                }, 3000);
            }
        };

        const passwordInput = document.getElementById('admin_password');
        const errorMsg = document.getElementById('password_requirement_msg');
        const addAdminForm = document.getElementById('addAdminForm');
        
        passwordInput.addEventListener('input', function() {
            if (this.value.length >= 6) {
                errorMsg.style.display = 'none';
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            }
        });

        addAdminForm.onsubmit = function(e) {
            const password = passwordInput.value;
            const confirmPassword = document.getElementsByName('confirm_password')[0].value;

            if (password.length < 6) {
                e.preventDefault(); 
                errorMsg.style.display = 'block';
                passwordInput.style.borderColor = '#ef4444';
                passwordInput.style.backgroundColor = '#fff5f5';
                passwordInput.focus();
                return false;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match! Please check again.');
                return false;
            }

            return true;
        };
    </script>
</body>
</html>