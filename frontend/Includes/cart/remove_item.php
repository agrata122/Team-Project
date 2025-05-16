<?php
require 'C:\xampp\htdocs\E-commerce\backend\db_connection.php';
$conn = getDBConnection();
if(isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    $delete_item = $conn->prepare("DELETE FROM product_cart WHERE product_id = ?");
    $delete_item->execute([$product_id]);

    echo "success";
}
?>