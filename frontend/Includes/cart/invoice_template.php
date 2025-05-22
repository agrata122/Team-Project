<!DOCTYPE html>
<html>
<head>
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
        <div class="invoice-date">Date: <?php echo date('F j, Y'); ?></div>
    </div>
    
    <div class="row">
        <div class="col">
            <h3>Billed To</h3>
            <p><?php echo htmlspecialchars($order['FULL_NAME']); ?></p>
            <p>Email: <?php echo htmlspecialchars($order['EMAIL']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($order['CONTACT_NO']); ?></p>
        </div>
    </div>
    
    <div class="invoice-section">
        <h3>Collection Details</h3>
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
        <h3>Order Summary</h3>
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
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['PRODUCT_NAME']); ?></td>
                    <td><?php echo htmlspecialchars($item['SHOP_NAME']); ?></td>
                    <td>$<?php echo number_format($item['PRICE'], 2); ?></td>
                    <td><?php echo $item['QUANTITY']; ?></td>
                    <td class="text-right">$<?php echo number_format($item['PRICE'] * $item['QUANTITY'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
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