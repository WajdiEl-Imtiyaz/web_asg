<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="app container">
  <aside class="sidebar">
    <h2>Admin</h2>
    <nav class="menu">
      <a href="dashboard.php">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
    <div class="logout-section">
      <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </aside>

  <main>
    <h1>Dashboard</h1>
    <div class="stats">
        <div class="stat"><div class="label">Users</div><div class="value">0</div></div>
        <div class="stat"><div class="label">Posts</div><div class="value">0</div></div>
        <div class="stat"><div class="label">Comments</div><div class="value">0</div></div>
    </div>

    <div class="card">
        <h3>Recent Posts</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Sample Post Title 1</td>
                    <td>2025-10-29</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Sample Post Title 2</td>
                    <td>2025-10-28</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>
</div>

    
</body>
</html>