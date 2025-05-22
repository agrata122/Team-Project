<?php
session_start();
require 'C:\xampp\htdocs\E-commerce\backend\connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Handle user ID - prioritize logged-in user over cookie
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    header("Location: /E-commerce/frontend/Includes/pages/login.php");
    exit();
}

// Ensure numeric user_id for Oracle
$numeric_user_id = (int)$user_id;

// Get user details
$userQuery = "SELECT * FROM users WHERE user_id = :user_id";
$stid = oci_parse($conn, $userQuery);
oci_bind_by_name($stid, ":user_id", $numeric_user_id);
oci_execute($stid);
$user = oci_fetch_assoc($stid);

// Get the user's cart
$cartQuery = "SELECT cart_id FROM cart WHERE user_id = :user_id";
$stid = oci_parse($conn, $cartQuery);
oci_bind_by_name($stid, ":user_id", $numeric_user_id);
oci_execute($stid);
$cart = oci_fetch_assoc($stid);

if (!$cart) {
    header("Location: shopping_cart.php");
    exit();
}

$cart_id = $cart['CART_ID'];

// Get cart items with product details
$itemsQuery = "
SELECT p.product_id, p.product_name, p.price, pc.quantity, 
       s.shop_name, s.shop_category
FROM product_cart pc
JOIN product p ON pc.product_id = p.product_id
JOIN shops s ON p.shop_id = s.shop_id
WHERE pc.cart_id = :cart_id
";

$stid = oci_parse($conn, $itemsQuery);
oci_bind_by_name($stid, ":cart_id", $cart_id);
oci_execute($stid);

$cart_items = [];
$total_price = 0;
$shops = [];

while ($row = oci_fetch_assoc($stid)) {
    $cart_items[] = $row;
    $total_price += $row['PRICE'] * $row['QUANTITY'];
    
    // Group items by shop
    $shop_id = $row['SHOP_CATEGORY'] . '-' . $row['SHOP_NAME'];
    if (!isset($shops[$shop_id])) {
        $shops[$shop_id] = [
            'name' => $row['SHOP_NAME'],
            'category' => $row['SHOP_CATEGORY'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $shops[$shop_id]['items'][] = $row;
    $shops[$shop_id]['subtotal'] += $row['PRICE'] * $row['QUANTITY'];
}

// Handle coupon application if form submitted
$coupon_discount = 0;
$final_price = $total_price;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = $_POST['coupon_code'];
    
    $couponQuery = "SELECT * FROM coupon 
                   WHERE coupon_code = :coupon_code 
                   AND start_date <= SYSDATE 
                   AND end_date >= SYSDATE";
    $stid = oci_parse($conn, $couponQuery);
    oci_bind_by_name($stid, ":coupon_code", $coupon_code);
    oci_execute($stid);
    $coupon = oci_fetch_assoc($stid);
    
    if ($coupon) {
        $coupon_discount = $total_price * ($coupon['COUPON_DISCOUNT_PERCENT'] / 100);
        $final_price = $total_price - $coupon_discount;
        $_SESSION['applied_coupon'] = $coupon['COUPON_ID'];
        $coupon_message = "Coupon applied successfully!";
    } else {
        $coupon_message = "Invalid or expired coupon code.";
    }
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    // Debug logging
    error_log("POST data: " . print_r($_POST, true));
    
    // Create order - using a two-step approach instead of RETURNING INTO
    $orderQuery = "INSERT INTO orders (order_amount, total_amount, coupon_id, status, 
                   user_id, cart_id, collection_slot_id) 
                   VALUES (:order_amount, :total_amount, :coupon_id, 'pending', 
                   :user_id, :cart_id, :collection_slot_id)";
    
    $stid = oci_parse($conn, $orderQuery);
    
    $coupon_id = isset($_SESSION['applied_coupon']) ? $_SESSION['applied_coupon'] : null;
    $collection_slot_id = isset($_POST['collection_slot_id']) ? $_POST['collection_slot_id'] : null;
    
    // Debug logging
    error_log("Collection slot ID: " . $collection_slot_id);
    
    oci_bind_by_name($stid, ":order_amount", $total_price);
    oci_bind_by_name($stid, ":total_amount", $final_price);
    oci_bind_by_name($stid, ":coupon_id", $coupon_id);
    oci_bind_by_name($stid, ":user_id", $numeric_user_id);
    oci_bind_by_name($stid, ":cart_id", $cart_id);
    oci_bind_by_name($stid, ":collection_slot_id", $collection_slot_id);
    
    // Debug logging before execution
    error_log("Executing order creation with collection_slot_id: " . $collection_slot_id);
    
    if (oci_execute($stid)) {
        // Debug logging after successful execution
        error_log("Order created successfully with collection_slot_id: " . $collection_slot_id);
        // Get the sequence value or the latest order_id for this user
        $getOrderIdQuery = "SELECT MAX(order_id) as last_order_id FROM orders WHERE user_id = :user_id";
        $stid = oci_parse($conn, $getOrderIdQuery);
        oci_bind_by_name($stid, ":user_id", $numeric_user_id);
        oci_execute($stid);
        $orderRow = oci_fetch_assoc($stid);
        
        if ($orderRow && isset($orderRow['LAST_ORDER_ID'])) {
            $order_id = $orderRow['LAST_ORDER_ID'];
            $_SESSION['current_order_id'] = $order_id;
            $_SESSION['order_total'] = $final_price;
            
            // Store order items
            $itemsQuery = "SELECT pc.product_id, pc.quantity, p.price 
                          FROM product_cart pc 
                          JOIN product p ON pc.product_id = p.product_id 
                          WHERE pc.cart_id = :cart_id";
            $stid = oci_parse($conn, $itemsQuery);
            oci_bind_by_name($stid, ":cart_id", $cart_id);
            oci_execute($stid);
            
            while ($item = oci_fetch_assoc($stid)) {
                $insertItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (:order_id, :product_id, :quantity, :price)";
                $itemStid = oci_parse($conn, $insertItemQuery);
                oci_bind_by_name($itemStid, ":order_id", $order_id);
                oci_bind_by_name($itemStid, ":product_id", $item['PRODUCT_ID']);
                oci_bind_by_name($itemStid, ":quantity", $item['QUANTITY']);
                oci_bind_by_name($itemStid, ":price", $item['PRICE']);
                oci_execute($itemStid);
                oci_free_statement($itemStid);
            }
            
            // Redirect to payment processing
            header("Location: checkout.php?step=payment");
            exit();
        } else {
            $error = "Error: Could not retrieve order ID after creation.";
        }
    } else {
        $error = oci_error($stid);
        $order_error = "Error creating order: " . $error['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-commerce</title>
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/checkout.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Footer.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/Header.css">
</head>
<body>
    <header>
        <?php include '../../Includes/header.php'; ?>
    </header>

    <div class="container">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>
        
        <div class="checkout-steps">
            <div class="step <?php echo (!isset($_GET['step']) ? 'active' : 'completed'); ?>">
                <div class="step-number">1</div>
                <div class="step-title">Order Details</div>
            </div>
            <div class="step <?php echo (isset($_GET['step']) && $_GET['step'] === 'payment' ? 'active' : (isset($_GET['step']) ? 'completed' : '')); ?>">
                <div class="step-number">2</div>
                <div class="step-title">Payment</div>
            </div>
            <div class="step <?php echo (isset($_GET['step']) && $_GET['step'] === 'confirmation' ? 'active' : ''); ?>">
                <div class="step-number">3</div>
                <div class="step-title">Confirmation</div>
            </div>
        </div>
        
        <?php if (!isset($_GET['step'])): ?>
        <!-- Order Details Step -->
        <form method="POST" action="checkout.php">
            <div class="row">
                <div class="col-md-6">
                    <div class="checkout-content">
                        <h2 class="section-title">Billing Details</h2>
                        
                        <!-- Add hidden input for collection slot ID -->
                        <?php if (isset($_POST['collection_slot_id'])): ?>
                        <input type="hidden" name="collection_slot_id" value="<?php echo htmlspecialchars($_POST['collection_slot_id']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['FULL_NAME']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['CONTACT_NO']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="checkout-content">
                        <h2 class="section-title">Order Summary</h2>
                        
                        <?php foreach ($shops as $shop): ?>
                        <div class="shop-group">
                            <div class="shop-header">
                                <?php echo htmlspecialchars($shop['name']); ?> (<?php echo htmlspecialchars($shop['category']); ?>)
                            </div>
                            
                            <?php foreach ($shop['items'] as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['PRODUCT_NAME']); ?> × <?php echo $item['QUANTITY']; ?></span>
                                <span>$<?php echo number_format($item['PRICE'] * $item['QUANTITY'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="order-item" style="font-weight: 600;">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($shop['subtotal'], 2); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="order-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        
                        <?php if (isset($coupon_message)): ?>
                        <div class="alert <?php echo ($coupon_discount > 0) ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $coupon_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="coupon-form">
                            <input type="text" name="coupon_code" placeholder="Coupon Code">
                            <button type="submit" name="apply_coupon" class="btn btn-outline">Apply</button>
                        </div>
                        
                        <?php if ($coupon_discount > 0): ?>
                        <div class="order-total">
                            <span>Discount</span>
                            <span>-$<?php echo number_format($coupon_discount, 2); ?></span>
                        </div>
                        
                        <div class="order-total">
                            <span>Final Total</span>
                            <span>$<?php echo number_format($final_price, 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="checkout-content">
                        <h2 class="section-title">Payment Method</h2>
                        
                        <div class="payment-method selected">
                            <input type="radio" name="payment_method" value="paypal" checked>
                            <span>PayPal</span>
                            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal">
                        </div>
                        
                        
                    </div>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="submit" name="proceed_to_payment" class="btn">Proceed to Payment</button>
            </div>
        </form>
        
        <?php elseif (isset($_GET['step']) && $_GET['step'] === 'payment'): ?>
        <!-- Payment Step -->
        <div class="checkout-content">
            <h2 class="section-title">Payment</h2>
            
            <?php
            // Debug information
            if (!isset($_SESSION['current_order_id'])) {
                echo '<div class="alert alert-danger">Error: Order ID not found. Please try again.</div>';
                echo '<div style="text-align: center; margin-top: 20px;">';
                echo '<a href="checkout.php" class="btn btn-outline">Back to Order Details</a>';
                echo '</div>';
                exit();
            }
            ?>
            
            <div id="paypal-button-container"></div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="checkout.php" class="btn btn-outline">Back to Order Details</a>
            </div>
        </div>
        
        <!-- PayPal SDK -->
        <script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>
        
        <script>
            // Debug information
            console.log("PayPal script loaded");
            console.log("Order ID: <?php echo isset($_SESSION['current_order_id']) ? $_SESSION['current_order_id'] : 'Not set'; ?>");
            console.log("Final price: <?php echo isset($final_price) ? $final_price : 'Not set'; ?>");
            
            // Add visual feedback in case PayPal doesn't load
            window.onload = function() {
                if (document.getElementById('paypal-button-container').children.length === 0) {
                    document.getElementById('paypal-button-container').innerHTML = 
                        '<div style="padding: 20px; border: 1px solid #ddd; text-align: center; margin-bottom: 20px;">' +
                        '<p>PayPal payment processing is temporarily unavailable.</p>' +
                        '<button type="button" id="simulate-payment" class="btn" style="margin-top: 15px;">Simulate Successful Payment</button>' +
                        '</div>';
                    
                    document.getElementById('simulate-payment').addEventListener('click', function() {
                        // Simulate a successful payment
                        window.location.href = 'process_payment.php?order_id=<?php echo isset($_SESSION['current_order_id']) ? $_SESSION['current_order_id'] : '0'; ?>&simulated=true';
                    });
                }
            };
            
            // Render PayPal button
            paypal.Buttons({
                // Style the button
                style: {
                    layout: 'vertical',
                    color:  'blue',
                    shape:  'rect',
                    label:  'pay'
                },
                
                createOrder: function(data, actions) {
                    console.log("Creating PayPal order");
                    // This value needs to be passed from PHP to JS
                    var finalPrice = <?php echo isset($final_price) ? $final_price : '0'; ?>;
                    console.log("Amount: " + finalPrice);
                    
                    return actions.order.create({
                        purchase_units: [{
                            description: 'Your E-commerce Order',
                            amount: {
                                currency_code: 'USD',
                                value: finalPrice
                            }
                        }]
                    });
                },
                
                onApprove: function(data, actions) {
                    console.log("Payment approved, capturing funds");
                    
                    return actions.order.capture().then(function(details) {
                        console.log("Payment completed successfully!", details);
                        
                        // Redirect approach instead of form submission
                        window.location.href = 'process_payment.php' +
                            '?order_id=<?php echo isset($_SESSION['current_order_id']) ? $_SESSION['current_order_id'] : '0'; ?>' +
                            '&payment_id=' + details.id +
                            '&payment_amount=<?php echo isset($final_price) ? $final_price : '0'; ?>';
                    });
                },
                
                onError: function(err) {
                    console.error("PayPal error occurred:", err);
                    alert("There was an error processing your payment. Please try again or contact support.");
                }
            }).render('#paypal-button-container');
        </script>
        
        <?php elseif (isset($_GET['step']) && $_GET['step'] === 'confirmation'): ?>
        <!-- Confirmation Step -->
        <div class="checkout-content" style="text-align: center;">
            <h2>Thank you for your order!</h2>
            <p>Your order has been placed successfully.</p>
            <?php if (isset($_SESSION['current_order_id'])): ?>
                <p>Order ID: #<?php echo htmlspecialchars($_SESSION['current_order_id']); ?></p>
                
                <div style="margin: 30px 0;">
                    <a href="invoice.php?order_id=<?php echo htmlspecialchars($_SESSION['current_order_id']); ?>" class="btn" target="_blank">View Invoice</a>
                    <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            <?php else: ?>
                <p>There was an issue retrieving your order details. Please contact customer support.</p>
                <div style="margin: 30px 0;">
                    <a href="/E-commerce/frontend/Includes/pages/homepage.php" class="btn btn-outline">Return to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <?php include '../../Includes/footer.php'; ?>
    </footer>
</body>
</html>