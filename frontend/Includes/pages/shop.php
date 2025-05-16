<?php 
  session_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
   
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Local Market</title>
    <style>
        /* Main Styles */
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --dark-green: #1b5e20;
            --text-dark: #333;
            --text-light: #666;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--white);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            color: var(--primary-green);
            margin-bottom: 40px;
            font-size: 2.2em;
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        h2 {
            color: var(--primary-green);
            font-weight: 400;
            margin: 50px 0 20px 0;
            font-size: 1.5em;
            position: relative;
            padding-bottom: 10px;
        }
        
        h2:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-green);
        }
        
        /* Shop Categories */
        .shops-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        /* Individual Shop Cards */
        .shop-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: var(--white);
        }
        
        .shop-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.1);
        }
        
        .shop-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            background-color: #f5f5f5;
        }
        
        .shop-info {
            padding: 20px;
        }
        
        .shop-name {
            font-size: 1.2em;
            margin: 0 0 8px 0;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .shop-description {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .shop-button {
            display: inline-block;
            background: transparent;
            color: var(--primary-green);
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--primary-green);
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        
        .shop-button:hover {
            background: var(--primary-green);
            color: var(--white);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .shops-container {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <header>
    <?php
include '../../Includes/header.php'; 
?>
    <div class="container">
        <h1>Browse Our Shops</h1>
        
        <!-- Butcher Shops -->
        <div>
            <h2>Butcher</h2>
            <div class="shops-container">
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1603360946369-dc9bb6258143?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Prime Cuts Butchery</h3>
                        <p class="shop-description">Premium quality meats and custom cuts with expert advice on preparation.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
                
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1558030006-450675393462?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Heritage Meats</h3>
                        <p class="shop-description">Organic, grass-fed beef and free-range poultry from local farms.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fishmonger Shops -->
        <div>
            <h2>Fishmonger</h2>
            <div class="shops-container">
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Ocean's Bounty</h3>
                        <p class="shop-description">Daily fresh seafood, sustainably sourced from local waters.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
                
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">The Fisherman's Wharf</h3>
                        <p class="shop-description">Exotic seafood and shellfish with same-day delivery from the coast.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Greengrocer Shops -->
        <div>
            <h2>Greengrocer</h2>
            <div class="shops-container">
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1518843875459-f738682238a6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Green Valley Produce</h3>
                        <p class="shop-description">Locally grown seasonal fruits and vegetables, organic options available.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
                
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Farm Fresh Market</h3>
                        <p class="shop-description">Direct from local farms, picked at peak ripeness for maximum flavor.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bakery Shops -->
        <div>
            <h2>Bakery</h2>
            <div class="shops-container">
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Golden Crust Bakery</h3>
                        <p class="shop-description">Artisan breads and pastries made with traditional methods and local ingredients.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
                
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Sweet Flour Patisserie</h3>
                        <p class="shop-description">French-inspired pastries and custom cakes for special occasions.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delicatessen Shops -->
        <div>
            <h2>Delicatessen</h2>
            <div class="shops-container">
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">Gourmet Delights</h3>
                        <p class="shop-description">Premium cheeses, cured meats, and imported delicacies from Europe.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
                
                <div class="shop-card">
                    <div class="shop-image" style="background-image: url('https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80');"></div>
                    <div class="shop-info">
                        <h3 class="shop-name">The Artisan Deli</h3>
                        <p class="shop-description">Handcrafted sandwiches and house-made specialty foods.</p>
                        <a href="#" class="shop-button">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<?php
include '../../Includes/footer.php';
?>
</html>

