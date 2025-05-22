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

// Query to get recent orders for the user
$orders_sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status,
               p.product_name, pc.quantity, p.price
               FROM orders o
               JOIN cart c ON o.cart_id = c.cart_id
               JOIN product_cart pc ON c.cart_id = pc.cart_id
               JOIN product p ON pc.product_id = p.product_id
               WHERE o.user_id = :user_id
               ORDER BY o.order_date DESC";
$orders_stid = oci_parse($db, $orders_sql);
oci_bind_by_name($orders_stid, ":user_id", $user_id);

if (!oci_execute($orders_stid)) {
    echo "Failed to fetch orders.";
    exit();
}

$recent_orders = [];
$counter = 0;
while ($row = oci_fetch_assoc($orders_stid)) {
    if ($counter >= 3) break;
    $recent_orders[] = $row;
    $counter++;
}
oci_free_statement($orders_stid);

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
    <link rel = "stylesheet" href = "/E-commerce/frontend/assets/CSS/user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Header.css">
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
                
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <span class="order-name"><?php echo htmlspecialchars($order['PRODUCT_NAME']); ?></span>
                            <span class="order-price">$<?php echo number_format($order['PRICE'] * $order['QUANTITY'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="order-item">
                        <span class="order-name">No recent orders</span>
                    </div>
                <?php endif; ?>
                
                <a href="recent_orders.php" class="view-all">
                    View All Orders <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Quick Links Card -->
            <div class="sidebar-card">
                <h3 class="section-title">Quick Links</h3>
                
                <div class="quick-links">
                    <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="quick-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="/E-commerce/frontend/Includes/cart/shopping_cart.php" class="quick-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>My Cart</span>
                    </a>
                    <a href="recent_orders.php" class="quick-link">
                        <i class="fas fa-history"></i>
                        <span>Order History</span>
                    </a>
                    <a href="edit_profile.php" class="quick-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Edit Profile</span>
                    </a>
                    
                    <a href="../includes/logout.php" class="quick-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            
            
        </div>
    </div>
    
    <?php include '../Includes/footer.php'; ?>
</body>
</html>