<?php
session_start();
if(empty($_SESSION['user']) || empty($_SESSION['is_admin'])){
    header("Location: ../login/login.php");
    exit();
}

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
  <div class="sidebar">
    <h2>Admin </h2>
    <nav class="menu">
      <a href="dashboard.php" >Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
      <a href="?logout=1" class="logout-btn">Logout</a>
  </div>

  <main>
    <h1>Report Summary</h1>
  <div class="card">
     
    </div>
  </main>


</div>    
</body>
</html>