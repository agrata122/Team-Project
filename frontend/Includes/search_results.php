<?php

include 'C:\xampp\htdocs\E-commerce\backend\connect.php';
include 'header.php';

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


// Get the search query from URL parameters
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize variables
$products = [];
$categories = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];

// Only search if query is not empty
if (!empty($searchQuery)) {
    $conn = getDBConnection();
    
    // Prepare the search query with wildcards
    $searchTerm = '%' . strtolower($searchQuery) . '%';
    
    // Search in products
   $sql = "SELECT p.*, s.shop_name, s.shop_id 
        FROM product p
        JOIN shops s ON p.shop_id = s.shop_id
        WHERE LOWER(p.product_name) LIKE :query 
           OR LOWER(p.description) LIKE :query 
           OR LOWER(p.product_category_name) LIKE :query
        ORDER BY p.product_name";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":query", $searchTerm);
    
    if (oci_execute($stmt)) {
        while ($row = oci_fetch_assoc($stmt)) {
            $products[] = $row;
        }
    }
    
    oci_free_statement($stmt);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - FresGrub</title>
    <link rel="stylesheet" href="/E-commerce/frontend/assets/css/style.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/css/Footer.css">
    <style>
        .search-results-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .search-results-header {
            margin-bottom: 30px;
        }
        
        .search-results-count {
            color: #666;
            margin-top: 10px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .product-shop {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #00C12B;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 0;
            font-size: 18px;
            color: #666;
        }
        
        .search-suggestions {
            margin-top: 20px;
        }
        
        .suggestion-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .suggestion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .suggestion-item {
            background: #f5f5f5;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .suggestion-item:hover {
            background: #e1e1e1;
        }
    </style>
</head>
<body>
    <div class="search-results-container">
        <div class="search-results-header">
            <h1>Search Results</h1>
            <?php if (!empty($searchQuery)): ?>
                <p class="search-results-count">Found <?php echo count($products); ?> results for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
            <?php else: ?>
                <p class="search-results-count">Please enter a search term</p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($searchQuery)): ?>
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
    <a href="/E-commerce/frontend/Includes/pages/product_detail.php?product_id=<?php echo $product['PRODUCT_ID']; ?>">
        <img src="/E-commerce/frontend/trader/uploaded_files/<?php echo $product['PRODUCT_IMAGE']; ?>" alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" class="product-image">
        <div class="product-info">
            <h3 class="product-name"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h3>
            <p class="product-shop"><?php echo htmlspecialchars($product['SHOP_NAME']); ?></p>
            <p class="product-price">$<?php echo number_format($product['PRICE'], 2); ?></p>
        </div>
    </a>
</div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>No products found matching your search.</p>
                    <div class="search-suggestions">
                        <p class="suggestion-title">Try searching for:</p>
                        <div class="suggestion-list">
                            <?php foreach ($categories as $category): ?>
                                <div class="suggestion-item" onclick="window.location.href='search_results.php?query=<?php echo urlencode($category); ?>'">
                                    <?php echo ucfirst($category); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>