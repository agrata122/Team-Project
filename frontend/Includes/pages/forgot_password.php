<?php
session_start();
require_once '../../../backend/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    $conn = getDBConnection();
    
    if ($conn) {
        // Check if email exists
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":email", $email);
        
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            
            if ($row) {
                // Generate a unique token
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
                
                // Store token in database (we'll use verification_code column)
                $update_sql = "UPDATE users SET verification_code = :token WHERE email = :email";
                $update_stmt = oci_parse($conn, $update_sql);
                oci_bind_by_name($update_stmt, ":token", $token);
                oci_bind_by_name($update_stmt, ":email", $email);
                
                if (oci_execute($update_stmt)) {
                    // In a real application, you would send an email with the reset link
                    // For this example, we'll just display the link
                    $reset_link = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/reset-password.php?token=$token";
                    
                    $_SESSION['reset_message'] = "A Password reset link has been generated: <a href='$reset_link'>$reset_link</a>";
                    header("Location: forgot_password.php");
                    exit();
                } else {
                    $e = oci_error($update_stmt);
                    $_SESSION['reset_error'] = "Failed to generate reset token: " . $e['message'];
                }
                
                oci_free_statement($update_stmt);
            } else {
                $_SESSION['reset_error'] = "No account found with that email address.";
            }
        } else {
            $e = oci_error($stmt);
            $_SESSION['reset_error'] = "Database error: " . $e['message'];
        }
        
        oci_free_statement($stmt);
        oci_close($conn);
    } else {
        $_SESSION['reset_error'] = "Database connection failed.";
    }
    
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FresGrub</title>
    <link rel="stylesheet" href="../../assets/CSS/LoginPage.css">
    <link rel="stylesheet" href="../../assets/CSS/forgot_password.css">
</head>
<body>
    <div class="container">
        <div class="login-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Forgot Password</h2>
            
            <?php if (isset($_SESSION['reset_message'])): ?>
                <div class="message success">
                    <?php echo $_SESSION['reset_message']; ?>
                    <?php unset($_SESSION['reset_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['reset_error'])): ?>
                <div class="message error">
                    <?php echo $_SESSION['reset_error']; ?>
                    <?php unset($_SESSION['reset_error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit">SEND RESET LINK</button>
            </form>  
            
            <p class="signup-link">Remember your password? <a href="login.php">Log in</a></p>
        </div>
        <div class="image-side">
            <img src="../../assets/Images/login-picture.png" alt="Fresh Grocery">
        </div>
    </div>
</body>
</html>