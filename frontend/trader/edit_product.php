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
    die("❌ Failed to connect to Oracle database.");
}

$user_id = $_SESSION['user_id'];
$success_msg = [];
$warning_msg = [];
$product = null;

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: My_products.php");
    exit();
}

$product_id = $_GET['id'];

try {
    // Fetch product details and verify ownership
    $query = "SELECT p.*, s.shop_name 
              FROM product p 
              JOIN shops s ON p.shop_id = s.shop_id 
              WHERE p.product_id = :product_id AND s.user_id = :user_id";
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ':product_id', $product_id);
    oci_bind_by_name($stmt, ':user_id', $user_id);
    oci_execute($stmt);
    
    $product = oci_fetch_assoc($stmt);
    
    if (!$product) {
        header("Location: My_products.php");
        exit();
    }

    // Handle CLOB fields
    if (is_object($product['DESCRIPTION']) && get_class($product['DESCRIPTION']) === 'OCILob') {
        $product['DESCRIPTION'] = $product['DESCRIPTION']->read($product['DESCRIPTION']->size());
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $product_status = $_POST['product_status'];

        // Update product
        $updateQuery = "UPDATE product 
                       SET product_name = :product_name,
                           description = :description,
                           price = :price,
                           stock = :stock,
                           product_status = :product_status
                       WHERE product_id = :product_id";
        
        $updateStmt = oci_parse($conn, $updateQuery);
        oci_bind_by_name($updateStmt, ':product_name', $product_name);
        oci_bind_by_name($updateStmt, ':description', $description);
        oci_bind_by_name($updateStmt, ':price', $price);
        oci_bind_by_name($updateStmt, ':stock', $stock);
        oci_bind_by_name($updateStmt, ':product_status', $product_status);
        oci_bind_by_name($updateStmt, ':product_id', $product_id);

        if (oci_execute($updateStmt)) {
            $success_msg[] = "Product updated successfully!";
            // Refresh product data
            oci_execute($stmt);
            $product = oci_fetch_assoc($stmt);
            
            // Handle CLOB fields after refresh
            if (is_object($product['DESCRIPTION']) && get_class($product['DESCRIPTION']) === 'OCILob') {
                $product['DESCRIPTION'] = $product['DESCRIPTION']->read($product['DESCRIPTION']->size());
            }
        } else {
            $warning_msg[] = "Failed to update product.";
        }
    }

} catch (Exception $e) {
    $warning_msg[] = "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) oci_free_statement($stmt);
    if (isset($updateStmt)) oci_free_statement($updateStmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - FresGrub</title>
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            margin: 0;
            color: var(--primary);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        
        .success-msg {
            background-color: #e8f5e9;
            color: var(--success);
            border: 1px solid #c8e6c9;
        }
        
        .warning-msg {
            background-color: #fff3e0;
            color: var(--warning);
            border: 1px solid #ffe0b2;
        }
        
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Product</h1>
            <a href="My_products.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="message success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg[0]; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($warning_msg)): ?>
            <div class="message warning-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo $warning_msg[0]; ?>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" id="product_name" name="product_name" class="form-control" 
                           value="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required><?php 
                        echo htmlspecialchars($product['DESCRIPTION']); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (£)</label>
                    <input type="number" id="price" name="price" class="form-control" 
                           value="<?php echo htmlspecialchars($product['PRICE']); ?>" 
                           step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" class="form-control" 
                           value="<?php echo htmlspecialchars($product['STOCK']); ?>" 
                           min="0" required>
                </div>

                <div class="form-group">
                    <label for="product_status">Status</label>
                    <select id="product_status" name="product_status" class="form-control" required>
                        <option value="In Stock" <?php echo $product['PRODUCT_STATUS'] === 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="Low Stock" <?php echo $product['PRODUCT_STATUS'] === 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="Out of Stock" <?php echo $product['PRODUCT_STATUS'] === 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="My_products.php" class="btn btn-primary" style="background-color: #666;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 