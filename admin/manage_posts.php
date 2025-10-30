<?php
session_start();
require '../db.php';

if(empty($_SESSION['user']) || empty($_SESSION['is_admin'])){
    // not logged in or not admin -> redirect to login
    header("Location: ../login/login.php");
    exit();
}

// Handle logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}

// Handle post archiving/unarchiving
if(isset($_GET['action']) && isset($_GET['id'])) {
    $post_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if($action === 'archive') {
        $sql = "UPDATE posts SET is_archived = TRUE WHERE postID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Post archived successfully!";
        } else {
            $_SESSION['error'] = "Error archiving post.";
        }
        header("Location: manage_posts.php");
        exit();
    }
    
    if($action === 'unarchive') {
        $sql = "UPDATE posts SET is_archived = FALSE WHERE postID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Post unarchived successfully!";
        } else {
            $_SESSION['error'] = "Error unarchiving post.";
        }
        header("Location: manage_posts.php");
        exit();
    }
}

// Fetch ALL posts from database with user information (both active and archived)
$sql = "SELECT p.postID, p.content, p.image, p.createdAt, p.is_archived,
               u.uEmail, u.uId,
               COALESCE(up.name, u.uEmail) as display_name 
        FROM posts p 
        JOIN users u ON p.uID = u.uId 
        LEFT JOIN user_profile up ON p.uID = up.uID 
        ORDER BY p.createdAt DESC";
$result = mysqli_query($conn, $sql);
$posts = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count posts by status
$active_count = 0;
$archived_count = 0;
foreach ($posts as $post) {
    if ($post['is_archived']) {
        $archived_count++;
    } else {
        $active_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Posts</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .post-row.archived {
            opacity: 0.7;
            background: rgba(220, 53, 69, 0.05);
        }
        .post-row.archived:hover {
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
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php" class="active">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
    <div class="logout-section">
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </div>

  <main>
    <h1>Manage Posts</h1>
    
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>All Posts (<?php echo count($posts); ?> total)</h3>
        
        <!-- Status Summary -->
        <div class="status-filter">
            <span class="status-active" style="margin-right: 15px;">
                Active: <?php echo $active_count; ?>
            </span>
            <span class="status-banned">
                Archived: <?php echo $archived_count; ?>
            </span>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Content</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr class="post-row <?php echo $post['is_archived'] ? 'archived' : ''; ?>">
                    <td><?php echo htmlspecialchars($post['postID']); ?></td>
                    <td style="max-width: 300px; word-wrap: break-word;">
                        <?php 
                        $content = htmlspecialchars($post['content']);
                        // Truncate long content
                        if(strlen($content) > 100) {
                            echo substr($content, 0, 100) . '...';
                        } else {
                            echo $content;
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($post['display_name']); ?>
                        <br>
                        <small class="text-muted">
                            ID: <?php echo $post['uId']; ?>
                            <?php if ($post['display_name'] !== $post['uEmail']): ?>
                                <br>Email: <?php echo htmlspecialchars($post['uEmail']); ?>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($post['createdAt'])); ?></td>
                    <td>
                        <?php if (!empty($post['image'])): ?>
                            <span class="status-active">Yes</span>
                        <?php else: ?>
                            <span class="text-muted">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($post['is_archived']): ?>
                            <span class="status-banned">Archived</span>
                        <?php else: ?>
                            <span class="status-active">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$post['is_archived']): ?>
                            <button class="ban-btn" onclick="confirmArchive(<?php echo $post['postID']; ?>)">Archive</button>
                        <?php else: ?>
                            <button class="unban-btn" onclick="confirmUnarchive(<?php echo $post['postID']; ?>)">Unarchive</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 20px;">
                        No posts found in the database.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </main>
</div>

<script>
function confirmArchive(postId) {
    if(confirm('Are you sure you want to archive this post? It will be hidden from public view.')) {
        window.location.href = 'manage_posts.php?action=archive&id=' + postId;
    }
}

function confirmUnarchive(postId) {
    if(confirm('Are you sure you want to unarchive this post? It will be visible to the public again.')) {
        window.location.href = 'manage_posts.php?action=unarchive&id=' + postId;
    }
}
</script>

</body>
</html>