<?php
// Use $product_data instead of $product to match the new structure
$product = $product_data;
?>
<div class="product-card" 
     data-name="<?php echo htmlspecialchars($product['name']); ?>" 
     data-price="<?php echo $product['price']; ?>" 
     data-image="<?php echo $product['image']; ?>"
     data-id="<?php echo $product['id']; ?>"
     onclick="window.location.href='/E-commerce/frontend/Includes/pages/product_detail.php?product_id=<?php echo $product['id']; ?>'">
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
    <p class="price">RS. <?php echo number_format($product['price'], 2); ?></p>
    
    <?php if (isset($product['rating'])): ?>
    <div class="rating">
        <?php if ($product['rating']): ?>
            <span class="rating-stars">
                <?php
                $rating = round($product['rating']);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $rating ? '★' : '☆';
                }
                ?>
            </span>
            <span>(<?= number_format($product['rating'], 1) ?>)</span>
        <?php else: ?>
            <span class="no-rating">No ratings yet</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card-actions">
        <!-- Add to Cart Button -->
        <button class="add-to-cart" onclick="event.stopPropagation();">Add to Cart</button>
        
        <!-- Quantity Selector (Initially Hidden) -->
        <div class="quantity-container" style="display: none;">
            <button type="button" class="quantity-btn decrease">▼</button>
            <input type="text" class="quantity-input" value="1" readonly>
            <button type="button" class="quantity-btn increase">▲</button>
        </div>

        <span class="wishlist" onclick="event.stopPropagation();">&#9825;</span> 
    </div>
</div>

<style>
.rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 5px 0;
    color: #666;
}

.rating-stars {
    color: #ffd700;
}

.no-rating {
    color: #999;
    font-style: italic;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".product-card").forEach((card) => {
        const addToCartBtn = card.querySelector(".add-to-cart");
        const quantityContainer = card.querySelector(".quantity-container");
        const decreaseBtn = card.querySelector(".decrease");
        const increaseBtn = card.querySelector(".increase");
        const quantityInput = card.querySelector(".quantity-input");

        // Show quantity selector when "Add to Cart" is clicked
        addToCartBtn.addEventListener("click", function (event) {
            event.stopPropagation(); // Prevent card click
            quantityContainer.style.display = "flex"; 
            addToCartBtn.disabled = true; // Disable "Add to Cart" after clicking
        }, { once: true }); // Ensure it runs only once

        // Prevent multiple event listeners from attaching
        decreaseBtn.replaceWith(decreaseBtn.cloneNode(true));
        increaseBtn.replaceWith(increaseBtn.cloneNode(true));

        // Select the new buttons to avoid duplicate listeners
        const newDecreaseBtn = card.querySelector(".decrease");
        const newIncreaseBtn = card.querySelector(".increase");

        newDecreaseBtn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent card click
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        newIncreaseBtn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent card click
            let currentValue = parseInt(quantityInput.value) || 1;
            quantityInput.value = currentValue + 1;
        });
    });
});
</script>
