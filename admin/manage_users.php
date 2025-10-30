<?php
session_start();
require '../db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || !$_SESSION['is_admin']) {
    header("Location: ../login/login.php");
    exit();
}

// Handle ban/unban actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Prevent modifying the main admin (user ID 1)
    if ($user_id == 1) {
        $_SESSION['error'] = "Cannot modify the main administrator.";
        header("Location: manage_users.php");
        exit();
    }
    
    if ($action === 'ban') {
        $sql = "UPDATE users SET is_banned = 1 WHERE uId = ?";
        $alert_message = "User banned successfully!";
    } elseif ($action === 'unban') {
        $sql = "UPDATE users SET is_banned = 0 WHERE uId = ?";
        $alert_message = "User unbanned successfully!";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Set alert message in session to show after redirect
        $_SESSION['alert_message'] = $alert_message;
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['alert_message'] = "Error updating user.";
    }
}

// Fetch users from database
$sql = "SELECT uId, uEmail, is_admin, is_banned, created_at FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="app container">
  <aside class="sidebar">
    <h2>Admin</h2>
    <nav class="menu">
      <a href="dashboard.php">Dashboard</a>
      <a href="manage_users.php" class="active">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
  </aside>

  <main>
    <h1>Manage Users</h1>
    
    <div class="card">
        <h3>User List (<?php echo count($users); ?> users)</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['uId']); ?></td>
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
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['uId'] != 1): // Don't allow banning the main admin ?>
                            <?php if ($user['is_banned']): ?>
                                <button class="unban-btn" onclick="confirmAction(<?php echo $user['uId']; ?>, 'unban')">Unban</button>
                            <?php else: ?>
                                <button class="ban-btn" onclick="confirmAction(<?php echo $user['uId']; ?>, 'ban')">Ban</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #b2b5be; font-size: 12px;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
  </main>
</div>

<script>
function confirmAction(userId, action) {
    const message = action === 'ban' 
        ? 'Are you sure you want to ban this user? They will not be able to login.'
        : 'Are you sure you want to unban this user? They will be able to login again.';
    
    if (confirm(message)) {
        window.location.href = 'manage_users.php?action=' + action + '&id=' + userId;
    }
}

// Show alert popup if there's a message from PHP
<?php if (isset($_SESSION['alert_message'])): ?>
    alert("<?php echo $_SESSION['alert_message']; ?>");
    <?php unset($_SESSION['alert_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo $_SESSION['error']; ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

</body>
</html>