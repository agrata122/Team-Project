<?php
session_start();
require_once "../../backend/connect.php";

// Check admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /E-commerce/frontend/Includes/pages/login.php");
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - FresGrub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
        }
        
        .sidebar {
            width: 250px;
            background-color: rgb(38, 94, 50);
            height: 100vh;
            color: white;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 10px;
            color: #2ecc71;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            padding: 0;
        }
        
        .sidebar-menu a {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgb(16, 56, 33);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-title {
            margin-top: 0;
            color: rgb(50, 95, 49);
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: rgb(50, 95, 49);
            margin: 10px 0;
        }
        
        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .chart-title {
            margin-top: 0;
            color: rgb(50, 95, 49);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span>FresGrub</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="admindashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="Admin_daily_reports.php">
                    <i class="fas fa-calendar-day"></i>
                    <span>Daily Reports</span>
                </a>
            </li>
            <li>
                <a href="Admin_weekly_reports.php">
                    <i class="fas fa-calendar-week"></i>
                    <span>Weekly Reports</span>
                </a>
            </li>
            <li>
                <a href="Admin_monthly_reports.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Monthly Reports</span>
                </a>
            </li>
            <li>
                <a href="product_sales_reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Product Sales Reports</span>
                </a>
            </li>
            <li>
                <a href="logout_admin.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Admin Reports</h2>
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="Admin">
                <span>Welcome, Admin</span>
            </div>
        </div> 