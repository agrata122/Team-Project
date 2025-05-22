<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Get parameters
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : null;
$payment_amount = isset($_GET['payment_amount']) ? $_GET['payment_amount'] : null;
$simulated = isset($_GET['simulated']) ? $_GET['simulated'] : false;

if (!$order_id) {
    die("No order ID provided");
}

// Update order status
$updateOrderQuery = "UPDATE orders SET status = 'completed' WHERE order_id = :order_id";
$stid = oci_parse($conn, $updateOrderQuery);
oci_bind_by_name($stid, ":order_id", $order_id);

if (oci_execute($stid)) {
    // Create payment record
    $paymentQuery = "INSERT INTO payment (payment_date, payment_amount, payment_method, 
                    payment_status, order_id, user_id) 
                    VALUES (SYSDATE, :payment_amount, :payment_method, 'completed', 
                    :order_id, :user_id)";
    
    $stid = oci_parse($conn, $paymentQuery);
    $payment_method = $simulated ? 'simulated' : 'paypal';
    $user_id = $_SESSION['user_id'];
    
    oci_bind_by_name($stid, ":payment_amount", $payment_amount);
    oci_bind_by_name($stid, ":payment_method", $payment_method);
    oci_bind_by_name($stid, ":order_id", $order_id);
    oci_bind_by_name($stid, ":user_id", $user_id);
    
    if (oci_execute($stid)) {
        // Maintain session variables
        $_SESSION['current_order_id'] = $order_id;
        
        // Clear cart
        $cartQuery = "DELETE FROM product_cart WHERE cart_id = (SELECT cart_id FROM orders WHERE order_id = :order_id)";
        $stid = oci_parse($conn, $cartQuery);
        oci_bind_by_name($stid, ":order_id", $order_id);
        oci_execute($stid);
        
        // Redirect to confirmation
        header("Location: checkout.php?step=confirmation");
        exit();
    } else {
        $error = oci_error($stid);
        die("Error creating payment record: " . $error['message']);
    }
} else {
    $error = oci_error($stid);
    die("Error updating order status: " . $error['message']);
}

oci_close($conn);
?>