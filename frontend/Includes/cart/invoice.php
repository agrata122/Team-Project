<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

if (!isset($_GET['order_id'])) {
    header("Location: shopping_cart.php");
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$order_id = $_GET['order_id'];

// Get order details
$orderQuery = "SELECT o.*, u.full_name, u.email, u.contact_no, 
              c.coupon_code, c.coupon_discount_percent,
              cs.slot_date, cs.slot_time, cs.slot_day
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              LEFT JOIN coupon c ON o.coupon_id = c.coupon_id
              LEFT JOIN collection_slot cs ON o.collection_slot_id = cs.collection_slot_id
              WHERE o.order_id = :order_id";
$stid = oci_parse($conn, $orderQuery);
oci_bind_by_name($stid, ":order_id", $order_id);
oci_execute($stid);
$order = oci_fetch_assoc($stid);

// Debug: Check if order exists
if (!$order) {
    echo "No order found with ID: $order_id";
    exit();
}

// Get order items - Modified query for debugging
$itemsQuery = "SELECT p.product_name, oi.price, oi.quantity, 
              s.shop_name, s.shop_category
              FROM order_items oi
              JOIN product p ON oi.product_id = p.product_id
              JOIN shops s ON p.shop_id = s.shop_id
              WHERE oi.order_id = :order_id";
$stid = oci_parse($conn, $itemsQuery);
oci_bind_by_name($stid, ":order_id", $order_id);
$result = oci_execute($stid);

// Debug: Check query execution
if (!$result) {
    $e = oci_error($stid);
    echo "Error executing items query: " . $e['message'];
    exit();
}

$items = [];
$total = 0;

// Debug: Let's check what rows are returned
$row_count = 0;
while ($row = oci_fetch_assoc($stid)) {
    $row_count++;
    $items[] = $row;
    $total += $row['PRICE'] * $row['QUANTITY'];
    
    // Debug: Print the first row to see its structure
    if ($row_count == 1) {
        echo "<!-- Debug: First row keys: " . implode(', ', array_keys($row)) . " -->";
    }
}

// Debug: If no items found, show message
if (count($items) == 0) {
    echo "<!-- No items found for this order -->";
}

// Get payment details
$paymentQuery = "SELECT * FROM payment WHERE order_id = :order_id";
$stid = oci_parse($conn, $paymentQuery);
oci_bind_by_name($stid, ":order_id", $order_id);
oci_execute($stid);
$payment = oci_fetch_assoc($stid);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
        }
        
        .invoice-title {
            font-size: 28px;
            color: #4CAF50;
            margin: 0;
        }
        
        .invoice-number {
            font-size: 18px;
            color: #777;
        }
        
        .invoice-date {
            font-size: 16px;
            color: #777;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px 20px;
        }
        
        .col {
            flex: 1;
            padding: 0 15px;
        }
        
        .invoice-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            color: #4CAF50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f5f5f5;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 18px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1 class="invoice-title">INVOICE</h1>
        <div class="invoice-number">Order #<?php echo $order_id; ?></div>
        <div class="invoice-date">Date: <?php echo date('F j, Y', strtotime($order['ORDER_DATE'])); ?></div>
    </div>
    
    <div class="row">
        <div class="col">
            <div class="invoice-section">
                <h3 class="section-title">Billed To</h3>
                <p><?php echo htmlspecialchars($order['FULL_NAME']); ?></p>
                <p>Email: <?php echo htmlspecialchars($order['EMAIL']); ?></p>
                <p>Phone: <?php echo htmlspecialchars($order['CONTACT_NO']); ?></p>
            </div>
        </div>
        
        <div class="col">
            <div class="invoice-section">
                <h3 class="section-title">Payment Method</h3>
                <p><?php echo strtoupper($payment['PAYMENT_METHOD']); ?></p>
                <p>Transaction ID: <?php echo $payment['PAYMENT_ID']; ?></p>
                <p>Status: <?php echo ucfirst($payment['PAYMENT_STATUS']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="invoice-section">
        <h3 class="section-title">Collection Details</h3>
        <?php if ($order['SLOT_DATE'] && $order['SLOT_TIME']): ?>
            <p>Your collection slot is scheduled for:</p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['SLOT_DATE'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($order['SLOT_TIME'])); ?></p>
            <p><strong>Day:</strong> <?php echo $order['SLOT_DAY']; ?></p>
        <?php else: ?>
            <p>No collection slot has been assigned for this order.</p>
        <?php endif; ?>
    </div>
    
    <div class="invoice-section">
        <h3 class="section-title">Order Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Shop</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['PRODUCT_NAME']); ?></td>
                        <td><?php echo htmlspecialchars($item['SHOP_NAME']); ?></td>
                        <td>$<?php echo number_format($item['PRICE'], 2); ?></td>
                        <td><?php echo $item['QUANTITY']; ?></td>
                        <td class="text-right">$<?php echo number_format($item['PRICE'] * $item['QUANTITY'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No items found for this order</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">Subtotal</td>
                    <td class="text-right">$<?php echo number_format($total, 2); ?></td>
                </tr>
                
                <?php if ($order['COUPON_ID']): ?>
                <tr>
                    <td colspan="4" class="text-right">Discount (<?php echo $order['COUPON_CODE']; ?> - <?php echo $order['COUPON_DISCOUNT_PERCENT']; ?>%)</td>
                    <td class="text-right">-$<?php echo number_format($total * ($order['COUPON_DISCOUNT_PERCENT'] / 100), 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <tr class="total-row">
                    <td colspan="4" class="text-right">Total</td>
                    <td class="text-right">$<?php echo number_format($order['TOTAL_AMOUNT'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>If you have any questions about this invoice, please contact our customer service.</p>
    </div>
</body>
</html>