<!-- Global Confirmation Pop-up
<div id="confirmation-popup" class="confirmation-popup" style="display: none;">
    <div class="popup-content">
        <p id="popup-message"></p>
        <button id="confirm-add" class="confirm-btn">Confirm</button>
        <button id="cancel-add" class="cancel-btn">Cancel</button>
    </div>
</div>

<style>
.confirmation-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    z-index: 1000;
    text-align: center;
    display: none;
    width: 300px;
}

.popup-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.confirm-btn, .cancel-btn {
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
}

.confirm-btn {
    background-color: green;
    color: white;
}

.cancel-btn {
    background-color: red;
    color: white;
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

        // Global pop-up elements (ensure it's included on the page)
        const popup = document.getElementById("confirmation-popup");
        const popupMessage = document.getElementById("popup-message");
        const confirmBtn = document.getElementById("confirm-add");
        const cancelBtn = document.getElementById("cancel-add");

        let selectedProductName = ""; // To store product name
        let selectedQuantity = 1; // To store quantity

        // Show quantity selector when "Add to Cart" is clicked for the first time
        addToCartBtn.addEventListener("click", function () {
            if (quantityContainer.style.display === "none" || quantityContainer.style.display === "") {
                quantityContainer.style.display = "inline-flex"; // Show quantity selector
            } else {
                // Store the product name and quantity
                selectedProductName = card.querySelector("h3").textContent;
                selectedQuantity = parseInt(quantityInput.value) || 1; // Ensure it's a number

                // Update pop-up message
                popupMessage.textContent = `Add ${selectedQuantity}x "${selectedProductName}" to the cart?`;

                // Show the pop-up
                popup.style.display = "block";
            }
        });

        // Decrease quantity
        decreaseBtn.addEventListener("click", function () {
            let currentValue = parseInt(quantityInput.value) || 1; // Ensure current value is a number
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        // Increase quantity
        increaseBtn.addEventListener("click", function () {
            let currentValue = parseInt(quantityInput.value) || 1; // Ensure current value is a number
            quantityInput.value = currentValue + 1;
        });

        // Handle Confirm Button Click
        confirmBtn.addEventListener("click", function () {
            popup.style.display = "none";
            alert(`${selectedQuantity}x "${selectedProductName}" added to cart!`); 
            // Here, replace this alert with your actual cart logic
        });

        // Handle Cancel Button Click
        cancelBtn.addEventListener("click", function () {
            popup.style.display = "none";
        });
    });
});
</script> -->
