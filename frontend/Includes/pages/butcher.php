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
    <title>Butcher Category</title>
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
    <div class="container">
        <div class="hero-text">
            <h1>FRESH, LOCAL, YOURS.</h1>
            <h2>Your Neighborhood Market, <span class="highlight">Online</span></h2>
            <p>Shop from your favorite local traders online and pick up fresh goods with ease.</p>
        </div>
        <div class="hero-image">
            <img src="../../assets/Images/grocerypic.png" alt="Bag of fresh vegetables">
        </div>
    </div>
</section>

<!-- Butcher Category Products Section -->
<section class="featured-header-section">
    <div class="container">
        <div class="featured-header">
            <h2>Butcher Category Products</h2>
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
                    ["name" => "Fresh Chicken Breast", "price" => 12.99, "image" => "../../assets/Images/chicken_breast.jpg"],
                    ["name" => "Mutton Steak", "price" => 18.99, "image" => "../../assets/Images/beef_steak.jpg"],
                    ["name" => "Ground Pork", "price" => 9.49, "image" => "../../assets/Images/ground_pork.jpg"],
                    ["name" => "Lamb Chops", "price" => 22.99, "image" => "../../assets/Images/lamb_chops.jpg"],
                    ["name" => "Turkey Drumsticks", "price" => 14.99, "image" => "../../assets/Images/turkey_drumsticks.jpg"],
                    ["name" => "Pork Ribs", "price" => 16.99, "image" => "../../assets/Images/pork_ribs.jpg"],
                    ["name" => "Chicken Wings", "price" => 10.99, "image" => "../../assets/Images/chicken_wings.jpg"],
                    ["name" => "Bat Mince", "price" => 11.99, "image" => "../../assets/Images/beef_mince.jpg"],
                    ["name" => "Duck Breast", "price" => 24.99, "image" => "../../assets/Images/duck_breast.jpg"],
                    ["name" => "Sausages", "price" => 8.99, "image" => "../../assets/Images/sausages.jpg"]
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
