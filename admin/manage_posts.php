<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="topbar">
  <div class="brand"><div class="logo"></div>Admin</div>
</div>

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
    <h1>Manage Posts</h1>
  <div class="card">
        <h3>Post List</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Sample Post Title 1</td>
                    <td>admin</td>
                    <td>2025-10-29</td>
                    <td><button>Archive</button></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Sample Post Title 2</td>
                    <td>editor</td>
                    <td>2025-10-28</td>
                    <td><button>Archive</button></td>
                </tr>
            </tbody>
        </table>
    </div>
  </main>

    

   
</div>

    
</body>
</html>