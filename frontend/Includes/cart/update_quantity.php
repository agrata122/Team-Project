<?php
session_start();
require_once '../../backend/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['qty'];
    
    // Get cart ID based on user type
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $cartQuery = "SELECT cart_id FROM cart WHERE user_id = :user_id";
        $stid = oci_parse($conn, $cartQuery);
        oci_bind_by_name($stid, ":user_id", $user_id);
    } elseif (isset($_COOKIE['guest_id'])) {
        $guest_id = $_COOKIE['guest_id'];
        $cartQuery = "SELECT cart_id FROM guest_cart WHERE guest_id = :guest_id";
        $stid = oci_parse($conn, $cartQuery);
        oci_bind_by_name($stid, ":guest_id", $guest_id, -1, SQLT_CHR);
    } else {
        echo "error";
        exit;
    }
    
    if (!oci_execute($stid)) {
        echo "error";
        exit;
    }
    
    $row = oci_fetch_assoc($stid);
    if (!$row) {
        echo "error";
        exit;
    }
    
    $cart_id = $row['CART_ID'];
    
    // Check if this is a guest cart
    $checkCartQuery = "SELECT 1 FROM guest_cart WHERE cart_id = :cart_id";
    $stid = oci_parse($conn, $checkCartQuery);
    oci_bind_by_name($stid, ":cart_id", $cart_id);
    oci_execute($stid);
    $isGuestCart = (oci_fetch($stid) !== false);
    
    // Calculate total items in cart (excluding current item)
    if ($isGuestCart) {
        $totalQuery = "SELECT SUM(quantity) as total FROM guest_product_cart WHERE cart_id = :cart_id AND product_id != :product_id";
    } else {
        $totalQuery = "SELECT SUM(quantity) as total FROM product_cart WHERE cart_id = :cart_id AND product_id != :product_id";
    }
    $stid = oci_parse($conn, $totalQuery);
    oci_bind_by_name($stid, ":cart_id", $cart_id);
    oci_bind_by_name($stid, ":product_id", $product_id);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    $totalOtherItems = $row['TOTAL'] ? (int)$row['TOTAL'] : 0;
    
    // Check if new total would exceed 20 items
    if (($totalOtherItems + $new_quantity) > 20) {
        echo "error";
        exit;
    }
    
    // Check stock availability
    $stockQuery = "SELECT stock FROM product WHERE product_id = :product_id";
    $stid = oci_parse($conn, $stockQuery);
    oci_bind_by_name($stid, ":product_id", $product_id);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    
    if ($row && $new_quantity <= $row['STOCK']) {
        if ($isGuestCart) {
            $updateQuery = "UPDATE guest_product_cart SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id";
        } else {
            $updateQuery = "UPDATE product_cart SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id";
        }
        $stid = oci_parse($conn, $updateQuery);
        oci_bind_by_name($stid, ":quantity", $new_quantity);
        oci_bind_by_name($stid, ":cart_id", $cart_id);
        oci_bind_by_name($stid, ":product_id", $product_id);
        
        if (oci_execute($stid)) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>
