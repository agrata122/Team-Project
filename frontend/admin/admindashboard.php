<?php
session_start();
require_once "../../backend/db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: \E-commerce\frontend\Includes\pages\login.php");
    exit;
}

$db = getDBConnection();
if (!$db) {
    die("Database connection failed.");
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trader actions
    if (isset($_POST['approve_trader'])) {
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    } elseif (isset($_POST['reject_trader'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
    
    // Customer actions
    if (isset($_POST['update_customer'])) {
        // Handle customer updates
    } elseif (isset($_POST['delete_customer'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
}

// Fetch data
$pendingTraders = $db->query("
    SELECT u.user_id, u.full_name, u.email, u.created_date, 
           GROUP_CONCAT(s.shop_type, ': ', s.shop_name SEPARATOR ', ') AS shop_details
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader' AND u.status = 'pending'
    GROUP BY u.user_id
")->fetchAll();

$allTraders = $db->query("
    SELECT u.user_id, u.full_name, u.email, u.created_date, u.status,
           GROUP_CONCAT(s.shop_type, ': ', s.shop_name SEPARATOR ', ') AS shop_details
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader'
    GROUP BY u.user_id
")->fetchAll();

$customers = $db->query("
    SELECT user_id, full_name, email,created_date, status
    FROM users
    WHERE role = 'customer'
    ORDER BY created_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FresGrub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #005005;
            --secondary: #0288d1;
            --dark: #263238;
            --light: #f5f7fa;
            --success: #388e3c;
            --warning: #f57c00;
            --danger: #d32f2f;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-dark);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--primary);
            border-left: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            background-color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-bar input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex: 1;
            max-width: 400px;
        }
        
        .search-bar button {
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab-content.active {
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--primary);
            color: white;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: var(--warning);
            font-weight: bold;
        }
        
        .status-approved {
            color: var(--success);
            font-weight: bold;
        }
        
        .status-active {
            color: var(--success);
            font-weight: bold;
        }
        
        .status-inactive {
            color: var(--danger);
            font-weight: bold;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 13px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .form-inline {
            display: inline;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-title {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span>FresGrub</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="active" onclick="switchTab('customers')">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="switchTab('pending-traders')">
                    <i class="fas fa-user-clock"></i>
                    <span>Pending Traders</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="switchTab('all-traders')">
                    <i class="fas fa-user-tie"></i>
                    <span>All Traders</span>
                </a>
            </li>
            <li>
                <a href="logout_admin.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="Admin">
                <span>Welcome, Admin</span>
            </div>
        </div>
        
        <div class="search-bar">
            <input type="text" placeholder="Search users...">
            <button><i class="fas fa-search"></i> Search</button>
        </div>
        
        <!-- Customers Tab -->
        <div id="customers-tab" class="tab-content active">
            <div class="card">
                <h3 class="card-title"><i class="fas fa-users"></i> Customer Management</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['user_id']) ?></td>
                                <td><?= htmlspecialchars($customer['full_name']) ?></td>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($customer['created_date'])) ?></td>
                                <td class="status-<?= strtolower($customer['status'] ?? 'active') ?>">
                                    <?= ucfirst(htmlspecialchars($customer['status'] ?? 'active')) ?>
                                </td>
                                <td class="action-buttons">
                                    <button onclick="openEditModal(<?= $customer['user_id'] ?>)" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?= $customer['user_id'] ?>">
                                        <button type="submit" name="delete_customer" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pending Traders Tab -->
        <div id="pending-traders-tab" class="tab-content">
            <div class="card">
                <h3 class="card-title"><i class="fas fa-user-clock"></i> Traders Pending Approval</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Shops</th>
                            <th>Joined On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingTraders as $trader): ?>
                            <tr>
                                <td><?= htmlspecialchars($trader['user_id']) ?></td>
                                <td><?= htmlspecialchars($trader['full_name']) ?></td>
                                <td><?= htmlspecialchars($trader['email']) ?></td>
                                <td><?= htmlspecialchars($trader['shop_details'] ?? 'No shops') ?></td>
                                <td><?= date('M d, Y', strtotime($trader['created_date'])) ?></td>
                                <td class="action-buttons">
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                        <button type="submit" name="approve_trader" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                        <button type="submit" name="reject_trader" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pendingTraders)): ?>
                            <tr>
                                <td colspan="6" class="no-data">No traders pending approval</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- All Traders Tab -->
        <div id="all-traders-tab" class="tab-content">
            <div class="card">
                <h3 class="card-title"><i class="fas fa-user-tie"></i> All Traders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Shops</th>
                            <th>Status</th>
                            <th>Joined On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTraders as $trader): ?>
                            <tr>
                                <td><?= htmlspecialchars($trader['user_id']) ?></td>
                                <td><?= htmlspecialchars($trader['full_name']) ?></td>
                                <td><?= htmlspecialchars($trader['email']) ?></td>
                                <td><?= htmlspecialchars($trader['shop_details'] ?? 'No shops') ?></td>
                                <td class="status-<?= strtolower($trader['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($trader['status'])) ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($trader['created_date'])) ?></td>
                                <td class="action-buttons">
                                    <button onclick="openEditModal(<?= $trader['user_id'] ?>)" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?= $trader['user_id'] ?>">
                                        <button type="submit" name="reject_trader" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Edit User</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="update_customer" value="1">
                
                <div class="form-group">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Update sidebar active item
            document.querySelectorAll('.sidebar-menu a').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show the selected tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tabName}-tab`).classList.add('active');
        }
        
        // Modal functions
        function openEditModal(userId) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>