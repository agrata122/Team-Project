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
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">FRESGRUB</div>
            <div class="search-bar">
                <input type="text" placeholder="Search For Product or Category">
            </div>
            <div class="auth-buttons">
            <button class="login" onclick="location.pathname='E-commerce/frontend/Includes/pages/login.php'">Login</button>
            <button class="signup" onclick="location.pathname='E-commerce/frontend/Includes/pages/signup.php'">Signup</button>
            </div>
        </div>
    </header>
    <section class="hero">
        <div class="container">
            <div class="hero-text">
                <h1>FRESH, LOCAL, YOURS.</h1>
                <h2>Your Neighborhood Market, <span class="highlight">Online</span></h2>
                <p>Shop from your favorite local traders online and pick up fresh goods with ease.</p>
                <button class="shop-now">SHOP NOW</button>
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
            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/butcher.png" alt="Butcher">
                </div>
                <div class="text-container">
                    <h3>Butcher</h3>
                    <p>10 items</p>
                </div>
            </div>

            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/fishmonger.png" alt="Fish Monger">
                </div>
                <div class="text-container">
                    <h3>Fish Monger</h3>
                    <p>20 items</p>
                </div>
            </div>

            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/greengrocer.png" alt="Greengrocer">
                </div>
                <div class="text-container">
                    <h3>Greengrocer</h3>
                    <p>15 items</p>
                </div>
            </div>

            <div class="category">
                <div class="image-container">
                    <img src="../../assets/Images/bakery.png" alt="Bakery">
                </div>
                <div class="text-container">
                    <h3>Bakery</h3>
                    <p>20 items</p>
                </div>
            </div>

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
    
    
    <section class="about-us">
    <div class="container">
        <div class="about-us-text">
            <h2>About Us</h2>
            <h3>Trust in our experience</h3>
            <p>With years of dedication and passion, we have been committed to serving our customers with excellence. Our mission is to provide top-notch service,With years of dedication and passion, we have been committed to serving our customers with excellence. Our mission is to provide top-notch service, ensuring satisfaction and reliability in every interaction. We believe in quality, trust, and building long-lasting relationships with our customers. Our team is always ready to go the extra mile, striving to bring you the best experience possible.</p>
            <button class="see-more">SEE MORE</button>
        </div>
        <div class="about-us-image">
            <img src="../../assets/Images/Home-aboutus.jpg" alt="Hands exchanging tomatoes in a bag">
        </div>
    </div>
</section>


<section class="offer-section">
    <div class="offer-content">
        <h1>Enjoy Free Delivery</h1>
        <p>On All Greengrocer Products</p>
        <button class="shop-now-btn">SHOP NOW</button>
    </div>
    <div class="offer-image">
        <img src="../../assets/Images/offer-image.png" alt="Fresh greengrocer products">
    </div>
</section>


<!-- Featured Products Header Section -->
<section class="featured-header-section">
    <div class="container">
        <div class="featured-header">
            <h2>FEATURED PRODUCTS</h2>
        </div>
        <div class="view-all-products">
            View all products >
        </div>
    </div>
</section>

<!-- Product Section -->
<section class="product-section">
    <div class="container">
        <div class="product-container">
            <?php
            $products = [
                ["image" => "../../assets/Images/product-fish.png", "name" => "Healthy Fish", "price" => 7.00],
                ["image" => "../../assets/Images/product-sausage.png", "name" => "Chicken Sausage", "price" => 9.00],
                ["image" => "../../assets/Images/product-banana.png", "name" => "Banana", "price" => 12.00],
                ["image" => "../../assets/Images/product-wholechicken.png", "name" => "Whole Chicken", "price" => 14.00],
                ["image" => "../../assets/Images/product-cupcake.png", "name" => "Vanilla Cupcake", "price" => 3.00],
                ["image" => "../../assets/Images/delicatessen.png", "name" => "Pan Pizza", "price" => 7.00],
                ["image" => "../../assets/Images/greengrocer.png", "name" => "Healthy Vegetables", "price" => 3.00],
                ["image" => "../../assets/Images/product-banana.png", "name" => "Tasty Vegetables", "price" => 7.00],
                ["image" => "../../assets/Images/product-sausage.png", "name" => "Chicken Sausage", "price" => 9.00],
                ["image" => "../../assets/Images/delicatessen.png", "name" => "Pan Pizza", "price" => 7.00],
               
            ];

            foreach ($products as $product) {
                include '../../Includes/product-card.php';
            }
            ?>
        </div>
    </div>
</section>

</body>
<?php
include '../../Includes/footer.php';
?>

</html>
