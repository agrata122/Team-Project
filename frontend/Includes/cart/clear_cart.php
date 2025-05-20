<?php
session_start();
require_once '../../backend/connect.php';

$conn = getDBConnection();
if (!$conn) {
    echo "error";
    exit;
}

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

// Begin transaction
oci_execute($conn, "BEGIN");

try {
    if ($isGuestCart) {
        // Delete items from guest cart
        $deleteItemsQuery = "DELETE FROM guest_product_cart WHERE cart_id = :cart_id";
    } else {
        // Delete items from regular cart
        $deleteItemsQuery = "DELETE FROM product_cart WHERE cart_id = :cart_id";
    }
    
    $stid = oci_parse($conn, $deleteItemsQuery);
    oci_bind_by_name($stid, ":cart_id", $cart_id);
    
    if (!oci_execute($stid)) {
        throw new Exception("Failed to delete cart items");
    }
    
    // Commit transaction
    oci_execute($conn, "COMMIT");
    echo "success";
} catch (Exception $e) {
    // Rollback transaction on error
    oci_execute($conn, "ROLLBACK");
    echo "error";
}

oci_close($conn);
?>
