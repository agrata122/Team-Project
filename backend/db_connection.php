<?php
function getDBConnection() {
    $host = "localhost";
    $db_name = "FresGrub";
    $username = "root";
    $password = ""; 

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        echo "Connection Error: " . $e->getMessage();
        return null;
    }
}

$db = getDBConnection();

if ($db) {
    try {
        // Create the users table with trader-specific fields as NULL for customers
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            contact_no VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            verification_code VARCHAR(100) DEFAULT NULL,
            role ENUM('customer', 'trader', 'admin') NOT NULL DEFAULT 'customer',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
            category ENUM('butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen') DEFAULT NULL,
            shop_name VARCHAR(255) DEFAULT NULL
        )");

        echo "Created users table successfully.<br>";

        // Create a check statement to verify if user already exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");

        // Prepare to insert new users
        $insertStmt = $db->prepare("INSERT INTO users (full_name, email, contact_no, password, role, status, category, shop_name) 
                                    VALUES (:full_name, :email, :contact_no, :password, :role, 'active', :category, :shop_name)");

        // Test users to insert (you can later modify this based on your form data)
        $users = [
            ['full_name' => 'Admin User', 'email' => 'admin@example.com', 'contact_no' => '1234567890', 'password' => 'admin123', 'role' => 'admin', 'category' => NULL, 'shop_name' => NULL],
            ['full_name' => 'Trader User', 'email' => 'trader@example.com', 'contact_no' => '0987654321', 'password' => 'trader123', 'role' => 'trader', 'category' => 'butcher', 'shop_name' => 'Meat Heaven'],
            ['full_name' => 'Customer User', 'email' => 'customer@example.com', 'contact_no' => '1122334455', 'password' => 'customer123', 'role' => 'customer', 'category' => NULL, 'shop_name' => NULL],
        ];

        foreach ($users as $user) {
            $checkStmt->execute(['email' => $user['email']]);
            if ($checkStmt->fetchColumn() == 0) {
                $insertStmt->execute([
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'contact_no' => $user['contact_no'],
                    'password' => password_hash($user['password'], PASSWORD_BCRYPT),
                    'role' => $user['role'],
                    'category' => $user['category'],
                    'shop_name' => $user['shop_name']
                ]);
                echo "Inserted user: {$user['full_name']}<br>";
            } else {
                echo "User {$user['email']} already exists. Skipping...<br>";
            }
        }

        echo "<br>Database setup completed successfully!<br>";

    } catch (PDOException $e) {
        echo "Error setting up database: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Failed to connect to database.<br>";
}
?>
