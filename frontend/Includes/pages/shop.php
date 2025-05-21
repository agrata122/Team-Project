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
    <style>
        /* Main Styles */
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --dark-green: #1b5e20;
            --text-dark: #333;
            --text-light: #666;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--white);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            color: var(--primary-green);
            margin-bottom: 40px;
            font-size: 2.2em;
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        h2 {
            color: var(--primary-green);
            font-weight: 400;
            margin: 50px 0 20px 0;
            font-size: 1.5em;
            position: relative;
            padding-bottom: 10px;
        }
        
        h2:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-green);
        }
        
        /* Shop Categories */
        .shops-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        /* Individual Shop Cards */
        .shop-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: var(--white);
            cursor: pointer;
        }
        
        .shop-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.1);
        }
        
        .shop-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            background-color: #f5f5f5;
        }
        
        .shop-info {
            padding: 20px;
        }
        
        .shop-name {
            font-size: 1.2em;
            margin: 0 0 8px 0;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .shop-description {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .shop-button {
            display: inline-block;
            background: transparent;
            color: var(--primary-green);
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--primary-green);
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        
        .shop-button:hover {
            background: var(--primary-green);
            color: var(--white);
        }
        
        /* Display message when no shops are available */
        .no-shops {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            color: var(--text-light);
            font-style: italic;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .shops-container {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.8em;
            }
        }
    </style>
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
