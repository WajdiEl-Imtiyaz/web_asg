<?php
    session_start();
    require '../db.php';
    
    // Clear form data if coming from back button
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
                if(password_verify($password, $row['uPassword'])) {
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
                // Check if email already exists
                $check_sql = "SELECT uEmail FROM users WHERE uEmail = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $email);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if(mysqli_num_rows($check_result) > 0){
                    $error = "Email address already registered. Please use a different email.";
                } else {
                    // Hash password before storing
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
    <!-- Add autocomplete="off" to prevent form data persistence -->
    <script>
        // Prevent form resubmission on page refresh/back
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Clear form on page load
        window.addEventListener('load', function() {
            document.getElementById('login-form').reset();
        });

        // Handle back/forward navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('login-form').reset();
            }
        });
    </script>
</head>

<body>
    <div id="login-dir-btn">
        <button id="show-login-btn">Log in</button>
        <button id="show-signup-btn">Sign up</button>
    </div>
    <form method="POST" id="login-form">
        <div id="email-field">
             <label for="email">Email</label> <!-- add regex pattern for email -->
            <input type="text" id="email" name="email" required>
        </div>
        <div id="password-field">
            <label for="password">Password</label> 
            <input type="password" id="password" name="password" required>
        </div>
        <div id="confirm-field" style="display: none;">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm">
        </div>
        <input type="submit" name="action" id="login-btn" value="Login">
        <input type="submit" name="action" id="signup-btn" value="Sign Up">
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

