<?php
session_start();
require_once "../../backend/db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

$db = getDBConnection();
if (!$db) {
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        header("Location: admindashboard.php?message=Trader rejected");
    } else {
        echo "Rejection failed.";
    }
}
?>
