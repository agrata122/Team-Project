<?php
session_start();
require_once '../../../backend/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = getDBConnection();

    if ($db) {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                // If user is pending, prevent login
                echo "Admin must approve first.";
                exit();
            }

            // Store user session details
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: ../../admin/admindashboard.php");
            } elseif ($user['role'] == 'trader') {
                header("Location: trader-dashboard.php");
            } elseif ($user['role'] == 'customer') {
                header("Location: homepage.php");
            }
            exit();
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Database connection failed.";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FresGrub Login</title>
    <link rel="stylesheet" href="../../assets/CSS/LoginPage.css">
</head>
<body>
    <div class="container">
        <div class="login-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Log in →</h2>
             
            <form action="" method="POST">  <!-- ADD FORM TAG -->
    <div class="form-group">
        <label>Email address or user name</label>
        <input type="text" id="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="form-group">
        <label>Password</label>
        <div class="password-container">
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <span class="password-toggle" id="passwordToggle" type="button">Show</span>

        </div>
    </div>
    <div class="options">
        <label for="remember" class="remember-label">
            <input type="checkbox" id="remember" name="remember"> Remember me
        </label>
        <a href="forgot-password.php">Forgot your password?</a>
    </div>
    <button type="submit">LOG IN</button>
</form>  

            <p class="signup-link">Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
        <div class="image-side">
            <img src="../../assets/Images/login-picture.png" alt="Fresh Grocery">
        </div>
    </div>

    <script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    passwordToggle.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordToggle.textContent = 'Hide';
        } else {
            passwordInput.type = 'password';
            passwordToggle.textContent = 'Show';
        }
    });
});

    </script>
</body>
</html>