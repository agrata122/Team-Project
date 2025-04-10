<div class="product-card" 
     data-name="<?php echo htmlspecialchars($product['name']); ?>" 
     data-price="<?php echo $product['price']; ?>" 
     data-image="<?php echo $product['image']; ?>">
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
    <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
    
    <div class="card-actions">
        <!-- Add to Cart Button -->
        <button class="add-to-cart">Add to Cart</button>
        
        <!-- Quantity Selector (Initially Hidden) -->
        <div class="quantity-container" style="display: none;">
            <button type="button" class="quantity-btn decrease">▼</button>
            <input type="text" class="quantity-input" value="1" readonly>
            <button type="button" class="quantity-btn increase">▲</button>
        </div>

        <span class="wishlist">&#9825;</span> 
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".product-card").forEach((card) => {
        const addToCartBtn = card.querySelector(".add-to-cart");
        const quantityContainer = card.querySelector(".quantity-container");
        const decreaseBtn = card.querySelector(".decrease");
        const increaseBtn = card.querySelector(".increase");
        const quantityInput = card.querySelector(".quantity-input");

        // Show quantity selector when "Add to Cart" is clicked
        addToCartBtn.addEventListener("click", function () {
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
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        newIncreaseBtn.addEventListener("click", function (event) {
            event.preventDefault();
            let currentValue = parseInt(quantityInput.value) || 1;
            quantityInput.value = currentValue + 1;
        });
    });
});


</script>
