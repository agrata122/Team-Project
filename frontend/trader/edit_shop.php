<?php
session_start();

// Check if the user is logged in and is a trader
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    header("Location: login.php");
    exit();
}

// Include the Oracle DB connection
require_once 'C:\xampp\htdocs\E-commerce\backend\connect.php';

// Get a valid Oracle connection
$conn = getDBConnection();

if (!$conn) {
    die("Failed to connect to Oracle database.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id = $_POST['shop_id'];
    $shop_name = $_POST['shop_name'];
    $shop_category = $_POST['shop_category'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    try {
        // First verify that the shop belongs to the logged-in trader
        $verify_query = "SELECT shop_id FROM shops WHERE shop_id = :shop_id AND user_id = :user_id";
        $verify_stmt = oci_parse($conn, $verify_query);
        oci_bind_by_name($verify_stmt, ":shop_id", $shop_id);
        oci_bind_by_name($verify_stmt, ":user_id", $user_id);
        
        if (!oci_execute($verify_stmt)) {
            throw new Exception("Failed to verify shop ownership");
        }
        
        if (!oci_fetch($verify_stmt)) {
            throw new Exception("Unauthorized: Shop does not belong to this trader");
        }

        // Update the shop details
        $update_query = "UPDATE shops 
                        SET shop_name = :shop_name,
                            shop_category = :shop_category,
                            description = :description
                        WHERE shop_id = :shop_id 
                        AND user_id = :user_id";
        
        $update_stmt = oci_parse($conn, $update_query);
        
        oci_bind_by_name($update_stmt, ":shop_name", $shop_name);
        oci_bind_by_name($update_stmt, ":shop_category", $shop_category);
        oci_bind_by_name($update_stmt, ":description", $description);
        oci_bind_by_name($update_stmt, ":shop_id", $shop_id);
        oci_bind_by_name($update_stmt, ":user_id", $user_id);
        
        if (oci_execute($update_stmt)) {
            $_SESSION['success_message'] = "Shop details updated successfully!";
        } else {
            throw new Exception("Failed to update shop details");
        }
        
        // Free resources
        oci_free_statement($verify_stmt);
        oci_free_statement($update_stmt);
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    } finally {
        if ($conn) oci_close($conn);
    }
    
    // Redirect back to the dashboard
    header("Location: traderdashboard.php");
    exit();
} else {
    // If not a POST request, redirect to dashboard
    header("Location: traderdashboard.php");
    exit();
}
?> 