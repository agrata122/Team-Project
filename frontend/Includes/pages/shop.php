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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Local Market</title>
    <link rel="stylesheet" href="../../assets/CSS/shop_page.css">
</head>
<body>
    <header>
    <?php include '../../Includes/header.php'; ?>
    </header>
    
    <div class="container">
        <h1>Browse By Shops</h1>
        
        <?php
        // Define category array with default image URLs
        $categories = [
            'Butcher' => [
                'images' => [
                    'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1558030006-450675393462?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
                ]
            ],
            'Fishmonger' => [
                'images' => [
                    'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
                ]
            ],
            'Greengrocer' => [
                'images' => [
                    'https://images.unsplash.com/photo-1518843875459-f738682238a6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
                ]
            ],
            'Bakery' => [
                'images' => [
                    'https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
                ]
            ],
            'Delicatessen' => [
                'images' => [
                    'https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
                ]
            ]
        ];
        
        // Loop through each category
        foreach ($categories as $category => $data) {
            echo "<div>";
            echo "<h2>$category</h2>";
            echo "<div class='shops-container'>";
            
            // Convert category to lowercase to match database values
            $dbCategory = strtolower($category);
            
            // For Oracle DB using OCI
            $query = "SELECT * FROM shops WHERE shop_category = :category";
            $statement = oci_parse($conn, $query);
            oci_bind_by_name($statement, ":category", $dbCategory);
            oci_execute($statement);
            
            // Counter for alternating default images
            $imgCounter = 0;
            $shopCount = 0;
            
            // Display shops for this category
            while($shop = oci_fetch_assoc($statement)) {
                $shopCount++;
                
                // Use default image based on category (alternating between the available ones)
                $imgIndex = $imgCounter % count($data['images']);
                $defaultImage = $data['images'][$imgIndex];
                $imgCounter++;
                
                // Get the shop description or use a default one if not available
                $description = !empty($shop['DESCRIPTION']) ? $shop['DESCRIPTION'] : "Quality products from {$shop['SHOP_NAME']}";
                
                // Display the shop card with onclick event to navigate to shop_products.php
                echo "<div class='shop-card' onclick=\"window.location.href='shop_products.php?shop_id={$shop['SHOP_ID']}'\">";
                echo "<div class='shop-image' style='background-image: url(\"{$defaultImage}\");'></div>";
                echo "<div class='shop-info'>";
                echo "<h3 class='shop-name'>{$shop['SHOP_NAME']}</h3>";
                $descText = is_object($description) ? $description->load() : $description;
                echo "<p class='shop-description'>{$descText}</p>";

                echo "<a href='shop_products.php?shop_id={$shop['SHOP_ID']}' class='shop-button'>View Products</a>";
                echo "</div>";
                echo "</div>";
            }
            
            // Display message if no shops found in this category
            if ($shopCount == 0) {
                echo "<div class='no-shops'>No $category shops available at the moment.</div>";
            }
            
            echo "</div>";
            echo "</div>";
            
            // Free the statement
            oci_free_statement($statement);
        }
        ?>
    </div>
    
    <?php include '../../Includes/footer.php'; ?>
</body>
</html>
