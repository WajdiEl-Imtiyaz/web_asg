<?php
session_start();
require '../db.php';

function getAvatarPath($avatar) {
    if (empty($avatar)) {
        return '';
    }
    
    if (strpos($avatar, '../') !== 0) {
        return '../' . $avatar;
    }
    
    return $avatar;
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

if (!empty($userProfile['avatar'])) {
    $userProfile['avatar'] = getAvatarPath($userProfile['avatar']);
}

if($result->num_rows === 0) {
    $insert = "INSERT INTO user_profile (uID, name) VALUES (?, ?)";
    $stmt = $conn->prepare($insert);
    $name = explode('@', $email)[0];
    $stmt->bind_param("is", $uID, $name);
    $stmt->execute();
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userProfile = $result->fetch_assoc();
    
    if (!empty($userProfile['avatar'])) {
        $userProfile['avatar'] = getAvatarPath($userProfile['avatar']);
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $bio = $_POST['bio'];
    
    $avatar = $userProfile['avatar']; 
    
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/';
        
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
        
        if(in_array($fileType, $allowedTypes)) {
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                $avatar = 'uploads/avatars/' . $fileName;
                
                if(!empty($userProfile['avatar']) && file_exists('../' . $userProfile['avatar'])) {
                    unlink('../' . $userProfile['avatar']);
                }
            }
        }
    }
    
    $update = "UPDATE user_profile SET name = ?, age = ?, bio = ?, avatar = ? WHERE uID = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("sissi", $name, $age, $bio, $avatar, $uID);
    
    if($stmt->execute()) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $uID);
        $stmt->execute();
        $result = $stmt->get_result();
        $userProfile = $result->fetch_assoc();
        
        if (!empty($userProfile['avatar'])) {
            $userProfile['avatar'] = getAvatarPath($userProfile['avatar']);
        }
        
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile.";
    }
}

$posts_query = "SELECT p.postID, p.content, p.image, p.createdAt, p.is_archived
                FROM posts p 
                WHERE p.uID = ? AND (p.is_archived = FALSE OR p.is_archived IS NULL)
                ORDER BY p.createdAt DESC";
$stmt = $conn->prepare($posts_query);
$stmt->bind_param("i", $uID);
$stmt->execute();
$posts_result = $stmt->get_result();
$user_posts = $posts_result->fetch_all(MYSQLI_ASSOC);

$post_count = count($user_posts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile</title>
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="profile.css" />
</head>
<body>
    
    <div class="container">
        <div class="left-sidebar">
            <div class="comet-sphere-title">Comet Sphere</div>
            <nav class="menu">
                <a href="home.php">Home</a>
                <a href="">Explore</a>
                <a href="">Notification</a>
                <a href="profile.php" class="active">Profile</a>
            </nav>
            <a href="?logout=1" class="logout">Logout</a>
        </div>

        <main>
            <header><h1>Profile</h1></header>
            <hr id="header-separator">

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-top-row">
                        <div class="profile-avatar">
                            <?php 
                                if (!empty($userProfile['avatar'])) {
                                    echo "<img src='{$userProfile['avatar']}' alt='Profile Avatar'>";
                                } else {
                                    $initial = !empty($userProfile['name']) ? substr($userProfile['name'], 0, 1) : 'U';
                                    echo "<div class='default-avatar'>$initial</div>";
                                }
                            ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name-row">
                                <h1 class="profile-name"><?php echo htmlspecialchars($userProfile['name']); ?></h1>
                                <button class="edit-profile-btn" onclick="openEditModal()">Edit Profile</button>
                            </div>
                            <?php if (!empty($userProfile['bio'])): ?>
                                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($userProfile['bio'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="user-posts-section">
                    <h2 class="section-title">My Posts (<?php echo $post_count; ?>)</h2>
                    
                    <div class="post-card-list">
                        <?php if (!empty($user_posts)): ?>
                            <?php foreach($user_posts as $post): ?>
                                <div class="post-card">
                                    <div class="post-header">
                                        <div class="user-avatar">
                                            <?php 
                                                if (!empty($userProfile['avatar'])) {
                                                    echo "<img src='{$userProfile['avatar']}' alt='Profile'>";
                                                } else {
                                                    $initial = !empty($userProfile['name']) ? substr($userProfile['name'], 0, 1) : 'U';
                                                    echo "<div class='default-avatar'>$initial</div>";
                                                }
                                            ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($userProfile['name']); ?></span>
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
                                        <button class="like-btn">like</button>
                                        <button class="comment-btn">comment</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-posts">
                                <p>You haven't created any posts yet.</p>
                                <p>Share your thoughts with the community!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <div class="right-sidebar">
            <input type="search" id="search" placeholder="Search..." />
            <h2>Trending</h2>
            <nav class="menu">
                <a href="home.php">#Home</a>
                <a href="">#Explore</a>
                <a href="">#Notification</a>
                <a href="profile.php">#Profile</a>
            </nav>
        </div>
    </div>

    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">Profile Picture</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                    <div class="avatar-preview-container">
                        <?php 
                            if (!empty($userProfile['avatar'])) {
                                echo "<img src='{$userProfile['avatar']}' alt='Current Avatar' class='avatar-preview' id='avatarPreview'>";
                            } else {
                                $initial = !empty($userProfile['name']) ? substr($userProfile['name'], 0, 1) : 'U';
                                echo "<div class='avatar-preview default-avatar' id='avatarPreview'>$initial</div>";
                            }
                        ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">Display Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userProfile['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" value="<?php echo !empty($userProfile['age']) ? htmlspecialchars($userProfile['age']) : ''; ?>" min="1" max="120">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo !empty($userProfile['bio']) ? htmlspecialchars($userProfile['bio']) : ''; ?></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_profile" class="edit-profile-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bottom-sidebar">
            <nav class="menu">
                <a href="home.php">Home</a>
                <a href="profile.php">Profile</a>
                <a href="?logout=1" class="logout">Logout</a>
            </nav>
            
        </div>

    <script>
        function openEditModal() {
            document.getElementById('editProfileModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }
        
        function previewAvatar(input) {
            const preview = document.getElementById('avatarPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.classList.contains('default-avatar')) {
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Avatar Preview';
                        newImg.className = 'avatar-preview';
                        newImg.id = 'avatarPreview';
                        preview.parentNode.replaceChild(newImg, preview);
                    } else {
                        preview.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>