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

// Get user ID
$sql = "SELECT uID FROM users WHERE uEmail = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$uID = $user['uID'];

// Get or create user profile
$sql = "SELECT * FROM user_profile WHERE uID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uID);
$stmt->execute();
$result = $stmt->get_result();
$userProfile = $result->fetch_assoc();

// If no profile exists, create a basic one
if($result->num_rows === 0) {
    $insert = "INSERT INTO user_profile (uID, name) VALUES (?, ?)";
    $stmt = $conn->prepare($insert);
    $name = explode('@', $email)[0]; // Use username part of email as default name
    $stmt->bind_param("is", $uID, $name);
    $stmt->execute();
    
    // Fetch the newly created profile
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userProfile = $result->fetch_assoc();
}

// Handle profile updates
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $bio = $_POST['bio'];
    
    // Handle avatar upload
    $avatar = $userProfile['avatar']; // Keep existing avatar by default
    
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/';
        
        // Create directory if it doesn't exist
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
        
        if(in_array($fileType, $allowedTypes)) {
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                $avatar = 'uploads/avatars/' . $fileName;
                
                // Delete old avatar if it exists
                if(!empty($userProfile['avatar']) && file_exists('../' . $userProfile['avatar'])) {
                    unlink('../' . $userProfile['avatar']);
                }
            }
        }
    }
    
    // Update profile in database
    $update = "UPDATE user_profile SET name = ?, age = ?, bio = ?, avatar = ? WHERE uID = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("sissi", $name, $age, $bio, $avatar, $uID);
    
    if($stmt->execute()) {
        // Refresh profile data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $uID);
        $stmt->execute();
        $result = $stmt->get_result();
        $userProfile = $result->fetch_assoc();
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile.";
    }
}

// Get user's posts for profile
$posts_query = "SELECT p.postID, p.content, p.image, p.createdAt, p.is_archived
                FROM posts p 
                WHERE p.uID = ? AND (p.is_archived = FALSE OR p.is_archived IS NULL)
                ORDER BY p.createdAt DESC";
$stmt = $conn->prepare($posts_query);
$stmt->bind_param("i", $uID);
$stmt->execute();
$posts_result = $stmt->get_result();
$user_posts = $posts_result->fetch_all(MYSQLI_ASSOC);

// Get post count
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
            <h2>Logo</h2>
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

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="profile-container">
    <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-top-row">
                        <img src="<?php echo !empty($userProfile['avatar']) ? '../' . htmlspecialchars($userProfile['avatar']) : 'https://via.placeholder.com/80/1c2433/4ca9c2?text=USER'; ?>" 
                            alt="Profile Avatar" class="profile-avatar">
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

                <!-- User's Posts -->
                <div class="user-posts-section">
                    <h2 class="section-title">My Posts (<?php echo $post_count; ?>)</h2>
                    
                    <div class="post-card-list">
                        <?php if (!empty($user_posts)): ?>
                            <?php foreach($user_posts as $post): ?>
                                <div class="post-card">
                                    <div class="post-header">
                                        <div class="user-avatar">
                                            <img src="<?php echo !empty($userProfile['avatar']) ? '../' . htmlspecialchars($userProfile['avatar']) : 'https://via.placeholder.com/40/1c2433/4ca9c2?text=USER'; ?>" alt="Profile">
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
                <a href="../home.php">#Home</a>
                <a href="">#Explore</a>
                <a href="">#Notification</a>
                <a href="/profile/profile.php">#Profile</a>
            </nav>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">Profile Picture</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                    <?php if (!empty($userProfile['avatar'])): ?>
                        <img src="../<?php echo htmlspecialchars($userProfile['avatar']); ?>" alt="Current Avatar" class="avatar-preview" id="avatarPreview">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/100/1c2433/4ca9c2?text=USER" alt="Avatar Preview" class="avatar-preview" id="avatarPreview">
                    <?php endif; ?>
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
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>