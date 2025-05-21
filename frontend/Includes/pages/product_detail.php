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

// Check if product ID is provided
// if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
//     header("Location: /E-commerce/frontend/Includes/pages/product_list.php");
//     exit();
// }

$product_id = filter_var($_GET['product_id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch product details
$product_sql = "SELECT p.*, p.product_category_name FROM product p  
               WHERE p.product_id = :product_id";
$product_stmt = oci_parse($conn, $product_sql);
oci_bind_by_name($product_stmt, ':product_id', $product_id);
oci_execute($product_stmt);
$product = oci_fetch_assoc($product_stmt);

// if (!$product) {
//     header("Location: /E-commerce/frontend/Includes/pages/product_list.php");
//     exit();
// }

// Handle CLOB fields
$description = $product['DESCRIPTION'];
if (is_object($description) && get_class($description) === 'OCILob') {
    $description = $description->read($description->size());
}

// Handle product image
$product_image = $product['PRODUCT_IMAGE'];
if (is_object($product_image) && get_class($product_image) === 'OCILob') {
    $product_image = $product_image->read($product_image->size());
}

// Process Add to Cart
if (isset($_POST['add_to_cart'])) {
    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

    if (!$qty) {
        die("Invalid quantity.");
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
            // Update quantity if product already in cart
            $updateQtySql = "UPDATE product_cart SET quantity = quantity + :qty WHERE cart_id = :cart_id AND product_id = :product_id";
            $updateQtyStmt = oci_parse($conn, $updateQtySql);
            oci_bind_by_name($updateQtyStmt, ':cart_id', $cart_id);
            oci_bind_by_name($updateQtyStmt, ':product_id', $product_id);
            oci_bind_by_name($updateQtyStmt, ':qty', $qty);

            if (!oci_execute($updateQtyStmt)) {
                $e = oci_error($updateQtyStmt);
                die("Update quantity error: " . $e['message']);
            }

            $message = "Cart updated successfully!";
            $messageType = "success";
            oci_free_statement($updateQtyStmt);
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

            $message = "Product added to cart successfully!";
            $messageType = "success";
            oci_free_statement($insertProductStmt);
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
        oci_free_statement($updateStockStmt);
        oci_free_statement($checkCartStmt);
        oci_free_statement($checkProductStmt);
        if (isset($insertCartStmt)) oci_free_statement($insertCartStmt);
    }
}

// Process Add to Wishlist
if (isset($_POST['add_to_wishlist'])) {
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
            oci_free_statement($createWishlistStmt);
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

            oci_free_statement($insertProductStmt);
            oci_free_statement($updateWishlistStmt);
        }

        oci_free_statement($checkWishlistStmt);
        oci_free_statement($checkProductStmt);
    }
}

// Process Review Submission
if (isset($_POST['submit_review'])) {
    if (!$user_id) {
        $message = "Please log in to submit a review";
        $messageType = "error";
    } else {
        $rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
        $review_text = filter_var($_POST['review_text'], FILTER_SANITIZE_STRING);

        if ($rating < 1 || $rating > 5) {
            $message = "Invalid rating value";
            $messageType = "error";
        } else {
            // Check if user has already reviewed this product
            $checkReviewSql = "SELECT * FROM review WHERE user_id = :user_id AND product_id = :product_id";
            $checkReviewStmt = oci_parse($conn, $checkReviewSql);
            oci_bind_by_name($checkReviewStmt, ':user_id', $user_id);
            oci_bind_by_name($checkReviewStmt, ':product_id', $product_id);
            oci_execute($checkReviewStmt);

            if (oci_fetch($checkReviewStmt)) {
                // Update existing review
                $updateReviewSql = "UPDATE review SET review_rating = :review_rating, review = :review, review_date = SYSDATE WHERE user_id = :user_id AND product_id = :product_id";
                $updateReviewStmt = oci_parse($conn, $updateReviewSql);
                oci_bind_by_name($updateReviewStmt, ':review_rating', $rating);
                oci_bind_by_name($updateReviewStmt, ':review', $review_text);
                oci_bind_by_name($updateReviewStmt, ':user_id', $user_id);
                oci_bind_by_name($updateReviewStmt, ':product_id', $product_id);

                if (!oci_execute($updateReviewStmt)) {
                    $e = oci_error($updateReviewStmt);
                    die("Update review error: " . $e['message']);
                }

                $message = "Your review has been updated!";
                $messageType = "success";
                oci_free_statement($updateReviewStmt);
            } else {
                // Insert new review
                $insertReviewSql = "INSERT INTO review (user_id, product_id, review_rating, review, review_date) VALUES (:user_id, :product_id, :review_rating, :review, SYSDATE)";
                $insertReviewStmt = oci_parse($conn, $insertReviewSql);
                oci_bind_by_name($insertReviewStmt, ':user_id', $user_id);
                oci_bind_by_name($insertReviewStmt, ':product_id', $product_id);
                oci_bind_by_name($insertReviewStmt, ':review_rating', $rating);
                oci_bind_by_name($insertReviewStmt, ':review', $review_text);

                if (!oci_execute($insertReviewStmt)) {
                    $e = oci_error($insertReviewStmt);
                    die("Insert review error: " . $e['message']);
                }

                $message = "Your review has been submitted!";
                $messageType = "success";
                oci_free_statement($insertReviewStmt);
            }

            oci_commit($conn);
            oci_free_statement($checkReviewStmt);
        }
    }
}

// Fetch reviews for this product
$reviews_sql = "SELECT r.*, u.full_name as user_name FROM review r 
               JOIN users u ON r.user_id = u.user_id 
               WHERE r.product_id = :product_id 
               ORDER BY r.review_date DESC";

$reviews_stmt = oci_parse($conn, $reviews_sql);
oci_bind_by_name($reviews_stmt, ':product_id', $product_id);
oci_execute($reviews_stmt);

// Fetch all reviews into an array
$reviews = [];
while (($row = oci_fetch_assoc($reviews_stmt)) !== false) {
    $reviews[] = $row;
}


// Calculate average rating
$avg_rating_sql = "SELECT AVG(review_rating) as avg_rating, COUNT(*) as review_count FROM review WHERE product_id = :product_id";
$avg_rating_stmt = oci_parse($conn, $avg_rating_sql);
oci_bind_by_name($avg_rating_stmt, ':product_id', $product_id);
oci_execute($avg_rating_stmt);
$rating_data = oci_fetch_assoc($avg_rating_stmt);
$avg_rating = round($rating_data['AVG_RATING'], 1);
$review_count = $rating_data['REVIEW_COUNT'];

// Get related products (same category)
$related_products_sql = "SELECT * FROM product 
                        WHERE product_category_name = :product_category_name 
                        AND product_id != :product_id 
                        AND ROWNUM <= 4";

$related_products_stmt = oci_parse($conn, $related_products_sql);
oci_bind_by_name($related_products_stmt, ':product_category_name', $product['PRODUCT_CATEGORY_NAME']);
oci_bind_by_name($related_products_stmt, ':product_id', $product_id);
oci_execute($related_products_stmt);

// Fetch all related products into an array
$related_products = [];
while (($row = oci_fetch_assoc($related_products_stmt)) !== false) {
    $related_products[] = $row;
}

// Check if the current user has this product in their wishlist
$in_wishlist = false;
if ($user_id) {
    $wishlist_check_sql = "SELECT * FROM wishlist w 
                          JOIN wishlist_product wp ON w.wishlist_id = wp.wishlist_id 
                          WHERE w.user_id = :user_id AND wp.product_id = :product_id";
    $wishlist_check_stmt = oci_parse($conn, $wishlist_check_sql);
    oci_bind_by_name($wishlist_check_stmt, ':user_id', $user_id);
    oci_bind_by_name($wishlist_check_stmt, ':product_id', $product_id);
    oci_execute($wishlist_check_stmt);
    $in_wishlist = (oci_fetch($wishlist_check_stmt)) ? true : false;
    oci_free_statement($wishlist_check_stmt);
}

// Check if current user has already reviewed this product
$user_review = null;
if ($user_id) {
    $user_review_sql = "SELECT * FROM review WHERE user_id = :user_id AND product_id = :product_id";
    $user_review_stmt = oci_parse($conn, $user_review_sql);
    oci_bind_by_name($user_review_stmt, ':user_id', $user_id);
    oci_bind_by_name($user_review_stmt, ':product_id', $product_id);
    oci_execute($user_review_stmt);
    $user_review = oci_fetch_assoc($user_review_stmt);
    oci_free_statement($user_review_stmt);
}

// Close connection
oci_free_statement($product_stmt);
oci_free_statement($reviews_stmt);
oci_free_statement($avg_rating_stmt);
oci_free_statement($related_products_stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></title>
    <link rel="stylesheet" href="../../assets/CSS/product_detail.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Font Awesome for stars -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <?php include '../../Includes/header.php'; ?>
    </header>

    <?php if (!$user_id): ?>
        <div id="login-message" class="login-message">
            You need to <a href="/E-commerce/frontend/Includes/pages/login.php">log in</a> to add items to your cart, wishlist, or submit reviews
        </div>
    <?php endif; ?>

    <div class="product-detail-container">
        <div class="breadcrumb">
            <a href="/E-commerce/frontend/Includes/pages/product_list.php">Products</a> >
            <a href="/E-commerce/frontend/Includes/pages/product_list.php?category=<?php echo $product['PRODUCT_CATEGORY_NAME']; ?>"><?php echo htmlspecialchars($product['PRODUCT_CATEGORY_NAME']); ?></a> >
            <span><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></span>
        </div>

        <div class="product-main">
            <div class="product-image">
                <img src="/E-commerce/frontend/trader/uploaded_files/<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>">
                <?php if ($product['STOCK'] < 5): ?>
                    <span class="badge low-stock">Only <?php echo $product['STOCK']; ?> left in stock!</span>
                <?php elseif ($product['STOCK'] <= 0): ?>
                    <span class="badge out-of-stock">Out of Stock</span>
                <?php else: ?>
                    <span class="badge in-stock">In Stock</span>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h1>

                <div class="product-meta">
                    <div class="product-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $avg_rating): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span>(<?php echo $review_count; ?> reviews)</span>
                    </div>
                    <div class="product-category">
                        Category: <a href="/E-commerce/frontend/Includes/pages/product_list.php?category=<?php echo $product['PRODUCT_CATEGORY_NAME']; ?>"><?php echo htmlspecialchars($product['PRODUCT_CATEGORY_NAME']); ?></a>
                    </div>
                </div>

                <div class="product-price">
                    <span class="price">£<?php echo number_format($product['PRICE'], 2); ?></span>
                    <?php if (isset($product['OLD_PRICE']) && $product['OLD_PRICE'] > $product['PRICE']): ?>
                        <span class="old-price">£<?php echo number_format($product['OLD_PRICE'], 2); ?></span>
                        <span class="discount"><?php echo round((($product['OLD_PRICE'] - $product['PRICE']) / $product['OLD_PRICE']) * 100); ?>% OFF</span>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                </div>

                <form action="" method="POST" class="product-actions">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                    <div class="quantity-selector">
                        <label for="qty">Quantity:</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn decrease">-</button>
                            <input type="number" id="qty" name="qty" value="1" min="1" max="<?php echo $product['STOCK']; ?>" <?php echo ($product['STOCK'] <= 0) ? 'disabled' : ''; ?>>
                            <button type="button" class="quantity-btn increase">+</button>
                        </div>
                        <span class="stock-info"><?php echo $product['STOCK']; ?> available</span>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?php echo ($product['STOCK'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>

                        <button type="submit" name="add_to_wishlist" class="wishlist-btn <?php echo ($in_wishlist) ? 'in-wishlist' : ''; ?>">
                            <?php if ($in_wishlist): ?>
                                <i class="fas fa-heart"></i> In Wishlist
                            <?php else: ?>
                                <i class="far fa-heart"></i> Add to Wishlist
                            <?php endif; ?>
                        </button>
                    </div>
                </form>

                <div class="product-additional-info">
                    <div class="info-item">
                        <i class="fas fa-truck"></i>
                        <span>Free shipping on orders over Rs. 1000</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo"></i>
                        <span>2-days return policy</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure payment</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="reviews">Reviews (<?php echo $review_count; ?>)</button>
                <button class="tab-btn" data-tab="specifications">Specifications</button>
                <button class="tab-btn" data-tab="shipping">Shipping & Returns</button>
            </div>

            <div class="tab-content active" id="reviews">
                <div class="reviews-summary">
                    <div class="rating-summary">
                        <div class="big-rating">
                            <?php echo $avg_rating; ?><span>/5</span>
                        </div>
                        <div class="star-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $avg_rating): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="review-count"><?php echo $review_count; ?> reviews</div>
                    </div>

                    <div class="write-review">
                        <h3><?php echo ($user_review) ? 'Update Your Review' : 'Write a Review'; ?></h3>

                        <?php if ($user_id): ?>
                            <form action="" method="POST" class="review-form">
                                <div class="rating-select">
                                    <span>Your Rating:</span>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($user_review && $user_review['REVIEW_RATING'] == $i) ? 'checked' : ''; ?>>
                                            <label for="star<?php echo $i; ?>"><i class="fa fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="review-text">
                                    <label for="review_text">Your Review:</label>
                                    <textarea name="review_text" id="review_text" rows="5" required><?php echo ($user_review) ? htmlspecialchars($user_review['REVIEW_RATING']) : ''; ?></textarea>
                                </div>

                                <button type="submit" name="submit_review" class="submit-review">
                                    <?php echo ($user_review) ? 'Update Review' : 'Submit Review'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="login-to-review">
                                <p>Please <a href="/E-commerce/frontend/Includes/pages/login.php">login</a> to write a review</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="reviews-list">
                    <h3>Customer Reviews</h3>

                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review) : ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-user"><?php echo htmlspecialchars($review['USER_NAME']); ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['REVIEW_DATE'])); ?></div>
                                </div>

                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['REVIEW_RATING']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>

                                <div class="review-content">
                                    <?php
                                    $review_text = $review['REVIEW'];
                                    if (is_object($review_text) && get_class($review_text) === 'OCILob') {
                                        $review_text = $review_text->read($review_text->size());
                                    }
                                    echo nl2br(htmlspecialchars($review_text));

                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <p>No reviews yet. Be the first to review this product!</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="tab-content" id="specifications">
                <div class="specifications-content">
                    <table class="specs-table">
                        <tr>
                            <th>Product Name</th>
                            <td><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td><?php echo htmlspecialchars($product['PRODUCT_CATEGORY_NAME']); ?></td>
                        </tr>
                        <tr>
                            <th>Stock Availability</th>
                            <td><?php echo $product['STOCK']; ?> units</td>
                        </tr>

                        <tr>
                            <th>Allergy Information</th>
                            <td><?php echo $product['ALLERGY_INFORMATION']; ?> </td>
                        </tr>

                        <tr>
                            <th>Price</th>
                            <td>£<?php echo number_format($product['PRICE'], 2); ?></td>
                        </tr>
                        <?php if (isset($product['PRODUCT_SKU']) && !empty($product['PRODUCT_SKU'])): ?>
                            <tr>
                                <th>SKU</th>
                                <td><?php echo htmlspecialchars($product['PRODUCT_SKU']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (isset($product['DIMENSIONS']) && !empty($product['DIMENSIONS'])): ?>
                            <tr>
                                <th>Dimensions</th>
                                <td><?php echo htmlspecialchars($product['DIMENSIONS']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (isset($product['WEIGHT']) && !empty($product['WEIGHT'])): ?>
                            <tr>
                                <th>Weight</th>
                                <td><?php echo htmlspecialchars($product['WEIGHT']); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="shipping">
                <div class="shipping-content">
                    <h3>Shipping Policy</h3>
                    <p>We offer free standard shipping on all orders over Rs. 1000. For orders under Rs. 1000, a flat shipping fee of Rs. 100 will be applied.</p>
                    <p>Standard shipping typically takes 3-5 business days depending on your location.</p>
                    <p>Express shipping is available at an additional cost, with delivery within 1-2 business days.</p>

                    <h3>Return Policy</h3>
                    <p>We offer a 30-day return policy for most products. To be eligible for a return, your item must be unused and in the same condition that you received it.</p>
                    <p>To initiate a return, please contact our customer support team with your order details.</p>
                    <p>Once your return is received and inspected, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>
                    <p>If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within 5-7 business days.</p>
                </div>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2>You May Also Like</h2>
                <div class="related-products-container">
                    <?php foreach ($related_products as $related): ?>
                        <?php
                        // Handle CLOB fields for related products
                        $related_image = $related['PRODUCT_IMAGE'];
                        if (is_object($related_image) && get_class($related_image) === 'OCILob') {
                            $related_image = $related_image->read($related_image->size());
                        }
                        ?>
                        <div class="related-product-card" onclick="window.location.href='/E-commerce/frontend/Includes/pages/product_detail.php?product_id=<?= $related['PRODUCT_ID']; ?>'">
                            <div class="related-product-img">
                                <img src="/E-commerce/frontend/trader/uploaded_files/<?= $related_image; ?>" alt="<?php echo htmlspecialchars($related['PRODUCT_NAME']); ?>">
                            </div>
                            <div class="related-product-info">
                                <h3><?php echo htmlspecialchars($related['PRODUCT_NAME']); ?></h3>
                                <p class="related-product-price">£<?php echo number_format($related['PRICE'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Toast messages
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

                // If it's an error about logging in, show the login message
                if (message.includes("log in") && document.getElementById("login-message")) {
                    document.getElementById("login-message").style.display = "block";
                }
            }

            // Quantity selectors
            const qtyInput = document.getElementById('qty');
            const decreaseBtn = document.querySelector('.decrease');
            const increaseBtn = document.querySelector('.increase');
            const maxStock = parseInt(<?php echo $product['STOCK']; ?>);

            if (decreaseBtn && increaseBtn && qtyInput) {
                decreaseBtn.addEventListener('click', function() {
                    let currentValue = parseInt(qtyInput.value) || 1;
                    if (currentValue > 1) {
                        qtyInput.value = currentValue - 1;
                    }
                });

                increaseBtn.addEventListener('click', function() {
                    let currentValue = parseInt(qtyInput.value) || 1;
                    if (currentValue < maxStock) {
                        qtyInput.value = currentValue + 1;
                    }
                });

                qtyInput.addEventListener('change', function() {
                    let value = parseInt(this.value) || 1;
                    if (value < 1) {
                        this.value = 1;
                    } else if (value > maxStock) {
                        this.value = maxStock;
                    }
                });
            }

            // Tab switching
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons and content
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Star rating selector
            const starLabels = document.querySelectorAll('.star-rating label');

            starLabels.forEach(label => {
                label.addEventListener('mouseover', function() {
                    // Reset all stars
                    starLabels.forEach(l => l.querySelector('i').className = 'fa fa-star');

                    // Fill stars up to current star
                    let currentStar = this;
                    while (currentStar) {
                        currentStar.querySelector('i').className = 'fas fa-star';
                        currentStar = currentStar.previousElementSibling &&
                            currentStar.previousElementSibling.tagName === 'LABEL' ?
                            currentStar.previousElementSibling : null;
                    }
                });
            });

            // Reset stars when mouse leaves rating area
            const starRating = document.querySelector('.star-rating');
            if (starRating) {
                starRating.addEventListener('mouseleave', function() {
                    const checkedInput = this.querySelector('input:checked');
                    if (checkedInput) {
                        const value = parseInt(checkedInput.value);

                        starLabels.forEach(label => {
                            const labelValue = parseInt(label.getAttribute('for').replace('star', ''));
                            if (labelValue <= value) {
                                label.querySelector('i').className = 'fas fa-star';
                            } else {
                                label.querySelector('i').className = 'fa fa-star';
                            }
                        });
                    } else {
                        starLabels.forEach(label => {
                            label.querySelector('i').className = 'fa fa-star';
                        });
                    }
                });
            }
        });
    </script>
</body>

<?php include '../../Includes/footer.php'; ?>

</html>