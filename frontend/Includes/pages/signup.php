<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FresGrub Sign Up</title>
    <link rel="stylesheet" href="../../assets/CSS/SignupPage.css">
</head>
<body>
    <div class="container">
        <div class="signup-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Create New Account</h2>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <div class="phone-container">
                        <div class="country-code">
                            <input type = "text" id="contry-code" required>
                        </div>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <span class="password-toggle">Hide</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirm-password" name="confirm-password" required>
                        <span class="password-toggle">Hide</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sign-up As</label>
                    <div class="radio-options">
                        <div class="radio-option">
                            <input type="radio" id="customer" name="user-type" value="customer" checked>
                            <label for="customer">Customer</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="trader" name="user-type" value="trader">
                            <label for="trader">Trader</label>
                        </div>
                    </div>
                </div>

                <div class="terms-container">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="terms.php">Terms and Conditions</a></label>
                </div>

                <button type="submit">SIGN UP</button>
            </form>
            <p class="login-link">Already have an account? <a href="login.php">Log In</a></p>
        </div>
        <div class="image-side">
            <img src="../../assets/Images/login-picture.png" alt="Fresh Grocery">
        </div>
    </div>
</body>
</html>