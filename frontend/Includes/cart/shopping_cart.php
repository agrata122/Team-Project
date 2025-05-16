<?php
require 'C:\xampp\htdocs\E-commerce\backend\db_connection.php';

$conn = getDBConnection();
if(!$conn) {
    die("Database connection failed");
}

// Ensure user has a unique ID
if(isset($_COOKIE['user_id'])){
    $user_id = $_COOKIE['user_id'];
}else{
    setcookie('user_id', uniqid(), time() + 60*60*24*30, "/");
    $user_id = $_COOKIE['user_id'];
}

// Fetch cart details with quantity
$cart_query = $conn->prepare("SELECT cart.cart_id, product.product_id, product.product_name, product.product_image, product.price, product.stock, product_cart.quantity 
                             FROM cart
                             JOIN product_cart ON cart.cart_id = product_cart.cart_id
                             JOIN product ON product_cart.product_id = product.product_id
                             WHERE cart.user_id = ?");
$cart_query->execute([$user_id]);
$cart_items = $cart_query->fetchAll(PDO::FETCH_ASSOC);

$total_price = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>/* Reset & basic styles */
/* Cart Container */
.cart-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  max-width: 1200px;
  margin: 40px auto;
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.cart-left {
  flex: 2;
  min-width: 650px;
}

.cart-right {
  flex: 1;
  min-width: 280px;
}

/* Table Styles */
.cart-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.cart-table th, .cart-table td {
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.cart-table th {
  background-color: #f8f8f8;
  font-weight: 600;
  color: #333;
}

.product-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-info img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 6px;
}

/* Quantity Controls */
.qty-controls {
  display: flex;
  align-items: center;
  border: 1px solid #ccc;
  border-radius: 6px;
  overflow: hidden;
}

.qty-controls button {
  padding: 6px 6px;
  background: #eee;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

.qty-controls input {
  width: 30px;
  text-align: center;
  border: none;
  background: #f8f8f8;
}

/* Buttons */
.btn {
  background-color: #4CAF50;
  color: #fff;
  padding: 10px 18px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  text-align: center;
  display: inline-block;
  transition: 0.3s ease;
}

.btn:hover {
  background-color: #43a047;
}

.btn.grey {
  background-color: #ccc;
  color: #000;
}

.btn.green {
  background-color: #00c853;
}

.btn.full {
  display: block;
  width: 100%;
  margin-top: 20px;
}

/* Actions Section */
.cart-actions {
  margin: 20px 0;
  display: flex;
  gap: 10px;
}

.coupon-section {
  display: flex;
  gap: 10px;
  margin: 20px 0;
}

.coupon-section input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
}

/* Payment Methods */
.payment-methods {
  margin-top: 20px;
}

.payment-methods p {
  margin-bottom: 10px;
  font-weight: 500;
}

.payment-methods img {
  width: 100px;
  margin-right: 10px;
}

/* Pickup Time */
.pickup-time {
  background: #f9f9f9;
  padding: 20px;
  border-radius: 10px;
}

.pickup-time h3 {
  margin-bottom: 10px;
}

.pickup-time .days,
.pickup-time .slots {
  display: flex;
  gap: 10px;
  margin: 10px 0;
}

.pickup-time button {
  padding: 10px 14px;
  border-radius: 6px;
  border: 1px solid #ddd;
  background: #fff;
  cursor: pointer;
  transition: 0.2s ease;
}

.pickup-time button.active,
.pickup-time button:hover {
  background-color: #00c853;
  color: #fff;
  border-color: #00c853;
}

/* Totals */
.total-section {
  background: #f9f9f9;
  padding: 20px;
  margin-top: 20px;
  border-radius: 10px;
}

.total-section p {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 1rem;
}

.btn.red {
    background-color: #e53935;
}

.btn.red:hover {
    background-color: #c62828;
}

.cart-table td {
    vertical-align: middle;
}

 </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <?php
include '../../Includes/header.php'; 
?>
        
    </header>

<section class="shopping-cart">
  <div class="cart-container">
    <!-- Left side: Product table -->
    <div class="cart-left">
      <h2>Shopping Cart</h2>
      <table class="cart-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($cart_items) > 0) { 
            foreach($cart_items as $item) { 
              $subtotal = $item['price'] * $item['quantity'];
              $total_price += $subtotal;
          ?>
          <tr>
            <td class="product-info">
              <img src="../../trader/uploaded_files/<?= $item['product_image']; ?>" alt="">
              <span><?= $item['product_name'] ?></span>
            </td>
            <td>$<?= number_format($item['price'], 2) ?></td>
            <td>
              <div class="qty-controls">
                <button onclick="updateQuantity(<?= $item['product_id'] ?>, -1, this)">-</button>
                <input type="number" class="qty" value="<?= $item['quantity'] ?>" readonly>


                <button onclick="updateQuantity(<?= $item['product_id'] ?>, 1, this)">+</button>
              </div>
            </td>
            <td>$<span class="item-price" data-price="<?= $item['price'] ?>"><?= number_format($subtotal, 2) ?></span></td>
            <td>
    <button class="btn red" onclick="removeItem(<?= $item['product_id'] ?>, this)">Remove</button>
</td>
          </tr>
          <?php } } else { ?>
          <tr><td colspan="4" class="empty">Your cart is empty!</td></tr>

          <?php } ?>
        </tbody>
      </table>
      <!-- Replace the current cart-actions div with this: -->
<div class="cart-actions">
    <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="btn green">Return to Home</a>
    <button class="btn red" onclick="clearCart()">Clear Cart</button>
</div>

      <div class="coupon-section">
        <input type="text" placeholder="Coupon Code">
        <button class="btn green">Apply Coupon</button>
      </div>

      <div class="payment-methods">
        <p>Select a Payment Method</p>
        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal">
        <img src="https://stripe.com/img/v3/home/twitter.png" alt="Stripe" style="height: 40px;">

      </div>
    </div>

    <!-- Right side: Summary and pickup -->
    <div class="cart-right">
      <div class="pickup-time">
        <h3>Pickup Time</h3>
        <div class="days">
          <button class="active">Wed</button>
          <button>Thurs</button>
          <button>Fri</button>
        </div>
        <div class="slots">
          <button>10–13</button>
          <button class="active">13–16</button>
          <button>16–19</button>
        </div>
      </div>

      <div class="total-section">
        <p>Sub Total: <strong>$<span id="total-price"><?= number_format($total_price, 2) ?></span></strong></p>
        <p>Total: <strong>$<span id="total-price"><?= number_format($total_price, 2) ?></span></strong></p>
        <a href="checkout.php" class="btn green full">Proceed to Checkout</a>
      </div>
    </div>
  </div>
</section>
<?php
include '../../Includes/footer.php';
?>

<script>
function updateQuantity(productId, change, button) {
    const qtyInput = button.parentElement.querySelector('.qty');
    let newQty = parseInt(qtyInput.value) + change;
    if (newQty < 1) return;
    
    qtyInput.value = newQty;

    // Update subtotal
    const row = button.closest('tr');
    const priceElement = row.querySelector('.item-price');
    const pricePerItem = parseFloat(priceElement.getAttribute('data-price'));
    const newSubtotal = newQty * pricePerItem;
    priceElement.textContent = newSubtotal.toFixed(2);

    // Update total
    updateTotalPrice();

    // Send update to server
    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("qty", newQty);

    fetch("update_quantity.php", {
        method: "POST",
        body: formData
    });
}

function updateTotalPrice() {
    let total = 0;
    document.querySelectorAll('.item-price').forEach(price => {
        total += parseFloat(price.textContent);
    });
    document.querySelectorAll('#total-price').forEach(el => {
        el.textContent = total.toFixed(2);
    });
}

function removeItem(productId, button) {
    if(confirm("Are you sure you want to remove this item?")) {
        const formData = new FormData();
        formData.append("product_id", productId);

        fetch("remove_item.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if(data.trim() === "success") {
                const row = button.closest('tr');
                row.remove();
                updateTotalPrice();
            } else {
                alert("Error removing item. Please try again.");
            }
        })
        .catch(error => console.error("Error:", error));
    }
}

function clearCart() {
    if(confirm("Are you sure you want to clear your entire cart?")) {
        fetch("clear_cart.php", {
            method: "POST"
        })
        .then(response => response.text())
        .then(data => {
            if(data.trim() === "success") {
                // Remove all rows except the header
                const tbody = document.querySelector('.cart-table tbody');
                tbody.innerHTML = '<tr><td colspan="5" class="empty">Your cart is empty!</td></tr>';
                
                // Update total price to 0
                document.querySelectorAll('#total-price').forEach(el => {
                    el.textContent = '0.00';
                });
            } else {
                alert("Error clearing cart. Please try again.");
            }
        })
        .catch(error => console.error("Error:", error));
    }
}
</script>

</body>
</html>
