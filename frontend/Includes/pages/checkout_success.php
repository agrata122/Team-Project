<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$order_details = null;

if ($order_id) {
    $conn = getDBConnection();
    if ($conn) {
        $query = "SELECT o.*, cs.slot_date, cs.slot_time 
                 FROM orders o 
                 JOIN collection_slot cs ON o.collection_slot_id = cs.collection_slot_id 
                 WHERE o.order_id = :order_id";
        $stid = oci_parse($conn, $query);
        oci_bind_by_name($stid, ":order_id", $order_id);
        
        if (oci_execute($stid)) {
            $order_details = oci_fetch_assoc($stid);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Header.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }
        .order-details {
            margin: 30px 0;
            text-align: left;
            padding: 20px;
            background-color: white;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <?php include '../header.php'; ?>
    </header>

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1>Order Confirmed!</h1>
        <p>Thank you for your order. Your order has been successfully placed.</p>
        
        <?php if ($order_details): ?>
        <div class="order-details">
            <h2>Order Details</h2>
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
            <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['TOTAL_AMOUNT'], 2); ?></p>
            <p><strong>Collection Date:</strong> <?php echo date('F j, Y', strtotime($order_details['SLOT_DATE'])); ?></p>
            <p><strong>Collection Time:</strong> <?php echo htmlspecialchars($order_details['SLOT_TIME']); ?></p>
            <p><strong>Order Status:</strong> <?php echo ucfirst(htmlspecialchars($order_details['STATUS'])); ?></p>
        </div>
        <?php endif; ?>

        <div>
            <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="btn">Return to Home</a>
            <a href="/E-commerce/frontend/Includes/pages/orders.php" class="btn">View Orders</a>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html> 