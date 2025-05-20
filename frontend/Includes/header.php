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
    gap: 25px; /* Change this from 15px to 10px or even 8px */
}   

        .user-profile img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .wishlist {
            position: relative;
        }

        .wishlist-count {
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

        .cart {
            position: relative;
            margin-right: 20px;
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

        .login-btn,
        .signup-btn {
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

        /* RESPONSIVENESS: Header and navigation for smaller screens */
@media (max-width: 1024px) {
    .main-header {
        flex-wrap: wrap;
        padding: 15px 20px;
    }

    .logo img {
        margin-left: 0;
    }

    .search-bar {
        order: 3;
        width: 100%;
        margin-top: 10px;
    }

    .user-actions,
    .cart {
        margin-right: 0;
    }

    .cart {
        margin-left: auto;
    }

    .navigation {
        flex-wrap: wrap;
        gap: 20px;
        padding: 15px 20px;
        margin-left: 0;
    }

    .nav-item {
        margin-right: 15px;
    }

    .contact-info {
        margin-left: 0;
        margin-top: 10px;
        justify-content: center;
        width: 100%;
    }

    .search-bar {
    position: relative;
    max-width: 600px;
    width: 100%;
}

.search-bar input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.search-bar button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    padding: 0 15px;
    background-color: #00C12B;
    color: white;
    border: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
}

.search-bar button:hover {
    background-color: #009922;
}
}

@media (max-width: 768px) {
    .main-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-bar {
        margin-top: 10px;
    }

    .user-actions {
        gap: 10px;
    }

    .navigation {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .contact-info {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .search-bar input {
        font-size: 14px;
    }

    .banner {
        font-size: 12px;
        padding: 8px;
    }

    .cart-total {
        display: none;
    }

    .nav-item {
        font-size: 14px;
    }

    .login-btn,
    .signup-btn {
        padding: 6px 10px;
        font-size: 14px;
    }
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