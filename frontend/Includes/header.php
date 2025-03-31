<?php
// Get the current banner message from your database or configuration
$bannerMessages = [
    "Get 10% off in all Bakery items. Hurry up! don't miss this! Get free Delivery for all greengrocer products.",
    "Free shipping on orders over $50! Limited time offer.",
    "New customers get 15% off their first order with code WELCOME15."
];


$currentBannerIndex = isset($_SESSION['banner_index']) ? $_SESSION['banner_index'] : 0;
$currentBanner = $bannerMessages[$currentBannerIndex];

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Get cart items count and total
$cartCount = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
$cartTotal = isset($_SESSION['cart_total']) ? $_SESSION['cart_total'] : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FresGrub</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Basic styling for the header */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        .banner {
            background-color: #5C9D5D;
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            border-bottom: 1px solid #eee;
        }
        
        .logo img {
            height: 50px;
            margin-left: 110px;
        }
        
        .search-bar {
            display: flex;
            max-width: 600px;
            width: 100%;
        }
        
        .search-bar input {
            flex-grow: 1;
            padding: 8px 15px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
        }
        
        .search-bar button {
            background-color: #00C12B;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-profile img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notifications {
            position: relative;
        }
        
        .cart {
            position: relative;
            margin-right: 70px;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #00C12B;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .cart-total {
            font-size: 12px;
            color: #333;
        }
        
        .navigation {
            display: flex;
            padding: 20px 50px;
            border-bottom: 1px solid #eee;
            margin-left: 110px;
            gap: 50px;
        }
        
        .nav-item {
            margin-right: 30px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            position: relative;
        }
        
        .nav-item.active::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #00C12B;
        }
        
        .nav-item:hover {
            color: #00C12B;
        }
        
        .auth-buttons {
            display: flex;
            gap: 10px;
            
        }
        
        .login-btn, .signup-btn {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }
        
        .login-btn {
            border: 1px solid #00C12B;
            color: #00C12B;
            background-color: transparent;
        }
        
        .signup-btn {
            background-color: #00C12B;
            color: white;
            border: none;
        }
        
        .contact-info {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 70px;
        }
        
        .phone {
            font-weight: 500;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Top Banner -->
    <div class="banner">
        <div class="banner-content">
            <p id="banner-text"><?php echo $currentBanner; ?></p>
        </div>
    </div>
    
    <!-- Main Header -->
    <div class="main-header">
        <!-- Logo -->
        <div class="logo">
            <a href="index.php">
                <img src="../../assets/images/logo.png" alt="FresGrub Logo">
            </a>
        </div>
        
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" placeholder="Search for products or category">
            <button type="submit">Search</button>
        </div>
        
        <!-- User Actions -->
        <div class="user-actions">
            <?php if ($isLoggedIn): ?>
                <!-- User Profile -->
                <div class="user-profile">
                    <img src="assets/images/profile-placeholder.jpg" alt="User Profile">
                </div>
                
                <!-- Notifications -->
                <div class="notifications">
                    <a href="notifications.php">
                        <i class="fa-regular fa-bell"></i>
                    </a>
                </div>
            <?php else: ?>
                <!-- Authentication Buttons -->
                <div class="auth-buttons">
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="signup.php" class="signup-btn">Sign Up</a>
                </div>
            <?php endif; ?>
            
            <!-- Shopping Cart -->
            <div class="cart">
                <a href="cart.php">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
                <div class="cart-total">
                    $<?php echo number_format($cartTotal, 2); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <div class="navigation">
        <a href="index.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>">Home</a>
        <a href="shop.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/shop.php') ? 'active' : ''; ?>">Shop</a>
        <a href="about.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/about.php') ? 'active' : ''; ?>">About us</a>
        <a href="contact.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/contact.php') ? 'active' : ''; ?>">Contact us</a>
        
        <!-- Contact Info -->
        <div class="contact-info">
            <a href="tel:(977) 97XXXXXXX" class="phone">
                <i class="fa-solid fa-phone"></i>
                (977) 9842584634
            </a>
        </div>
    </div>

    <!-- Banner Message Animation Script -->
    <script>
        // Simple banner message rotation script
        const bannerMessages = <?php echo json_encode($bannerMessages); ?>;
        let currentIndex = 0;
        const bannerText = document.getElementById('banner-text');
        
        function rotateBannerMessage() {
            currentIndex = (currentIndex + 1) % bannerMessages.length;
            
            // Fade out
            bannerText.style.opacity = 0;
            
            setTimeout(() => {
                // Change text
                bannerText.textContent = bannerMessages[currentIndex];
                
                // Fade in
                bannerText.style.opacity = 1;
            }, 500);
        }
        
        // Add CSS transition for smooth fade effect
        bannerText.style.transition = 'opacity 0.5s ease';
        
        // Rotate banner message every 5 seconds
        setInterval(rotateBannerMessage, 5000);
    </script>
</body>
</html>