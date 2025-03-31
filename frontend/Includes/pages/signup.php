<?php
session_start();
$email = $_POST['email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FresGrub Sign Up</title>
    <link rel="stylesheet" href="../../assets/CSS/SignupPage.css">
    <script>
        function toggleTraderFields() {
            var userType = document.getElementById("user-type").value;
            var traderFields = document.getElementById("trader-fields");
            
            if (userType === "trader") {
                traderFields.style.display = "block";
            } else {
                traderFields.style.display = "none";
            }
        }

        // Add password validation
        function validatePassword() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm-password").value;
            var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            
            if (!passwordRegex.test(password)) {
                alert("Password must be at least 8 characters long and include uppercase, lowercase, and numbers.");
                return false;
            }
            
            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            window.onload = function () {
            toggleTraderFields();
};

            
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="signup-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Create New Account</h2>
            <form action="../registerprocess.php" method="POST" onsubmit="return validatePassword()">
            <!-- <form action="loginprocess.php" method="POST" onsubmit="return validatePassword()"> -->
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" id="email" name="email" placeholder="someone@gmail.com" value="<?php echo htmlspecialchars($email); ?>" required>

                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" name="password" 
                           
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                           title="Must be at least 8 characters long, Uppercase letters and numbers included" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" 
                            required>
                </div>
                
                <div class="form-group">
                    <label>Sign-up As</label>
                    <select id="user-type" name="user-type" onchange="toggleTraderFields()" required>
                        <option value="customer">Customer</option>
                        <option value="trader">Trader</option>
                    </select>
                </div>
                
                <!-- Extra fields for Traders -->
                <div id="trader-fields" style="display: none;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="butcher">Butcher</option>
                            <option value="greengrocer">Greengrocer</option>
                            <option value="fishmonger">Fishmonger</option>
                            <option value="bakery">Bakery</option>
                            <option value="delicatessen">Delicatessen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Shop Name</label>
                        <input type="text" name="shop_name" placeholder="Enter your shop name">
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