<?php
session_start();
require_once '../../../backend/connect.php';

$error = '';
$success = '';
$valid_token = false;
$email = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $conn = getDBConnection();
    
    if ($conn) {
        // Check if token exists and is not expired
        $sql = "SELECT email FROM users WHERE verification_code = :token";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":token", $token);
        
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            
            if ($row) {
                $valid_token = true;
                $email = $row['EMAIL'];
            } else {
                $error = "Invalid or expired reset token.";
            }
        } else {
            $e = oci_error($stmt);
            $error = "Database error: " . $e['message'];
        }
        
        oci_free_statement($stmt);
        oci_close($conn);
    } else {
        $error = "Database connection failed.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $conn = getDBConnection();
        
        if ($conn) {
            // Update password and clear the verification code
            $sql = "UPDATE users SET password = :password, verification_code = NULL WHERE email = :email";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":password", $hashed_password);
            oci_bind_by_name($stmt, ":email", $email);
            
            if (oci_execute($stmt)) {
                $success = "Your password has been reset successfully. You can now <a href='login.php'>log in</a> with your new password.";
                $valid_token = false; // Token is now invalid after use
            } else {
                $e = oci_error($stmt);
                $error = "Failed to update password: " . $e['message'];
            }
            
            oci_free_statement($stmt);
            oci_close($conn);
        } else {
            $error = "Database connection failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FresGrub</title>
    <link rel="stylesheet" href="../../assets/CSS/LoginPage.css">
    <link rel="stylesheet" href="../../assets/CSS/reset_password.css">
</head>
<body>
    <div class="container">
        <div class="login-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="message error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token && !$success): ?>
                <form action="" method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Enter new password" required>
                            <span class="password-toggle" id="passwordToggle">Show</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                            <span class="password-toggle" id="confirmPasswordToggle">Show</span>
                        </div>
                    </div>
                    
                    <button type="submit">RESET PASSWORD</button>
                </form>
            <?php elseif (!$success): ?>
                <p>Please request a new password reset link from the <a href="forgot-password.php">forgot password page</a>.</p>
            <?php endif; ?>
            
            <p class="signup-link">Remember your password? <a href="login.php">Log in</a></p>
        </div>
        <div class="image-side">
            <img src="../../assets/Images/login-picture.png" alt="Fresh Grocery">
        </div>
    </div>

    <script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');

        if (passwordToggle) {
            passwordToggle.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordToggle.textContent = 'Hide';
                } else {
                    passwordInput.type = 'password';
                    passwordToggle.textContent = 'Show';
                }
            });
        }

        if (confirmPasswordToggle) {
            confirmPasswordToggle.addEventListener('click', function() {
                if (confirmPasswordInput.type === 'password') {
                    confirmPasswordInput.type = 'text';
                    confirmPasswordToggle.textContent = 'Hide';
                } else {
                    confirmPasswordInput.type = 'password';
                    confirmPasswordToggle.textContent = 'Show';
                }
            });
        }
    });
    </script>
</body>
</html>