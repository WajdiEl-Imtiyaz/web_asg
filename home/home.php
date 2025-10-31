<?php

    session_start();
    require '../db.php';    

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data && isset($data['postId']) && isset($data['action'])) {
            $postId = $data['postId'];
            $action = $data['action'];
            
            $email = $_SESSION['user'];
            $stmt = $conn->prepare("SELECT uID FROM users WHERE uEmail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $uID = $user['uID'];
            
            if ($action === 'like') {
                $check = $conn->prepare("SELECT * FROM likes WHERE postID = ? AND uID = ?");
                $check->bind_param("ii", $postId, $uID);
                $check->execute();
                
                if ($check->get_result()->num_rows === 0) {
                    $stmt = $conn->prepare("INSERT INTO likes (postID, uID) VALUES (?, ?)");
                    $stmt->bind_param("ii", $postId, $uID);
                    $stmt->execute();
                }
            } else if ($action === 'unlike') {
                $stmt = $conn->prepare("DELETE FROM likes WHERE postID = ? AND uID = ?");
                $stmt->bind_param("ii", $postId, $uID);
                $stmt->execute();
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE postID = ?");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'likeCount' => $count]);
            exit();
        }
    }

    if (isset($_GET['logout'])) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ../login/login.php');
        exit();
    }

    if(!isset($_SESSION['user'])){
        header("Location: ../login/login.php");
        exit();
    }

    $email = $_SESSION['user'];

    $sql = "SELECT uID FROM users WHERE uEmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $uID = $user['uID'];

    $sql = "SELECT * FROM user_profile WHERE uID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userProfile = $result->fetch_assoc();
    
    $showPopup = ($result->num_rows === 0);

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST['saveProfile'])) {
            $name = $_POST['name'];
            $age = $_POST['age'];

            $insert = "INSERT INTO user_profile (uID, name, age) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("isi", $uID, $name, $age);
            if($stmt->execute()) {
                $showPopup = false;
            }
        } elseif(isset($_POST['post_content'])) {
            $content = $_POST['post_content'];
            
            $insert = "INSERT INTO posts (uID, content) VALUES (?, ?)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("is", $uID, $content);
            $stmt->execute();
            
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    }
    
    $posts_query = "SELECT p.postID, p.uID, p.content, p.image, p.createdAt, p.is_archived, 
                   (SELECT up.name FROM user_profile up WHERE up.uID = p.uID ORDER BY up.profileID DESC LIMIT 1) as name,
                   (SELECT up.avatar FROM user_profile up WHERE up.uID = p.uID ORDER BY up.profileID DESC LIMIT 1) as avatar
                   FROM posts p 
                   WHERE (p.is_archived = FALSE OR p.is_archived IS NULL)
                   ORDER BY p.createdAt DESC";
    $posts_result = $conn->query($posts_query);
    $posts = $posts_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="stylesheet" href="home.css" />
    <script>
        function toggleLike(button) {
            const postId = button.getAttribute('data-post-id');
            const likeIcon = button.querySelector('.like-icon');
            const likeCount = button.querySelector('.like-count');
            const isLiked = button.classList.contains('liked');

            fetch('home.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    postId: postId,
                    action: isLiked ? 'unlike' : 'like'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('liked');
                    likeIcon.textContent = isLiked ? 'like' : 'unlike';
                    likeCount.textContent = data.likeCount;
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
  </head>
  <body>
    
    <?php if ($showPopup): ?>
        <div id="profile-popup" class="popup">
            <div class="popup-content">
                <h2>Complete your profile</h2>
                <form method="POST">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required />

                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" required />

                    <button type="submit" name="saveProfile">Save</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
      <div class="left-sidebar">
        <h2>Logo</h2>
        <nav class="menu">
          <a href="../home/home.php">Home</a>
          <a href="">Explore</a>
          <a href="">Notification</a>
          <a href="">Profile</a>
        </nav>
                <a href="?logout=1" class="logout">Logout</a>
      </div>

      <main>
        <header><h1>Home</h1></header>
        <hr id="header-separator">

        <div class="post-card-list">
            <?php foreach($posts as $post): 
        // Get like count for this post
        $like_count_sql = "SELECT COUNT(*) as count FROM likes WHERE postID = ?";
        $stmt = $conn->prepare($like_count_sql);
        $stmt->bind_param("i", $post['postID']);
        $stmt->execute();
        $like_count = $stmt->get_result()->fetch_assoc()['count'];

        // Check if current user has liked this post
        $is_liked_sql = "SELECT * FROM likes WHERE postID = ? AND uID = ?";
        $stmt = $conn->prepare($is_liked_sql);
        $stmt->bind_param("ii", $post['postID'], $uID);
        $stmt->execute();
        $is_liked = $stmt->get_result()->num_rows > 0;
    ?>
                <div class="post-card">
                    <div class="post-header">
                        <div class="user-avatar">
                            <?php echo !empty($post['avatar']) ? "<img src='{$post['avatar']}' alt='Profile'>" : ""; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($post['name'] ?? 'Anonymous'); ?></span>
                            <span class="post-timestamp">
                                <?php 
                                    $date = new DateTime($post['createdAt']);
                                    $now = new DateTime();
                                    $interval = $now->diff($date);
                                    if($interval->y > 0) echo $interval->y . "y ago";
                                    elseif($interval->m > 0) echo $interval->m . "m ago";
                                    elseif($interval->d > 0) echo $interval->d . "d ago";
                                    elseif($interval->h > 0) echo $interval->h . "h ago";
                                    elseif($interval->i > 0) echo $interval->i . "m ago";
                                    else echo "Just now";
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="post-content">
                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if(!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <button class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" 
                                data-post-id="<?php echo $post['postID']; ?>"
                                onclick="toggleLike(this)">
                            <span class="like-icon"><?php echo $is_liked ? 'unlike' : 'like'; ?></span>
                            <span class="like-count"><?php echo $like_count; ?></span>
                        </button>
                        <button class="comment-btn">comment</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="user-post-input-container">
            <div id="home-avatar">
                <?php 
                    echo !empty($userProfile['avatar']) ? "<img src='{$userProfile['avatar']}' alt='Profile'>" : "prof";
                ?>
            </div>
            <input type="text" name="post_content" id="user-post-field" placeholder="What's on your mind?" required />
            <button type="submit" id="post-btn" aria-label="Post">Post</button>
        </form>
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