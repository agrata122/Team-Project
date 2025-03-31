<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
</head>
<body>

<h2>Your Cart</h2>

<table border="1">
    <thead>
        <tr>
            <th>Image</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody id="cart-summary"></tbody>
</table>

<p><strong>Total: $<span id="total-price">0.00</span></strong></p>

<button onclick="clearCart()">Clear Cart</button>
<a href="homepage.php"><button>Continue Shopping</button></a>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    const cartSummary = document.getElementById("cart-summary");
    const totalPriceElement = document.getElementById("total-price");

    let totalPrice = 0;
    cartSummary.innerHTML = "";

    cart.forEach(item => {
        let row = document.createElement("tr");

        let imageCell = document.createElement("td");
        let img = document.createElement("img");
        img.src = item.image;
        img.width = 50;
        imageCell.appendChild(img);
        row.appendChild(imageCell);

        let productCell = document.createElement("td");
        productCell.textContent = item.name;
        row.appendChild(productCell);

        let quantityCell = document.createElement("td");
        quantityCell.textContent = item.quantity;
        row.appendChild(quantityCell);

        let priceCell = document.createElement("td");
        priceCell.textContent = `$${item.price.toFixed(2)}`;
        row.appendChild(priceCell);

        let subtotalCell = document.createElement("td");
        let subtotal = item.price * item.quantity;
        subtotalCell.textContent = `$${subtotal.toFixed(2)}`;
        row.appendChild(subtotalCell);

        cartSummary.appendChild(row);
        totalPrice += subtotal;
    });

    totalPriceElement.textContent = totalPrice.toFixed(2);
});

function clearCart() {
    localStorage.removeItem("cart");
    window.location.reload();
}
</script>

</body>
</html>
