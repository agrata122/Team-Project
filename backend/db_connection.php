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

function create_unique_id() {
    return bin2hex(random_bytes(16)); // Generates a 32-character hex string
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
            status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'
        )");

        // echo "Created users table successfully.<br>";

        $db->exec("CREATE TABLE IF NOT EXISTS shops (
            shop_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shop_category ENUM('butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen') NOT NULL,
            shop_name VARCHAR(255) NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS product (
            product_id INTEGER AUTO_INCREMENT,
            product_name VARCHAR(500),
            description LONGTEXT,
            price INTEGER NOT NULL,
            stock INTEGER NOT NULL,
            min_order INTEGER NOT NULL,
            max_order INTEGER NOT NULL,
            product_image VARCHAR(2000) NOT NULL,
            add_date DATE,
            product_status VARCHAR(30),
            shop_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            product_categroy_id INTEGER NOT NULL,
            PRIMARY KEY (product_id)
        )");
        $db->exec("ALTER TABLE product MODIFY product_name VARCHAR(500)");

        //echo "Created product table successfully.<br>";

        $db->exec("CREATE TABLE IF NOT EXISTS cart (
            cart_id INT AUTO_INCREMENT,
            user_id INTEGER NOT NULL,
            shopping_list_id INTEGER NOT NULL,
            item_numbers INTEGER,
            add_date DATE,
            PRIMARY KEY (cart_id)
        )");
        //echo "Created cart table successfully.<br>";
        $db->exec("ALTER TABLE cart MODIFY user_id VARCHAR(50) NOT NULL;
 ");

        $db->exec("CREATE TABLE IF NOT EXISTS product_cart (
            cart_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL
        )");
        //echo "Created product_cart table successfully.<br>";


        // Create the orders table
$db->exec("CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE
)");






        // echo "Database setup completed successfully.<br>";

        // Create a check statement to verify if user already exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");

        // Prepare to insert new users
        $insertStmt = $db->prepare("INSERT INTO users (full_name, email, contact_no, password, role, status) 
                                    VALUES (:full_name, :email, :contact_no, :password, :role, 'active')");

        // Test users to insert (you can later modify this based on your form data)
        $users = [
            ['full_name' => 'Admin User', 'email' => 'admin@example.com', 'contact_no' => '1234567890', 'password' => 'admin123', 'role' => 'admin'],
            ['full_name' => 'Trader User', 'email' => 'trader@example.com', 'contact_no' => '0987654321', 'password' => 'trader123', 'role' => 'trader'],
            ['full_name' => 'Customer User', 'email' => 'customer@example.com', 'contact_no' => '1122334455', 'password' => 'customer123', 'role' => 'customer'],
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
                    
                ]);
                echo "Inserted user: {$user['full_name']}<br>";
             } 
            //else {
            //     echo "User {$user['email']} already exists. Skipping...<br>";
            // }
        }

        // echo "<br>Database setup completed successfully!<br>";

    } catch (PDOException $e) {
        echo "Error setting up database: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Failed to connect to database.<br>";
}
?>
