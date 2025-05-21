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

// Fetch trader data from users table
$user_id = $_SESSION['user_id'];
$shops = $_SESSION['shops'] ?? [];

try {
    // Fetch user data
    $query = "SELECT * FROM users WHERE user_id = :user_id AND role = 'trader'";
    $stmt = oci_parse($conn, $query);
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement: " . oci_error($conn)['message']);
    }
    
    oci_bind_by_name($stmt, ':user_id', $user_id);
    
    // Define columns before execution
    oci_define_by_name($stmt, 'USER_ID', $user_id_col);
    oci_define_by_name($stmt, 'FULL_NAME', $full_name_col);
    oci_define_by_name($stmt, 'EMAIL', $email_col);
    oci_define_by_name($stmt, 'ROLE', $role_col);
    
    $result = oci_execute($stmt);
    if (!$result) {
        throw new Exception("Failed to execute query: " . oci_error($stmt)['message']);    
    }
    
    // Fetch the data
    $userData = [];
    if (oci_fetch($stmt)) {
        $userData = [
            'USER_ID' => $user_id_col,
            'FULL_NAME' => $full_name_col,
            'EMAIL' => $email_col,
            'ROLE' => $role_col
        ];
    }
    
    if (empty($userData)) {
        throw new Exception("Trader data not found");
    }

    // Fetch trader's shops if not already in session
    if (empty($shops)) {
        $shopsQuery = "SELECT * FROM shops WHERE trader_id = :trader_id";
        $shopsStmt = oci_parse($conn, $shopsQuery);
        oci_bind_by_name($shopsStmt, ':trader_id', $user_id);
        
        // Define columns for shops
        oci_define_by_name($shopsStmt, 'SHOP_ID', $shop_id_col);
        oci_define_by_name($shopsStmt, 'SHOP_NAME', $shop_name_col);
        oci_define_by_name($shopsStmt, 'TRADER_ID', $trader_id_col);
        
        oci_execute($shopsStmt);
        
        $shops = [];
        while (oci_fetch($shopsStmt)) {
            $shops[] = [
                'SHOP_ID' => $shop_id_col,
                'SHOP_NAME' => $shop_name_col,
                'TRADER_ID' => $trader_id_col
            ];
        }
        $_SESSION['shops'] = $shops;
    }

    // Fetch trader's shop category
    $shopCategoryQuery = "SELECT DISTINCT shop_category 
                         FROM shops 
                         WHERE user_id = :user_id";
    $shopCategoryStmt = oci_parse($conn, $shopCategoryQuery);
    oci_bind_by_name($shopCategoryStmt, ":user_id", $user_id);
    oci_execute($shopCategoryStmt);
    
    $shopCategories = [];
    while (oci_fetch($shopCategoryStmt)) {
        $shopCategories[] = oci_result($shopCategoryStmt, 'SHOP_CATEGORY');
    }

    // Fetch order statistics for trader's category
    $statsQuery = "SELECT 
        COUNT(*) as total_orders,
        NVL(SUM(o.total_amount), 0) as total_sales,
        SUM(CASE WHEN UPPER(o.status) = 'COMPLETED' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN UPPER(o.status) = 'PENDING' THEN 1 ELSE 0 END) as pending_orders
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND s.shop_category = (
            SELECT DISTINCT shop_category 
            FROM shops 
            WHERE user_id = :user_id
        )";
    $statsStmt = oci_parse($conn, $statsQuery);
    if (!$statsStmt) {
        throw new Exception("Failed to parse stats query: " . oci_error($conn)['message']);
    }
    
    oci_bind_by_name($statsStmt, ":user_id", $user_id);
    
    // Define columns for stats
    oci_define_by_name($statsStmt, 'TOTAL_ORDERS', $total_orders_col);
    oci_define_by_name($statsStmt, 'TOTAL_SALES', $total_sales_col);
    oci_define_by_name($statsStmt, 'COMPLETED_ORDERS', $completed_orders_col);
    oci_define_by_name($statsStmt, 'PENDING_ORDERS', $pending_orders_col);
    
    $result = oci_execute($statsStmt);
    if (!$result) {
        throw new Exception("Failed to execute stats query: " . oci_error($statsStmt)['message']);
    }
    
    $orderStats = [];
    if (oci_fetch($statsStmt)) {
        $orderStats = [
            'TOTAL_ORDERS' => $total_orders_col,
            'TOTAL_SALES' => $total_sales_col,
            'COMPLETED_ORDERS' => $completed_orders_col,
            'PENDING_ORDERS' => $pending_orders_col
        ];
    }

    // Fetch daily report data for trader's category
    $dailyQuery = "SELECT 
        TO_CHAR(TRUNC(o.order_date), 'YYYY-MM-DD') as \"order_date\",
        COUNT(*) as order_count,
        NVL(SUM(o.total_amount), 0) as total_sales
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND o.order_date >= TRUNC(SYSDATE) - 7
        GROUP BY TRUNC(o.order_date)
        ORDER BY TRUNC(o.order_date)";
    $dailyStmt = oci_parse($conn, $dailyQuery);
    oci_bind_by_name($dailyStmt, ":user_id", $user_id);
    
    // Define columns for daily data
    oci_define_by_name($dailyStmt, 'ORDER_DATE', $date_col);
    oci_define_by_name($dailyStmt, 'ORDER_COUNT', $order_count_col);
    oci_define_by_name($dailyStmt, 'TOTAL_SALES', $daily_sales_col);
    
    oci_execute($dailyStmt);
    
    $dailyData = [];
    while (oci_fetch($dailyStmt)) {
        $dailyData[] = [
            'DATE' => $date_col,
            'ORDER_COUNT' => (int)$order_count_col,
            'TOTAL_SALES' => (float)$daily_sales_col
        ];
    }

    // Fetch weekly report data for trader's category
    $weeklyQuery = "SELECT 
        TO_CHAR(TRUNC(o.order_date, 'IW'), 'YYYY-MM-DD') as week_start,
        COUNT(*) as order_count,
        NVL(SUM(o.total_amount), 0) as total_sales
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND o.order_date >= TRUNC(SYSDATE) - 30
        GROUP BY TRUNC(o.order_date, 'IW')
        ORDER BY TRUNC(o.order_date, 'IW')";
    $weeklyStmt = oci_parse($conn, $weeklyQuery);
    oci_bind_by_name($weeklyStmt, ":user_id", $user_id);
    
    // Define columns for weekly data
    oci_define_by_name($weeklyStmt, 'WEEK_START', $week_start_col);
    oci_define_by_name($weeklyStmt, 'ORDER_COUNT', $weekly_order_count_col);
    oci_define_by_name($weeklyStmt, 'TOTAL_SALES', $weekly_sales_col);
    
    oci_execute($weeklyStmt);
    
    $weeklyData = [];
    while (oci_fetch($weeklyStmt)) {
        $weeklyData[] = [
            'WEEK_START' => $week_start_col,
            'ORDER_COUNT' => (int)$weekly_order_count_col,
            'TOTAL_SALES' => (float)$weekly_sales_col
        ];
    }

    // Fetch monthly report data for trader's category
    $monthlyQuery = "SELECT 
        TO_CHAR(TRUNC(o.order_date, 'MM'), 'YYYY-MM-DD') as month_start,
        COUNT(*) as order_count,
        NVL(SUM(o.total_amount), 0) as total_sales
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND o.order_date >= TRUNC(SYSDATE) - 365
        GROUP BY TRUNC(o.order_date, 'MM')
        ORDER BY TRUNC(o.order_date, 'MM')";
    $monthlyStmt = oci_parse($conn, $monthlyQuery);
    oci_bind_by_name($monthlyStmt, ":user_id", $user_id);
    
    // Define columns for monthly data
    oci_define_by_name($monthlyStmt, 'MONTH_START', $month_start_col);
    oci_define_by_name($monthlyStmt, 'ORDER_COUNT', $monthly_order_count_col);
    oci_define_by_name($monthlyStmt, 'TOTAL_SALES', $monthly_sales_col);
    
    oci_execute($monthlyStmt);
    
    $monthlyData = [];
    while (oci_fetch($monthlyStmt)) {
        $monthlyData[] = [
            'MONTH_START' => $month_start_col,
            'ORDER_COUNT' => (int)$monthly_order_count_col,
            'TOTAL_SALES' => (float)$monthly_sales_col
        ];
    }

    // Fetch recent orders for trader's category
    $ordersQuery = "SELECT DISTINCT o.order_id, o.order_date, o.total_amount, o.status,
                   u.full_name as customer_name
                   FROM orders o
                   JOIN cart c ON o.cart_id = c.cart_id
                   JOIN product_cart pc ON c.cart_id = pc.cart_id
                   JOIN product p ON pc.product_id = p.product_id
                   JOIN shops s ON p.shop_id = s.shop_id
                   JOIN users u ON o.user_id = u.user_id
                   WHERE s.user_id = :user_id
                   ORDER BY o.order_date DESC";
    $ordersStmt = oci_parse($conn, $ordersQuery);
    if (!$ordersStmt) {
        throw new Exception("Failed to parse orders query: " . oci_error($conn)['message']);
    }
    
    oci_bind_by_name($ordersStmt, ":user_id", $user_id);
    
    // Define columns for orders
    oci_define_by_name($ordersStmt, 'ORDER_ID', $order_id_col);
    oci_define_by_name($ordersStmt, 'CUSTOMER_NAME', $customer_name_col);
    oci_define_by_name($ordersStmt, 'ORDER_DATE', $order_date_col);
    oci_define_by_name($ordersStmt, 'TOTAL_AMOUNT', $total_amount_col);
    oci_define_by_name($ordersStmt, 'STATUS', $status_col);
    
    $result = oci_execute($ordersStmt);
    if (!$result) {
        throw new Exception("Failed to execute orders query: " . oci_error($ordersStmt)['message']);
    }

    $recentOrders = [];
    $counter = 0;
    while (oci_fetch($ordersStmt) && $counter < 5) {
        $recentOrders[] = [
            'ORDER_ID' => $order_id_col,
            'CUSTOMER_NAME' => $customer_name_col,
            'ORDER_DATE' => $order_date_col,
            'TOTAL_AMOUNT' => $total_amount_col,
            'STATUS' => $status_col
        ];
        $counter++;
    }

    // Process order actions if submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['action'])) {
        $order_id = $_POST['order_id'];
        $action = $_POST['action'];
        $newStatus = '';
        
        switch($action) {
            case 'process':
                $newStatus = 'PROCESSING';
                break;
            case 'complete':
                $newStatus = 'COMPLETED';
                break;
            case 'cancel':
                $newStatus = 'CANCELLED';
                break;
        }
        
        if ($newStatus) {
            $updateQuery = "UPDATE orders SET status = :status WHERE order_id = :order_id";
            $updateStmt = oci_parse($conn, $updateQuery);
            oci_bind_by_name($updateStmt, ':status', $newStatus);
            oci_bind_by_name($updateStmt, ':order_id', $order_id);
            
            if (oci_execute($updateStmt)) {
                // Refresh the page to show updated status
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }

    // Free resources
    if (isset($stmt)) oci_free_statement($stmt);
    if (isset($shopsStmt)) oci_free_statement($shopsStmt);
    if (isset($ordersStmt)) oci_free_statement($ordersStmt);
    if (isset($statsStmt)) oci_free_statement($statsStmt);
    if (isset($updateStmt)) oci_free_statement($updateStmt);
    if (isset($dailyStmt)) oci_free_statement($dailyStmt);
    if (isset($weeklyStmt)) oci_free_statement($weeklyStmt);
    if (isset($monthlyStmt)) oci_free_statement($monthlyStmt);
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    // Close the connection
    if ($conn) oci_close($conn);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Dashboard - FresGrub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .report-section {
            margin-top: 30px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .report-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .report-title {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .report-tab {
            padding: 10px 20px;
            background: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .report-tab.active {
            background: var(--primary);
            color: white;
        }

        .report-content {
            display: none;
        }

        .report-content.active {
            display: block;
        }

        .btn-edit-shop {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 12px;
        }

        .btn-edit-shop:hover {
            background-color: #0277bd;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
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
                <a href="My_products.php">
                    <i class="fas fa-list"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li>
                <a href="Daily_reports.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Daily Reports</span>
                </a>
            </li>

            <li>
                <a href="Weekly_reports.php.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Weekly Reports</span>
                </a>
            </li>

            <li>
                <a href="Monthly_reports.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Monthly Reports</span>
                </a>
            </li>
            <li>
                <a href="Invoice.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Invoice</span>
                </a>
            </li>
            <li>
                <a href="trader_profile.php">
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
            <h2>Trader Dashboard</h2>
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="User">
                <span>Welcome, <?php echo htmlspecialchars($userData['FULL_NAME']); ?></span>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Your Shops</h3>
            <?php if (!empty($shops)): ?>
                <ul class="shop-links">
                    <?php foreach ($shops as $shop): ?>
                        <li>
                            <a href="shop.php?id=<?php echo $shop['SHOP_ID']; ?>">
                                <i class="fas fa-store"></i>
                                <?php echo htmlspecialchars($shop['SHOP_NAME']); ?>
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
                <div class="metric-value"><?php echo $orderStats['TOTAL_ORDERS'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Total Sales</h3>
                <div class="metric-value">$<?php echo number_format($orderStats['TOTAL_SALES'] ?? 0, 2); ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Completed Orders</h3>
                <div class="metric-value"><?php echo $orderStats['COMPLETED_ORDERS'] ?? 0; ?></div>
            </div>
            
            <div class="metric-card">
                <h3>Pending Orders</h3>
                <div class="metric-value"><?php echo $orderStats['PENDING_ORDERS'] ?? 0; ?></div>
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
                                <td><?php echo htmlspecialchars($order['ORDER_ID']); ?></td>
                                <td><?php echo isset($order['CUSTOMER_NAME']) ? htmlspecialchars($order['CUSTOMER_NAME']) : 'Customer'; ?></td>
                                <td><?php echo isset($order['ORDER_DATE']) ? $order['ORDER_DATE'] : '-'; ?></td>
                                <td>$<?php echo isset($order['TOTAL_AMOUNT']) ? number_format($order['TOTAL_AMOUNT'], 2) : '0.00'; ?></td>
                                <td class="status-<?php echo isset($order['STATUS']) ? strtolower(htmlspecialchars($order['STATUS'])) : 'pending'; ?>">
                                    <?php echo isset($order['STATUS']) ? ucfirst(strtolower(htmlspecialchars($order['STATUS']))) : 'Pending'; ?>
                                </td>
                                <td>
                                    <?php if (strtolower($order['STATUS']) == 'pending'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                            <input type="hidden" name="action" value="process">
                                            <button type="submit" class="btn btn-process">Process</button>
                                        </form>
                                    <?php elseif (strtolower($order['STATUS']) == 'processing'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-complete">Complete</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (strtolower($order['STATUS']) != 'completed' && strtolower($order['STATUS']) != 'cancelled'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
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

        <!-- Reports Section -->
        <div class="card report-section">
            <h3 class="card-title">Sales Reports</h3>
            
            <div class="report-tabs">
                <button class="report-tab active" data-tab="daily">Daily Report</button>
                <button class="report-tab" data-tab="weekly">Weekly Report</button>
                <button class="report-tab" data-tab="monthly">Monthly Report</button>
            </div>

            <div class="report-content active" id="daily-report">
                <div class="report-grid">
                    <div class="report-card">
                        <h4 class="report-title">Daily Orders</h4>
                        <div class="chart-container">
                            <canvas id="dailyOrdersChart"></canvas>
                        </div>
                    </div>
                    <div class="report-card">
                        <h4 class="report-title">Daily Sales</h4>
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-content" id="weekly-report">
                <div class="report-grid">
                    <div class="report-card">
                        <h4 class="report-title">Weekly Orders</h4>
                        <div class="chart-container">
                            <canvas id="weeklyOrdersChart"></canvas>
                        </div>
                    </div>
                    <div class="report-card">
                        <h4 class="report-title">Weekly Sales</h4>
                        <div class="chart-container">
                            <canvas id="weeklySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-content" id="monthly-report">
                <div class="report-grid">
                    <div class="report-card">
                        <h4 class="report-title">Monthly Orders</h4>
                        <div class="chart-container">
                            <canvas id="monthlyOrdersChart"></canvas>
                        </div>
                    </div>
                    <div class="report-card">
                        <h4 class="report-title">Monthly Sales</h4>
                        <div class="chart-container">
                            <canvas id="monthlySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Highlight active menu item
        document.querySelectorAll('.sidebar-menu a').forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });

        // Chart data
        const dailyData = <?php echo json_encode($dailyData); ?>;
        const weeklyData = <?php echo json_encode($weeklyData); ?>;
        const monthlyData = <?php echo json_encode($monthlyData); ?>;

        // Format dates
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
        }

        function formatWeek(dateStr) {
            const date = new Date(dateStr);
            return `Week ${date.getDate()}/${date.getMonth() + 1}`;
        }

        function formatMonth(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
        }

        // Chart configurations
        const chartConfig = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        // Daily Charts
        new Chart(document.getElementById('dailyOrdersChart'), {
            ...chartConfig,
            data: {
                labels: dailyData.map(d => formatDate(d.DATE)),
                datasets: [{
                    label: 'Orders',
                    data: dailyData.map(d => d.ORDER_COUNT),
                    borderColor: '#2e7d32',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        new Chart(document.getElementById('dailySalesChart'), {
            ...chartConfig,
            data: {
                labels: dailyData.map(d => formatDate(d.DATE)),
                datasets: [{
                    label: 'Sales (£)',
                    data: dailyData.map(d => d.TOTAL_SALES),
                    borderColor: '#0288d1',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        // Weekly Charts
        new Chart(document.getElementById('weeklyOrdersChart'), {
            ...chartConfig,
            data: {
                labels: weeklyData.map(d => formatWeek(d.WEEK_START)),
                datasets: [{
                    label: 'Orders',
                    data: weeklyData.map(d => d.ORDER_COUNT),
                    borderColor: '#2e7d32',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        new Chart(document.getElementById('weeklySalesChart'), {
            ...chartConfig,
            data: {
                labels: weeklyData.map(d => formatWeek(d.WEEK_START)),
                datasets: [{
                    label: 'Sales (£)',
                    data: weeklyData.map(d => d.TOTAL_SALES),
                    borderColor: '#0288d1',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        // Monthly Charts
        new Chart(document.getElementById('monthlyOrdersChart'), {
            ...chartConfig,
            data: {
                labels: monthlyData.map(d => formatMonth(d.MONTH_START)),
                datasets: [{
                    label: 'Orders',
                    data: monthlyData.map(d => d.ORDER_COUNT),
                    borderColor: '#2e7d32',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        new Chart(document.getElementById('monthlySalesChart'), {
            ...chartConfig,
            data: {
                labels: monthlyData.map(d => formatMonth(d.MONTH_START)),
                datasets: [{
                    label: 'Sales (£)',
                    data: monthlyData.map(d => d.TOTAL_SALES),
                    borderColor: '#0288d1',
                    tension: 0.1,
                    fill: false
                }]
            }
        });

        // Report tabs functionality
        document.querySelectorAll('.report-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.report-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}-report`).classList.add('active');
            });
        });

        // Add this to your existing JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('editShopModal');
            const closeBtn = document.getElementsByClassName('close')[0];
            const editButtons = document.getElementsByClassName('btn-edit-shop');

            // Open modal when edit button is clicked
            Array.from(editButtons).forEach(button => {
                button.addEventListener('click', function() {
                    const shopId = this.getAttribute('data-shop-id');
                    const shopName = this.getAttribute('data-shop-name');
                    const shopCategory = this.getAttribute('data-shop-category');
                    const shopDescription = this.getAttribute('data-shop-description');

                    document.getElementById('edit_shop_id').value = shopId;
                    document.getElementById('edit_shop_name').value = shopName;
                    document.getElementById('edit_shop_category').value = shopCategory;
                    document.getElementById('edit_shop_description').value = shopDescription;

                    modal.style.display = 'block';
                });
            });

            // Close modal when X is clicked
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>