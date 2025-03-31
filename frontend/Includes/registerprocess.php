<?php
session_start();
require_once "../../backend/db_connection.php";

$db = getDBConnection();

if (!$db) {
    die("Database connection failed.");
}

// Step 1: Check if OTP was verified
if (isset($_POST['otp_verified']) && $_POST['otp_verified'] === "true" && isset($_SESSION['reg_data'])) {
    $data = $_SESSION['reg_data'];

    try {
        $stmt = $db->prepare("INSERT INTO users 
            (full_name, email, contact_no, password, role, status, category, shop_name)
            VALUES 
            (?, ?, ?, ?, ?, 'active', ?, ?)");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['contact_no'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'],
            $data['category'] ?? null,
            $data['shop_name'] ?? null
        ]);

        // Step 2: Clear session and redirect to login
        unset($_SESSION['reg_data']);
        unset($_SESSION['otp_verified']);

        header("Location: pages/login.php");
        exit();
    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
} 

// Step 3: Initial form submission from signup.php
else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['reg_data'] = [
        'full_name' => $_POST['fullname'],
        'email' => $_POST['email'],
        'contact_no' => $_POST['phone'],
        'password' => $_POST['password'],
        'role' => $_POST['user-type'] ?? 'customer',
        'category' => $_POST['category'] ?? null,
        'shop_name' => $_POST['shop_name'] ?? null
    ];

    // Step 4: Send OTP
    $_SESSION['user_email'] = $_POST['email'];
    header("Location: verify_otp.php?email=" . urlencode($_POST['email']));
    exit();
} else {
    die("Invalid request");
}
?>
