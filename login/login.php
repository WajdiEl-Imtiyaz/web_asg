<?php
    session_start();
    require '../db.php';
    
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
                if($password === $row['uPassword']) {
                    $_SESSION['user'] = $row['uEmail'];
                    $_SESSION['is_admin'] = (bool)$row['is_admin']; // Set admin status in session
                    
                    // Redirect based on admin status
                    if($_SESSION['is_admin']){
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: index.php"); //later change to the location 
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
            $sql = "insert into users (uEmail, uPassword) values (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $email, $password);
            if(mysqli_stmt_execute($stmt)){
                if($password === $confirm){
                    $success = "Account created successfully. Please log in.";
                } else {
                    $error = "Passwords do not match.";
                }   
            } else {
                $error = "Error creating account.";
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

