<?php
session_start();
require_once "../../backend/db_connection.php";

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = getDBConnection();
if (!$db) {
    die("Database connection failed.");
}

// Fetch pending traders
$stmt = $db->prepare("SELECT user_id, full_name, email, category, first_shop_name, second_shop_name FROM users WHERE role = 'trader' AND status = 'pending'");
$stmt->execute();
$pendingTraders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Admin Dashboard - Approve Traders</h2>
    <table border="1">
        <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Category</th>
            <th>First Shop Name</th>
            <th>Second Shop Name</th>
            <th>Action</th>
        </tr>
        <?php foreach ($pendingTraders as $trader): ?>
            <tr>
                <td><?= htmlspecialchars($trader['full_name']) ?></td>
                <td><?= htmlspecialchars($trader['email']) ?></td>
                <td><?= htmlspecialchars($trader['category']) ?></td>
                <td><?= htmlspecialchars($trader['first_shop_name']) ?></td>
                <td><?= htmlspecialchars($trader['second_shop_name']) ?></td>

                <td>
                    <form action="approve_trader.php" method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                        <button type="submit" name="approve">Approve</button>
                    </form>
                    <form action="reject_trader.php" method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                        <button type="submit" name="reject">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
