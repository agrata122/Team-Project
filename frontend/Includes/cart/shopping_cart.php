<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';




$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Initialize variables
$cart_items = array();
$total_price = 0;

// Enhanced debug logging
error_log("===== CART PAGE DEBUG =====");
error_log("SESSION: " . print_r($_SESSION, true));
error_log("COOKIES: " . print_r($_COOKIE, true));

// Handle user ID - prioritize logged-in user over cookie
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    error_log("Using user_id from SESSION: " . $user_id);
    
    // Get cart for logged-in user
    $cartQuery = "SELECT cart_id FROM cart WHERE user_id = :user_id";
    $stid = oci_parse($conn, $cartQuery);
    oci_bind_by_name($stid, ":user_id", $user_id);
} elseif (isset($_COOKIE['guest_id'])) {
    $user_id = $_COOKIE['guest_id'];
    error_log("Using guest_id from COOKIE: " . $user_id);
    
    // Get cart for guest user
    $cartQuery = "SELECT cart_id FROM guest_cart WHERE guest_id = :guest_id";
    $stid = oci_parse($conn, $cartQuery);
    oci_bind_by_name($stid, ":guest_id", $user_id, -1, SQLT_CHR);
} else {
    $user_id = 'guest_' . uniqid();
    setcookie('guest_id', $user_id, time() + 60 * 60 * 24 * 30, "/");
    error_log("Generated new guest_id: " . $user_id);
    
    // Create new guest cart
    $createCartQuery = "INSERT INTO guest_cart (guest_id, add_date) VALUES (:guest_id, SYSDATE) RETURNING cart_id INTO :new_cart_id";
    $stid = oci_parse($conn, $createCartQuery);
    $new_cart_id = null;
    oci_bind_by_name($stid, ":guest_id", $user_id, -1, SQLT_CHR);
    oci_bind_by_name($stid, ":new_cart_id", $new_cart_id, 32, SQLT_INT);
    
    if (!oci_execute($stid)) {
        $error = oci_error($stid);
        error_log("Guest cart creation error: " . $error['message']);
        die("Error creating your cart. Please try again.");
    }
    
    $cart_id = $new_cart_id;
    error_log("Created new guest cart with cart_id: " . $cart_id);
}

if (!isset($cart_id)) {
    if (!oci_execute($stid)) {
        $error = oci_error($stid);
        error_log("Cart lookup error: " . $error['message']);
        die("Error accessing your cart. Please try again later.");
    }

    $cartRow = oci_fetch_assoc($stid);
    if ($cartRow) {
        $cart_id = $cartRow['CART_ID'];
        error_log("Found existing cart_id: " . $cart_id);
    } else {
        if (isset($_SESSION['user_id'])) {
            // Create new cart for logged-in user
            $createCartQuery = "INSERT INTO cart (user_id, add_date) VALUES (:user_id, SYSDATE) RETURNING cart_id INTO :new_cart_id";
            $stid = oci_parse($conn, $createCartQuery);
            $new_cart_id = null;
            oci_bind_by_name($stid, ":user_id", $user_id);
            oci_bind_by_name($stid, ":new_cart_id", $new_cart_id, 32, SQLT_INT);
        } else {
            // Create new guest cart
            $createCartQuery = "INSERT INTO guest_cart (guest_id, add_date) VALUES (:guest_id, SYSDATE) RETURNING cart_id INTO :new_cart_id";
            $stid = oci_parse($conn, $createCartQuery);
            $new_cart_id = null;
            oci_bind_by_name($stid, ":guest_id", $user_id, -1, SQLT_CHR);
            oci_bind_by_name($stid, ":new_cart_id", $new_cart_id, 32, SQLT_INT);
        }
        
        if (!oci_execute($stid)) {
            $error = oci_error($stid);
            error_log("Cart creation error: " . $error['message']);
            die("Error creating your cart. Please try again.");
        }
        
        $cart_id = $new_cart_id;
        error_log("Created new cart with cart_id: " . $cart_id);
    }
}

// Now fetch cart items with stock information
if (isset($_SESSION['user_id'])) {
    $itemsQuery = "
    SELECT p.product_id, p.product_name, p.product_image, p.price, p.stock, pc.quantity
    FROM product_cart pc
    JOIN product p ON pc.product_id = p.product_id
    WHERE pc.cart_id = :cart_id
    ";
} else {
    $itemsQuery = "
    SELECT p.product_id, p.product_name, p.product_image, p.price, p.stock, pc.quantity
    FROM guest_product_cart pc
    JOIN product p ON pc.product_id = p.product_id
    WHERE pc.cart_id = :cart_id
    ";
}

$stid = oci_parse($conn, $itemsQuery);
oci_bind_by_name($stid, ":cart_id", $cart_id);

if (oci_execute($stid)) {
    while ($row = oci_fetch_assoc($stid)) {
        $cart_items[] = $row;
        $total_price += $row['PRICE'] * $row['QUANTITY'];
    }
    error_log("Found " . count($cart_items) . " items in cart");
} else {
    $error = oci_error($stid);
    error_log("Cart items query error: " . $error['message']);
}

// Debug: Output cart items
error_log("Cart items: " . print_r($cart_items, true));
error_log("===== END DEBUG =====");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        /* Reset & basic styles */
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

        .empty {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: #777;
        }
        
        .stock-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }

        .slot-selection {
            margin-top: 15px;
        }

        .date-selection, .time-selection {
            margin-bottom: 15px;
        }

        .date-selection h4, .time-selection h4 {
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }

        .date-buttons, .time-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .date-buttons button, .time-buttons button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .date-buttons button:hover, .time-buttons button:hover {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .date-buttons button.active, .time-buttons button.active {
            background: #4CAF50;
            color: #fff;
            border-color: #4CAF50;
        }

        .slot-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .slot-warning {
            font-size: 14px;
            color: #e53935;
            margin-top: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
  <?php include '../../Includes/header.php'; ?>
</header>

<section class="shopping-cart">
  <div class="cart-container">
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
          <?php if (count($cart_items) > 0) {
            foreach ($cart_items as $item) {
              $subtotal = $item['PRICE'] * $item['QUANTITY'];
          ?>
          <tr>
            <td class="product-info">
              <img src="../../trader/uploaded_files/<?= htmlspecialchars($item['PRODUCT_IMAGE']); ?>" alt="Product Image">
              <div>
                <span><?= htmlspecialchars($item['PRODUCT_NAME']); ?></span>
                <div class="stock-info"><?= intval($item['STOCK']); ?> available in stock</div>
              </div>
            </td>
            <td>$<?= number_format($item['PRICE'], 2); ?></td>
            <td>
              <div class="qty-controls" data-stock="<?= intval($item['STOCK']); ?>">
                <button type="button" onclick="updateQuantity('<?= htmlspecialchars($item['PRODUCT_ID']); ?>', -1, this)">-</button>
                <input type="number" class="qty" value="<?= intval($item['QUANTITY']); ?>" readonly>
                <button type="button" onclick="updateQuantity('<?= htmlspecialchars($item['PRODUCT_ID']); ?>', 1, this)">+</button>
              </div>
            </td>
            <td>$<span class="item-price" data-price="<?= floatval($item['PRICE']); ?>"><?= number_format($subtotal, 2); ?></span></td>
            <td><button type="button" class="btn red" onclick="removeItem('<?= htmlspecialchars($item['PRODUCT_ID']); ?>', this)">Remove</button></td>
          </tr>
          <?php }
          } else { ?>
            <tr><td colspan="5" class="empty">Your cart is empty!</td></tr>
          <?php } ?>
        </tbody>
      </table>

      <div class="cart-actions">
        <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="btn green">Return to Home</a>
        <button type="button" class="btn red" onclick="clearCart()">Clear Cart</button>
      </div>

      

     

      <div class="payment-methods">
        <p><br>Payment Method We Offer:</p>
        <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal">
      
      </div>
    </div>

    <div class="cart-right">
      <div class="pickup-time">
        <h3>Collection Slot</h3>
        <p class="slot-info">Please select a collection slot at least 24 hours in advance.</p>
        <div class="slot-selection">
            <div class="date-selection">
                <h4>Select Date</h4>
                <div class="date-buttons" id="dateButtons">
                    <!-- Dates will be populated by JavaScript -->
                </div>
            </div>
            <div class="time-selection">
                <h4>Select Time</h4>
                <div class="time-buttons" id="timeButtons">
                    <!-- Time slots will be populated by JavaScript -->
                </div>
            </div>
            <input type="hidden" id="selectedSlotId" name="slot_id" value="">
            <p class="slot-warning" style="display: none; color: #e53935; margin-top: 10px;"></p>
        </div>
      </div>

      <div class="total-section">
        <p>Sub Total: <strong>$<span id="total-price"><?= number_format($total_price, 2); ?></span></strong></p>
        <p>Total: <strong>$<span id="final-price"><?= number_format($total_price, 2); ?></span></strong></p>
        <button id="checkoutBtn" class="btn green full">Proceed to Checkout</button>
      </div>
    </div>
  </div>
</section>

<?php include '../../Includes/footer.php'; ?>

<script>
function updateQuantity(productId, change, button) {
    const qtyControls = button.parentElement;
    const qtyInput = qtyControls.querySelector('.qty');
    const currentQty = parseInt(qtyInput.value);
    const stock = parseInt(qtyControls.getAttribute('data-stock'));
    let newQty = currentQty + change;
    
    // Prevent decreasing below 1
    if (newQty < 1) return;
    
    // Calculate total items in cart
    let totalCartItems = 0;
    document.querySelectorAll('.qty').forEach(input => {
        if (input !== qtyInput) { // Don't count the current item's quantity
            totalCartItems += parseInt(input.value);
        }
    });
    
    // Check if adding this item would exceed the 20 item limit
    if (change > 0 && (totalCartItems + newQty) > 20) {
        alert('Sorry, you cannot add more than 20 items total in your cart.');
        return;
    }
    
    // Prevent increasing above stock
    if (change > 0 && newQty > stock) {
        alert(`Sorry, only ${stock} items available in stock.`);
        return;
    }
    
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
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(data => {
        if (data.trim() !== "success") {
            throw new Error('Update failed');
        }
    })
    .catch(error => {
        console.error("Error updating quantity:", error);
        alert("Error updating quantity. Please try again.");
        // Revert the UI changes if the server update failed
        qtyInput.value = currentQty;
        const revertedSubtotal = currentQty * pricePerItem;
        priceElement.textContent = revertedSubtotal.toFixed(2);
        updateTotalPrice();
    });
}

function updateTotalPrice() {
    let total = 0;
    document.querySelectorAll('.item-price').forEach(price => {
        total += parseFloat(price.textContent);
    });
    
    document.getElementById('total-price').textContent = total.toFixed(2);
    document.getElementById('final-price').textContent = total.toFixed(2);
}

function removeItem(productId, button) {
    if(confirm("Are you sure you want to remove this item?")) {
        const formData = new FormData();
        formData.append("product_id", productId);

        fetch("remove_item.php", {
            method: "POST",
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            if(data.trim() === "success") {
                const row = button.closest('tr');
                row.remove();
                updateTotalPrice();
                
                const tbody = document.querySelector('.cart-table tbody');
                if (tbody.children.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty">Your cart is empty!</td></tr>';
                }
            } else {
                alert("Error removing item. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error removing item:", error);
            alert("Error removing item. Please try again.");
        });
    }
}

function clearCart() {
    if(confirm("Are you sure you want to clear your entire cart?")) {
        fetch("clear_cart.php", {
            method: "POST"
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            if(data.trim() === "success") {
                const tbody = document.querySelector('.cart-table tbody');
                tbody.innerHTML = '<tr><td colspan="5" class="empty">Your cart is empty!</td></tr>';
                
                document.getElementById('total-price').textContent = '0.00';
                document.getElementById('final-price').textContent = '0.00';
            } else {
                alert("Error clearing cart. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error clearing cart:", error);
            alert("Error clearing cart. Please try again.");
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dateButtons = document.getElementById('dateButtons');
    const timeButtons = document.getElementById('timeButtons');
    const selectedSlotId = document.getElementById('selectedSlotId');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const slotWarning = document.querySelector('.slot-warning');
    
    // Fetch available slots
    fetch('collection_slot.php?action=get_slots')
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Failed to fetch collection slots');
                });
            }
            return response.json();
        })
        .then(response => {
            if (!response.success) {
                throw new Error(response.error || 'Failed to fetch collection slots');
            }
            
            const slots = response.data;
            if (!Array.isArray(slots) || slots.length === 0) {
                slotWarning.textContent = 'No collection slots available at this time.';
                slotWarning.style.display = 'block';
                checkoutBtn.disabled = true;
                return;
            }
            
            // Group slots by date
            const slotsByDate = {};
            slots.forEach(slot => {
                if (!slotsByDate[slot.date]) {
                    slotsByDate[slot.date] = [];
                }
                slotsByDate[slot.date].push(slot);
            });
            
            // Create date buttons
            Object.keys(slotsByDate).sort().forEach(date => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = formatDate(date);
                button.dataset.date = date;
                button.addEventListener('click', () => selectDate(date, slotsByDate[date]));
                dateButtons.appendChild(button);
            });
        })
        .catch(error => {
            console.error('Error fetching slots:', error);
            slotWarning.textContent = error.message || 'Error loading collection slots. Please try again.';
            slotWarning.style.display = 'block';
            checkoutBtn.disabled = true;
        });
    
    function selectDate(date, slots) {
        // Update date buttons
        dateButtons.querySelectorAll('button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.date === date);
        });
        
        // Clear and update time buttons
        timeButtons.innerHTML = '';
        slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = `${slot.time_slot} - ${getEndTime(slot.time_slot)}`;
            button.dataset.slotId = slot.slot_id;
            button.addEventListener('click', () => selectTimeSlot(slot));
            timeButtons.appendChild(button);
        });
    }
    
    function getEndTime(startTime) {
        const [hours, minutes] = startTime.split(':');
        const endTime = new Date();
        endTime.setHours(parseInt(hours) + 3, parseInt(minutes), 0);
        return endTime.toTimeString().slice(0, 5);
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }
    
    function selectTimeSlot(slot) {
        // Update time buttons
        timeButtons.querySelectorAll('button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.slotId === slot.slot_id);
        });
        
        // Update hidden input and enable checkout
        selectedSlotId.value = slot.slot_id;
        checkoutBtn.disabled = false;
        slotWarning.style.display = 'none';
    }
    
    // Update checkout button click handler
    checkoutBtn.addEventListener('click', function() {
        window.location.href = 'checkout.php';
    });
});
</script>

</body>
</html>