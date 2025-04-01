<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trader') {
    header("Location: index.php");
    exit;
}

echo "Welcome to the Trader Dashboard, "
?>
 