<?php
session_start();
session_destroy();
header("Location:/E-commerce/frontend/Includes/pages/login.php"); // Redirect to homepage.php after logout
exit();