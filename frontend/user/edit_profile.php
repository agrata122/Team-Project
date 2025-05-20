<?php
session_start();
require_once '../../backend/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /E-commerce/frontend/Includes/pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    $db = getDBConnection();

    // Fetch current user data including password
    $sql = "SELECT user_id, full_name, email, password FROM users WHERE user_id = :user_id";
    $stid = oci_parse($db, $sql);
    oci_bind_by_name($stid, ":user_id", $user_id);
    oci_execute($stid);
    $user = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if (empty($full_name) || empty($email)) {
            throw new Exception("Name and email are required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if email exists for another user
        $check_sql = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
        $check_stid = oci_parse($db, $check_sql);
        oci_bind_by_name($check_stid, ":email", $email);
        oci_bind_by_name($check_stid, ":user_id", $user_id);
        oci_execute($check_stid);

        if (oci_fetch($check_stid)) {
            oci_free_statement($check_stid);
            throw new Exception("This email is already registered.");
        }
        oci_free_statement($check_stid);

        // Start manual transaction
        oci_execute(oci_parse($db, "BEGIN"), OCI_NO_AUTO_COMMIT);

        try {
            // Only verify password if trying to change it
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    throw new Exception("Current password is required to change your password.");
                }

                // Verify current password
                if (!password_verify($current_password, $user['PASSWORD'])) {
                    throw new Exception("Current password is incorrect. Please try again.");
                }

                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords don't match.");
                }

                if (strlen($new_password) < 8) {
                    throw new Exception("Password must be at least 8 characters long.");
                }

                // Hash and update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pw_sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $update_pw_stid = oci_parse($db, $update_pw_sql);
                oci_bind_by_name($update_pw_stid, ":password", $hashed_password);
                oci_bind_by_name($update_pw_stid, ":user_id", $user_id);
                oci_execute($update_pw_stid, OCI_NO_AUTO_COMMIT);
                oci_free_statement($update_pw_stid);
            }

            // Update name and email
            $update_sql = "UPDATE users SET full_name = :full_name, email = :email WHERE user_id = :user_id";
            $update_stid = oci_parse($db, $update_sql);
            oci_bind_by_name($update_stid, ":full_name", $full_name);
            oci_bind_by_name($update_stid, ":email", $email);
            oci_bind_by_name($update_stid, ":user_id", $user_id);
            oci_execute($update_stid, OCI_NO_AUTO_COMMIT);
            oci_free_statement($update_stid);

            // Commit all changes
            oci_commit($db);
            $success = "Profile updated successfully!";

            // Refresh user data
            $refresh_sql = "SELECT full_name, email FROM users WHERE user_id = :user_id";
            $refresh_stid = oci_parse($db, $refresh_sql);
            oci_bind_by_name($refresh_stid, ":user_id", $user_id);
            oci_execute($refresh_stid);
            $user_refresh = oci_fetch_assoc($refresh_stid);
            oci_free_statement($refresh_stid);
            $user = array_merge($user, $user_refresh);
        } catch (Exception $e) {
            oci_rollback($db);
            throw $e;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - FresGrub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
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
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-dark);
            color: white;
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
            padding: 30px;
            align-items: center;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-title {
            color: var(--primary);
            margin: 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 18px;
            color: var(--primary);
            margin: 25px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #666;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 15px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        .alert-error {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            color: #c62828;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: var(--success);
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
        }
        
        .btn-secondary {
            background-color: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .info-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <?php include 'C:\xampp\htdocs\E-commerce\frontend\Includes\header.php'; ?>
    </header>
    
    <div class="dashboard-container">
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="profile-header">
                <h1 class="profile-title">Edit Profile</h1>
            </div>
            
            <div class="profile-card">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Basic Information
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['FULL_NAME']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-lock"></i> Password Change
                    </div>
                    
                    <p class="info-text">Leave blank to keep current password</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-container">
                            <input type="password" id="current_password" name="current_password" class="form-control">
                            <span class="password-toggle" onclick="togglePassword('current_password', this)">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <span class="password-toggle" onclick="togglePassword('new_password', this)">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            <span class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="User_Profile.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, toggleElement) {
            const field = document.getElementById(fieldId);
            const icon = toggleElement.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && !document.getElementById('current_password').value) {
                alert('Please enter your current password to change your password.');
                e.preventDefault();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                e.preventDefault();
                return;
            }
            
            if (newPassword && newPassword.length < 8) {
                alert('Password must be at least 8 characters long.');
                e.preventDefault();
                return;
            }
        });
    </script>
    <?php include '../Includes/footer.php'; ?>
</body>
</html>