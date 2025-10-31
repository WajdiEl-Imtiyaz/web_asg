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

$sql = "SELECT COUNT(DISTINCT uId) as total_users FROM users";
$result = mysqli_query($conn, $sql);
$total_users = mysqli_fetch_assoc($result)['total_users'];

$sql = "SELECT COUNT(DISTINCT postID) as total_posts FROM posts WHERE is_archived = FALSE OR is_archived IS NULL";
$result = mysqli_query($conn, $sql);
$total_posts = mysqli_fetch_assoc($result)['total_posts'];

$sql = "SELECT COUNT(DISTINCT commentID) as total_comments FROM comments";
$result = mysqli_query($conn, $sql);
if ($result) {
    $total_comments = mysqli_fetch_assoc($result)['total_comments'];
} else {
    $total_comments = 0;
}

$sql = "SELECT COUNT(DISTINCT uId) as banned_users FROM users WHERE is_banned = TRUE";
$result = mysqli_query($conn, $sql);
$banned_users = mysqli_fetch_assoc($result)['banned_users'];

$sql = "SELECT COUNT(DISTINCT postID) as archived_posts FROM posts WHERE is_archived = TRUE";
$result = mysqli_query($conn, $sql);
$archived_posts = mysqli_fetch_assoc($result)['archived_posts'];

$sql = "SELECT p.postID, p.content, p.createdAt, 
               COALESCE(up.name, u.uEmail) as author_name 
        FROM posts p 
        JOIN users u ON p.uID = u.uId 
        LEFT JOIN user_profile up ON (p.uID = up.uID AND up.profileID = (
            SELECT MAX(profileID) FROM user_profile up2 WHERE up2.uID = p.uID
        ))
        WHERE (p.is_archived = FALSE OR p.is_archived IS NULL)
        ORDER BY p.createdAt DESC 
        LIMIT 10";
$recent_posts_result = mysqli_query($conn, $sql);
$recent_posts = $recent_posts_result ? mysqli_fetch_all($recent_posts_result, MYSQLI_ASSOC) : [];

$sql = "SELECT u.uId, u.uEmail, u.created_at, 
               COALESCE(up.name, u.uEmail) as display_name 
        FROM users u 
        LEFT JOIN user_profile up ON (u.uId = up.uID AND up.profileID = (
            SELECT MAX(profileID) FROM user_profile up2 WHERE up2.uID = u.uId
        ))
        ORDER BY u.created_at DESC 
        LIMIT 10";
$recent_users_result = mysqli_query($conn, $sql);
$recent_users = $recent_users_result ? mysqli_fetch_all($recent_users_result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="app container">
  <div class="sidebar">
    <h2>Admin</h2>
    <nav class="menu">
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
    <div class="logout-section">
      <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </div>

  <main>
    <h1>Dashboard</h1>
    
    <div class="stats">
        <div class="stat">
            <div class="label">Total Users</div>
            <div class="value"><?php echo $total_users; ?></div>
        </div>
        <div class="stat">
            <div class="label">Active Posts</div>
            <div class="value"><?php echo $total_posts; ?></div>
        </div>
        <div class="stat">
            <div class="label">Total Comments</div>
            <div class="value"><?php echo $total_comments; ?></div>
        </div>
        <div class="stat">
            <div class="label">Banned Users</div>
            <div class="value" style="color: #dc3545;"><?php echo $banned_users; ?></div>
        </div>
        <div class="stat">
            <div class="label">Archived Posts</div>
            <div class="value" style="color: #f57c00;"><?php echo $archived_posts; ?></div>
        </div>
    </div>

    <div class="stat-table" >
        <div class="card">
            <h3>Recent Posts</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content</th>
                        <th>Author</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_posts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['postID']); ?></td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php 
                            $content = htmlspecialchars($post['content']);
                            echo strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($post['createdAt'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($recent_posts)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No posts found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Recent Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['uId']); ?></td>
                        <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['uEmail']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($recent_users)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No users found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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