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

// Pagination settings
$orders_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $orders_per_page;

// Get total number of orders for pagination
$count_sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id";
$count_stid = oci_parse($db, $count_sql);
oci_bind_by_name($count_stid, ":user_id", $user_id);
oci_execute($count_stid);
$total_orders = oci_fetch_assoc($count_stid)['TOTAL'];
oci_free_statement($count_stid);

$total_pages = ceil($total_orders / $orders_per_page);

// Query to get all orders for the user with pagination using Oracle's ROWNUM
$orders_sql = "SELECT * FROM (
    SELECT a.*, ROWNUM rnum FROM (
        SELECT o.order_id, o.order_date, o.total_amount, o.status,
               p.product_name, pc.quantity, p.price
        FROM orders o
        JOIN cart c ON o.cart_id = c.cart_id
        JOIN product_cart pc ON c.cart_id = pc.cart_id
        JOIN product p ON pc.product_id = p.product_id
        WHERE o.user_id = :user_id
        ORDER BY o.order_date DESC
    ) a WHERE ROWNUM <= :end_row
) WHERE rnum > :start_row";

$orders_stid = oci_parse($db, $orders_sql);
$end_row = $offset + $orders_per_page;
$start_row = $offset;

oci_bind_by_name($orders_stid, ":user_id", $user_id);
oci_bind_by_name($orders_stid, ":end_row", $end_row);
oci_bind_by_name($orders_stid, ":start_row", $start_row);

if (!oci_execute($orders_stid)) {
    echo "Failed to fetch orders.";
    exit();
}

$orders = [];
while ($row = oci_fetch_assoc($orders_stid)) {
    $orders[] = $row;
}
oci_free_statement($orders_stid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - FresGrub</title>
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
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .orders-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a {
            padding: 8px 15px;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #28a745;
            color: white;
        }

        .pagination .active {
            background: #28a745;
            color: white;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #28a745;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        <a href="user_profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>

        <div class="page-header">
            <h1 class="page-title">Order History</h1>
        </div>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <h2>No Orders Found</h2>
                <p>You haven't placed any orders yet.</p>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['ORDER_ID']); ?></td>
                            <td><?php echo htmlspecialchars($order['ORDER_DATE']); ?></td>
                            <td><?php echo htmlspecialchars($order['PRODUCT_NAME']); ?></td>
                            <td><?php echo htmlspecialchars($order['QUANTITY']); ?></td>
                            <td>$<?php echo number_format($order['PRICE'], 2); ?></td>
                            <td>$<?php echo number_format($order['PRICE'] * $order['QUANTITY'], 2); ?></td>
                            <td>
                                <span class="order-status status-<?php echo strtolower($order['STATUS']); ?>">
                                    <?php echo htmlspecialchars($order['STATUS']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../Includes/footer.php'; ?>
</body>
</html> 