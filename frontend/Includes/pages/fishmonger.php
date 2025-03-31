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
    <title>Fishmonger Category</title>
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
            <h2>Fishmonger Category Products</h2>
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
            ["name" => "Salmon Fillet", "price" => 15.99, "image" => "../../assets/Images/salmon_fillet.jpg"],
            ["name" => "Tuna Steak", "price" => 18.49, "image" => "../../assets/Images/tuna_steak.jpg"],
            ["name" => "Shrimp", "price" => 12.99, "image" => "../../assets/Images/shrimp.jpg"],
            ["name" => "Lobster Tail", "price" => 29.99, "image" => "../../assets/Images/lobster_tail.jpg"],
            ["name" => "Crab Legs", "price" => 24.99, "image" => "../../assets/Images/crab_legs.jpg"],
            ["name" => "Tilapia Fillet", "price" => 10.99, "image" => "../../assets/Images/tilapia_fillet.jpg"],
            ["name" => "Catfish", "price" => 13.49, "image" => "../../assets/Images/catfish.jpg"],
            ["name" => "Sardines", "price" => 7.99, "image" => "../../assets/Images/sardines.jpg"],
            ["name" => "Squid", "price" => 16.49, "image" => "../../assets/Images/squid.jpg"],
            ["name" => "Mussels", "price" => 9.99, "image" => "../../assets/Images/mussels.jpg"]
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
