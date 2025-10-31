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

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$end_date_with_time = $end_date . ' 23:59:59';

$sql = "SELECT 
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as total_users,
        COUNT(CASE WHEN is_banned = 1 AND created_at BETWEEN ? AND ? THEN 1 END) as banned_users,
        COUNT(CASE WHEN is_admin = 1 AND created_at BETWEEN ? AND ? THEN 1 END) as admin_users,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users
        FROM users";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssssss", 
    $start_date, $end_date_with_time, 
    $start_date, $end_date_with_time,
    $start_date, $end_date_with_time,
    $start_date, $end_date_with_time
);
mysqli_stmt_execute($stmt);
$user_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$sql = "SELECT 
        COUNT(CASE WHEN createdAt BETWEEN ? AND ? THEN 1 END) as total_posts,
        COUNT(CASE WHEN is_archived = 1 AND createdAt BETWEEN ? AND ? THEN 1 END) as archived_posts,
        COUNT(CASE WHEN createdAt BETWEEN ? AND ? THEN 1 END) as new_posts,
        AVG(CASE WHEN createdAt BETWEEN ? AND ? THEN LENGTH(content) END) as avg_post_length
        FROM posts";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssssss", 
    $start_date, $end_date_with_time,
    $start_date, $end_date_with_time,
    $start_date, $end_date_with_time,
    $start_date, $end_date_with_time
);
mysqli_stmt_execute($stmt);
$post_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$sql = "SELECT u.uId, COALESCE(up.name, u.uEmail) as display_name, 
        COUNT(p.postID) as post_count
        FROM users u 
        LEFT JOIN user_profile up ON u.uId = up.uID
        LEFT JOIN posts p ON u.uId = p.uID AND p.createdAt BETWEEN ? AND ?
        WHERE u.created_at BETWEEN ? AND ? 
        AND u.is_admin = 0
        GROUP BY u.uId
        HAVING post_count > 0
        ORDER BY post_count DESC 
        LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date_with_time, $start_date, $end_date_with_time);
mysqli_stmt_execute($stmt);
$active_users = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$sql = "SELECT 'user' as type, uId as id, uEmail as name, created_at as date, NULL as content
        FROM users 
        WHERE created_at BETWEEN ? AND ? AND is_admin = 0
        UNION ALL
        SELECT 'post' as type, postID as id, 
               CONCAT(LEFT(content, 50), CASE WHEN LENGTH(content) > 50 THEN '...' ELSE '' END) as name, 
               createdAt as date,
               content
        FROM posts 
        WHERE createdAt BETWEEN ? AND ? AND uID NOT IN (SELECT uId FROM users WHERE is_admin = 1)
        ORDER BY date DESC 
        LIMIT 15";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date_with_time, $start_date, $end_date_with_time);
mysqli_stmt_execute($stmt);
$recent_activity = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$sql_total_users = "SELECT COUNT(*) as count FROM users";
$total_users_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_total_users))['count'];

$sql_total_posts = "SELECT COUNT(*) as count FROM posts";
$total_posts_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_total_posts))['count'];

$new_users_percentage = $total_users_count > 0 ? round(($user_stats['new_users'] / $total_users_count) * 100, 1) : 0;
$new_posts_percentage = $total_posts_count > 0 ? round(($post_stats['new_posts'] / $total_posts_count) * 100, 1) : 0;

$period_days = max(1, round((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)) + 1);
$users_per_day = $user_stats['total_users'] > 0 ? round($user_stats['total_users'] / $period_days, 1) : 0;
$posts_per_day = $post_stats['total_posts'] > 0 ? round($post_stats['total_posts'] / $period_days, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .date-info {
            background: #1c2433;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #2d6b67;
            margin-bottom: 20px;
            text-align: center;
        }
        .date-info h3 {
            margin: 0 0 10px 0;
            color: #4ca9c2;
        }
        .date-range {
            color: #b2b5be;
            font-size: 14px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat {
            background: #1c2433;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #2d6b67;
            text-align: center;
        }
        .stat .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4ca9c2;
            margin: 10px 0;
        }
        .stat .label {
            color: #b2b5be;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat small {
            color: #8b8d94;
            font-size: 0.8rem;
        }
        .card {
            background: #1c2433;
            border-radius: 12px;
            border: 1px solid #2d6b67;
            overflow: hidden;
        }
        .card h3 {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid #2d6b67;
            background: rgba(76, 169, 194, 0.1);
            color: #4ca9c2;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: rgba(76, 169, 194, 0.1);
            color: #4ca9c2;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #2d6b67;
        }
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #2d6b67;
            color: #e8e9ed;
        }
        .table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-bottom: 1px solid #2d6b67;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .type-user { background: rgba(40, 167, 69, 0.2); color: #28a745; }
        .type-post { background: rgba(76, 169, 194, 0.2); color: #4ca9c2; }
        .activity-content {
            flex: 1;
        }
        .activity-time {
            font-size: 0.8rem;
            color: #8b8d94;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .text-muted { color: #8b8d94; }
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #8b8d94;
        }
        .period-stats {
            background: rgba(76, 169, 194, 0.1);
            padding: 10px 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.85rem;
        }
    </style>
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
    <h1>Period Analytics Report</h1>
    
    <div class="date-info">
        <h3>Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?></h3>
        <div class="date-range">
            Showing data from selected period only (<?php echo $period_days; ?> days)
            <div class="period-stats">
                <?php echo $users_per_day; ?> users/day • <?php echo $posts_per_day; ?> posts/day
            </div>
        </div>
    </div>
    
    <div class="card date-filter">
        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap; padding: 20px;">
            <label style="display: flex; flex-direction: column; color: #b2b5be; font-size: 14px; min-width: 150px;">
                Start Date:
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" required 
                       style="margin-top: 5px; background: #0e0e12; border: 1px solid #2d6b67; color: white; padding: 10px; border-radius: 6px;">
            </label>
            <label style="display: flex; flex-direction: column; color: #b2b5be; font-size: 14px; min-width: 150px;">
                End Date:
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" required 
                       style="margin-top: 5px; background: #0e0e12; border: 1px solid #2d6b67; color: white; padding: 10px; border-radius: 6px;">
            </label>
            <div style="display: flex; gap: 10px; align-items: end;">
                <button type="submit" class="edit-btn" style="background: #4ca9c2; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    Generate Report
                </button>
                <a href="reports.php" class="edit-btn" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
                    Reset to 30 Days
                </a>
            </div>
        </form>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="label">Users in Period</div>
            <div class="value"><?php echo $user_stats['total_users']; ?></div>
            <small>
                <?php echo $user_stats['new_users']; ?> total created<br>
                <?php echo $users_per_day; ?> per day average<br>
                (<?php echo $new_users_percentage; ?>% of all-time total)
            </small>
        </div>
        <div class="stat">
            <div class="label">Posts in Period</div>
            <div class="value"><?php echo $post_stats['total_posts']; ?></div>
            <small>
                <?php echo $post_stats['new_posts']; ?> total created<br>
                <?php echo $posts_per_day; ?> per day average<br>
                (<?php echo $new_posts_percentage; ?>% of all-time total)
            </small>
        </div>
        <div class="stat">
            <div class="label">Banned in Period</div>
            <div class="value" style="color: #dc3545;"><?php echo $user_stats['banned_users']; ?></div>
            <small>
                <?php echo round(($user_stats['banned_users'] / max($user_stats['total_users'], 1)) * 100, 1); ?>% of period users<br>
                Moderation activity
            </small>
        </div>
        <div class="stat">
            <div class="label">Content Health</div>
            <div class="value" style="color: #f57c00;"><?php echo $post_stats['archived_posts']; ?></div>
            <small>
                <?php echo round(($post_stats['archived_posts'] / max($post_stats['total_posts'], 1)) * 100, 1); ?>% archived<br>
                Avg. length: <?php echo round($post_stats['avg_post_length'] ?? 0); ?> chars
            </small>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <div class="card">
            <h3>Most Active Users (This Period)</h3>
            <div style="padding: 20px;">
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
                            <td colspan="2" class="empty-state">
                                No user activity in selected period
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Recent Activity (This Period)</h3>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach($recent_activity as $activity): ?>
                <div class="activity-item">
                    <span class="activity-type type-<?php echo $activity['type']; ?>">
                        <?php echo strtoupper($activity['type']); ?>
                    </span>
                    <div class="activity-content">
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($activity['name']); ?></div>
                    </div>
                    <div class="activity-time"><?php echo date('M j g:i A', strtotime($activity['date'])); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($recent_activity)): ?>
                <div class="empty-state">
                    No recent activity in selected period
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 25px;">
        <h3>Period Summary</h3>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="color: #e8e9ed; margin-bottom: 15px;">User Activity</h4>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #2d6b67;">
                        <span>Active Users:</span>
                        <strong><?php echo $user_stats['total_users'] - $user_stats['banned_users']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #2d6b67;">
                        <span>Daily Average:</span>
                        <strong><?php echo $users_per_day; ?> users/day</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>Admin Activity:</span>
                        <strong style="color: #4ca9c2;"><?php echo $user_stats['admin_users']; ?> admins</strong>
                    </div>
                </div>
                <div>
                    <h4 style="color: #e8e9ed; margin-bottom: 15px;">Content Activity</h4>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #2d6b67;">
                        <span>Active Posts:</span>
                        <strong><?php echo $post_stats['total_posts'] - $post_stats['archived_posts']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #2d6b67;">
                        <span>Daily Average:</span>
                        <strong><?php echo $posts_per_day; ?> posts/day</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span>Top Contributors:</span>
                        <strong><?php echo count($active_users); ?> active users</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Quick Actions</h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="manage_users.php" class="edit-btn">Manage Users</a>
            <a href="manage_posts.php" class="edit-btn">Manage Posts</a>
            <a href="reports.php" class="edit-btn">View Reports</a>
        </div>
    </div>
  </main>
</div>
    
</body>
</html>