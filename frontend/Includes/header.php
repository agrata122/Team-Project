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
    <link rel="stylesheet" href="assets/CSS/header.css">
    
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
            <a href="\E-commerce\frontend\Includes\pages\homepage.php">
                <img src="\E-commerce\frontend\assets\Images\logo.png" alt="FresGrub Logo">
            </a>
        </div>

        <!-- Search Bar -->
        <!-- Search Bar -->
<form class="search-bar" action="/E-commerce/frontend/Includes/search_results.php" method="get">
    <input type="text" name="query" placeholder="Search for products or category">
    <button type="submit">Search</button>
</form>

        <!-- User Actions -->
        <div class="user-actions">
            <?php if ($isLoggedIn): ?>
                <!-- User Profile -->
                <div class="user-profile">
                    <a href="\E-commerce\frontend\user\user_profile.php">
                        <i class="fas fa-user-circle fa-2x"></i> <!-- User icon -->
                    </a>
                </div>

                <!-- Wishlist -->
                <div class="wishlist">
                    <a href="\E-commerce\frontend\Includes\pages\wishlist.php">
                        <i class="fas fa-heart"></i>
                        <span class="wishlist-count">0</span> <!-- You can add dynamic count here if needed -->
                    </a>
                </div>
                
                <a href="\E-commerce\frontend\Includes\logout.php">Logout</a>
            <?php else: ?>
                <!-- Authentication Buttons -->
                <button class="login-btn" onclick="location.href='signup.php'">Sign Up</button>
                <button class="signup-btn" onclick="location.href='login.php'">Log in</button>
            <?php endif; ?>
        </div>

        <!-- Shopping Cart -->
        <div class="cart">
            <a href="\E-commerce\frontend\Includes\cart\shopping_cart.php">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>
            <div class="cart-total">
                $<?php echo number_format($cartTotal, 2); ?>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="navigation">
        <a href="\E-commerce\frontend\Includes\pages\homepage.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>">Home</a>
        <a href="shop.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/shop.php') ? 'active' : ''; ?>">Shop</a>
        <a href="aboutUs.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/aboutUs.php') ? 'active' : ''; ?>">About us</a>
        <a href="contactUs.php" class="nav-item <?php echo ($_SERVER['PHP_SELF'] == '/contactUs.php') ? 'active' : ''; ?>">Contact us</a>

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

        // Add this after your existing script in header.php
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-bar input[name="query"]');
    const searchForm = document.querySelector('.search-bar');
    
    // Create suggestion dropdown
    const suggestionDropdown = document.createElement('div');
    suggestionDropdown.className = 'search-suggestions-dropdown';
    suggestionDropdown.style.display = 'none';
    suggestionDropdown.style.position = 'absolute';
    suggestionDropdown.style.backgroundColor = 'white';
    suggestionDropdown.style.border = '1px solid #ddd';
    suggestionDropdown.style.width = searchInput.offsetWidth + 'px';
    suggestionDropdown.style.maxHeight = '200px';
    suggestionDropdown.style.overflowY = 'auto';
    suggestionDropdown.style.zIndex = '1000';
    
    searchForm.appendChild(suggestionDropdown);
    
    // Sample categories for suggestions
    const categories = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];
    const popularProducts = ['Bacon', 'Salmon', 'Bread', 'Apples', 'Cheese Cake'];
    
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        
        if (query.length < 2) {
            suggestionDropdown.style.display = 'none';
            return;
        }
        
        // Filter suggestions
        const categoryMatches = categories.filter(cat => cat.includes(query));
        const productMatches = popularProducts.filter(prod => prod.toLowerCase().includes(query));
        
        // Create suggestion HTML
        let html = '';
        
        if (categoryMatches.length > 0) {
            html += '<div class="suggestion-header">Categories</div>';
            categoryMatches.forEach(cat => {
                html += `<div class="suggestion-item" onclick="selectSuggestion('${cat}')">${cat}</div>`;
            });
        }
        
        if (productMatches.length > 0) {
            html += '<div class="suggestion-header">Popular Products</div>';
            productMatches.forEach(prod => {
                html += `<div class="suggestion-item" onclick="selectSuggestion('${prod}')">${prod}</div>`;
            });
        }
        
        if (html) {
            suggestionDropdown.innerHTML = html;
            suggestionDropdown.style.display = 'block';
        } else {
            suggestionDropdown.style.display = 'none';
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            suggestionDropdown.style.display = 'none';
        }
    });
    
    // Function to select a suggestion
    window.selectSuggestion = function(value) {
        searchInput.value = value;
        suggestionDropdown.style.display = 'none';
        searchForm.submit();
    };
    
    // Add styles for suggestions
    const style = document.createElement('style');
    style.textContent = `
        .search-suggestions-dropdown {
            font-family: Arial, sans-serif;
        }
        .suggestion-header {
            padding: 8px 12px;
            font-weight: bold;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
        }
        .suggestion-item:hover {
            background-color: #f0f0f0;
        }
    `;
    document.head.appendChild(style);
});
    </script>
</body>
</html>