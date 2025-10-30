<?php
session_start();
require '../db.php';
if(empty($_SESSION['user']) || empty($_SESSION['is_admin'])){
    header("Location: ../login/login.php");
    exit();
}

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($user_id == 1) {
        $_SESSION['error'] = "Cannot modify the main administrator.";
        header("Location: manage_users.php");
        exit();
    }
    
    if ($action === 'ban') {
        $sql = "UPDATE users SET is_banned = 1 WHERE uId = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "User banned successfully!";
        } else {
            $_SESSION['error'] = "Error banning user.";
        }
    } elseif ($action === 'unban') {
        $sql = "UPDATE users SET is_banned = 0 WHERE uId = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "User unbanned successfully!";
        } else {
            $_SESSION['error'] = "Error unbanning user.";
        }
    }
    header("Location: manage_users.php");
    exit();
}

$sql = "SELECT u.uId, u.uEmail, u.is_admin, u.is_banned, u.created_at,
               COALESCE(up.name, u.uEmail) as display_name 
        FROM users u 
        LEFT JOIN user_profile up ON u.uId = up.uID 
        ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

$active_count = 0;
$banned_count = 0;
$admin_count = 0;
foreach ($users as $user) {
    if ($user['is_banned']) {
        $banned_count++;
    } else {
        $active_count++;
    }
    if ($user['is_admin']) {
        $admin_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .user-row.banned {
            opacity: 0.7;
            background: rgba(220, 53, 69, 0.05);
        }
        .user-row.banned:hover {
            opacity: 0.9;
            background: rgba(220, 53, 69, 0.1);
        }
        .status-filter {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="app container">
  <div class="sidebar">
    <h2>Admin</h2>
    <nav class="menu">
      <a href="dashboard.php">Dashboard</a>
      <a href="manage_users.php" class="active">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
    <div class="logout-section">
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </div>

  <main>
    <h1>Manage Users</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>All Users (<?php echo count($users); ?> total)</h3>
        
        <div class="status-filter">
            <span class="status-active" style="margin-right: 15px;">
                Active: <?php echo $active_count; ?>
            </span>
            <span class="status-banned" style="margin-right: 15px;">
                Banned: <?php echo $banned_count; ?>
            </span>
            <span class="role-admin">
                Admins: <?php echo $admin_count; ?>
            </span>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Display Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="user-row <?php echo $user['is_banned'] ? 'banned' : ''; ?>">
                    <td><?php echo htmlspecialchars($user['uId']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($user['display_name']); ?>
                        <?php if ($user['display_name'] !== $user['uEmail']): ?>
                            <br><small class="text-muted">(Profile Name)</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['uEmail']); ?></td>
                    <td>
                        <?php if ($user['is_admin']): ?>
                            <span class="role-admin">Administrator</span>
                        <?php else: ?>
                            <span class="role-user">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['is_banned']): ?>
                            <span class="status-banned">Banned</span>
                        <?php else: ?>
                            <span class="status-active">Active</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['uId'] != 1): ?>
                            <?php if ($user['is_banned']): ?>
                                <button class="unban-btn" onclick="confirmUnban(<?php echo $user['uId']; ?>)">Unban</button>
                            <?php else: ?>
                                <button class="ban-btn" onclick="confirmBan(<?php echo $user['uId']; ?>)">Ban</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted" style="font-size: 12px;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 20px;">
                        No users found in the database.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </main>
</div>

<script>
function confirmBan(userId) {
    if(confirm('Are you sure you want to ban this user? They will not be able to login.')) {
        window.location.href = 'manage_users.php?action=ban&id=' + userId;
    }
}

function confirmUnban(userId) {
    if(confirm('Are you sure you want to unban this user? They will be able to login again.')) {
        window.location.href = 'manage_users.php?action=unban&id=' + userId;
    }
}
</script>

</body>
</html>