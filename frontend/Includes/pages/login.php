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
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Email address or user name</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <span class="password-toggle">Hide</span>
                    </div>
                </div>
                <div class="options">
                    <label for="remember" class="remember-label">
                        <span>
                            <input type="checkbox" id="remember" name="remember">
                            Remember me
                        </span>
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
</body>
</html>