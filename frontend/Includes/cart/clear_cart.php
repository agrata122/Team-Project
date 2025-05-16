<?php
require 'C:\xampp\htdocs\E-commerce\backend\db_connection.php';
$conn = getDBConnection();

if(isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
    
    // First get the cart_id for this user
    $cart_query = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $cart_query->execute([$user_id]);
    $cart = $cart_query->fetch(PDO::FETCH_ASSOC);
    
    if($cart) {
        // Delete all items from product_cart for this cart
        $clear_cart = $conn->prepare("DELETE FROM product_cart WHERE cart_id = ?");
        $clear_cart->execute([$cart['cart_id']]);
        
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>