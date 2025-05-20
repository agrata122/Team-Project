<?php
session_start();
require_once "../../backend/connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

// Initialize the connection
$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Prepare OCI DELETE statement
    $stmt = oci_parse($conn, "DELETE FROM users WHERE user_id = :user_id");
    oci_bind_by_name($stmt, ":user_id", $user_id);

    // Execute and check result
    if (oci_execute($stmt)) {
        header("Location: admindashboard.php?message=Trader rejected");
        exit();
    } else {
        $e = oci_error($stmt);
        echo "Rejection failed: " . htmlentities($e['message']);
    }
}
?>
