<?php
    session_start();
    require '../db.php';
    
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $email = $_POST['email'];
        $password = $_POST['password'];
        $action = $_POST['action'];
            
        if($action === 'Login'){
            $sql = "SELECT * FROM users WHERE uEmail = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

        if($row = mysqli_fetch_assoc($result)){
            if ($row['is_banned']) {
                $error = "This account has been banned. Please contact administrator.";
            } else if (password_verify($password, $row['uPassword'])) {
                $_SESSION['user'] = $row['uEmail'];
                $_SESSION['is_admin'] = (bool)$row['is_admin'];
                
                if($_SESSION['is_admin']){
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../home/home.php"); 
                }
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No user found with that email.";
        }
    }
    
    if($action === 'Sign Up'){
        $email = $_POST['email'];
        $confirm = $_POST['confirm'];
        
        if($password === $confirm){
            $check_sql = "SELECT uEmail FROM users WHERE uEmail = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if(mysqli_num_rows($check_result) > 0){
                $error = "Email address already registered. Please use a different email.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (uEmail, uPassword) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);
                
                if(mysqli_stmt_execute($stmt)){
                    $success = "Account created successfully. Please log in.";
                } else {
                    $error = "Error creating account.";
                }
            }
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .comet-sphere-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #4ca9c2;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(76, 169, 194, 0.5);
            letter-spacing: 1px;
        }
        
        #login-dir-btn {
            width: 85%;
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        #login-dir-btn button {
            width: 100px;
            height: 35px;
            border-radius: 5px;
            border: 2px solid transparent;
            background-color: #1c2433;
            color: #e8e9ed;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        #login-dir-btn button:hover {
            border-color: #4ca9c2;
            color: #4ca9c2;
        }
        
        #login-dir-btn button.active {
            background-color: rgba(76, 169, 194, 0.2);
            color: #4ca9c2;
            border: 2px solid #4ca9c2;
            transform: translateY(-2px);
        }
    </style>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        window.addEventListener('load', function() {
            document.getElementById('login-form').reset();
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('login-form').reset();
            }
        });
    </script>
</head>

<body>
    <div class="comet-sphere-title">Comet Sphere</div>
    
    <form method="POST" id="login-form">
        <div id="login-dir-btn">
            <button type="button" id="show-login-btn" class="active">Log in</button>
            <button type="button" id="show-signup-btn">Sign up</button>
        </div>
        
        <div id="email-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
        </div>
        <div id="password-field">
            <label for="password">Password</label> 
            <input type="password" id="password" name="password" required>
        </div>
        <div id="confirm-field" style="display: none;">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm">
        </div>
        
        <input type="submit" name="action" id="login-btn" value="Login" style="display: block;">
        <input type="submit" name="action" id="signup-btn" value="Sign Up" style="display: none;">
        
        <?php 
        if (isset($success)) {
            echo "<p style='color:green'>$success</p>";
        } 
        if (isset($error)) {
            echo "<p style='color:red'>$error</p>"; 
        }
        ?>
    </form>

    <script src="login.js"></script>
</body>

</html>