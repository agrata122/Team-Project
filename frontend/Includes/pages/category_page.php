<?php
// Start the session at the very beginning
session_start();

require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed.");
}

// Generate or retrieve session ID from cookie for guest users
if (isset($_COOKIE['session_id'])) {
    $session_id = $_COOKIE['session_id'];
} else {
    $session_id = uniqid();
    setcookie('session_id', $session_id, time() + (60 * 60 * 24 * 30), "/"); // valid for 30 days
}

// Check if user is logged in
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// Initialize message variables
$message = "";
$messageType = "";

// Get category from URL parameter
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Validate category (ensure it's one of the allowed categories)
$valid_categories = array('butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen');
if (!in_array($category, $valid_categories)) {
    // Redirect to homepage or show error
    header("Location: /E-commerce/frontend/Includes/pages/index.php");
    exit();
}

// Format category for display (capitalize first letter)
$display_category = ucfirst($category);

// Handle Add to Cart request
if (isset($_POST['add_to_cart'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

    if (!$product_id || !$qty) {
        die("Invalid product ID or quantity.");
    }

    // If user is not logged in, redirect to login page with return URL
    if (!$user_id) {
        // Store the product info in session to add to cart after login
        $_SESSION['pending_cart_action'] = [
            'product_id' => $product_id,
            'qty' => $qty
        ];
        
        $message = "Please log in to add items to your cart";
        $messageType = "error";
    } else {
        // User is logged in, proceed with cart operations
        
        // Check if cart exists for this user
        $checkCartSql = "SELECT * FROM cart WHERE user_id = :user_id";
        $checkCartStmt = oci_parse($conn, $checkCartSql);
        oci_bind_by_name($checkCartStmt, ':user_id', $user_id);
        oci_execute($checkCartStmt);
        $cart = oci_fetch_assoc($checkCartStmt);

        if (!$cart) {
            // Create new cart
            $insertCartSql = "INSERT INTO cart (user_id, add_date) VALUES (:user_id, SYSDATE)";
            $insertCartStmt = oci_parse($conn, $insertCartSql);
            oci_bind_by_name($insertCartStmt, ':user_id', $user_id);

            if (!oci_execute($insertCartStmt)) {
                $e = oci_error($insertCartStmt);
                die("Cart insert error: " . $e['message']);
            }

            // Get the last inserted cart_id
            $getCartIdSql = "SELECT MAX(cart_id) as cart_id FROM cart WHERE user_id = :user_id";
            $getCartIdStmt = oci_parse($conn, $getCartIdSql);
            oci_bind_by_name($getCartIdStmt, ':user_id', $user_id);
            oci_execute($getCartIdStmt);
            $cartRow = oci_fetch_assoc($getCartIdStmt);
            $cart_id = $cartRow['CART_ID'];
            oci_free_statement($getCartIdStmt);
        } else {
            $cart_id = $cart['CART_ID'];
        }

        if (!isset($cart_id) || !is_numeric($cart_id)) {
            die("Invalid cart ID.");
        }

        // Check if product already in cart
        $checkProductSql = "SELECT * FROM product_cart WHERE cart_id = :cart_id AND product_id = :product_id";
        $checkProductStmt = oci_parse($conn, $checkProductSql);
        oci_bind_by_name($checkProductStmt, ':cart_id', $cart_id);
        oci_bind_by_name($checkProductStmt, ':product_id', $product_id);
        oci_execute($checkProductStmt);

        if (oci_fetch($checkProductStmt)) {
            $message = "Product already in cart!";
            $messageType = "error";
        } else {
            // Insert into product_cart
            $insertProductSql = "INSERT INTO product_cart (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :qty)";
            $insertProductStmt = oci_parse($conn, $insertProductSql);
            oci_bind_by_name($insertProductStmt, ':cart_id', $cart_id);
            oci_bind_by_name($insertProductStmt, ':product_id', $product_id);
            oci_bind_by_name($insertProductStmt, ':qty', $qty);

            if (!oci_execute($insertProductStmt)) {
                $e = oci_error($insertProductStmt);
                die("Insert product error: " . $e['message']);
            }

            // Update product stock
            $updateStockSql = "UPDATE product SET stock = stock - :qty WHERE product_id = :product_id";
            $updateStockStmt = oci_parse($conn, $updateStockSql);
            oci_bind_by_name($updateStockStmt, ':qty', $qty);
            oci_bind_by_name($updateStockStmt, ':product_id', $product_id);

            if (!oci_execute($updateStockStmt)) {
                $e = oci_error($updateStockStmt);
                die("Stock update error: " . $e['message']);
            }

            oci_commit($conn);

            $message = "Product added to cart successfully!";
            $messageType = "success";
        }

        // Free resources
        oci_free_statement($checkCartStmt);
        oci_free_statement($checkProductStmt);
        if (isset($insertCartStmt)) oci_free_statement($insertCartStmt);
        if (isset($insertProductStmt)) oci_free_statement($insertProductStmt);
        if (isset($updateStockStmt)) oci_free_statement($updateStockStmt);
    }
}

// Handle Add to Wishlist request
if (isset($_POST['add_to_wishlist'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);

    if (!$product_id) {
        die("Invalid product ID.");
    }

    if (!$user_id) {
        $_SESSION['pending_wishlist_action'] = $product_id;
        $message = "Please log in to add items to your wishlist";
        $messageType = "error";
    } else {
        // First, check if the user already has a wishlist
        $checkWishlistSql = "SELECT wishlist_id FROM wishlist WHERE user_id = :user_id";
        $checkWishlistStmt = oci_parse($conn, $checkWishlistSql);
        oci_bind_by_name($checkWishlistStmt, ':user_id', $user_id);
        oci_execute($checkWishlistStmt);
        $wishlist = oci_fetch_assoc($checkWishlistStmt);
        
        // If user doesn't have a wishlist, create one
        if (!$wishlist) {
            $createWishlistSql = "INSERT INTO wishlist (user_id, no_of_items) VALUES (:user_id, 0)";
            $createWishlistStmt = oci_parse($conn, $createWishlistSql);
            oci_bind_by_name($createWishlistStmt, ':user_id', $user_id);
            
            if (!oci_execute($createWishlistStmt)) {
                $e = oci_error($createWishlistStmt);
                die("Create wishlist error: " . $e['message']);
            }
            
            // Get the newly created wishlist ID
            $getWishlistIdSql = "SELECT wishlist_id FROM wishlist WHERE user_id = :user_id";
            $getWishlistIdStmt = oci_parse($conn, $getWishlistIdSql);
            oci_bind_by_name($getWishlistIdStmt, ':user_id', $user_id);
            oci_execute($getWishlistIdStmt);
            $wishlist = oci_fetch_assoc($getWishlistIdStmt);
            oci_free_statement($getWishlistIdStmt);
        }
        
        $wishlist_id = $wishlist['WISHLIST_ID'];
        
        // Check if product already in wishlist_product table
        $checkProductSql = "SELECT * FROM wishlist_product WHERE wishlist_id = :wishlist_id AND product_id = :product_id";
        $checkProductStmt = oci_parse($conn, $checkProductSql);
        oci_bind_by_name($checkProductStmt, ':wishlist_id', $wishlist_id);
        oci_bind_by_name($checkProductStmt, ':product_id', $product_id);
        oci_execute($checkProductStmt);

        if (oci_fetch($checkProductStmt)) {
            $message = "Product already in wishlist!";
            $messageType = "error";
        } else {
            // Insert into wishlist_product table
            $insertProductSql = "INSERT INTO wishlist_product (wishlist_id, product_id, added_date) VALUES (:wishlist_id, :product_id, SYSDATE)";
            $insertProductStmt = oci_parse($conn, $insertProductSql);
            oci_bind_by_name($insertProductStmt, ':wishlist_id', $wishlist_id);
            oci_bind_by_name($insertProductStmt, ':product_id', $product_id);

            if (!oci_execute($insertProductStmt)) {
                $e = oci_error($insertProductStmt);
                die("Insert product to wishlist error: " . $e['message']);
            }
            
            // Update the item count in the wishlist table
            $updateWishlistSql = "UPDATE wishlist SET no_of_items = no_of_items + 1 WHERE wishlist_id = :wishlist_id";
            $updateWishlistStmt = oci_parse($conn, $updateWishlistSql);
            oci_bind_by_name($updateWishlistStmt, ':wishlist_id', $wishlist_id);
            
            if (!oci_execute($updateWishlistStmt)) {
                $e = oci_error($updateWishlistStmt);
                die("Update wishlist count error: " . $e['message']);
            }
            
            oci_commit($conn);

            $message = "Product added to wishlist successfully!";
            $messageType = "success";
        }

        // Free resources
        oci_free_statement($checkWishlistStmt);
        oci_free_statement($checkProductStmt);
        if (isset($createWishlistStmt)) oci_free_statement($createWishlistStmt);
        if (isset($insertProductStmt)) oci_free_statement($insertProductStmt);
        if (isset($updateWishlistStmt)) oci_free_statement($updateWishlistStmt);
    }
}

// Fetch products by shop category
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$order_by = '';

switch($sort) {
    case 'price_low_high':
        $order_by = 'ORDER BY p.price ASC';
        break;
    case 'price_high_low':
        $order_by = 'ORDER BY p.price DESC';
        break;
    case 'rating_high_low':
        $order_by = 'ORDER BY avg_rating DESC NULLS LAST';
        break;
    default:
        $order_by = 'ORDER BY p.product_id DESC';
}

$select_products_sql = "
    SELECT p.*, 
    (SELECT AVG(review_rating) FROM review r WHERE r.product_id = p.product_id) as avg_rating 
    FROM product p
    JOIN shops s ON p.shop_id = s.shop_id
    WHERE s.shop_category = :category
    $order_by
";
$select_products_stmt = oci_parse($conn, $select_products_sql);
oci_bind_by_name($select_products_stmt, ':category', $category);
oci_execute($select_products_stmt);

// Count products
$count_products_sql = "
    SELECT COUNT(*) as total 
    FROM product p
    JOIN shops s ON p.shop_id = s.shop_id
    WHERE s.shop_category = :category
";
$count_products_stmt = oci_parse($conn, $count_products_sql);
oci_bind_by_name($count_products_stmt, ':category', $category);
oci_execute($count_products_stmt);
$product_count = oci_fetch_assoc($count_products_stmt)['TOTAL'];
oci_free_statement($count_products_stmt);

// Close connection
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $display_category; ?> Products</title>
   <link rel="stylesheet" href="../../assets/CSS/category_page.css">
   
   <!-- Toastify CSS -->
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
   
   <style>
   .sort-container {
       max-width: 1200px;
       margin: 0 auto 20px;
       padding: 0 20px;
   }
   
   .sort-form {
       display: flex;
       justify-content: flex-end;
   }
   
   .sort-select {
       padding: 8px 12px;
       border: 1px solid #ddd;
       border-radius: 4px;
       background-color: white;
       font-size: 14px;
       cursor: pointer;
       outline: none;
   }
   
   .sort-select:hover {
       border-color: #4CAF50;
   }
   
   .rating {
       display: flex;
       align-items: center;
       gap: 5px;
       margin-top: 5px;
       color: #666;
   }
   
   .rating-stars {
       color: #ffd700;
   }
   
   .no-rating {
       color: #999;
       font-style: italic;
   }
   </style>
</head>
<body>
   <header>
    <?php include '../../Includes/header.php'; ?>
   </header>

<?php if (!$user_id): ?>
<div id="login-message" class="login-message">
   You need to <a href="/E-commerce/frontend/Includes/pages/login.php">log in</a> to add items to your cart or wishlist
</div>
<?php endif; ?>

<div class="page-container">
    <!-- Left Sidebar -->
    <div class="sidebar">
        <!-- Category Sidebar -->
        <?php include '../../Includes/category-popup.php'; ?>
        
        <!-- New Products Popup -->
        <?php include '../../Includes/new_products_popup.php'; ?>
    </div>
    
    <!-- Right Side - Products -->
    <section class="product-section">
       <div class="category-header">
          
          <h1 class="heading"><?php echo $display_category; ?> Products</h1>
       </div>
       
       <!-- Add sorting dropdown -->
       <div class="sort-container">
           <form method="GET" class="sort-form">
               <input type="hidden" name="category" value="<?php echo $category; ?>">
               <select name="sort" onchange="this.form.submit()" class="sort-select">
                   <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sort by: Default</option>
                   <option value="price_low_high" <?= $sort === 'price_low_high' ? 'selected' : '' ?>>Price: Low to High</option>
                   <option value="price_high_low" <?= $sort === 'price_high_low' ? 'selected' : '' ?>>Price: High to Low</option>
                   <option value="rating_high_low" <?= $sort === 'rating_high_low' ? 'selected' : '' ?>>Rating: High to Low</option>
               </select>
           </form>
       </div>
       
       <div class="product-container">
       <?php 
          if($product_count > 0){
             while($fetch_product = oci_fetch_assoc($select_products_stmt)){
                // Properly handle potential CLOB fields
                $description = $fetch_product['DESCRIPTION'];
                if (is_object($description) && get_class($description) === 'OCILob') {
                    $description = $description->read($description->size());
                }
                
                // Handle product image
                $product_image = $fetch_product['PRODUCT_IMAGE'];
                if (is_object($product_image) && get_class($product_image) === 'OCILob') {
                    $product_image = $product_image->read($product_image->size());
                }
       ?>
       <form action="" method="POST" class="product-card" 
      data-name="<?= htmlspecialchars($fetch_product['PRODUCT_NAME']); ?>" 
      data-price="<?= $fetch_product['PRICE']; ?>" 
      data-image="<?= $product_image; ?>"
      data-id="<?= $fetch_product['PRODUCT_ID']; ?>">
      
    <div class="product-card-content"> <!-- clickable wrapper -->
        <div class="image-container">
            <img src="/E-commerce/frontend/trader/uploaded_files/<?= $product_image; ?>" alt="<?= htmlspecialchars($fetch_product['PRODUCT_NAME']); ?>">
            <?php if($fetch_product['STOCK'] < 5): ?>
                <span class="badge">Low Stock</span>
            <?php endif; ?>
        </div>
        <h3><?= htmlspecialchars($fetch_product['PRODUCT_NAME']); ?></h3>
        <p class="price">£<?= number_format($fetch_product['PRICE'], 2); ?></p>
        <p class="stock">Available: <?= $fetch_product['STOCK'] ?> in stock</p>
        <div class="rating">
            <?php if ($fetch_product['AVG_RATING']): ?>
                <span class="rating-stars">
                    <?php
                    $rating = round($fetch_product['AVG_RATING']);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $rating ? '★' : '☆';
                    }
                    ?>
                </span>
                <span>(<?= number_format($fetch_product['AVG_RATING'], 1) ?>)</span>
            <?php else: ?>
                <span class="no-rating">No ratings yet</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-actions">
        <input type="hidden" name="product_id" value="<?= $fetch_product['PRODUCT_ID']; ?>">
        <input type="hidden" name="qty" class="hidden-qty" value="1">

        <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>

        <div class="quantity-container" style="display: none;">
            <button type="button" class="quantity-btn decrease">▼</button>
            <input type="text" class="quantity-input" value="1" readonly>
            <button type="button" class="quantity-btn increase">▲</button>
        </div>

        <button type="submit" name="add_to_wishlist" class="wishlist-btn">
            <span class="wishlist">&#9825;</span>
        </button>
    </div>
</form>

       <?php
             }
             oci_free_statement($select_products_stmt);
          } else {
             echo '<p class="empty">No ' . $display_category . ' products added!</p>';
          }
       ?>
       </div>
    </section>
</div>



<!-- Toastify JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Get message from PHP
    let message = "<?= isset($message) ? $message : ''; ?>";
    let messageType = "<?= isset($messageType) ? $messageType : ''; ?>";
    
    // Check if there's a message to show
    if (message !== "") {
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: 'top',
            position: 'right',
            backgroundColor: messageType === "success" ? "green" : "red",
        }).showToast();
        
        // If it's an error about logging in, show the login message
        if (message.includes("log in") && document.getElementById("login-message")) {
            document.getElementById("login-message").style.display = "block";
        }
    }

    // Handle product card interactions
    document.querySelectorAll(".product-card").forEach((card) => {
        const addToCartBtn = card.querySelector(".add-to-cart");
        const quantityContainer = card.querySelector(".quantity-container");
        const decreaseBtn = card.querySelector(".decrease");
        const increaseBtn = card.querySelector(".increase");
        const quantityInput = card.querySelector(".quantity-input");
        const hiddenQtyInput = card.querySelector(".hidden-qty");

        // Make product card redirect to detail page
document.querySelectorAll(".product-card .product-card-content").forEach(card => {
    card.addEventListener("click", function (e) {
        const parentForm = this.closest(".product-card");

        // Prevent redirect if the click originated from an interactive element
        if (e.target.closest('.card-actions')) return;

        const productId = parentForm.getAttribute("data-id");
        window.location.href = `/E-commerce/frontend/includes/pages/product_detail.php?product_id=${productId}`;
    });
});


        // Show quantity selector when "Add to Cart" is clicked
        addToCartBtn.addEventListener("click", function (e) {
            if (quantityContainer.style.display === "none") {
                e.preventDefault();
                quantityContainer.style.display = "flex";
                addToCartBtn.textContent = "Confirm";
            } else {
                // Update the hidden quantity field before form submission
                hiddenQtyInput.value = quantityInput.value;
            }
        });

        // Quantity adjustment buttons
        decreaseBtn.addEventListener("click", function (e) {
            e.preventDefault();
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseBtn.addEventListener("click", function (e) {
            e.preventDefault();
            let currentValue = parseInt(quantityInput.value) || 1;
            quantityInput.value = currentValue + 1;
        });
    });

    // Find all wishlist buttons and attach event handlers
    document.querySelectorAll(".wishlist-btn").forEach(button => {
        button.addEventListener("click", function(e) {
            e.preventDefault();
            const form = this.closest("form");
            const wishlistIcon = this.querySelector(".wishlist");
            const productId = form.querySelector('input[name="product_id"]').value;
            const productName = form.getAttribute('data-name');
            
            // Check if user is logged in
            const isLoggedIn = <?= $user_id ? 'true' : 'false'; ?>;
            
            if (!isLoggedIn) {
                Toastify({
                    text: "Please log in to add items to your wishlist",
                    duration: 3000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: "red",
                }).showToast();
                
                document.getElementById("login-message").style.display = "block";
                return;
            }
            
            // Toggle wishlist icon immediately for better UX
            wishlistIcon.classList.toggle('active');
            
            // Submit the form with wishlist action
            const formData = new FormData(form);
            formData.append('add_to_wishlist', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                }
                throw new Error('Network response was not ok.');
            })
            .then(() => {
                // Show success message without reloading the page
                Toastify({
                    text: wishlistIcon.classList.contains('active') ? 
                          `${productName} added to wishlist!` : 
                          `${productName} removed from wishlist!`,
                    duration: 3000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: "green",
                }).showToast();
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert the icon if there was an error
                wishlistIcon.classList.toggle('active');
                
                Toastify({
                    text: "Error updating wishlist",
                    duration: 3000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: "red",
                }).showToast();
            });
        });
    });
});
</script>

<?php include '../../Includes/footer.php'; ?>
</body>
</html>