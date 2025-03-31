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
    <title>Greengrocer Category</title>
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
            <h2>Greengrocer Category Products</h2>
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
            ["name" => "Fresh Spinach", "price" => 3.49, "image" => "../../assets/Images/spinach.jpg"],
            ["name" => "Organic Carrots", "price" => 2.99, "image" => "../../assets/Images/carrots.jpg"],
            ["name" => "Red Bell Peppers", "price" => 4.99, "image" => "../../assets/Images/red_bell_pepper.jpg"],
            ["name" => "Broccoli", "price" => 3.99, "image" => "../../assets/Images/broccoli.jpg"],
            ["name" => "Avocados", "price" => 5.99, "image" => "../../assets/Images/avocados.jpg"],
            ["name" => "Cherry Tomatoes", "price" => 4.49, "image" => "../../assets/Images/cherry_tomatoes.jpg"],
            ["name" => "Sweet Potatoes", "price" => 3.99, "image" => "../../assets/Images/sweet_potatoes.jpg"],
            ["name" => "Green Beans", "price" => 3.79, "image" => "../../assets/Images/green_beans.jpg"],
            ["name" => "Cucumbers", "price" => 2.99, "image" => "../../assets/Images/cucumbers.jpg"],
            ["name" => "Lettuce", "price" => 2.49, "image" => "../../assets/Images/lettuce.jpg"]
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
