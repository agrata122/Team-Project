<?php
// Simulated product data (random examples with placeholder images)
$products = [
    ["name" => "Mango Juice", "price" => "$99.50", "image" => "/E-commerce/frontend/assets/Images/mango-juice.jpg"],
    ["name" => "Fresh Bananas", "price" => "$89.50", "image" => "/E-commerce/frontend/assets/Images/product-banana.png"],
    ["name" => "Tasty Watermelon", "price" => "$25.00", "image" => "/E-commerce/frontend/assets/Images/watermelon.jpeg"],
    ["name" => "Ground Pork", "price" => "$15.00", "image" => "/E-commerce/frontend/assets/Images/ground-pork.png"],
    ["name" => "Fish", "price" => "$49.99", "image" => "/E-commerce/frontend/assets/Images/product-fish.png"]
];
?>

<div class="new-products-popup">
    <h2>Latest Products</h2>
    <div class="new-products-container">
        <?php foreach ($products as $product): ?>
            <div class="new-product-card">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" />
                <div class="new-product-info">
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="price"><?php echo $product['price']; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Popup container styling */
.new-products-popup {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 15px;
    max-width: 250px;
    margin: 0 auto;
    font-family: Arial, sans-serif;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

/* Header styling */
.new-products-popup h2 {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
    padding-bottom: 3px;
    border-bottom: 3px solid #4caf50;
    display: inline-block;
}

/* Products container */
.new-products-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Individual product cards */
.new-product-card {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    background: #fff;
    border-radius: 8px;
    transition: background 0.3s;
}

.new-product-card:hover {
    background: #f3f3f3;
}

/* Product image */
.new-product-card img {
    width: 50px;
    height: 50px;
    border-radius: 5px;
    object-fit: contain;
    margin-right: 12px;
}

/* Product info */
.new-product-info {
    display: flex;
    flex-direction: column;
}

.new-product-info h3 {
    font-size: 14px;
    font-weight: bold;
    color: #388e3c;
    margin: 0;
}

.new-product-info .price {
    font-size: 14px;
    color: #666;
    margin: 2px 0;
}

.star {
    font-size: 14px;
    color: gold;
    margin-top: 2px;
}
</style>