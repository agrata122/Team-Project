<?php
// Check if the function is already defined before including connect.php
if (!function_exists('getDBConnection')) {
    require 'C:\xampp\htdocs\E-commerce\backend\connect.php';
}
$conn = getDBConnection();

// Query to fetch latest products with their images and prices
$sql = "SELECT * FROM (
    SELECT p.product_id, p.product_name, p.price, p.product_image, 
           (SELECT AVG(review_rating) FROM review r WHERE r.product_id = p.product_id) as avg_rating
    FROM product p 
    ORDER BY p.product_id DESC
) WHERE ROWNUM <= 5";

$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$products = [];
while ($row = oci_fetch_assoc($stmt)) {
    // Handle CLOB fields
    $product_image = $row['PRODUCT_IMAGE'];
    if (is_object($product_image) && get_class($product_image) === 'OCILob') {
        $product_image = $product_image->read($product_image->size());
    }
    
    $products[] = [
        "id" => $row['PRODUCT_ID'],
        "name" => $row['PRODUCT_NAME'],
        "price" => "£" . number_format($row['PRICE'], 2),
        "image" => "/E-commerce/frontend/trader/uploaded_files/" . $product_image,
        "rating" => $row['AVG_RATING']
    ];
}

oci_free_statement($stmt);
oci_close($conn);
?>

<div class="new-products-popup">
    <h2>Latest Products</h2>
    <div class="new-products-container">
        <?php foreach ($products as $product): ?>
            <a href="/E-commerce/frontend/includes/pages/product_detail.php?product_id=<?php echo $product['id']; ?>" class="new-product-card">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                <div class="new-product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price"><?php echo $product['price']; ?></p>
                    <?php if ($product['rating']): ?>
                        <div class="rating">
                            <?php
                            $rating = round($product['rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Popup container styling */
.new-products-popup {
    background-color: #ffffff;
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
    text-decoration: none;
    color: inherit;
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

.rating {
    color: #ffd700;
    font-size: 12px;
    margin-top: 2px;
}
</style>