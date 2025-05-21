<?php 
    session_start(); 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FRESGRUB</title>
    <link rel="stylesheet" href="../../assets/CSS/Homepage.css">
    <link rel="stylesheet" href="../../assets/CSS/Product-card.css">
</head>
<body>
    <header>
    <?php
include '../../Includes/header.php'; 
?>
        
    </header>
    <section class="hero">
    
    <div class="container">
        <div class="hero-text">
            <h1>FRESH, LOCAL, YOURS.</h1>
            <h2>Your Neighborhood Market, <span class="highlight">Online</span></h2>
            <p>Shop from your favorite local traders online and pick up fresh goods with ease.</p>
            <button class="shop-now" onclick="location.href='product_list.php'">SHOP NOW</button>
        </div>
        <div class="hero-image">
            <img src="../../assets/Images/grocerypic.png" alt="Bag of fresh vegetables">
        </div>
    </div>
</section>

    <section class="features">
    <div class="container">
        <div class="feature">
            <img src="../../assets/Images/feature-delivery.png" alt="Fastest Delivery">
            <h3>Fastest Delivery</h3>
            <p>Delivery at your doorstep with lightning speed.</p>
        </div>
        <div class="feature">
            <img src="../../assets/Images/feature-24.png" alt="24x7 Services">
            <h3>24x7 Services</h3>
            <p>We are here for you any time of the day.</p>
        </div>
        <div class="feature">
            <img src="../../assets/Images/feature-verifiedbrands.png" alt="Verified Brands">
            <h3>Verified Brands</h3>
            <p>Only the best and trusted brands for you.</p>
        </div>
        <div class="feature">
            <img src="../../assets/Images/feature-assurance.png" alt="100% Assurance">
            <h3>100% Assurance</h3>
            <p>Complete confidence in every purchase.</p>
        </div>
    </div>
</section>

<section class="browse-section">
        <h2 class="category-title">Browse By Category</h2>
    </section>

    <section class="categories">
        <div class="category-items">
        
        <a href="butcher.php" class="category-link">
    <div class="category">
        <div class="image-container">
            <img src="../../assets/Images/butcher.png" alt="Butcher">
        </div>
        <div class="text-container">
            <h3>Butcher</h3>
            <p>10 items</p>
        </div>
    </div>
</a>

        <a href="fishmonger.php" class="category-link">
            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/fishmonger.png" alt="Fish Monger">
                </div>
                <div class="text-container">
                    <h3>Fish Monger</h3>
                    <p>20 items</p>
                </div>
            </div>
</a>

            <a href="greengrocer.php" class="category-link">
            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/greengrocer.png" alt="Greengrocer">
                </div>
                <div class="text-container">
                    <h3>Greengrocer</h3>
                    <p>15 items</p>
                </div>
            </div>
</a>

            
            <a href="bakery.php" class="category-link">
            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/bakery.png" alt="Bakery">
                </div>
                <div class="text-container">
                    <h3>Bakery</h3>
                    <p>20 items</p>
                </div>
            </div>
</a>


            <a href="delicatessen.php" class="category-link">
            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/delicatessen.png" alt="Delicatessen">
                </div>
                <div class="text-container">
                    <h3>Delicatessen</h3>
                    <p>13 items</p>
                </div>
            </div>
        </div>
    </section>
</a>


    
    
    <section class="about-us">
    <div class="container">
        <div class="about-us-text">
            <h2>About Us</h2>
            <h3>Trust in our experience</h3>
            <p>With years of dedication and passion, we have been committed to serving our customers with excellence. Our mission is to provide top-notch service, ensuring With years of dedication and passion, we have been committed to serving our customers with excellence. Our missio satisfaction and reliability in every interaction. We believe in quality, trust, and building long-lasting relationships with our customers. Our team is always ready to go the extra mile, striving to bring you the best experience possible.</p>
            <button class="see-more" onclick="location.href='aboutUs.php'">SEE MORE</button>
        </div>
        <div class="about-us-video">
            <video autoplay loop muted playsinline>
                <source src="../../assets/Images/about-video.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
</section>


<section class="offer-section">
    <div class="offer-content">
        <h1>Use Code 'FISH'</h1>
        <p>And Get 10% On Your Order</p>
        <a href="/E-commerce/frontend/Includes/pages/product_list.php" class="shop-now-btn">SHOP NOW</a>
    </div>
    <div class="offer-image">
        <img src="../../assets/Images/fish-offer.png" alt="Fresh Fish Products">
    </div>
</section>


<!-- Featured Products Header Section -->
<section class="featured-header-section">
    <div class="container">
        <div class="featured-header">
            <h2>FEATURED PRODUCTS</h2>
        </div>
        <div class="view-all-products">
            <a href="/E-commerce/frontend/Includes/pages/product_list.php">View all products ></a>
        </div>
    </div>
</section>

<!-- Product Section -->
<section class="product-section">
    <div class="container">
        <div class="product-container"> 
            <?php
            // Get database connection
            require_once 'C:\xampp\htdocs\E-commerce\backend\connect.php';
            $conn = getDBConnection();

            // Query to get the most recently added products
            $sql = "SELECT p.*, s.shop_name 
                   FROM product p 
                   JOIN shops s ON p.shop_id = s.shop_id 
                   WHERE p.product_status = 'In Stock' 
                   AND ROWNUM <= 10
                   ORDER BY p.add_date DESC";
            
            $stmt = oci_parse($conn, $sql);
            oci_execute($stmt);

            while ($product = oci_fetch_assoc($stmt)) {
                // Handle CLOB fields
                $product_image = $product['PRODUCT_IMAGE'];
                if (is_object($product_image) && get_class($product_image) === 'OCILob') {
                    $product_image = $product_image->read($product_image->size());
                }
                
                // Create product array for the product card
                $product_data = [
                    "image" => "/E-commerce/frontend/trader/uploaded_files/" . $product_image,
                    "name" => $product['PRODUCT_NAME'],
                    "price" => $product['PRICE'],
                    "id" => $product['PRODUCT_ID']
                ];
                
                include '../../Includes/product-card.php';
            }

            oci_free_statement($stmt);
            oci_close($conn);
            ?>
        </div>
    </div>
</section>


</body>
<?php
include '../../Includes/footer.php';
?>

</html>
