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
    <title>Delicatessen Category</title>
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
            <h2>Delicatessen Category Products</h2>
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


    </div>
</section>
</body>
<?php
include '../../Includes/footer.php';
?>
</html>
