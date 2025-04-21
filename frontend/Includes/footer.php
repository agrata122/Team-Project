<?php

?>
<!-- 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../assets/CSS/Footer.css">

</head>
<body>
<footer>
    <div class="footer-container">
        <div class="footer-column">
            <h3>Categories</h3>
            <ul>
                <li><a href="#">Butcher</a></li>
                <li><a href="#">Fish Monger</a></li>
                <li><a href="#">Greengrocer</a></li>
                <li><a href="#">Bakery</a></li>
                <li><a href="#">Delicatessen</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>About Us</h3>
            <ul>
                <li><a href="#">About Company</a></li>
                <li><a href="#">Terms and Conditions</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>My Account</h3>
            <ul>
                <li><a href="#">My Account</a></li>
                <li><a href="#">My Cart</a></li>
                <li><a href="#">Order History</a></li>
                <li><a href="#">My Wish List</a></li>
                <li><a href="#">My Address</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Privacy & Terms</h3>
            <ul>
                <li><a href="#">Payment Policy</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Return Policy</a></li>
                <li><a href="#">Shipping Policy</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>FRESGRUB<br>All rights reserved <span>&copy;</span></p>
        <div class="social-icons">
            <a href="#"><img src="facebook-icon.png" alt="Facebook"></a>
            <a href="#"><img src="instagram-icon.png" alt="Instagram"></a>
            <a href="#"><img src="other-icon.png" alt="Other"></a>
        </div>
    </div>
</footer>

</body>
</html> -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer</title>
    <style>
        /* Basic Styling for Footer */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
        }

        footer {
            background-color: #E7EDEB; /* Soft grey background for sections */
            color: #37474F; /* Dark grey text */
            padding: 30px 0;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-column {
            flex: 1;
            min-width: 220px;
            margin: 20px;
        }

        .footer-column h3 {
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 12px;
        }

        .footer-column ul li a {
            color: #37474F;
            text-decoration: none; /* No underline */
            transition: color 0.3s ease;
        }

        .footer-column ul li a:hover {
            color: #607D8B; /* Softer grey for hover effect */
        }

        /* Section Below Without a Box */
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            background-color: transparent; /* No background here */
        }

        .footer-bottom-left {
            text-align: left;
        }

        .footer-bottom-left p {
            font-size: 14px;
            color: #37474F;
            margin: 5px 0;
        }

        .social-icons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .social-icons a img {
            width: 28px;
            height: 28px;
            transition: transform 0.3s ease, filter 0.3s ease;
            filter: brightness(0.8); /* Dim icons slightly */
        }

        .social-icons a img:hover {
            transform: scale(1.1);
            filter: brightness(1); /* Brighten icons on hover */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .footer-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-column {
                margin: 20px 0;
            }

            .footer-bottom {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .social-icons {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .footer-column h3 {
                font-size: 16px;
            }

            .footer-column ul li {
                font-size: 14px;
            }

            .footer-bottom-left p {
                font-size: 14px;
            }

            .social-icons a img {
                width: 20px;
                height: 20px;
            }
        }
    </style>
</head>
<body>
<footer>
    <!-- Structured Content with Soft Grey Background -->
    <div class="footer-container">
        <div class="footer-column">
            <h3>Categories</h3>
            <ul>
                <li><a href="#">Butcher</a></li>
                <li><a href="#">Fish Monger</a></li>
                <li><a href="#">Greengrocer</a></li>
                <li><a href="#">Bakery</a></li>
                <li><a href="#">Delicatessen</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>About Us</h3>
            <ul>
                <li><a href="#">About Company</a></li>
                <li><a href="#">Terms and Conditions</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>My Account</h3>
            <ul>
                <li><a href="#">My Account</a></li>
                <li><a href="#">My Cart</a></li>
                <li><a href="#">Order History</a></li>
                <li><a href="#">My Wish List</a></li>
                <li><a href="#">My Address</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Privacy & Terms</h3>
            <ul>
                <li><a href="#">Payment Policy</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Return Policy</a></li>
                <li><a href="#">Shipping Policy</a></li>
            </ul>
        </div>
    </div>
    <!-- FresGrub and Social Icons Section Below -->
    <div class="footer-bottom">
        <!-- Left Side: FresGrub and All Rights Reserved -->
        <div class="footer-bottom-left">
            <p>FresGrub</p>
            <p>All rights reserved <span>&copy;</span> 2025</p>
        </div>
        <!-- Right Side: Social Media Icons -->
        <div class="social-icons">
            <a href="#"><img src="facebook-icon.png" alt="Facebook"></a>
            <a href="#"><img src="instagram-icon.png" alt="Instagram"></a>
            <a href="#"><img src="twitter-icon.png" alt="Twitter"></a>
        </div>
    </div>
</footer>
</body>
</html>

