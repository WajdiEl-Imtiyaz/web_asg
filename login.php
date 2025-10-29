<?php
    session_start();
    require 'db.php';
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $email = $_POST['email'];
        $password = $_POST['password'];
        $sql = "SELECT * FROM users WHERE uEmail = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($row = mysqli_fetch_assoc($result)){
            if($password === $row['uPassword']) {
                $_SESSION['user'] = $row['uEmail'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No user found with that email.";
        }
        
    }
?>

<form method="POST" id="loginForm">
    <label for="email">Email</label>
    <input type="text" id="email" name="email" required>
    <br>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <br>
    <input type="submit" value="Login">
    <p>Forgot password?</p>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
</form>