<?php
session_start();
require_once '../database/db.php'; 

// GET PARAMETERS (Filtering, Sorting, Paging)
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? ''; 
$sort_by = $_GET['sort'] ?? 'username'; 
$order = $_GET['order'] ?? 'ASC';       
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$limit = 10; 
$offset = ($page - 1) * $limit;

// Sorting Whitelist
$allowed_columns = ['user_id', 'username', 'user_email'];
$sort_by = in_array($sort_by, $allowed_columns) ? $sort_by : 'username';
$order = (strtoupper($order) === 'DESC') ? 'DESC' : 'ASC';

try {
    $where_clauses = ["role = 'member'"];
    $params = [];
    if (!empty($search)) {
        $where_clauses[] = "(username LIKE ? OR user_email LIKE ?)";
        $params[] = "%$search%"; 
        $params[] = "%$search%";
    }
    if (!empty($status_filter)) {
        $where_clauses[] = "status = ?";
        $params[] = $status_filter;
    }
    $where_sql = implode(" AND ", $where_clauses);

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_sql");
    $count_stmt->execute($params);
    $total_display_count = $count_stmt->fetchColumn(); 
    $total_pages = ceil($total_display_count / $limit);

    $sql = "SELECT user_id, username, user_email, user_telephone, user_address, status, profile_photo 
            FROM users 
            WHERE $where_sql 
            ORDER BY $sort_by $order 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

function buildQuery($overrides) {
    $current_params = $_GET;
    unset($current_params['msg']); 
    return http_build_query(array_merge($current_params, $overrides));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Maintenance | StepStyle Admin</title>
    <link rel="stylesheet" href="/css/admin_dash.css">
    <style>
        :root {
            --primary-blue: #0061ff;
            --bg-body: #f4f7fe;
            --text-main: #2d3748;
            --border: #e2e8f0;
            --success-green: #38b2ac;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); margin: 0; }
        .main-wrapper { flex: 1; padding: 30px; max-width: 1400px; margin: 0 auto; width: 95%; }
        
        #success-msg {
            background: #e6fffa; color: var(--success-green); padding: 15px 25px;
            border-radius: 8px; border-left: 5px solid var(--success-green);
            margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;
        }

        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        
        .search-area { background: white; border: 1px solid var(--border); padding: 5px 15px; border-radius: 8px; width: 550px; display: flex; align-items: center; }
        .search-area input { border: none; outline: none; width: 100%; font-size: 17px; padding: 8px 0; }
        
        .filter-select { padding: 10px; border-radius: 8px; border: 1px solid var(--border); background: #f8fafc; font-size: 15px; outline: none; cursor: pointer; margin-left: 10px; }
        .search-btn { background: var(--primary-blue); color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 15px; margin-left: 8px; }
        .clear-btn { background: #f1f5f9; color: #64748b; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 15px; margin-left: 8px; text-decoration: none; }

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th { background: #fafafa; padding: 15px; text-align: left; color: #a0aec0; font-size: 13px; text-transform: uppercase; }
        th a { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        td { padding: 15px; border-top: 1px solid var(--border); vertical-align: middle; color: var(--text-main); font-size: 15px; }

        .status-tag { padding: 5px 14px; border-radius: 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .active { background: #e6fffa; color: #38b2ac; }
        .blocked { background: #fff5f5; color: #f56565; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; object-fit: cover; background: #eee; }

        .pagination { display: flex; justify-content: center; align-items: center; margin-top: 30px; gap: 8px; padding-bottom: 50px; }
        .pagination a { padding: 10px 18px; border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--text-main); background: white; font-weight: 600; }
        .pagination a.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 40px 50px; border-radius: 12px; width: 90%; max-width: 460px; text-align: center; }
        .btn { padding: 12px 30px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin: 0 10px; font-weight: bold; }
        .btn-yes { background: #f56565; color: white; }
        .btn-no  { background: #cbd5e0; color: #2d3748; }
    </style>
</head>
<body>

    <?php include '../admin_header.php'; ?>

    <div class="main-wrapper">

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div id="success-msg">
                <span>✓</span> Member status updated successfully!
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1 style="cursor:pointer;" onclick="window.location.href='member_maintenance.php'">Member Maintenance</h1>
        </div>

        <div class="toolbar">
            <div class="search-area">
                <form action="member_maintenance.php" method="GET" style="display: flex; width: 100%; align-items: center;">
                    <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                    
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="blocked" <?= $status_filter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                    </select>

                    <button type="submit" class="search-btn">Search</button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="member_maintenance.php" class="clear-btn">Clear</a>
                    <?php endif; ?>
                    
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                </form>
            </div>
            
            <div style="color: #2d3748; font-weight: 700; font-size: 18px;">
                Total Members: <?= $total_display_count ?>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No.</th>
                        <th>
                            <a href="?<?= buildQuery(['sort' => 'username', 'order' => ($sort_by == 'username' && $order == 'ASC') ? 'DESC' : 'ASC']) ?>">
                                Name <span>⇅</span>
                            </a>
                        </th>
                        <th>Email</th>
                        <th>Telephone</th> 
                        <th>Address</th> 
                        <th>Status</th>
                        <th style="text-align: center;">Toggle</th> 
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 50px; color: #a0aec0;">No members found.</td></tr>
                <?php else: ?>
                    <?php 
                    if ($order === 'ASC') { $display_no = $offset + 1; } 
                    else { $display_no = $total_display_count - $offset; }
                    
                    foreach ($members as $user): 
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: #a0aec0;">
                            <?= sprintf("%03d", ($order === 'ASC' ? $display_no++ : $display_no--)) ?>
                        </td>
                        <td>
                            <a href="../member/member_detail.php?id=<?= urlencode($user['user_id']) ?>" style="text-decoration: none; color: inherit;">
                                <div style="display: flex; align-items: center;">
                                    <?php $photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'default-avatar.jpg'; ?>
                                    <img src="../../uploads/<?= htmlspecialchars($photo) ?>" class="user-avatar">
                                    <strong style="color: #000000;"><?= htmlspecialchars($user['username']) ?></strong>
                                </div>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($user['user_email']) ?></td>
                        <td><?= htmlspecialchars($user['user_telephone'] ?? 'N/A') ?></td>
                        <td><?= mb_strimwidth(htmlspecialchars($user['user_address'] ?? 'N/A'), 0, 40, "...") ?></td>
                        <td>
                            <span class="status-tag <?= $user['status'] == 'active' ? 'active' : 'blocked' ?>">
                                <?= $user['status'] ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <button style="border:none; background:none; cursor:pointer; font-size:20px; color:#0061ff;" onclick="showConfirm(<?= $user['user_id'] ?>, '<?= addslashes($user['username']) ?>', '<?= $user['status'] ?>')">
                                🔄
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= buildQuery(['page' => $page - 1]) ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?= buildQuery(['page' => $i]) ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?<?= buildQuery(['page' => $page + 1]) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-top:0;">Confirm Status Change</h2>
            <p id="modalMemberName" style="font-size: 18px; color: #2d3748; margin: 20px 0 10px 0;"></p>
            <p id="modalActionText" style="color: #64748b; font-size: 16px; margin-bottom: 25px;"></p>
            <div style="margin-top: 30px;">
                <button onclick="confirmChange()" class="btn btn-yes">YES, Update</button>
                <button onclick="closeConfirmModal()" class="btn btn-no">Cancel</button>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script>
        let currentId = null;
        function showConfirm(id, name, status) {
            currentId = id;
            document.getElementById('modalMemberName').innerHTML = 'Change status for <strong>' + name + '</strong>?';
            document.getElementById('modalActionText').innerHTML = status === 'active' 
                ? 'This will <strong style="color:#f56565">BLOCK</strong> this user account.' 
                : 'This will <strong style="color:#38b2ac">ACTIVATE</strong> this user account.';
            document.getElementById('confirmModal').style.display = 'flex';
        }
        function closeConfirmModal() { document.getElementById('confirmModal').style.display = 'none'; }
        function confirmChange() { if (currentId) window.location.href = 'member_status.php?id=' + currentId; }

        window.onload = function() {
            const msg = document.getElementById('success-msg');
            if (msg) {
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
                setTimeout(function() {
                    msg.style.transition = "opacity 0.5s";
                    msg.style.opacity = "0";
                    setTimeout(function() { msg.style.display = "none"; }, 500);
                }, 3000);
            }
        };

        window.onclick = function(event) {
            let modal = document.getElementById('confirmModal');
            if (event.target == modal) closeConfirmModal();
        }
    </script>
</body>
</html>