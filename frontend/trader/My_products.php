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

try {
    // Fetch trader's shops
    $shopsQuery = "SELECT * FROM shops WHERE user_id = :user_id";
    $shopsStmt = oci_parse($conn, $shopsQuery);
    oci_bind_by_name($shopsStmt, ':user_id', $user_id);
    oci_execute($shopsStmt);
    
    $shops = [];
    while ($shop = oci_fetch_assoc($shopsStmt)) {
        $shops[] = $shop;
    }

    // Fetch all products for the trader's shops
    $productsQuery = "SELECT p.*, s.shop_name, s.shop_category 
                     FROM product p 
                     JOIN shops s ON p.shop_id = s.shop_id 
                     WHERE s.user_id = :user_id 
                     ORDER BY p.add_date DESC";
    $productsStmt = oci_parse($conn, $productsQuery);
    oci_bind_by_name($productsStmt, ':user_id', $user_id);
    oci_execute($productsStmt);
    
    $products = [];
    while ($product = oci_fetch_assoc($productsStmt)) {
        // Handle CLOB fields
        if (is_object($product['DESCRIPTION']) && get_class($product['DESCRIPTION']) === 'OCILob') {
            $product['DESCRIPTION'] = $product['DESCRIPTION']->read($product['DESCRIPTION']->size());
        }
        $products[] = $product;
    }

    // Handle product deletion
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        
        // Check if product belongs to trader's shop
        $checkQuery = "SELECT p.product_id 
                      FROM product p 
                      JOIN shops s ON p.shop_id = s.shop_id 
                      WHERE p.product_id = :product_id AND s.user_id = :user_id";
        $checkStmt = oci_parse($conn, $checkQuery);
        oci_bind_by_name($checkStmt, ':product_id', $product_id);
        oci_bind_by_name($checkStmt, ':user_id', $user_id);
        oci_execute($checkStmt);
        
        if (oci_fetch($checkStmt)) {
            // Delete product
            $deleteQuery = "DELETE FROM product WHERE product_id = :product_id";
            $deleteStmt = oci_parse($conn, $deleteQuery);
            oci_bind_by_name($deleteStmt, ':product_id', $product_id);
            
            if (oci_execute($deleteStmt)) {
                $success_msg[] = "Product deleted successfully!";
                // Refresh the page to show updated list
                header("Location: My_products.php");
                exit();
            } else {
                $warning_msg[] = "Failed to delete product.";
            }
        } else {
            $warning_msg[] = "Unauthorized to delete this product.";
        }
    }

} catch (Exception $e) {
    $warning_msg[] = "Error: " . $e->getMessage();
} finally {
    if (isset($shopsStmt)) oci_free_statement($shopsStmt);
    if (isset($productsStmt)) oci_free_statement($productsStmt);
    if (isset($checkStmt)) oci_free_statement($checkStmt);
    if (isset($deleteStmt)) oci_free_statement($deleteStmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - FresGrub</title>
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
            max-width: 1200px;
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

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #b71c1c;
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
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: var(--dark);
        }
        
        .product-shop {
            color: var(--secondary);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .product-stock {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .in-stock {
            color: var(--success);
        }
        
        .low-stock {
            color: var(--warning);
        }
        
        .out-of-stock {
            color: var(--danger);
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .no-products i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .no-products p {
            color: #666;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Products</h1>
            <div class="header-actions">
                <a href="traderdashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
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

        <?php if(empty($products)): ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <p>You haven't added any products yet.</p>
                <a href="add_product.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Add Your First Product
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <img src="uploaded_files/<?php echo htmlspecialchars($product['PRODUCT_IMAGE']); ?>" 
                             alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h3>
                            <div class="product-shop">
                                <i class="fas fa-store"></i> <?php echo htmlspecialchars($product['SHOP_NAME']); ?>
                                (<?php echo ucfirst(htmlspecialchars($product['SHOP_CATEGORY'])); ?>)
                            </div>
                            <div class="product-price">£<?php echo number_format($product['PRICE'], 2); ?></div>
                            <div class="product-stock <?php echo strtolower(str_replace(' ', '-', $product['PRODUCT_STATUS'])); ?>">
                                <i class="fas fa-box"></i> <?php echo $product['STOCK']; ?> units in stock
                            </div>
                            <div class="product-actions">
                                <a href="edit_product.php?id=<?php echo $product['PRODUCT_ID']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['PRODUCT_ID']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
