<?php

    session_start();
    require '../db.php';    

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
        } elseif (isset($_POST['like_post_id'])) {
            $postId = intval($_POST['like_post_id']);

            // Check if the user already liked the post
            $checkSql = "SELECT likeID FROM likes WHERE uID = ? AND postID = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ii", $uID, $postId);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();

            if($checkRes && $checkRes->num_rows > 0) {
                // Unlike
                $delSql = "DELETE FROM likes WHERE uID = ? AND postID = ?";
                $delStmt = $conn->prepare($delSql);
                $delStmt->bind_param("ii", $uID, $postId);
                $delStmt->execute();
            } else {
                // Like
                $insSql = "INSERT INTO likes (uID, postID) VALUES (?, ?)";
                $insStmt = $conn->prepare($insSql);
                $insStmt->bind_param("ii", $uID, $postId);
                $insStmt->execute();
            }

            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    }

    $posts_query = "SELECT p.postID, p.uID, p.content, p.image, p.createdAt, up.name, up.avatar 
                   FROM posts p 
                   LEFT JOIN user_profile up ON p.uID = up.uID 
                   WHERE p.is_archived = FALSE OR p.is_archived IS NULL
                   ORDER BY p.createdAt DESC";
    $posts_result = $conn->query($posts_query);
    $posts = $posts_result->fetch_all(MYSQLI_ASSOC);

    $likesCount = [];
    $userLiked = [];
    $postIds = array_column($posts, 'postID');
    if (!empty($postIds)) {
        $ids = implode(',', array_map('intval', $postIds));

        $countSql = "SELECT postID, COUNT(*) as cnt FROM likes WHERE postID IN ($ids) GROUP BY postID";
        $countRes = $conn->query($countSql);
        if ($countRes) {
            while ($row = $countRes->fetch_assoc()) {
                $likesCount[$row['postID']] = (int)$row['cnt'];
            }
        }

        $likedSql = "SELECT postID FROM likes WHERE uID = ? AND postID IN ($ids)";
        $likedStmt = $conn->prepare($likedSql);
        $likedStmt->bind_param("i", $uID);
        $likedStmt->execute();
        $likedRes = $likedStmt->get_result();
        if ($likedRes) {
            while ($r = $likedRes->fetch_assoc()) {
                $userLiked[(int)$r['postID']] = true;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="stylesheet" href="home.css" />
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
            <?php foreach($posts as $post): ?>
                <div class="post-card">
                    <div class="post-header">
                        <div class="user-avatar">
                            <?php echo !empty($post['avatar']) ? "<img src='{$post['avatar']}' alt='Profile'>" : ""; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($post['name']); ?></span>
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
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="like_post_id" value="<?php echo $post['postID']; ?>">
                            <button type="submit" class="like-btn">
                                <?php echo !empty($userLiked[$post['postID']]) ? 'Unlike' : 'Like'; ?>
                            </button>
                        </form>
                        <span class="like-count" style="margin-left:8px; color:#9ba0a8;">
                            <?php echo isset($likesCount[$post['postID']]) ? $likesCount[$post['postID']] : 0; ?>
                        </span>
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
