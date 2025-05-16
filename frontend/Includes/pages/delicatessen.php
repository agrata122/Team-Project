<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/CSS/Homepage.css">
    <link rel="stylesheet" href="../../assets/CSS/Product-card.css">
    <title>Butcher Category</title>
    <style>
            /* Page container */
        .category-page-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Sidebar styling */
        .category-sidebar {
            width: 250px;
            margin-right: 40px;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 20px; /* Add spacing between category popup and new products */
}
        /* Main content area */
        .category-main-content {
            flex: 1;
        }
        
       /* Hero/Banner section */
.hero-delicatessen {
    position: relative;
    width: 100%;
    height: 450px; /* Increased height to match image */
    background-color: #f0f0f0;
    margin-bottom: 30px;
    overflow: hidden;
}

.banner-delicatessen {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banner-delicatessen img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: brightness(0.95); /* Slight adjustment to match the image */
}

.banner-delicatessen-text {
    position: absolute;
    top: 15%; /* Adjusted to match image positioning - text is higher up */
    left: 50%;
    transform: translate(-50%, -50%);
    color: #000;
    font-size: 32px;
    font-weight: bold;
    text-align: center;
    z-index: 2;
}

/* Category header section */
    .category-header-section {
        margin-bottom: 30px;
    }
    
    .category-header h2 {
        font-size: 24px;
        font-weight: bold;
        margin: 0;
        padding: 0;
    }
    
    /* Product grid */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    </style>
</head>
<body>
    <header>
        <?php include '../../Includes/header.php'; ?>
    </header>

    <!-- Hero Banner Section -->
<section class="hero-delicatessen">
    <div class="banner-delicatessen">
        <div class="banner-delicatessen-text">Delicatessen Items, Amazing!</div>
        <img src="/E-commerce/frontend/assets/Images/product-wholechicken.png" alt="Birthday Cake">
        
    </div>
</section>
   

    <div class="category-page-container">
        <!-- Sidebar with categories -->
        <div class="category-sidebar">
            <?php include '../../Includes/category-popup.php'; ?>

        <!-- New Products Popup -->
        <?php include '../../Includes/new_products_popup.php'; ?>
        
        </div>
        
        <div class="category-main-content">
            <!-- Bakery Category Products Section -->
            <section class="category-header-section">
                <div class="category-header">
                    <h2>Delicatessen Category Products</h2>
                </div>
            </section>
<section class="products">
    <div class="container">

    <div class="product-grid">
    <?php
        $products = [
            ["name" => "Smoked Salmon", "price" => 14.99, "image" => "../../assets/Images/smoked_salmon.jpg"],
            ["name" => "Prosciutto", "price" => 19.99, "image" => "../../assets/Images/prosciutto.jpg"],
            ["name" => "Salami", "price" => 12.99, "image" => "../../assets/Images/salami.jpg"],
            ["name" => "Roast Beef Slices", "price" => 16.99, "image" => "../../assets/Images/roast_beef.jpg"],
            ["name" => "Turkey Breast Slices", "price" => 13.49, "image" => "../../assets/Images/turkey_breast.jpg"],
            ["name" => "Pastrami", "price" => 15.99, "image" => "../../assets/Images/pastrami.jpg"],
            ["name" => "Cheddar Cheese", "price" => 8.99, "image" => "../../assets/Images/cheddar_cheese.jpg"],
            ["name" => "Brie Cheese", "price" => 11.99, "image" => "../../assets/Images/brie_cheese.jpg"],
            ["name" => "Gourmet Olives", "price" => 7.49, "image" => "../../assets/Images/gourmet_olives.jpg"],
            ["name" => "Stuffed Peppers", "price" => 9.99, "image" => "../../assets/Images/stuffed_peppers.jpg"]
        ];
        
        foreach ($products as $product) {
            include '../../Includes/product-card.php';
        }
    ?>
</div>
            </section>
        </div>
    </div>

    <?php include '../../Includes/footer.php'; ?>
</body>
</html>