<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    $query = "DELETE FROM product_cart WHERE product_id = :product_id";
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ":product_id", $product_id);

    $result = oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if ($result) {
        oci_commit($conn);
        echo "success";
    } else {
        oci_rollback($conn);
        echo "error";
    }

    oci_free_statement($stid);
}
?>
