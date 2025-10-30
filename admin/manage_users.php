<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="app container">
  <aside class="sidebar">
<h2>Admin </h2>
    <nav class="menu">
      <a href="dashboard.php" >Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_posts.php">Manage Posts</a>
      <a href="reports.php">Reports</a>
    </nav>
  </aside>

  <main>
    <h1>Manage Users</h1>
    <div class="card">
        <h3>User List</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>admin</td>
                    <td>admin@example.com</td>
                    <td>Administrator</td>
                    <td><button>Edit</button> <button>Ban</button></td>
  </main>
    </div>

   
</div>

    
</body>
</html>