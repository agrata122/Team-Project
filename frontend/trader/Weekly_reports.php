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
    die("❌ Failed to connect to Oracle database.");
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch weekly order statistics
    $weeklyStatsQuery = "SELECT 
        TO_CHAR(TRUNC(o.order_date, 'IW'), 'YYYY-MM-DD') as week_start,
        COUNT(*) as total_orders,
        NVL(SUM(o.total_amount), 0) as total_sales,
        COUNT(CASE WHEN o.status = 'COMPLETED' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN o.status = 'PENDING' THEN 1 END) as pending_orders,
        NVL(SUM(CASE WHEN o.status = 'COMPLETED' THEN o.total_amount ELSE 0 END), 0) as completed_sales
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND o.order_date >= TRUNC(SYSDATE) - 90
        GROUP BY TRUNC(o.order_date, 'IW')
        ORDER BY TRUNC(o.order_date, 'IW') DESC";

    $stmt = oci_parse($conn, $weeklyStatsQuery);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);

    $weeklyStats = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $weeklyStats[] = $row;
    }

    // Fetch weekly payment statistics
    $weeklyPaymentQuery = "SELECT 
        TO_CHAR(TRUNC(p.payment_date, 'IW'), 'YYYY-MM-DD') as week_start,
        COUNT(*) as total_payments,
        NVL(SUM(p.payment_amount), 0) as total_payment_amount,
        COUNT(CASE WHEN p.payment_status = 'completed' THEN 1 END) as completed_payments,
        COUNT(CASE WHEN p.payment_status = 'pending' THEN 1 END) as pending_payments
        FROM payment p
        JOIN orders o ON p.order_id = o.order_id
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product prod ON pc.product_id = prod.product_id
        JOIN shops s ON prod.shop_id = s.shop_id
        WHERE s.user_id = :user_id
        AND p.payment_date >= TRUNC(SYSDATE) - 90
        GROUP BY TRUNC(p.payment_date, 'IW')
        ORDER BY TRUNC(p.payment_date, 'IW') DESC";

    $stmt = oci_parse($conn, $weeklyPaymentQuery);
    oci_bind_by_name($stmt, ":user_id", $user_id);
    oci_execute($stmt);

    $weeklyPayments = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $weeklyPayments[] = $row;
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    if ($conn) oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Reports - FresGrub</title>
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-top: 20px;
        }
        
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .metric-label {
            color: #666;
            font-size: 14px;
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
                <a href="/E-commerce/frontend/Includes/pages/product_list.php">
                    <i class="fas fa-list"></i>
                    <span>View Products</span>
                </a>
            </li>
            <li>
                <a href="Daily_reports.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Daily Reports</span>
                </a>
            </li>
            <li>
                <a href="Weekly_reports.php" class="active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Weekly Reports</span>
                </a>
            </li>
            <li>
                <a href="Monthly_reports.php">
                    <i class="fas fa-chart-pie"></i>
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
            <h2>Weekly Reports</h2>
        </div>

        <!-- Summary Metrics -->
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">Total Orders (Last 90 Days)</div>
                <div class="metric-value"><?php echo array_sum(array_column($weeklyStats, 'TOTAL_ORDERS')); ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Total Sales (Last 90 Days)</div>
                <div class="metric-value">$<?php echo number_format(array_sum(array_column($weeklyStats, 'TOTAL_SALES')), 2); ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Completed Orders</div>
                <div class="metric-value"><?php echo array_sum(array_column($weeklyStats, 'COMPLETED_ORDERS')); ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Pending Orders</div>
                <div class="metric-value"><?php echo array_sum(array_column($weeklyStats, 'PENDING_ORDERS')); ?></div>
            </div>
        </div>

        <!-- Weekly Orders Chart -->
        <div class="card">
            <h3 class="card-title">Weekly Orders Overview</h3>
            <div class="chart-container">
                <canvas id="weeklyOrdersChart"></canvas>
            </div>
        </div>

        <!-- Weekly Sales Chart -->
        <div class="card">
            <h3 class="card-title">Weekly Sales Overview</h3>
            <div class="chart-container">
                <canvas id="weeklySalesChart"></canvas>
            </div>
        </div>

        <!-- Weekly Orders Table -->
        <div class="card">
            <h3 class="card-title">Weekly Orders Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Week Starting</th>
                        <th>Total Orders</th>
                        <th>Total Sales</th>
                        <th>Completed Orders</th>
                        <th>Pending Orders</th>
                        <th>Completed Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeklyStats as $stat): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($stat['WEEK_START'])); ?></td>
                        <td><?php echo $stat['TOTAL_ORDERS']; ?></td>
                        <td>$<?php echo number_format($stat['TOTAL_SALES'], 2); ?></td>
                        <td><?php echo $stat['COMPLETED_ORDERS']; ?></td>
                        <td><?php echo $stat['PENDING_ORDERS']; ?></td>
                        <td>$<?php echo number_format($stat['COMPLETED_SALES'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Weekly Payments Table -->
        <div class="card">
            <h3 class="card-title">Weekly Payments Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Week Starting</th>
                        <th>Total Payments</th>
                        <th>Total Amount</th>
                        <th>Completed Payments</th>
                        <th>Pending Payments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeklyPayments as $payment): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($payment['WEEK_START'])); ?></td>
                        <td><?php echo $payment['TOTAL_PAYMENTS']; ?></td>
                        <td>$<?php echo number_format($payment['TOTAL_PAYMENT_AMOUNT'], 2); ?></td>
                        <td><?php echo $payment['COMPLETED_PAYMENTS']; ?></td>
                        <td><?php echo $payment['PENDING_PAYMENTS']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const weeklyStats = <?php echo json_encode($weeklyStats); ?>;
        const labels = weeklyStats.map(stat => {
            const date = new Date(stat.WEEK_START);
            return `Week of ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
        });
        
        // Weekly Orders Chart
        new Chart(document.getElementById('weeklyOrdersChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Orders',
                    data: weeklyStats.map(stat => stat.TOTAL_ORDERS),
                    backgroundColor: '#2e7d32',
                }, {
                    label: 'Completed Orders',
                    data: weeklyStats.map(stat => stat.COMPLETED_ORDERS),
                    backgroundColor: '#388e3c',
                }]
            },
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
        });

        // Weekly Sales Chart
        new Chart(document.getElementById('weeklySalesChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Sales',
                    data: weeklyStats.map(stat => stat.TOTAL_SALES),
                    backgroundColor: '#0288d1',
                }, {
                    label: 'Completed Sales',
                    data: weeklyStats.map(stat => stat.COMPLETED_SALES),
                    backgroundColor: '#039be5',
                }]
            },
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
        });
    </script>
</body>
</html> 