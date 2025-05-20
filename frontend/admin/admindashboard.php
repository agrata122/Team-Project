<?php
session_start();
require_once "../../backend/connect.php"; // Ensure this path is correct

// Check admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /E-commerce/frontend/Includes/pages/login.php");
    exit;
}

$conn = getDBConnection(); 

if (!$conn) {
    die("Database connection failed.");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;

    if ($user_id) {
        if (isset($_POST['approve_trader'])) {
            $stmt = oci_parse($conn, "UPDATE users SET status = 'active' WHERE user_id = :user_id");
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
        } elseif (isset($_POST['reject_trader'])) {
            $stmt = oci_parse($conn, "DELETE FROM users WHERE user_id = :user_id");
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
        } elseif (isset($_POST['delete_customer'])) {
            $stmt = oci_parse($conn, "DELETE FROM users WHERE user_id = :user_id");
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
        } elseif (isset($_POST['update_customer'])) {
            // Handle update customer logic if needed
            $full_name = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            $stmt = oci_parse($conn, "UPDATE users SET full_name = :full_name, email = :email, status = :status WHERE user_id = :user_id");
            oci_bind_by_name($stmt, ":full_name", $full_name);
            oci_bind_by_name($stmt, ":email", $email);
            oci_bind_by_name($stmt, ":status", $status);
            oci_bind_by_name($stmt, ":user_id", $user_id);
            oci_execute($stmt);
        }
    }
}

// Helper function to fetch all results from an OCI statement
function fetchAllOCI($stmt) {
    $results = [];
    oci_execute($stmt); // Make sure statement is executed before fetching
    while ($row = oci_fetch_assoc($stmt)) {
        // Convert all keys to uppercase for consistency (Oracle returns uppercase column names)
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedRow[strtoupper($key)] = $value;
        }
        $results[] = $normalizedRow;
    }
    return $results;
}

// Fetch pending traders
$pendingQuery = "
    SELECT u.user_id AS USER_ID, u.full_name AS FULL_NAME, u.email AS EMAIL, u.created_date AS CREATED_DATE,
           LISTAGG(s.shop_category || ': ' || s.shop_name, ', ') 
           WITHIN GROUP (ORDER BY s.shop_name) AS SHOP_DETAILS
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader' AND u.status = 'pending'
    GROUP BY u.user_id, u.full_name, u.email, u.created_date
";
$pendingStmt = oci_parse($conn, $pendingQuery);
$pendingTraders = fetchAllOCI($pendingStmt);

// Fetch all traders
$allTradersQuery = "
    SELECT u.user_id AS USER_ID, u.full_name AS FULL_NAME, u.email AS EMAIL, u.created_date AS CREATED_DATE, u.status AS STATUS,
           LISTAGG(s.shop_category || ': ' || s.shop_name, ', ') 
           WITHIN GROUP (ORDER BY s.shop_name) AS SHOP_DETAILS
    FROM users u
    LEFT JOIN shops s ON u.user_id = s.user_id
    WHERE u.role = 'trader'
    GROUP BY u.user_id, u.full_name, u.email, u.created_date, u.status
";
$allTradersStmt = oci_parse($conn, $allTradersQuery);
$allTraders = fetchAllOCI($allTradersStmt);

// Fetch all customers with explicit column names
$customersQuery = "
    SELECT user_id AS USER_ID, full_name AS FULL_NAME, email AS EMAIL, created_date AS CREATED_DATE, status AS STATUS
    FROM users
    WHERE role = 'customer'
    ORDER BY created_date DESC
";
$customersStmt = oci_parse($conn, $customersQuery);
$customers = fetchAllOCI($customersStmt);
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FresGrub</title>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* CSS styles for the dashboard */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color:rgb(38, 94, 50);
            height: 100vh;
            color: white;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 10px;
            color: #2ecc71;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            padding: 0;
        }
        
        .sidebar-menu a {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color:rgb(16, 56, 33);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main content styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
            margin-bottom: 20px;
        }
        
        .search-bar input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }
        
        .search-bar button {
            padding: 10px 15px;
            background-color:rgb(35, 86, 56);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-title {
            margin-top: 0;
            color:rgb(50, 95, 49);
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            color: #3498db;
        }
        
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        thead th {
            background-color: #f8f9fa;
            color: #2c3e50;
        }
        
        tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-active {
            color: #2ecc71;
            font-weight: 500;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: 500;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .form-inline {
            display: inline;
        }
        
        /* Tab content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
                        <?php 
                        // Debug check to see if keys exist
                        if (!isset($customer['USER_ID']) || !isset($customer['FULL_NAME']) || 
                            !isset($customer['EMAIL']) || !isset($customer['CREATED_DATE'])) {
                            continue; // Skip this record if keys are missing
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['USER_ID']) ?></td>
                            <td><?= htmlspecialchars($customer['FULL_NAME']) ?></td>
                            <td><?= htmlspecialchars($customer['EMAIL']) ?></td>
                            <td><?= date('M d, Y', strtotime($customer['CREATED_DATE'])) ?></td>
                            <td class="status-<?= strtolower($customer['STATUS'] ?? 'active') ?>">
                                <?= ucfirst(htmlspecialchars($customer['STATUS'] ?? 'active')) ?>
                            </td>
                            <td class="action-buttons">
                                <button onclick="openEditModal('<?= $customer['USER_ID'] ?>', '<?= htmlspecialchars($customer['FULL_NAME']) ?>', '<?= htmlspecialchars($customer['EMAIL']) ?>', '<?= htmlspecialchars($customer['STATUS'] ?? 'active') ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $customer['USER_ID'] ?>">
                                    <button type="submit" name="delete_customer" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6" class="no-data">No customers found</td>
                        </tr>
                    <?php endif; ?>
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
                            <td><?= htmlspecialchars($trader['USER_ID']) ?></td>
                            <td><?= htmlspecialchars($trader['FULL_NAME']) ?></td>
                            <td><?= htmlspecialchars($trader['EMAIL']) ?></td>
                            <td><?= htmlspecialchars($trader['SHOP_DETAILS'] ?? 'No shops') ?></td>
                            <td><?= date('M d, Y', strtotime($trader['CREATED_DATE'])) ?></td>
                            <td class="action-buttons">
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['USER_ID'] ?>">
                                    <button type="submit" name="approve_trader" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['USER_ID'] ?>">
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
                            <td><?= htmlspecialchars($trader['USER_ID']) ?></td>
                            <td><?= htmlspecialchars($trader['FULL_NAME']) ?></td>
                            <td><?= htmlspecialchars($trader['EMAIL']) ?></td>
                            <td><?= htmlspecialchars($trader['SHOP_DETAILS'] ?? 'No shops') ?></td>
                            <td class="status-<?= strtolower($trader['STATUS']) ?>">
                                <?= ucfirst(htmlspecialchars($trader['STATUS'])) ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($trader['CREATED_DATE'])) ?></td>
                            <td class="action-buttons">
                                <button onclick="openEditModal('<?= $trader['USER_ID'] ?>', '<?= htmlspecialchars($trader['FULL_NAME']) ?>', '<?= htmlspecialchars($trader['EMAIL']) ?>', '<?= htmlspecialchars($trader['STATUS']) ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?= $trader['USER_ID'] ?>">
                                    <button type="submit" name="reject_trader" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($allTraders)): ?>
                        <tr>
                            <td colspan="7" class="no-data">No traders found</td>
                        </tr>
                    <?php endif; ?>
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
    function openEditModal(userId, fullName, email, status) {
        document.getElementById('modalUserId').value = userId;
        document.getElementById('editFullName').value = fullName;
        document.getElementById('editEmail').value = email;
        document.getElementById('editStatus').value = status;
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