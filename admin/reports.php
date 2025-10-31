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

// Get date range from URL parameters or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// User Statistics
$sql = "SELECT 
        COUNT(*) as total_users,
        SUM(is_banned) as banned_users,
        SUM(is_admin) as admin_users,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_users
        FROM users";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $start_date);
mysqli_stmt_execute($stmt);
$user_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Post Statistics
$sql = "SELECT 
        COUNT(*) as total_posts,
        SUM(is_archived) as archived_posts,
        COUNT(CASE WHEN createdAt >= ? THEN 1 END) as new_posts,
        AVG(LENGTH(content)) as avg_post_length
        FROM posts";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $start_date);
mysqli_stmt_execute($stmt);
$post_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Most Active Users (by post count) - Exclude admins
$sql = "SELECT u.uId, COALESCE(up.name, u.uEmail) as display_name, 
        COUNT(p.postID) as post_count
        FROM users u 
        LEFT JOIN user_profile up ON (u.uId = up.uID AND up.profileID = (
            SELECT MAX(profileID) FROM user_profile up2 WHERE up2.uID = u.uId
        ))
        LEFT JOIN posts p ON u.uId = p.uID
        WHERE p.createdAt BETWEEN ? AND ? AND u.is_admin = 0
        GROUP BY u.uId
        ORDER BY post_count DESC 
        LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$active_users = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Recent Activity - Exclude admin users
$sql = "SELECT 'user' as type, uId, uEmail as name, created_at as date 
        FROM users 
        WHERE created_at BETWEEN ? AND ? AND is_admin = 0
        UNION ALL
        SELECT 'post' as type, postID as id, LEFT(content, 50) as name, createdAt as date 
        FROM posts 
        WHERE createdAt BETWEEN ? AND ? AND uID NOT IN (SELECT uId FROM users WHERE is_admin = 1)
        ORDER BY date DESC 
        LIMIT 15";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$recent_activity = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="app container">
  <div class="sidebar">
    <h2>Admin</h2>
    <nav class="menu">
      <a href="dashboard.php">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php" class="active">Reports</a>
    </nav>
    <div class="logout-section">
      <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </div>

  <main>
    <h1>Report Summary</h1>
    
    <!-- Date Filter -->
    <div class="card date-filter">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <label style="display: flex; flex-direction: column; color: #b2b5be; font-size: 14px;">
                Start Date:
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" required 
                       style="margin-top: 5px; background: #0e0e12; border: 1px solid #2d6b67; color: white; padding: 8px; border-radius: 4px;">
            </label>
            <label style="display: flex; flex-direction: column; color: #b2b5be; font-size: 14px;">
                End Date:
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" required 
                       style="margin-top: 5px; background: #0e0e12; border: 1px solid #2d6b67; color: white; padding: 8px; border-radius: 4px;">
            </label>
            <div style="display: flex; gap: 10px; align-items: end;">
                <button type="submit" class="edit-btn">Generate Report</button>
                <a href="reports.php" class="edit-btn" style="background: #6c757d;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Statistics Overview -->
    <div class="stats">
        <div class="stat">
            <div class="label">Total Users</div>
            <div class="value"><?php echo $user_stats['total_users']; ?></div>
            <small class="text-muted"><?php echo $user_stats['new_users']; ?> new since <?php echo date('M j', strtotime($start_date)); ?></small>
        </div>
        <div class="stat">
            <div class="label">Total Posts</div>
            <div class="value"><?php echo $post_stats['total_posts']; ?></div>
            <small class="text-muted"><?php echo $post_stats['new_posts']; ?> new since <?php echo date('M j', strtotime($start_date)); ?></small>
        </div>
        <div class="stat">
            <div class="label">Banned Users</div>
            <div class="value" style="color: #dc3545;"><?php echo $user_stats['banned_users']; ?></div>
            <small class="text-muted"><?php echo round(($user_stats['banned_users'] / max($user_stats['total_users'], 1)) * 100, 1); ?>% of total</small>
        </div>
        <div class="stat">
            <div class="label">Archived Posts</div>
            <div class="value" style="color: #f57c00;"><?php echo $post_stats['archived_posts']; ?></div>
            <small class="text-muted"><?php echo round(($post_stats['archived_posts'] / max($post_stats['total_posts'], 1)) * 100, 1); ?>% of total</small>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Most Active Users -->
        <div class="card">
            <h3>Most Active Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Posts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($active_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                        <td><?php echo $user['post_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($active_users)): ?>
                    <tr>
                        <td colspan="2" class="text-center text-muted">No user activity in selected period</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h3>Recent User Activity</h3>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach($recent_activity as $activity): ?>
                <div style="padding: 12px; border-bottom: 1px solid #2d6b67; display: flex; align-items: center; gap: 10px;">
                    <span class="<?php echo $activity['type'] === 'user' ? 'status-active' : 'role-user'; ?>" 
                          style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        <?php echo strtoupper($activity['type']); ?>
                    </span>
                    <span style="flex: 1;"><?php echo htmlspecialchars($activity['name']); ?></span>
                    <small class="text-muted"><?php echo date('M j g:i A', strtotime($activity['date'])); ?></small>
                </div>
                <?php endforeach; ?>
                <?php if(empty($recent_activity)): ?>
                <div class="text-center text-muted" style="padding: 20px;">No recent user activity</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </main>
</div>
    
</body>
</html>