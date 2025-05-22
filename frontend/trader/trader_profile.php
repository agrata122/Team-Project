<?php
session_start();

// Check if the user is logged in and is a trader
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    header("Location: login.php");
    exit();
}

// Include the Oracle DB connection
require_once 'C:\xampp\htdocs\E-commerce\backend\connect.php';

// Get a valid Oracle connection
$conn = getDBConnection();

if (!$conn) {
    die("Failed to connect to Oracle database.");
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // First verify current password
        $verifyQuery = "SELECT password FROM users WHERE user_id = :user_id";
        $verifyStmt = oci_parse($conn, $verifyQuery);
        oci_bind_by_name($verifyStmt, ':user_id', $user_id);
        oci_execute($verifyStmt);
        
        if (oci_fetch($verifyStmt)) {
            $stored_password = oci_result($verifyStmt, 'PASSWORD');
            
            if (password_verify($current_password, $stored_password)) {
                // Update user information
                $updateQuery = "UPDATE users SET 
                    full_name = :full_name,
                    email = :email,
                    contact_no = :phone";
                
                // If new password is provided and matches confirmation
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $updateQuery .= ", password = :password";
                    } else {
                        throw new Exception("New passwords do not match!");
                    }
                }
                
                $updateQuery .= " WHERE user_id = :user_id";
                
                $updateStmt = oci_parse($conn, $updateQuery);
                oci_bind_by_name($updateStmt, ':full_name', $full_name);
                oci_bind_by_name($updateStmt, ':email', $email);
                oci_bind_by_name($updateStmt, ':phone', $phone);
                oci_bind_by_name($updateStmt, ':user_id', $user_id);
                
                if (!empty($new_password)) {
                    oci_bind_by_name($updateStmt, ':password', $hashed_password);
                }
                
                if (oci_execute($updateStmt)) {
                    $message = "Profile updated successfully!";
                } else {
                    throw new Exception("Failed to update profile.");
                }
            } else {
                throw new Exception("Current password is incorrect!");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch current user data
try {
    $query = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ':user_id', $user_id);
    
    // Define columns
    oci_define_by_name($stmt, 'FULL_NAME', $full_name_col);
    oci_define_by_name($stmt, 'EMAIL', $email_col);
    oci_define_by_name($stmt, 'CONTACT_NO', $phone_col);
    
    oci_execute($stmt);
    
    $userData = [];
    if (oci_fetch($stmt)) {
        $userData = [
            'FULL_NAME' => $full_name_col,
            'EMAIL' => $email_col,
            'PHONE' => $phone_col
        ];
    }
} catch (Exception $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Close the connection
if ($conn) oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Profile - FresGrub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #005005;
            --secondary: #0288d1;
            --dark: #263238;
            --light: #f5f7fa;
            --success: #388e3c;
            --warning: #f57c00;
            --danger: #d32f2f;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-dark);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--primary);
            border-left: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            background-color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: var(--success);
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: var(--danger);
            border: 1px solid #ffcdd2;
        }
        
        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .password-section h3 {
            margin-bottom: 20px;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span>FresGrub</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="traderdashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="add_product.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Products</span>
                </a>
            </li>
            <li>
                <a href="My_products.php">
                    <i class="fas fa-list"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li>
                <a href="Daily_reports.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Daily Reports</span>
                </a>
            </li>
            <li>
                <a href="Weekly_reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Weekly Reports</span>
                </a>
            </li>
            <li>
                <a href="Monthly_reports.php">
                    <i class="fas fa-chart-area"></i>
                    <span>Monthly Reports</span>
                </a>
            </li>
            <li>
                <a href="Invoice.php">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoice</span>
                </a>
            </li>
            <li>
                <a href="trader_profile.php" class="active">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="logout_trader.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>My Profile</h2>
        </div>

        <div class="profile-container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?php echo htmlspecialchars($userData['FULL_NAME']); ?></h2>
                <p>Trader Account</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['FULL_NAME']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['EMAIL']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['PHONE']); ?>" required>
                </div>

                <div class="password-section">
                    <h3>Change Password</h3>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html> 