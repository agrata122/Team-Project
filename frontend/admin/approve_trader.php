<?php
session_start();
require_once "../../backend/connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

// Initialize DB connection
$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    $stmt = oci_parse($conn, "UPDATE users SET status = 'active' WHERE user_id = :user_id");
    oci_bind_by_name($stmt, ":user_id", $user_id);

    if (oci_execute($stmt)) {
        header("Location: admindashboard.php?message=Trader approved");
        exit();
    } else {
        $e = oci_error($stmt);
        echo "Approval failed: " . htmlentities($e['message']);
    }
}
?>
