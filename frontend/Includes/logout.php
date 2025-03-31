<?php
session_start();
session_destroy();
header("Location: pages/homepage.php"); // Redirect to homepage.php after logout
exit();
