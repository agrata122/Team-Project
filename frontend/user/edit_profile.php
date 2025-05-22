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
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/edit_profile.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Header.css">
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