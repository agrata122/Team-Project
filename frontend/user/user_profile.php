<?php
session_start();
require_once '../../backend/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDBConnection();

if (!$db) {
    echo "Database connection failed.";
    exit();
}

// Prepare and execute the SELECT query
$sql = "SELECT full_name, email, role, created_date, status FROM users WHERE user_id = :user_id";
$stid = oci_parse($db, $sql);
oci_bind_by_name($stid, ":user_id", $user_id);

if (!oci_execute($stid)) {
    echo "Query execution failed.";
    exit();
}

// Fix: Oracle returns column names in uppercase by default
$user = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$user) {
    echo "User not found.";
    exit();
}

// Determine the home page based on user role
// Fixed to use uppercase key "ROLE"
$homePage = ($user["ROLE"] === "trader") ? "trader_dashboard.php" : "home.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - FresGrub</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .welcome-message {
            font-size: 18px;
            color: #555;
        }

        .date {
            color: #777;
            font-size: 14px;
        }

        /* Main Profile Card */
        .main-profile {
            flex: 2;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e9f5eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 50px;
            color: #28a745;
            font-weight: bold;
        }

        .profile-name {
            font-size: 28px;
            margin: 10px 0 5px;
            color: #333;
            text-align: center;
        }

        .profile-role {
            background-color: #e9f5eb;
            color: #28a745;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .profile-details {
            width: 100%;
            max-width: 500px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            flex: 1;
        }

        .detail-value {
            color: #333;
            flex: 2;
            text-align: right;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
            min-width: 150px;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Sidebar */
        .sidebar {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }

        .section-title {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-name {
            color: #333;
        }

        .order-price {
            font-weight: bold;
            color: #28a745;
        }

        .quick-links {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: #e2e6ea;
            transform: translateX(5px);
        }

        .quick-link i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .view-all {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
</head>
<body>
    <header>
        <?php include 'C:\xampp\htdocs\E-commerce\frontend\Includes\header.php'; ?>
    </header>
    
    <div class="container">
        <!-- Main Profile Section -->
        <div class="main-profile">
            <div class="profile-avatar">
                <?php echo substr(htmlspecialchars($user['FULL_NAME'] ?? ''), 0, 1); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['FULL_NAME'] ?? ''); ?></h1>
            <div class="profile-role"><?php echo htmlspecialchars(ucfirst($user['ROLE'] ?? '')); ?></div>
            
            <div class="profile-details">
                <div class="detail-row">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['EMAIL'] ?? ''); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Member Since</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['CREATED_DATE'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Account Status</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['STATUS'] ?? ''); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="edit_profile.php" class="btn btn-primary">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <a href="../includes/logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Sidebar Section -->
        <div class="sidebar">
            <!-- Recent Orders Card -->
            <div class="sidebar-card">
                <h3 class="section-title">Recent Orders</h3>
                
                <div class="order-item">
                    <span class="order-name">Fresh Mangoes</span>
                    <span class="order-price">$10.99</span>
                </div>
                
                <div class="order-item">
                    <span class="order-name">Yellow Bananas</span>
                    <span class="order-price">$8.50</span>
                </div>
                
                <div class="order-item">
                    <span class="order-name">Watermelons</span>
                    <span class="order-price">$20.50</span>
                </div>
                
                <a href="all_orders.php" class="view-all">
                    View All Orders <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Quick Links Card -->
            <div class="sidebar-card">
                <h3 class="section-title">Quick Links</h3>
                
                <div class="quick-links">
                    <a href="<?php echo $homePage; ?>" class="quick-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="my_cart.php" class="quick-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>My Cart</span>
                    </a>
                    <a href="recent_orders.php" class="quick-link">
                        <i class="fas fa-history"></i>
                        <span>Order History</span>
                    </a>
                    <a href="settings.php" class="quick-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
            
            <!-- Security Card -->
            <div class="sidebar-card">
                <h3 class="section-title">Account Security</h3>
                <a href="change_password.php" class="quick-link">
                    <i class="fas fa-lock"></i>
                    <span>Change Password</span>
                </a>
                <a href="two_factor.php" class="quick-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Two-Factor Auth</span>
                </a>
            </div>
        </div>
    </div>
    
    <?php include '../Includes/footer.php'; ?>
</body>
</html>