<?php
session_start();
require_once "../../backend/connect.php";

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.");
}

// Step 1: Checking if OTP was verified
if (isset($_POST['otp_verified']) && $_POST['otp_verified'] === "true" && isset($_SESSION['reg_data'])) {
    $data = $_SESSION['reg_data'];
    
    // Setting status based on user role
    $status = ($data['role'] === 'trader') ? 'pending' : 'active';

    try {
        // Begin transaction
        oci_execute(oci_parse($conn, "BEGIN"));
        
        // If user is a trader, check if category is available
        if ($data['role'] === 'trader') {
            $category_check = oci_parse($conn, 
                "SELECT COUNT(*) as category_count 
                 FROM shops 
                 WHERE shop_category = :shop_category");
            
            oci_bind_by_name($category_check, ":shop_category", $data['category']);
            oci_execute($category_check);
            
            $row = oci_fetch_assoc($category_check);
            if ($row['CATEGORY_COUNT'] > 0) {
                throw new Exception("This category is already taken by another trader. Please choose a different category.");
            }
        }
        
        // Prepare user insert statement
        $user_insert = oci_parse($conn, 
            "INSERT INTO users 
            (full_name, email, contact_no, password, role, status)
            VALUES 
            (:full_name, :email, :contact_no, :password, :role, :status)
            RETURNING user_id INTO :user_id");
        
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user_id = null;
        
        oci_bind_by_name($user_insert, ":full_name", $data['full_name']);
        oci_bind_by_name($user_insert, ":email", $data['email']);
        oci_bind_by_name($user_insert, ":contact_no", $data['contact_no']);
        oci_bind_by_name($user_insert, ":password", $hashed_password);
        oci_bind_by_name($user_insert, ":role", $data['role']);
        oci_bind_by_name($user_insert, ":status", $status);
        oci_bind_by_name($user_insert, ":user_id", $user_id, -1, SQLT_INT);
        
        $user_result = oci_execute($user_insert, OCI_NO_AUTO_COMMIT);
        
        if (!$user_result) {
            $e = oci_error($user_insert);
            throw new Exception("User insert failed: " . $e['message']);
        }
        
        // If user is a trader, insert their shop information
        if ($data['role'] === 'trader') {
            // Prepare shop insert statement
            $shop_insert = oci_parse($conn, 
                "INSERT INTO shops 
                (user_id, shop_category, shop_name)
                VALUES 
                (:user_id, :shop_category, :shop_name)");
            
            oci_bind_by_name($shop_insert, ":user_id", $user_id);
            oci_bind_by_name($shop_insert, ":shop_category", $data['category']);
            
            // Insert first shop
            oci_bind_by_name($shop_insert, ":shop_name", $data['first_shop_name']);
            $shop_result = oci_execute($shop_insert, OCI_NO_AUTO_COMMIT);
            
            if (!$shop_result) {
                $e = oci_error($shop_insert);
                throw new Exception("First shop insert failed: " . $e['message']);
            }
            
            // Insert second shop
            oci_bind_by_name($shop_insert, ":shop_name", $data['second_shop_name']);
            $shop_result = oci_execute($shop_insert, OCI_NO_AUTO_COMMIT);
            
            if (!$shop_result) {
                $e = oci_error($shop_insert);
                throw new Exception("Second shop insert failed: " . $e['message']);
            }
        }

        // Commit transaction
        oci_execute(oci_parse($conn, "COMMIT"));

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
    } catch (Exception $e) {
        // Roll back transaction if error occurs
        oci_execute(oci_parse($conn, "ROLLBACK"));
        header("Location: pages/signup.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Step 2: Handle first form submission from signup.php
else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If user is a trader, check category availability immediately
    if (($_POST['user-type'] ?? 'customer') === 'trader') {
        $category = $_POST['category'] ?? null;
        if ($category) {
            $category_check = oci_parse($conn, 
                "SELECT COUNT(*) as category_count 
                 FROM shops 
                 WHERE shop_category = :shop_category");
            
            oci_bind_by_name($category_check, ":shop_category", $category);
            oci_execute($category_check);
            
            $row = oci_fetch_assoc($category_check);
            if ($row['CATEGORY_COUNT'] > 0) {
                header("Location: pages/signup.php?error=" . urlencode("This category is already taken by another trader. Please choose a different category."));
                exit();
            }
        }
    }
    
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