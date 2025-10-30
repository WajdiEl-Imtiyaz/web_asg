<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="stylesheet" href="home.css" />
  </head>
  <body>
    <div class="container">
      <div class="left-sidebar">
        <h2>Logo</h2>
        <nav class="menu">
          <a href="../home/home.php">Home</a>
          <a href="">Explore</a>
          <a href="">Notification</a>
          <a href="">Profile</a>
        </nav>
      </div>

      <main>
        <header><h1>Home</h1></header>
        <hr id="header-separator">

        <div class="post-card-list">
            <div class="post-card">
                <div class="post-header">
                    <div class="user-avatar"></div>
                    <div class="user-info">
                        <span class="user-name">John Doe</span>
                        <span class="post-timestamp">2 hrs ago</span>
                    </div>
                </div>
                <div class="post-content">
                    <p>This is a sample post content.</p>
                </div>
                <div class="post-actions">
                    <button class="like-btn">like</button>
                    <button class="comment-btn">comment</button>
                </div>
            </div>
        </div>

        <div class="user-post-input-container">
            <div id="home-avatar">prof</div>
            <input type="text" id="user-post-field" placeholder="What's on your mind?" />
            <button id="post-btn">Post</button>
        </div>
      </main>

      <div class="right-sidebar">
        <input type="search" id="search" placeholder="Search..." />
        <h2>Trending</h2>
        <nav class="menu">
          <a href="../home/home.php">#Home</a>
          <a href="">#Explore</a>
          <a href="">#Notification</a>
          <a href="">#Profile</a>
      </div>
    </div>
  </body>
</html>
