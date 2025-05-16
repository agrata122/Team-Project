<?php
session_start();

// Check if the user is logged in and is a trader
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    header("Location: login.php");
    exit();
}

// Include the database connection script
require_once 'C:\xampp\htdocs\E-commerce\backend\db_connection.php';

// Fetch trader data from users table
$user_id = $_SESSION['user_id'];
$shops = $_SESSION['shops'] ?? [];
try {
    $query = "SELECT * FROM users WHERE user_id = :user_id AND role = 'trader'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("Trader data not found");
    }

    // COMMENTED: Orders-related queries since `orders` table doesn't exist
    
    $ordersQuery = "SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                    FROM orders o
                    JOIN users u ON o.customer_id = u.user_id
                    ORDER BY o.order_date DESC LIMIT 5";
    $ordersStmt = $db->prepare($ordersQuery);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    $statsQuery = "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
        FROM orders";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $orderStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Dashboard - FresGrub</title>
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
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-title {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--primary);
            color: white;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: var(--warning);
            font-weight: bold;
        }
        
        .status-processing {
            color: var(--secondary);
            font-weight: bold;
        }
        
        .status-completed {
            color: var(--success);
            font-weight: bold;
        }
        
        .status-cancelled {
            color: var(--danger);
            font-weight: bold;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .btn-process {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-complete {
            background-color: var(--success);
            color: white;
        }
        
        .btn-cancel {
            background-color: var(--danger);
            color: white;
        }
        
        .action-form {
            display: inline;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        .shop-links {
            list-style: none;
            padding-left: 0;
        }

        .shop-links li {
            margin-bottom: 8px;
        }

        .shop-links li a {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .shop-links li a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .shop-links li a i {
            margin-right: 8px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
                <a href="#" class="active">
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
                <a href="/E-commerce/frontend/Includes/pages/product_list.php">
                    <i class="fas fa-list"></i>
                    <span>View Products</span>
                </a>
            </li>
            <li>
                <a href="\E-commerce\frontend\user\user_profile.php">
                    <i class="fas fa-user"></i>
                    <span>View Profile</span>
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
            <h2>Trader Dashboard</h2>
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="User">
                <span>Welcome, <?php echo htmlspecialchars($userData['full_name']); ?></span>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Your Shops</h3>
            <?php if (!empty($shops)): ?>
                <ul class="shop-links">
                    <?php foreach ($shops as $shop): ?>
                        <li>
                            <a href="shop.php?id=<?php echo $shop['shop_id']; ?>">
                                <i class="fas fa-store"></i>
                                <?php echo htmlspecialchars($shop['shop_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">You have no shops listed.</p>
            <?php endif; ?>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <h3>Total Orders</h3>
                <div class="metric-value"><?php echo $orderStats['total_orders'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Total Sales</h3>
                <div class="metric-value">$<?php echo number_format($orderStats['total_sales'] ?? 0, 2); ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Completed Orders</h3>
                <div class="metric-value"><?php echo $orderStats['completed_orders'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Pending Orders</h3>
                <div class="metric-value"><?php echo $orderStats['pending_orders'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">Recent Orders</h3>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('m/d/Y', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="action" value="process">
                                            <button type="submit" class="btn btn-process">Process</button>
                                        </form>
                                    <?php elseif ($order['status'] == 'processing'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-complete">Complete</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($order['status'] != 'completed' && $order['status'] != 'cancelled'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-cancel">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No recent orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Highlight active menu item
        document.querySelectorAll('.sidebar-menu a').forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });
    </script>
</body>
</html>