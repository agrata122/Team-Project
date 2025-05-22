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

<link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/new_products_popup.css">