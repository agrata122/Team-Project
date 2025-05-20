<?php
session_start();

require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed.");
}

// Debug: Print session info
echo "<!-- Debug: User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . " -->\n";

// Check if user is logged in
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: /E-commerce/frontend/Includes/pages/login.php");
    exit();
}

// Get or create wishlist ID for the current user
$wishlistId = null;
$wishlistCheckSql = "SELECT wishlist_id FROM wishlist WHERE user_id = :user_id";
$wishlistCheckStmt = oci_parse($conn, $wishlistCheckSql);
oci_bind_by_name($wishlistCheckStmt, ':user_id', $user_id);
oci_execute($wishlistCheckStmt);

if ($row = oci_fetch_assoc($wishlistCheckStmt)) {
    $wishlistId = $row['WISHLIST_ID'];
    echo "<!-- Debug: Found existing wishlist ID: $wishlistId -->\n";
} else {
    // Create a new wishlist for the user
    $createWishlistSql = "INSERT INTO wishlist (no_of_items, user_id) VALUES (0, :user_id)";
    $createStmt = oci_parse($conn, $createWishlistSql);
    oci_bind_by_name($createStmt, ':user_id', $user_id);
    if (oci_execute($createStmt)) {
        oci_commit($conn); // Explicit commit for Oracle
        echo "<!-- Debug: Created new wishlist -->\n";
        
        // Get the newly created wishlist ID
        $wishlistCheckStmt = oci_parse($conn, $wishlistCheckSql);
        oci_bind_by_name($wishlistCheckStmt, ':user_id', $user_id);
        oci_execute($wishlistCheckStmt);
        if ($row = oci_fetch_assoc($wishlistCheckStmt)) {
            $wishlistId = $row['WISHLIST_ID'];
            echo "<!-- Debug: New wishlist ID: $wishlistId -->\n";
        }
    } else {
        $e = oci_error($createStmt);
        echo "<!-- Debug: Error creating wishlist: " . htmlentities($e['message']) . " -->\n";
    }
    oci_free_statement($createStmt);
}
oci_free_statement($wishlistCheckStmt);

// Debug: Print current wishlist ID
echo "<!-- Debug: Using wishlist ID: $wishlistId -->\n";
// Handle remove from wishlist
if (isset($_GET['remove'])) {
    $product_id = filter_var($_GET['remove'], FILTER_SANITIZE_NUMBER_INT);
    
    $deleteSql = "DELETE FROM wishlist_product WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
    $deleteStmt = oci_parse($conn, $deleteSql);
    oci_bind_by_name($deleteStmt, ':wishlist_id', $wishlistId);
    oci_bind_by_name($deleteStmt, ':product_id', $product_id);
    
    if (oci_execute($deleteStmt)) {
        // Update the item count in the wishlist table
        $updateCountSql = "UPDATE wishlist SET no_of_items = no_of_items - 1 WHERE wishlist_id = :wishlist_id";
        $updateStmt = oci_parse($conn, $updateCountSql);
        oci_bind_by_name($updateStmt, ':wishlist_id', $wishlistId);
        oci_execute($updateStmt);
        oci_free_statement($updateStmt);
        
        $message = "Product removed from wishlist!";
        $messageType = "success";
    } else {
        $message = "Failed to remove product from wishlist!";
        $messageType = "error";
    }
    
    oci_free_statement($deleteStmt);
}

// Fetch wishlist items
$wishlistSql = "SELECT p.* FROM product p 
                JOIN wishlist_product wp ON p.product_id = wp.product_id 
                WHERE wp.wishlist_id = :wishlist_id 
                ORDER BY wp.added_date DESC";
$wishlistStmt = oci_parse($conn, $wishlistSql);
oci_bind_by_name($wishlistStmt, ':wishlist_id', $wishlistId);
oci_execute($wishlistStmt);

// Count wishlist items
$wishlist_count = 0;
$countStmt = oci_parse($conn, "SELECT no_of_items AS total FROM wishlist WHERE wishlist_id = :wishlist_id");
oci_bind_by_name($countStmt, ':wishlist_id', $wishlistId);
oci_execute($countStmt);
if ($row = oci_fetch_assoc($countStmt)) {
    $wishlist_count = $row['TOTAL'];
}
oci_free_statement($countStmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Wishlist</title>
   
   <!-- Toastify CSS -->
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
   
   <style>
   /* Base styles */
   * {
       box-sizing: border-box;
       margin: 0;
       padding: 0;
       font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
   }

   body {
       background-color: #f5f5f5;
       padding: 0px;
   }

   .heading {
       text-align: center;
       font-size: 2.5rem;
       margin-bottom: 30px;
       color: #333;
   }

   .empty {
       text-align: center;
       color: #777;
       font-size: 1.2rem;
       margin-top: 50px;
       grid-column: 1 / -1;
   }

   .login-message {
       text-align: center;
       margin-bottom: 20px;
       padding: 10px;
       background-color: #f8d7da;
       color: #721c24;
       border-radius: 5px;
       display: none;
   }

   /* Product Section */
   .product-section {
       padding: 20px;
       background: #ffffff;
       max-width: 1400px;
       margin: 0 auto;
       border-radius: 10px;
   }

   /* Product Container */
   .product-container {
       display: grid;
       grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
       gap: 25px;
   }

   /* Product Card */
   .product-card {
       border: 1px solid #ddd;
       border-radius: 8px;
       padding: 15px;
       background: #fff;
       box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
       text-align: left;
       transition: all 0.3s ease;
   }

   .product-card:hover {
       transform: translateY(-5px);
       box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
   }

   .image-container {
       background: #f5f5f5;
       border-radius: 8px;
       padding: 10px 0;
       text-align: center;
       margin-bottom: 10px;
       position: relative;
       height: 150px;
       display: flex;
       align-items: center;
       justify-content: center;
   }

   .product-card img {
       max-width: 100%;
       max-height: 130px;
       object-fit: contain;
   }

   .badge {
       position: absolute;
       top: 10px;
       right: 10px;
       background: #e74c3c;
       color: white;
       padding: 3px 8px;
       border-radius: 3px;
       font-size: 0.7rem;
       z-index: 1;
   }

   .product-card h3 {
       margin: 10px 0;
       font-size: 15px;
       white-space: nowrap;
       overflow: hidden;
       text-overflow: ellipsis;
   }

   .price {
       color: green;
       margin: 10px 0;
       font-weight: bold;
   }

   .stock {
       font-size: 0.8rem;
       color: #666;
       margin-bottom: 10px;
   }

   .card-actions {
       display: flex;
       justify-content: space-between;
       align-items: center;
       margin-top: 15px;
   }

   .add-to-cart {
       background: #fff;
       color: black;
       border: 1px solid rgb(124, 124, 124);
       padding: 6px 10px;
       cursor: pointer;
       border-radius: 15px;
       font-size: 13px;
       transition: all 0.3s ease;
   }

   .add-to-cart:hover {
       background: #000;
       color: #fff;
   }

   /* Quantity Selector */
   .quantity-container {
       display: flex;
       align-items: center;
       border: 1px solid #ccc;
       border-radius: 15px;
       padding: 5px 10px;
       background: #ffffff;
       justify-content: center;
       gap: 5px;
   }

   .quantity-input {
       width: 20px;
       text-align: center;
       border: none;
       font-size: 11px;
       background: transparent;
       pointer-events: none;
   }

   .quantity-btn {
       background: none;
       border: none;
       cursor: pointer;
       font-size: 10px;
       font-weight: bold;
       color: rgb(55, 55, 55);
       padding: 2px;
       line-height: 1;
   }

   .quantity-btn:hover {
       color: #000;
   }

   .wishlist {
       background: none;
       border: none;
       font-size: 18px;
       cursor: pointer;
       color: gray;
       transition: color 0.3s ease;
   }

   .wishlist:hover {
       color: red;
   }

   .remove-btn {
       background: #ff6b6b;
       color: white;
       border: none;
       padding: 5px 10px;
       border-radius: 5px;
       cursor: pointer;
       font-size: 12px;
       margin-top: 10px;
   }
   
   .remove-btn:hover {
       background: #ff5252;
   }
   
   .wishlist-header {
       display: flex;
       justify-content: space-between;
       align-items: center;
       margin-bottom: 20px;
   }

   /* Responsive adjustments */
   @media (max-width: 1200px) {
       .product-container {
           grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
       }
   }

   @media (max-width: 768px) {
       .product-container {
           grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
           gap: 15px;
       }
       
       .product-card {
           padding: 10px;
       }
   }
   
   
   </style>
</head>
<body>
    <header>
    <?php
include '../../Includes/header.php'; 
?>
        
    </header>

<section class="product-section">
   <div class="wishlist-header">
       <h1 class="heading">My Wishlist</h1>
       <p><?= $wishlist_count ?> items</p>
   </div>
   
   <div class="product-container">
   <?php 
      if($wishlist_count > 0){
         while($product = oci_fetch_assoc($wishlistStmt)){
            // Handle potential CLOB fields
            $description = $product['DESCRIPTION'];
            if (is_object($description) && get_class($description) === 'OCILob') {
                $description = $description->read($description->size());
            }
            
            // Handle product image
            $product_image = $product['PRODUCT_IMAGE'];
            if (is_object($product_image) && get_class($product_image) === 'OCILob') {
                $product_image = $product_image->read($product_image->size());
            }
   ?>
   <div class="product-card">
      <div class="image-container">
          <img src="/E-commerce/frontend/trader/uploaded_files/<?= $product_image; ?>" alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>">
          <?php if($product['STOCK'] < 5): ?>
              <span class="badge">Low Stock</span>
          <?php endif; ?>
      </div>
      <h3><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h3>
      <p class="price">RS. <?php echo number_format($product['PRICE'], 2); ?></p>
      <p class="stock">Available: <?= $product['STOCK'] ?> in stock</p>
      
      <div class="card-actions">
          <form action="wishlist.php" method="GET">
              <input type="hidden" name="remove" value="<?= $product['PRODUCT_ID'] ?>">
              <button type="submit" class="remove-btn">Remove</button>
          </form>
          
         <!-- Replace with this fixed code -->
<form action="/E-commerce/frontend/Includes/pages/product_list.php" method="POST" style="display: inline;">
    <input type="hidden" name="product_id" value="<?= $product['PRODUCT_ID'] ?>">
    <input type="hidden" name="qty" value="1">
    <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
</form>
      </div>
   </div>
   <?php
         }
         oci_free_statement($wishlistStmt);
      } else {
         echo '<p class="empty">Your wishlist is empty!</p>';
      }
   ?>
   </div>
</section>

<!-- Toastify JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Show message if any
    let message = "<?= isset($message) ? $message : ''; ?>";
    let messageType = "<?= isset($messageType) ? $messageType : ''; ?>";
    
    if (message !== "") {
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: 'top',
            position: 'right',
            backgroundColor: messageType === "success" ? "green" : "red",
        }).showToast();
    }
});
</script>

</body>
<?php
include '../../Includes/footer.php';
?>
</html>