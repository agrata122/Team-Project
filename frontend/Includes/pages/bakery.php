<?php 
    session_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/CSS/Homepage.css">
    <link rel="stylesheet" href="../../assets/CSS/Product-card.css">
    <title>Bakery Category</title>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 50px;
            justify-content: center;
        }
        @media (min-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
    </style>
</head>
<body>
<header>
    <?php include '../../Includes/header.php'; ?>
</header>

<section class="hero">
    <!-- Banner Section -->
    <div class="banner">
        <div class="banner-text">Bakery Items, Delicious!</div>
        <img src="assets/images/birthday-cake.png" alt="Birthday Cake">
        <div class="banner-nav">
            <button class="prev-btn">&lt;</button>
            <button class="next-btn">&gt;</button>
        </div>
    </div>
</section>

<div class="page-container">
        <!-- Sidebar with categories -->
        <div class="sidebar">
            <?php include '../../Includes/category-popup.php'; ?>

<!-- Butcher Category Products Section -->
<section class="featured-header-section">
    <div class="container">
        <div class="featured-header">
            <h2>Bakery Category Products</h2>
        </div>
        <div class="view-all-products">
            View all products >
        </div>
    </div>
</section>
<section class="products">
    <div class="container">

    <div class="product-grid">
    <?php
        $products = [
            ["name" => "Fresh Baguette", "price" => 4.49, "image" => "../../assets/Images/baguette.jpg"],
            ["name" => "Croissants", "price" => 5.99, "image" => "../../assets/Images/croissants.jpg"],
            ["name" => "Chocolate Cake", "price" => 15.99, "image" => "../../assets/Images/chocolate_cake.jpg"],
            ["name" => "Blueberry Muffins", "price" => 6.99, "image" => "../../assets/Images/blueberry_muffins.jpg"],
            ["name" => "Sourdough Bread", "price" => 7.49, "image" => "../../assets/Images/sourdough_bread.jpg"],
            ["name" => "Danish Pastries", "price" => 8.99, "image" => "../../assets/Images/danish_pastries.jpg"],
            ["name" => "Cinnamon Rolls", "price" => 9.99, "image" => "../../assets/Images/cinnamon_rolls.jpg"],
            ["name" => "Glazed Donuts", "price" => 5.49, "image" => "../../assets/Images/glazed_donuts.jpg"],
            ["name" => "Brownies", "price" => 10.49, "image" => "../../assets/Images/brownies.jpg"],
            ["name" => "Whole Wheat Bread", "price" => 6.49, "image" => "../../assets/Images/whole_wheat_bread.jpg"]
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
