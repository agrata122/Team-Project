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
    
    // Set status based on user role
    $status = ($data['role'] === 'trader') ? 'pending' : 'active';

    try {
        $stmt = $db->prepare("INSERT INTO users 
            (full_name, email, contact_no, password, role, status, category, first_shop_name, second_shop_name)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['contact_no'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'],
            $status, // ✅ Traders = 'pending', Customers = 'active'
            $data['category'] ?? null,
            $data['first_shop_name'] ?? null,
            $data['second_shop_name'] ?? null
        ]);

        // Clear session
        unset($_SESSION['reg_data']);
        unset($_SESSION['otp_verified']);

        // Redirect based on status
        if ($status === 'pending') {
            header("Location: pages/login.php?message=Registration pending admin approval");
        } else {
            header("Location: pages/login.php?message=Registration successful! Please log in.");
        }
        exit();
    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
}

// Step 2: Handle first form submission from signup.php
else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['reg_data'] = [
        'full_name' => $_POST['fullname'],
        'email' => $_POST['email'],
        'contact_no' => $_POST['phone'],
        'password' => $_POST['password'],
        'role' => $_POST['user-type'] ?? 'customer',
        'category' => $_POST['category'] ?? null,
        'first_shop_name' => $_POST['first_shop_name'] ?? null,
        'second_shop_name' => $_POST['second_shop_name'] ?? null
    ];

    // Step 3: Send OTP
    $_SESSION['user_email'] = $_POST['email'];
    header("Location: verify_otp.php?email=" . urlencode($_POST['email']));
    exit();
} else {
    die("Invalid request");
}
?>
