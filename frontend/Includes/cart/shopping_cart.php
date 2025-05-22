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

// Handle user ID - only for logged-in users
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    error_log("Using user_id from SESSION: " . $user_id);
    
    // Get cart for logged-in user
    $cartQuery = "SELECT cart_id FROM cart WHERE user_id = :user_id";
    $stid = oci_parse($conn, $cartQuery);
    oci_bind_by_name($stid, ":user_id", $user_id);
} else {
    die("Please log in to access your cart");
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
        // Create new cart for logged-in user
        $createCartQuery = "INSERT INTO cart (user_id, add_date) VALUES (:user_id, SYSDATE) RETURNING cart_id INTO :new_cart_id";
        $stid = oci_parse($conn, $createCartQuery);
        $new_cart_id = null;
        oci_bind_by_name($stid, ":user_id", $user_id);
        oci_bind_by_name($stid, ":new_cart_id", $new_cart_id, 32, SQLT_INT);
        
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
$itemsQuery = "
SELECT p.product_id, p.product_name, p.product_image, p.price, p.stock, pc.quantity
FROM product_cart pc
JOIN product p ON pc.product_id = p.product_id
WHERE pc.cart_id = :cart_id
";

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

// Get available collection slots
error_log("===== COLLECTION SLOT DEBUG =====");
error_log("Database connection status: " . ($conn ? "Connected" : "Not connected"));

// First, let's check if we have any data at all
$check_sql = "SELECT COUNT(*) as total FROM collection_slot";
$check_stmt = oci_parse($conn, $check_sql);
if (oci_execute($check_stmt)) {
    $count_row = oci_fetch_assoc($check_stmt);
    error_log("Total slots in database: " . $count_row['TOTAL']);
}

// Now get all slots without any filters
//$sql = "SELECT collection_slot_id, 
//        TO_CHAR(slot_date, 'Day, Mon DD') as formatted_date,
//        slot_time as time_slot,
//        slot_date,
//        slot_day
//        FROM collection_slot 
//        ORDER BY slot_date, slot_time";

// Only get the next 6 available slots more than 24 hours from now
$sql = "SELECT * FROM (
    SELECT cs.collection_slot_id, 
           TO_CHAR(cs.slot_date, 'Day, Mon DD') AS formatted_date,
           TO_CHAR(cs.slot_time, 'HH:MI AM') AS time_slot,
           cs.slot_date,
           cs.slot_day,
           TO_CHAR(cs.slot_date, 'D') as day_number,
           COUNT(o.order_id) as order_count
    FROM collection_slot cs
    LEFT JOIN orders o ON cs.collection_slot_id = o.collection_slot_id
    WHERE cs.slot_time > SYSTIMESTAMP + INTERVAL '1' DAY
    AND cs.slot_day IN ('Wednesday', 'Thursday', 'Friday')
    GROUP BY cs.collection_slot_id, cs.slot_date, cs.slot_time, cs.slot_day
    HAVING COUNT(o.order_id) < 20
    ORDER BY cs.slot_date, cs.slot_time
)
WHERE ROWNUM <= 9";

error_log("SQL Query: " . $sql);

$stmt = oci_parse($conn, $sql);
if (!$stmt) {
    $error = oci_error($conn);
    error_log("Error parsing SQL: " . $error['message']);
    echo "<!-- Error parsing SQL: " . $error['message'] . " -->";
}

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    error_log("Error executing SQL: " . $error['message']);
    echo "<!-- Error executing SQL: " . $error['message'] . " -->";
}

$slots = [];
while ($row = oci_fetch_assoc($stmt)) {
    // Format the date to remove extra spaces
    $row['FORMATTED_DATE'] = trim($row['FORMATTED_DATE']);
    $slots[] = $row;
    error_log("Found slot: " . print_r($row, true));
    error_log("Day number: " . $row['DAY_NUMBER']); // 1=Sunday, 2=Monday, etc.
}

error_log("Total slots found: " . count($slots));
error_log("===== END COLLECTION SLOT DEBUG =====");

// Debug output in HTML
echo "<!-- Debug: Found " . count($slots) . " slots -->";
foreach ($slots as $slot) {
    echo "<!-- Slot: " . print_r($slot, true) . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/cart.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .collection-slot-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .collection-slot-section h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2em;
        }
        
        .collection-slot-section select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            background-color: white;
        }
        
        .collection-slot-section select optgroup {
            font-weight: bold;
            color: #2c3e50;
            padding: 8px 0;
        }
        
        .collection-slot-section select option {
            padding: 8px;
            color: #555;
        }
        
        .slot-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
    </style>
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
        <div class="collection-slot-section">
            <h3>Select Collection Slot</h3>
            <select id="collection-slot" name="collection_slot_id" class="form-control" required>
                <option value="">Select a collection slot</option>
                <?php 
                $currentDay = '';
                foreach ($slots as $slot) {
                    $formatted_date = trim($slot['FORMATTED_DATE']);
                    $day = date('l', strtotime($slot['SLOT_DATE']));
                    
                    if ($day !== $currentDay) {
                        if ($currentDay !== '') {
                            echo '</optgroup>';
                        }
                        echo '<optgroup label="' . $day . '">';
                        $currentDay = $day;
                    }
                    
                    $orderCount = $slot['ORDER_COUNT'];
                    $isAvailable = $orderCount < 20;
                    
                    echo "<option value='" . $slot['COLLECTION_SLOT_ID'] . "' " . 
                         (!$isAvailable ? 'disabled' : '') . ">" . 
                         $formatted_date . " at " . $slot['TIME_SLOT'] . 
                         ($isAvailable ? " (" . (20 - $orderCount) . " slots remaining)" : " - Slot Full") . 
                         "</option>";
                }
                if ($currentDay !== '') {
                    echo '</optgroup>';
                }
                ?>
            </select>
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
    const collectionSlot = document.getElementById('collection-slot');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    checkoutBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const selectedSlot = collectionSlot.value;
        console.log('Selected collection slot:', selectedSlot);
        
        if (!selectedSlot) {
            alert('Please select a collection slot before proceeding to checkout.');
            return;
        }
        
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/E-commerce/frontend/Includes/cart/checkout.php';
        
        const slotInput = document.createElement('input');
        slotInput.type = 'hidden';
        slotInput.name = 'collection_slot_id';
        slotInput.value = selectedSlot;
        
        form.appendChild(slotInput);
        document.body.appendChild(form);
        
        console.log('Submitting form with collection_slot_id:', selectedSlot);
        form.submit();
    });
});
</script>

</body>
</html>