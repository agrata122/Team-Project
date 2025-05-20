<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

if (!isset($_SESSION['current_order_id'])) {
    header("Location: shopping_cart.php");
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$order_id = $_SESSION['current_order_id'];
$payment_id = $_POST['payment_id'];
$payment_amount = $_POST['payment_amount'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_COOKIE['user_id'];
$numeric_user_id = (int)$user_id;

// Update order status
$updateOrderQuery = "UPDATE orders SET status = 'completed' WHERE order_id = :order_id";
$stid = oci_parse($conn, $updateOrderQuery);
oci_bind_by_name($stid, ":order_id", $order_id);
oci_execute($stid);

// Create payment record
$paymentQuery = "INSERT INTO payment (payment_date, payment_amount, payment_method, 
                payment_status, order_id, user_id) 
                VALUES (SYSDATE, :payment_amount, 'paypal', 'completed', 
                :order_id, :user_id)";
$stid = oci_parse($conn, $paymentQuery);
oci_bind_by_name($stid, ":payment_amount", $payment_amount);
oci_bind_by_name($stid, ":order_id", $order_id);
oci_bind_by_name($stid, ":user_id", $numeric_user_id);
oci_execute($stid);

// Clear session data
unset($_SESSION['current_order_id']);
unset($_SESSION['order_total']);
unset($_SESSION['applied_coupon']);

// Redirect to confirmation
header("Location: checkout.php?step=confirmation");
exit();
?>