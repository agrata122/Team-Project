<div class="product-card">
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
    <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
    <div class="card-actions">
        <button class="add-to-cart">Add to Cart</button>
        <span class="wishlist">&#9825;</span> <!-- Outline heart initially -->
    </div>
</div>