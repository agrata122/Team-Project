<?php
include 'connect.php';
$conn = getDBConnection();

function executeQuery($conn, $sql)
{
    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "<p style='color:red;'>Error: {$e['message']}</p>";
    } else {
        echo "<p style='color:green;'>Successfully executed: $sql</p>";
    }
    oci_free_statement($stmt);
}

function safeDrop($conn, $sql)
{
    $stmt = oci_parse($conn, $sql);
    if(@oci_execute($stmt)) {
        echo "<p style='color:green;'>Successfully executed: $sql</p>";
    } else {
        $e = oci_error($stmt);
        if ($e['code'] != 942) { // ORA-00942: table or view does not exist
            echo "<p style='color:red;'>Error: {$e['message']}</p>";
        }
    } // Suppress errors if they don't exist
    oci_free_statement($stmt);
}

// Drop tables in reverse order of dependencies
safeDrop($conn, "DROP TRIGGER trg_users_pk");
safeDrop($conn, "DROP SEQUENCE user_seq");
safeDrop($conn, "DROP TABLE wishlist_product CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_wishlist_pk");
safeDrop($conn, "DROP SEQUENCE wishlist_seq");
safeDrop($conn, "DROP TABLE wishlist CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TABLE product_report CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_report_pk");
safeDrop($conn, "DROP SEQUENCE report_seq");
safeDrop($conn, "DROP TABLE report CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_discount_pk");
safeDrop($conn, "DROP SEQUENCE discount_seq");
safeDrop($conn, "DROP TABLE discount CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_payment_pk");
safeDrop($conn, "DROP SEQUENCE payment_seq");
safeDrop($conn, "DROP TABLE payment CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_orders_pk");
safeDrop($conn, "DROP SEQUENCE orders_seq");
safeDrop($conn, "DROP TABLE orders CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_collection_slot_pk");
safeDrop($conn, "DROP SEQUENCE collection_slot_seq");
safeDrop($conn, "DROP TABLE collection_slot CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_coupon_pk");
safeDrop($conn, "DROP SEQUENCE coupon_seq");
safeDrop($conn, "DROP TABLE coupon CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_review_pk");
safeDrop($conn, "DROP SEQUENCE review_seq");
safeDrop($conn, "DROP TABLE review CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TABLE product_cart CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_product_pk");
safeDrop($conn, "DROP SEQUENCE product_seq");
safeDrop($conn, "DROP TABLE product CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_shops_pk");
safeDrop($conn, "DROP SEQUENCE shop_seq");
safeDrop($conn, "DROP TABLE shops CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TRIGGER trg_cart_pk");
safeDrop($conn, "DROP SEQUENCE cart_seq");
safeDrop($conn, "DROP TABLE cart CASCADE CONSTRAINTS");
safeDrop($conn, "DROP TABLE users CASCADE CONSTRAINTS");

// Reorder the table creation sequence
if ($conn) {

    // USERS TABLE
    executeQuery($conn, "
        CREATE TABLE users (
            user_id NUMBER PRIMARY KEY,
            full_name VARCHAR2(150) NOT NULL,
            email VARCHAR2(100) UNIQUE NOT NULL,
            contact_no VARCHAR2(20) NOT NULL,
            password VARCHAR2(255) NOT NULL,
            verification_code VARCHAR2(100),
            role VARCHAR2(10) DEFAULT 'customer' CHECK (role IN ('customer', 'trader', 'admin')),
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR2(10) DEFAULT 'pending' CHECK (status IN ('active', 'inactive', 'pending'))
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE user_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_users_pk
        BEFORE INSERT ON users
        FOR EACH ROW
        BEGIN
            SELECT user_seq.NEXTVAL INTO :new.user_id FROM dual;
        END;");

    // CART TABLE - create before PRODUCT for proper referencing
    executeQuery($conn, "
        CREATE TABLE cart (
            cart_id NUMBER PRIMARY KEY,
            user_id NUMBER NOT NULL,
            add_date DATE,
            CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE cart_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_cart_pk
        BEFORE INSERT ON cart
        FOR EACH ROW
        BEGIN
            SELECT cart_seq.NEXTVAL INTO :new.cart_id FROM dual;
        END;
    ");

    

    

    // SHOPS TABLE
    executeQuery($conn, "
        CREATE TABLE shops (
            shop_id NUMBER PRIMARY KEY,
            user_id NUMBER NOT NULL,
            shop_category VARCHAR2(20) CHECK (shop_category IN ('butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen')),
            shop_name VARCHAR2(255) NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            description CLOB,
            shop_email VARCHAR2(100),
            CONSTRAINT fk_shop_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE shop_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_shops_pk
        BEFORE INSERT ON shops
        FOR EACH ROW
        BEGIN
            SELECT shop_seq.NEXTVAL INTO :new.shop_id FROM dual;
        END;
    ");

    // PRODUCTS TABLE
    executeQuery($conn, "
        CREATE TABLE product (
            product_id NUMBER PRIMARY KEY,
            product_name VARCHAR2(100),
            description CLOB,
            price NUMBER NOT NULL,
            stock NUMBER NOT NULL,
            min_order NUMBER NOT NULL,
            max_order NUMBER NOT NULL,
            allergy_information VARCHAR2(1000),
            product_image VARCHAR2(100),
            add_date DATE,
            product_status VARCHAR2(20),
            rfid VARCHAR2(10) UNIQUE,
            shop_id NUMBER NOT NULL,
            product_category_name VARCHAR2(20) NOT NULL,
            CONSTRAINT fk_product_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE product_seq START WITH 1 INCREMENT BY 1");

    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_product_pk
        BEFORE INSERT ON product
        FOR EACH ROW
        BEGIN
            SELECT product_seq.NEXTVAL INTO :new.product_id FROM dual;
        END;
    ");

    // PRODUCT_CART TABLE
    executeQuery($conn, "
        CREATE TABLE product_cart (
            cart_id NUMBER,
            product_id NUMBER,
            quantity NUMBER NOT NULL,
            CONSTRAINT pk_product_cart PRIMARY KEY (cart_id, product_id),
            CONSTRAINT fk_pc_cart FOREIGN KEY (cart_id) REFERENCES cart(cart_id),
            CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES product(product_id)
        )
    ");

    // REVIEW TABLE - Fix the name discrepancy between table and trigger
    executeQuery($conn, "
        CREATE TABLE review (
            review_id NUMBER PRIMARY KEY,
            review_rating NUMBER,
            review CLOB,
            review_date DATE DEFAULT SYSDATE,
            user_id NUMBER NOT NULL,
            product_id NUMBER NOT NULL,
            CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(user_id),
            CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES product(product_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE review_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_review_pk
        BEFORE INSERT ON review
        FOR EACH ROW
        BEGIN
            SELECT review_seq.NEXTVAL INTO :new.review_id FROM dual;
        END;
    ");

    // COLLECTION_SLOT TABLE - Fix incorrect TIMESTAMP usage
    executeQuery($conn, "
        CREATE TABLE collection_slot (
            collection_slot_id NUMBER PRIMARY KEY,
            slot_date DATE,
            slot_day VARCHAR2(10),
            slot_time DATE NOT NULL,
            total_order NUMBER
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE collection_slot_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_collection_slot_pk
        BEFORE INSERT ON collection_slot
        FOR EACH ROW
        BEGIN
            SELECT collection_slot_seq.NEXTVAL INTO :new.collection_slot_id FROM dual;
        END;
    ");

    // COUPON TABLE
    executeQuery($conn, "
        CREATE TABLE coupon (
            coupon_id NUMBER PRIMARY KEY,
            coupon_code VARCHAR2(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            description VARCHAR2(200) NOT NULL,
            coupon_discount_percent NUMBER(5,2) NOT NULL
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE coupon_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_coupon_pk
        BEFORE INSERT ON coupon
        FOR EACH ROW
        BEGIN
            SELECT coupon_seq.NEXTVAL INTO :new.coupon_id FROM dual;
        END;
    ");

    // ORDERS TABLE
    executeQuery($conn, "
        CREATE TABLE orders (
            order_id NUMBER PRIMARY KEY,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            order_amount NUMBER,
            total_amount NUMBER,
            coupon_id NUMBER,
            status VARCHAR2(50),
            collection_slot_id NUMBER,
            user_id NUMBER NOT NULL,
            cart_id NUMBER NOT NULL,
            CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(user_id),
            CONSTRAINT fk_order_cart FOREIGN KEY (cart_id) REFERENCES cart(cart_id),
            CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES coupon(coupon_id),
            CONSTRAINT fk_order_slot FOREIGN KEY (collection_slot_id) REFERENCES collection_slot(collection_slot_id)
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE orders_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_orders_pk
        BEFORE INSERT ON orders
        FOR EACH ROW
        BEGIN
            SELECT orders_seq.NEXTVAL INTO :new.order_id FROM dual;
        END;
    ");

    // PAYMENT TABLE
    executeQuery($conn, "
        CREATE TABLE payment (
            payment_id NUMBER PRIMARY KEY,
            payment_date DATE,
            payment_amount NUMBER,
            payment_method VARCHAR2(50),
            payment_status VARCHAR2(20),
            order_id NUMBER NOT NULL,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(user_id),
            CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(order_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE payment_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_payment_pk
        BEFORE INSERT ON payment
        FOR EACH ROW
        BEGIN
            SELECT payment_seq.NEXTVAL INTO :new.payment_id FROM dual;
        END;
    ");

    // DISCOUNT TABLE
    executeQuery($conn, "
        CREATE TABLE discount (
            discount_id NUMBER PRIMARY KEY,
            discount_percentage NUMBER(5,2),
            product_id NUMBER NOT NULL,
            CONSTRAINT fk_discount_product FOREIGN KEY (product_id) REFERENCES product(product_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE discount_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_discount_pk
        BEFORE INSERT ON discount
        FOR EACH ROW
        BEGIN
            SELECT discount_seq.NEXTVAL INTO :new.discount_id FROM dual;
        END;
    ");

    // REPORT TABLE
    executeQuery($conn, "
        CREATE TABLE report (
            report_id NUMBER PRIMARY KEY,
            report_type VARCHAR2(50),
            report_title VARCHAR2(100),
            report_date DATE,
            report_description VARCHAR2(4000) NOT NULL,
            order_id NUMBER,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_report_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
            CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE report_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_report_pk
        BEFORE INSERT ON report
        FOR EACH ROW
        BEGIN
            SELECT report_seq.NEXTVAL INTO :new.report_id FROM dual;
        END;
    ");

    // PRODUCT_REPORT TABLE
    executeQuery($conn, "
        CREATE TABLE product_report (
            product_id NUMBER NOT NULL,
            report_id NUMBER NOT NULL,
            CONSTRAINT pk_product_report PRIMARY KEY (product_id, report_id),
            CONSTRAINT fk_pr_product FOREIGN KEY (product_id) REFERENCES product(product_id),
            CONSTRAINT fk_pr_report FOREIGN KEY (report_id) REFERENCES report(report_id)
        )
    ");

    // WISHLIST TABLE
    executeQuery($conn, "
        CREATE TABLE wishlist (
            wishlist_id NUMBER PRIMARY KEY,
            no_of_items NUMBER NOT NULL,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE wishlist_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_wishlist_pk
        BEFORE INSERT ON wishlist
        FOR EACH ROW
        BEGIN
            SELECT wishlist_seq.NEXTVAL INTO :new.wishlist_id FROM dual;
        END;
    ");

    // WISHLIST_PRODUCT TABLE
    executeQuery($conn, "
        CREATE TABLE wishlist_product (
            wishlist_id NUMBER NOT NULL,
            product_id NUMBER NOT NULL,
            added_date DATE NOT NULL,
            CONSTRAINT pk_wishlist_product PRIMARY KEY (wishlist_id, product_id),
            CONSTRAINT fk_wp_wishlist FOREIGN KEY (wishlist_id) REFERENCES wishlist(wishlist_id),
            CONSTRAINT fk_wp_product FOREIGN KEY (product_id) REFERENCES product(product_id)
        )
    ");


    // INSERT USERS
    $users = [
    ['full_name' => 'Admin User', 'email' => 'admin@example.com', 'contact_no' => '1234567890', 'password' => 'admin123', 'role' => 'admin', 'status' => 'active', 'created_date' => '2024-06-01 10:00:00'],
    ['full_name' => 'Trader User', 'email' => 'trader@example.com', 'contact_no' => '0987654321', 'password' => 'trader123', 'role' => 'trader', 'status' => 'active', 'created_date' => '2024-06-01 10:05:00'],
    ['full_name' => 'First Customer', 'email' => 'customer@gmail.com', 'contact_no' => '9820334455', 'password' => 'customer123', 'role' => 'customer', 'status' => 'pending', 'created_date' => '2024-06-01 10:10:00'],
    ['full_name' => 'Ram Poudel', 'email' => 'ram@gmail.com', 'contact_no' => '9851344575', 'password' => 'ram123', 'role' => 'customer', 'status' => 'active', 'created_date' => '2024-06-02 08:30:00'],
    ['full_name' => 'Agra Bhattarai', 'email' => 'agra@gmail.com', 'contact_no' => '9859344575', 'password' => 'agra123', 'role' => 'customer', 'status' => 'active', 'created_date' => '2024-06-02 09:00:00'],
    ['full_name' => 'Sita Baral', 'email' => 'sita@gmail.com', 'contact_no' => '9839344575', 'password' => 'sita123', 'role' => 'customer', 'status' => 'inactive', 'created_date' => '2024-06-02 09:15:00'],
    ['full_name' => 'Butcher Trader', 'email' => 'butcher@gmail.com', 'contact_no' => '0987654321', 'password' => 'butcher123', 'role' => 'trader', 'status' => 'active', 'created_date' => '2024-06-03 11:00:00'],
    ['full_name' => 'Bakery Trader', 'email' => 'bakery@gmail.com', 'contact_no' => '0989654321', 'password' => 'bakery123', 'role' => 'trader', 'status' => 'active', 'created_date' => '2024-06-03 11:05:00'],
    ['full_name' => 'Fishmonger Trader', 'email' => 'fishmonger@gmail.com', 'contact_no' => '0987054321', 'password' => 'fishmonger123', 'role' => 'trader', 'status' => 'pending', 'created_date' => '2024-06-03 11:10:00'],
    ['full_name' => 'Greengrocer Trader', 'email' => 'greengrocer@gmail.com', 'contact_no' => '0987054321', 'password' => 'greengrocer123', 'role' => 'trader', 'status' => 'active', 'created_date' => '2024-06-03 11:15:00'],
];


    foreach ($users as $user) {
        $check_sql = "SELECT COUNT(*) AS count FROM users WHERE email = :email";
        $stmt = oci_parse($conn, $check_sql);
        oci_bind_by_name($stmt, ":email", $user['email']);
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            if ($row['COUNT'] == 0) {
                $insert_sql = "INSERT INTO users (full_name, email, contact_no, password, role, status)
                               VALUES (:full_name, :email, :contact_no, :password, :role, 'active')";
                $insert_stmt = oci_parse($conn, $insert_sql);
                oci_bind_by_name($insert_stmt, ":full_name", $user['full_name']);
                oci_bind_by_name($insert_stmt, ":email", $user['email']);
                oci_bind_by_name($insert_stmt, ":contact_no", $user['contact_no']);
                $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
                oci_bind_by_name($insert_stmt, ":password", $hashed_password);
                oci_bind_by_name($insert_stmt, ":role", $user['role']);

                if (oci_execute($insert_stmt)) {
                    echo "Inserted user: {$user['full_name']}<br>";
                } else {
                    echo "Failed to insert: {$user['full_name']}<br>";
                }
                oci_free_statement($insert_stmt);
            } else {
                echo "User {$user['email']} already exists. Skipping...<br>";
            }
        } else {
            echo "Failed to check if {$user['email']} exists.<br>";
        }
        oci_free_statement($stmt);
    }

    
//insert into shops
$shops = [
    // Trader with user_id = 7 (Butcher Trader)
    ['user_id' => 7, 'shop_category' => 'butcher', 'shop_name' => 'Meat Haven', 'description' => 'Premium quality meat products.'],
    ['user_id' => 7, 'shop_category' => 'butcher', 'shop_name' => 'Carnivore Corner', 'description' => 'Fresh cuts every day.'],

    // Trader with user_id = 8 (Bakery Trader)
    ['user_id' => 8, 'shop_category' => 'bakery', 'shop_name' => 'Bread & Butter', 'description' => 'Delicious baked goods.'],
    ['user_id' => 8, 'shop_category' => 'bakery', 'shop_name' => 'Sweet Crumbs', 'description' => 'Cakes, cookies, and more.'],

    // Trader with user_id = 9 (Fishmonger Trader)
    ['user_id' => 9, 'shop_category' => 'fishmonger', 'shop_name' => 'Ocean Catch', 'description' => 'Fresh seafood daily.'],
    ['user_id' => 9, 'shop_category' => 'fishmonger', 'shop_name' => 'Fishy Fresh', 'description' => 'Straight from the sea.'],

    // Trader with user_id = 10 (Greengrocer Trader)
    ['user_id' => 10, 'shop_category' => 'greengrocer', 'shop_name' => 'Green Basket', 'description' => 'Fresh fruits and veggies.'],
    ['user_id' => 10, 'shop_category' => 'greengrocer', 'shop_name' => 'Veggie Delight', 'description' => 'Organic produce and more.'],
];

foreach ($shops as $shop) {
    $insert_sql = "INSERT INTO shops (user_id, shop_category, shop_name, description, shop_email)
        VALUES (:user_id, :shop_category, :shop_name, :description, :shop_email
    )";
    $stmt = oci_parse($conn, $insert_sql);

    oci_bind_by_name($stmt, ":user_id", $shop['user_id']);
    oci_bind_by_name($stmt, ":shop_category", $shop['shop_category']);
    oci_bind_by_name($stmt, ":shop_name", $shop['shop_name']);
    oci_bind_by_name($stmt, ":description", $shop['description']);
    oci_bind_by_name($stmt, ":shop_email", $shop['shop_email']);

    if (oci_execute($stmt)) {
        echo "Inserted shop: {$shop['shop_name']}<br>";
    } else {
        echo "Failed to insert shop: {$shop['shop_name']}<br>";
    }

    oci_free_statement($stmt);
}

$today = date('Y-m-d');
$baseUrl = '/E-commerce/frontend/assets/Images/product-images/';

$products = [
    // Butcher (Shop 1 and 2)
    [1, 'Duck Meat', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Duck_Meat.jpg', $today, 'In Stock', 1, 'butcher'],
    [2, 'Bacon', 'Juicy bacon steak.', 15.00, 30, 1, 30, 'None', 'Bacon.jpg', $today, 'In Stock', 1, 'butcher'],
    [3, 'Ribeye', 'Tender Ribeye that you are going to love.', 12.00, 50, 1, 50, 'None', 'Ribeye.jpg', $today, 'In Stock', 1, 'butcher'],
    [4, 'Sirloin', 'Very healthy and juicy Sirloin.', 15.00, 30, 1, 30, 'None', 'Sirloin.jpg', $today, 'In Stock', 1, 'butcher'],
    [5, 'Sausage', 'Tender Sausage perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Sausage.jpg', $today, 'In Stock', 1, 'butcher'],
    [6, 'Pork Chops', 'Juicy beef steak cuts.', 15.00, 30, 1, 30, 'None', 'Pork_Chops.jpg', $today, 'In Stock', 1, 'butcher'],
    [7, 'Whole Chicken', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Whole_Chicken.jpg', $today, 'In Stock', 1, 'butcher'],
    [8, 'Chicken Legs', 'Juicy beef steak cuts.', 15.00, 30, 1, 30, 'None', 'butcher.png', $today, 'In Stock', 1, 'butcher'],
    [9, 'Ground Pork', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'ground-pork.png', $today, 'In Stock', 1, 'butcher'],
    [10, 'Huge Whole Chicken', 'Juicy beef steak cuts.', 15.00, 30, 1, 30, 'None', 'product-wholechicken.png', $today, 'In Stock', 1, 'butcher'],
    [11, 'Chicken Sausage', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'product-sausage.png', $today, 'In Stock', 2, 'butcher'],
    [12, 'Red Pork Chops', 'Juicy beef steak cuts.', 17.00, 31, 1, 30, 'None', 'Pork_Chops.jpg', $today, 'In Stock', 2, 'butcher'],
    [13, 'Healthy Sirloin', 'Very healthy and juicy Sirloin.', 15.00, 30, 1, 30, 'None', 'Sirloin.jpg', $today, 'In Stock', 2, 'butcher'],
    [14, 'Tasty Bacon', 'Juicy bacon steak.', 15.00, 30, 1, 30, 'None', 'Bacon.jpg', $today, 'In Stock', 2, 'butcher'],
    [15, 'Tasty Duck Meat', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Duck_Meat.jpg', $today, 'In Stock', 2, 'butcher'],
    [16, 'Juicy Ribeye', 'Tender Ribeye that you are going to love.', 15.00, 50, 1, 50, 'None', 'Ribeye.jpg', $today, 'In Stock', 2, 'butcher'],
    [17, 'Duck Meat', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Duck_Meat.jpg', $today, 'In Stock', 2, 'butcher'],
    [18, 'Tasty Chicken Breast', 'Juicy beef steak cuts.', 15.00, 30, 1, 30, 'None', 'Chicken_Breast.jpg', $today, 'In Stock', 2, 'butcher'],
    [19, 'Healthy Duck Meat', 'Tender Duck Meat perfect for grilling.', 12.00, 50, 1, 50, 'None', 'Duck_Meat.jpg', $today, 'In Stock', 2, 'butcher'],
    [20, 'Juicy Chicken Legs', 'Juicy beef steak cuts.', 20.00, 30, 1, 30, 'None', 'butcher.png', $today, 'In Stock', 2, 'butcher'],

    // Bakery (Shop 3 and 4)
    [21, 'Bread', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Bread.jpg', $today, 'In Stock', 3, 'bakery'],
    [22, 'Cheese Cake', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Cheese_cake.jpg', $today, 'In Stock', 4, 'bakery'],
    [23, 'Bread', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Bread.jpg', $today, 'In Stock', 3, 'bakery'],
    [24, 'Apple Pie', 'Cheese cake with cream cheese frosting.', 12.00, 20, 1, 20, 'Eggs, Dairy', 'Apple_Pie.jpg', $today, 'In Stock', 4, 'bakery'],
    [25, 'Baguette', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Baguette.jpg', $today, 'In Stock', 3, 'bakery'],
    [26, 'Bagel', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Bagel.jpg', $today, 'In Stock', 4, 'bakery'],
    [27, 'Crossiant', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Crossiant.jpg', $today, 'In Stock', 3, 'bakery'],
    [28, 'Danish Pastry', 'Cheese cake with cream cheese frosting.', 14.00, 20, 1, 20, 'Eggs, Dairy', 'Danish_Pastry.jpg', $today, 'In Stock', 4, 'bakery'],
    [29, 'Eclair', 'Flaky croissant filled with chocolate.', 7.00, 3, 1, 3, 'Gluten, Dairy', 'Eclair.jpg', $today, 'In Stock', 3, 'bakery'],
    [30, 'Garlic Bread', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Garlic_Bread.jpg', $today, 'In Stock', 4, 'bakery'],
    [31, 'Hotdog', 'Flaky croissant filled with chocolate.', 16.00, 10, 1, 10, 'Gluten, Dairy', 'Hotdog.jpg', $today, 'In Stock', 3, 'bakery'],
    [32, 'Muffins', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'muffins.jpg', $today, 'In Stock', 4, 'bakery'],
    [33, 'Macaron', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Macaron.jpg', $today, 'In Stock', 3, 'bakery'],
    [34, 'Donuts', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Donuts.jpg', $today, 'In Stock', 4, 'bakery'],
    [35, 'Cupcakes', 'Flaky croissant filled with chocolate.', 8.00, 30, 1, 30, 'Gluten, Dairy', 'CupCakes.jpg', $today, 'In Stock', 3, 'bakery'],
    [36, 'Cookies', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Cookies.jpg', $today, 'In Stock', 4, 'bakery'],
    [37, 'Chocolate Cake', 'Flaky croissant filled with chocolate.', 7.00, 10, 1, 10, 'Gluten, Dairy', 'Chocolate_cake.jpg', $today, 'In Stock', 3, 'bakery'],
    [38, 'Brownie', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Brownie.jpg', $today, 'In Stock', 4, 'bakery'],
    [39, 'Biscuit', 'Flaky croissant filled with chocolate.', 10.00, 2, 1, 2, 'Gluten, Dairy', 'Biscuit.jpg', $today, 'In Stock', 3, 'bakery'],
    [40, 'Blueberry Cheese Cake', 'Cheese cake with cream cheese frosting.', 9.00, 20, 1, 20, 'Eggs, Dairy', 'Cheese_cake.jpg', $today, 'In Stock', 4, 'bakery'],

    // Fishmonger (Shop 5 and 6)
    [41, 'Salmon Fillet', 'Fresh Atlantic salmon fillet.', 18.00, 10, 1, 10, 'Fish', 'Salmon.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [42, 'Shrimp', 'Large fresh Shrimp.', 13.00, 60, 1, 6, 'Shellfish', 'Shrimp.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [43, 'Clam', 'Fresh Atlantic salmon fillet.', 12.00, 10, 1, 10, 'Fish', 'Clam.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [44, 'Cod', 'Large fresh Shrimp.', 15.00, 60, 1, 6, 'Shellfish', 'Cod.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [45, 'Crab', 'Fresh Atlantic salmon fillet.', 16.00, 10, 1, 10, 'Fish', 'Crab.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [46, 'Dried Fish', 'Large fresh Shrimp.', 11.00, 60, 1, 6, 'Shellfish', 'Dried_Fish.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [47, 'Eel', 'Fresh Atlantic salmon fillet.', 18.00, 10, 1, 10, 'Fish', 'Eel.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [48, 'King Fish', 'Large fresh Shrimp.', 10.00, 60, 1, 6, 'Shellfish', 'King_fish.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [49, 'Lobster', 'Fresh Atlantic salmon fillet.', 18.00, 10, 1, 10, 'Fish', 'Lobster.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [50, 'Mussels', 'Large fresh Shrimp.', 9.00, 60, 1, 6, 'Shellfish', 'Mussels.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [51, 'Octopus', 'Fresh Atlantic salmon fillet.', 20.00, 10, 1, 10, 'Fish', 'Octopus.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [52, 'Oysters', 'Large fresh Shrimp.', 13.00, 60, 1, 6, 'Shellfish', 'Oysters.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [53, 'Salmon', 'Fresh Atlantic salmon fillet.', 25.00, 10, 1, 10, 'Fish', 'Salmon.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [54, 'Sardinhas', 'Large fresh Shrimp.', 17.00, 60, 1, 6, 'Shellfish', 'Sardines.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [55, 'Scallops', 'Fresh Atlantic salmon fillet.', 28.00, 10, 1, 10, 'Fish', 'Scallops.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [56, 'Sea Urchin', 'Large fresh Shrimp.', 15.00, 60, 1, 6, 'Shellfish', 'Sea_Urchin.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [57, 'Squid', 'Fresh Atlantic salmon fillet.', 19.00, 10, 1, 10, 'Fish', 'Squid.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [58, 'Trout', 'Large fresh Shrimp.', 13.00, 60, 1, 6, 'Shellfish', 'Trout.jpg', $today, 'In Stock', 6, 'fishmonger'],
    [59, 'Local Salmon', 'Fresh Atlantic salmon fillet.', 8.00, 10, 1, 10, 'Fish', 'Salmon.jpg', $today, 'In Stock', 5, 'fishmonger'],
    [60, 'Local Shrimp', 'Large fresh Shrimp.', 13.00, 60, 1, 6, 'Shellfish', 'Shrimp.jpg', $today, 'In Stock', 6, 'fishmonger'],

    // Greengrocer (Shop 7 and 8)
    [61, 'Apples', 'Fresh organic Apples per kg.', 8.00, 2, 1, 2, 'None', 'Apples.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [62, 'Banana', 'Cleaned and packed banana.', 12.00, 15, 1, 15, 'None', 'Banana.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [63, 'Bell Pepper', 'Fresh organic Apples per kg.', 10.00, 12, 1, 12, 'None', 'Bell_Pepper.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [64, 'Bitter Gourd', 'Cleaned and packed banana.', 14.00, 15, 1, 15, 'None', 'Bitter_Gourd.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [65, 'Broccoli', 'Fresh organic Apples per kg.', 18.00, 4, 1, 4, 'None', 'Broccoli.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [66, 'Carrots', 'Cleaned and packed banana.', 16.00, 15, 1, 15, 'None', 'Carrots.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [67, 'Cauliflower', 'Fresh organic Apples per kg.', 28.00, 2, 1, 2, 'None', 'Cauliflower.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [68, 'Cucumber', 'Cleaned and packed banana.', 19.00, 17, 1, 17, 'None', 'Cucumber.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [69, 'Grapes', 'Fresh organic Apples per kg.', 18.00, 22, 1, 22, 'None', 'Grapes.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [70, 'Jalapenos', 'Cleaned and packed banana.', 17.00, 15, 1, 15, 'None', 'Jalapenos.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [71, 'Lemon', 'Fresh organic Apples per kg.', 13.00, 14, 1, 14, 'None', 'Lemon.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [72, 'Mango', 'Cleaned and packed banana.', 11.00, 19, 1, 19, 'None', 'Mango.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [73, 'Onion', 'Fresh organic Apples per kg.', 18.00, 13, 1, 13, 'None', 'Onion.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [74, 'Orange', 'Cleaned and packed banana.', 7.00, 18, 1, 18, 'None', 'Orange.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [75, 'Papaya', 'Fresh organic Apples per kg.', 14.00, 12, 1, 12, 'None', 'Papaya.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [76, 'Pineapple', 'Cleaned and packed banana.', 21.00, 15, 1, 15, 'None', 'Pineapple.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [77, 'Potato', 'Fresh organic Apples per kg.', 35.00, 2, 1, 2, 'None', 'Potato.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [78, 'Pumpkin', 'Cleaned and packed banana.', 5.00, 17, 1, 17, 'None', 'Pumpkin.jpg', $today, 'In Stock', 8, 'greengrocer'],
    [79, 'Spinach', 'Fresh organic Apples per kg.', 14.00, 2, 1, 2, 'None', 'Spinach.jpg', $today, 'In Stock', 7, 'greengrocer'],
    [80, 'Strawberry', 'Cleaned and packed banana.', 19.00, 4, 1, 4, 'None', 'Strawberry.jpg', $today, 'In Stock', 8, 'greengrocer'],
];

foreach ($products as $p) {
    $insert_sql = "INSERT INTO product (
        product_id, product_name, description, price, stock, min_order, max_order,
        allergy_information, product_image, add_date, product_status, rfid, shop_id, product_category_name
    ) VALUES (
        :product_id, :product_name, :description, :price, :stock, :min_order, :max_order,
        :allergy_information, :product_image, TO_DATE(:add_date, 'YYYY-MM-DD'),
        :product_status, :rfid, :shop_id, :product_category_name
    )";

    $stmt = oci_parse($conn, $insert_sql);

    oci_bind_by_name($stmt, ":product_id", $p[0]);
    oci_bind_by_name($stmt, ":product_name", $p[1]);
    oci_bind_by_name($stmt, ":description", $p[2]);
    oci_bind_by_name($stmt, ":price", $p[3]);
    oci_bind_by_name($stmt, ":stock", $p[4]);
    oci_bind_by_name($stmt, ":min_order", $p[5]);
    oci_bind_by_name($stmt, ":max_order", $p[6]);
    oci_bind_by_name($stmt, ":allergy_information", $p[7]);
    oci_bind_by_name($stmt, ":product_image", $p[8]);
    oci_bind_by_name($stmt, ":add_date", $p[9]);
    oci_bind_by_name($stmt, ":product_status", $p[10]);
    $rfid = ''; // Empty string for RFID
    oci_bind_by_name($stmt, ":rfid", $rfid);
    oci_bind_by_name($stmt, ":shop_id", $p[11]);
    oci_bind_by_name($stmt, ":product_category_name", $p[12]);

    if (oci_execute($stmt)) {
        echo "Inserted product: {$p[1]}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert product: {$p[1]}<br>Error: " . $e['message'] . "<br>";
    }

    oci_free_statement($stmt);
}

// INSERT CART DATA
$carts = [
    ['cart_id' => 1, 'user_id' => 4, 'add_date' => '2024-06-10'], // Ram Poudel's cart
    ['cart_id' => 2, 'user_id' => 5, 'add_date' => '2024-06-11'], // Agra Bhattarai's cart
    ['cart_id' => 3, 'user_id' => 6, 'add_date' => '2024-06-12'], // Sita's second cart
];

foreach ($carts as $cart) {
    $insert_sql = "INSERT INTO cart (cart_id, user_id, add_date) 
                   VALUES (:cart_id, :user_id, TO_DATE(:add_date, 'YYYY-MM-DD'))";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":cart_id", $cart['cart_id']);
    oci_bind_by_name($stmt, ":user_id", $cart['user_id']);
    oci_bind_by_name($stmt, ":add_date", $cart['add_date']);
    
    if (oci_execute($stmt)) {
        echo "Inserted cart: {$cart['cart_id']} for user {$cart['user_id']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert cart: {$cart['cart_id']}<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}

// INSERT PRODUCT_CART DATA
$product_carts = [
    // Ram Poudel's first cart (cart_id 1)
    ['cart_id' => 1, 'product_id' => 1, 'quantity' => 2],  // Duck Meat
    ['cart_id' => 1, 'product_id' => 21, 'quantity' => 1], // Bread
    ['cart_id' => 1, 'product_id' => 41, 'quantity' => 3], // Salmon Fillet
    ['cart_id' => 1, 'product_id' => 51, 'quantity' => 4], // Salmon Fillet
    ['cart_id' => 1, 'product_id' => 61, 'quantity' => 8], // Salmon Fillet
    ['cart_id' => 1, 'product_id' => 11, 'quantity' => 9], // Salmon Fillet
    
    // Agra Bhattarai's cart (cart_id 2)
    ['cart_id' => 2, 'product_id' => 5, 'quantity' => 1],  // Sausage
    ['cart_id' => 2, 'product_id' => 25, 'quantity' => 2], // Baguette
    ['cart_id' => 2, 'product_id' => 35, 'quantity' => 5], // Apples
    ['cart_id' => 2, 'product_id' => 45, 'quantity' => 2], // Apples
    ['cart_id' => 2, 'product_id' => 55, 'quantity' => 5], // Apples
    ['cart_id' => 2, 'product_id' => 65, 'quantity' => 3], // Apples
    
    // Sita's cart (cart_id 3)
    ['cart_id' => 3, 'product_id' => 15, 'quantity' => 1], // Tasty Duck Meat
    ['cart_id' => 3, 'product_id' => 35, 'quantity' => 3], // Cupcakes
    ['cart_id' => 3, 'product_id' =>26, 'quantity' => 2], // Papaya
    ['cart_id' => 3, 'product_id' => 36, 'quantity' => 2], // Papaya
    ['cart_id' => 3, 'product_id' => 46, 'quantity' => 2], // Papaya
    ['cart_id' => 3, 'product_id' => 56, 'quantity' => 2], // Papaya
];

foreach ($product_carts as $pc) {
    $insert_sql = "INSERT INTO product_cart (cart_id, product_id, quantity) 
                   VALUES (:cart_id, :product_id, :quantity)";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":cart_id", $pc['cart_id']);
    oci_bind_by_name($stmt, ":product_id", $pc['product_id']);
    oci_bind_by_name($stmt, ":quantity", $pc['quantity']);
    
    if (oci_execute($stmt)) {
        echo "Inserted product_cart: cart {$pc['cart_id']} with product {$pc['product_id']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert product_cart: cart {$pc['cart_id']}, product {$pc['product_id']}<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}


//INSERT DATA FOR COLLECTION SLOT
$collectionSlots = [
    ['slot_date' => '2024-06-15', 'slot_day' => 'Thursday', 'slot_time' => '10:00:00', 'total_order' => 15],
    ['slot_date' => '2024-06-15', 'slot_day' => 'Wednesday', 'slot_time' => '12:00:00', 'total_order' => 20],
    ['slot_date' => '2024-06-16', 'slot_day' => 'Friday', 'slot_time' => '11:00:00', 'total_order' => 15],
    ['slot_date' => '2024-06-17', 'slot_day' => 'Wednesday', 'slot_time' => '14:00:00', 'total_order' => 18],
    ['slot_date' => '2025-05-21', 'slot_day' => 'Wednesday', 'slot_time' => '15:00:00', 'total_order' => 15],
    ['slot_date' => '2025-05-22', 'slot_day' => 'Thursday', 'slot_time' => '15:00:00', 'total_order' => 15],
    ['slot_date' => '2025-05-23', 'slot_day' => 'Friday', 'slot_time' => '15:00:00', 'total_order' => 15],
    ['slot_date' => '2025-05-28', 'slot_day' => 'Wednesday', 'slot_time' => '15:00:00', 'total_order' => 15],
    ['slot_date' => '2025-05-29', 'slot_day' => 'Thi', 'slot_time' => '15:00:00', 'total_order' => 15]
];

foreach ($collectionSlots as $slot) {
    $insert_sql = "INSERT INTO collection_slot (slot_date, slot_day, slot_time, total_order) 
                   VALUES (TO_DATE(:slot_date, 'YYYY-MM-DD'), :slot_day, 
                   TO_DATE(:slot_time, 'HH24:MI:SS'), :total_order)";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":slot_date", $slot['slot_date']);
    oci_bind_by_name($stmt, ":slot_day", $slot['slot_day']);
    oci_bind_by_name($stmt, ":slot_time", $slot['slot_time']);
    oci_bind_by_name($stmt, ":total_order", $slot['total_order']);
    
    if (oci_execute($stmt)) {
        echo "Inserted collection slot: {$slot['slot_day']} at {$slot['slot_time']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert collection slot<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}

// Create some coupons since orders can reference them
$coupons = [
    ['coupon_code' => 'FISH', 'start_date' => '2025-04-01', 'end_date' => '2025-08-31', 
     'description' => 'fishmonger discount 10%', 'coupon_discount_percent' => 10],
    ['coupon_code' => 'WELCOME15', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 
     'description' => 'Welcome discount 15%', 'coupon_discount_percent' => 15],
    ['coupon_code' => 'MEAT20', 'start_date' => '2024-06-01', 'end_date' => '2024-06-30', 
     'description' => 'Meat discount 20%', 'coupon_discount_percent' => 20]
];

foreach ($coupons as $coupon) {
    $insert_sql = "INSERT INTO coupon (coupon_code, start_date, end_date, description, coupon_discount_percent) 
                   VALUES (:coupon_code, TO_DATE(:start_date, 'YYYY-MM-DD'), 
                   TO_DATE(:end_date, 'YYYY-MM-DD'), :description, :coupon_discount_percent)";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":coupon_code", $coupon['coupon_code']);
    oci_bind_by_name($stmt, ":start_date", $coupon['start_date']);
    oci_bind_by_name($stmt, ":end_date", $coupon['end_date']);
    oci_bind_by_name($stmt, ":description", $coupon['description']);
    oci_bind_by_name($stmt, ":coupon_discount_percent", $coupon['coupon_discount_percent']);
    
    if (oci_execute($stmt)) {
        echo "Inserted coupon: {$coupon['coupon_code']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert coupon<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}

// Now let's create 15 orders
$orders = [
    // Orders for Ram Poudel (user_id 4)
    ['order_amount' => 45.00, 'total_amount' => 45.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 1, 'user_id' => 4, 'cart_id' => 1, 'order_date' => '2024-06-10 10:15:00'],
    ['order_amount' => 32.50, 'total_amount' => 29.25, 'coupon_id' => 2, 'status' => 'completed', 
     'collection_slot_id' => 2, 'user_id' => 4, 'cart_id' => 1, 'order_date' => '2024-10-11 11:30:00'],
    ['order_amount' => 28.00, 'total_amount' => 28.00, 'coupon_id' => null, 'status' => 'pending', 
     'collection_slot_id' => 3, 'user_id' => 4, 'cart_id' => 1, 'order_date' => '2025-01-12 09:45:00'],
    ['order_amount' => 55.00, 'total_amount' => 49.50, 'coupon_id' => 1, 'status' => 'completed', 
     'collection_slot_id' => 4, 'user_id' => 4, 'cart_id' => 1, 'order_date' => '2025-02-13 14:20:00'],
    ['order_amount' => 18.00, 'total_amount' => 18.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 5, 'user_id' => 4, 'cart_id' => 1, 'order_date' => '2024-09-14 16:10:00'],
    
    // Orders for Agra Bhattarai (user_id 5)
    ['order_amount' => 42.00, 'total_amount' => 42.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 1, 'user_id' => 5, 'cart_id' => 2, 'order_date' => '2024-06-11 08:30:00'],
    ['order_amount' => 27.50, 'total_amount' => 24.75, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 2, 'user_id' => 5, 'cart_id' => 2, 'order_date' => '2024-06-12 10:45:00'],
    ['order_amount' => 36.00, 'total_amount' => 30.60, 'coupon_id' => null, 'status' => 'pending', 
     'collection_slot_id' => 3, 'user_id' => 5, 'cart_id' => 2, 'order_date' => '2025-05-13 12:15:00'],
    ['order_amount' => 19.00, 'total_amount' => 19.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 4, 'user_id' => 5, 'cart_id' => 2, 'order_date' => '2024-06-14 15:30:00'],
    ['order_amount' => 22.00, 'total_amount' => 22.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 5, 'user_id' => 5, 'cart_id' => 2, 'order_date' => '2024-06-15 17:45:00'],
    
    // Orders for Sita Baral (user_id 6)
    ['order_amount' => 38.00, 'total_amount' => 38.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 1, 'user_id' => 6, 'cart_id' => 3, 'order_date' => '2024-06-12 09:20:00'],
    ['order_amount' => 45.50, 'total_amount' => 40.95, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 2, 'user_id' => 6, 'cart_id' => 3, 'order_date' => '2024-06-13 11:35:00'],
    ['order_amount' => 29.00, 'total_amount' => 24.65, 'coupon_id' => null, 'status' => 'pending', 
     'collection_slot_id' => 3, 'user_id' => 6, 'cart_id' => 3, 'order_date' => '2025-05-14 13:50:00'],
    ['order_amount' => 17.00, 'total_amount' => 17.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 4, 'user_id' => 6, 'cart_id' => 3, 'order_date' => '2024-06-15 16:05:00'],
    ['order_amount' => 23.00, 'total_amount' => 23.00, 'coupon_id' => null, 'status' => 'completed', 
     'collection_slot_id' => 5, 'user_id' => 6, 'cart_id' => 3, 'order_date' => '2024-06-16 18:20:00']
];

foreach ($orders as $order) {
    $insert_sql = "INSERT INTO orders (order_amount, total_amount, coupon_id, status, 
                   collection_slot_id, user_id, cart_id, order_date) 
                   VALUES (:order_amount, :total_amount, :coupon_id, :status, 
                   :collection_slot_id, :user_id, :cart_id, TO_TIMESTAMP(:order_date, 'YYYY-MM-DD HH24:MI:SS'))";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":order_amount", $order['order_amount']);
    oci_bind_by_name($stmt, ":total_amount", $order['total_amount']);
    oci_bind_by_name($stmt, ":coupon_id", $order['coupon_id']);
    oci_bind_by_name($stmt, ":status", $order['status']);
    oci_bind_by_name($stmt, ":collection_slot_id", $order['collection_slot_id']);
    oci_bind_by_name($stmt, ":user_id", $order['user_id']);
    oci_bind_by_name($stmt, ":cart_id", $order['cart_id']);
    oci_bind_by_name($stmt, ":order_date", $order['order_date']);
    
    if (oci_execute($stmt)) {
        echo "Inserted order for user {$order['user_id']} with status {$order['status']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert order<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}

//15 corresponding payments
$payments = [
    // Payments for Ram Poudel's orders (order_id 1-5)
    ['payment_date' => '2024-06-10', 'payment_amount' => 45.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 1, 'user_id' => 4],
    ['payment_date' => '2024-10-11', 'payment_amount' => 29.25, 'payment_method' => 'paypal', 
     'payment_status' => 'completed', 'order_id' => 2, 'user_id' => 4],
    ['payment_date' => '2025-01-12', 'payment_amount' => 28.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'pending', 'order_id' => 3, 'user_id' => 4],
    ['payment_date' => '2025-02-13', 'payment_amount' => 49.50, 'payment_method' => 'bank_transfer', 
     'payment_status' => 'completed', 'order_id' => 4, 'user_id' => 4],
    ['payment_date' => '2024-09-14', 'payment_amount' => 18.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 5, 'user_id' => 4],
    
    // Payments for Agra Bhattarai's orders (order_id 6-10)
    ['payment_date' => '2024-06-11', 'payment_amount' => 42.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 6, 'user_id' => 5],
    ['payment_date' => '2024-06-12', 'payment_amount' => 24.75, 'payment_method' => 'paypal', 
     'payment_status' => 'completed', 'order_id' => 7, 'user_id' => 5],
    ['payment_date' => '2025-05-13', 'payment_amount' => 30.60, 'payment_method' => 'credit_card', 
     'payment_status' => 'pending', 'order_id' => 8, 'user_id' => 5],
    ['payment_date' => '2024-06-14', 'payment_amount' => 19.00, 'payment_method' => 'bank_transfer', 
     'payment_status' => 'completed', 'order_id' => 9, 'user_id' => 5],
    ['payment_date' => '2024-06-15', 'payment_amount' => 22.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 10, 'user_id' => 5],
    
    // Payments for Sita Baral's orders (order_id 11-15)
    ['payment_date' => '2024-06-12', 'payment_amount' => 38.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 11, 'user_id' => 6],
    ['payment_date' => '2024-06-13', 'payment_amount' => 40.95, 'payment_method' => 'paypal', 
     'payment_status' => 'completed', 'order_id' => 12, 'user_id' => 6],
    ['payment_date' => '2024-06-14', 'payment_amount' => 24.65, 'payment_method' => 'credit_card', 
     'payment_status' => 'pending', 'order_id' => 13, 'user_id' => 6],
    ['payment_date' => '2024-06-15', 'payment_amount' => 17.00, 'payment_method' => 'bank_transfer', 
     'payment_status' => 'completed', 'order_id' => 14, 'user_id' => 6],
    ['payment_date' => '2024-06-16', 'payment_amount' => 23.00, 'payment_method' => 'credit_card', 
     'payment_status' => 'completed', 'order_id' => 15, 'user_id' => 6]
];

foreach ($payments as $payment) {
    $insert_sql = "INSERT INTO payment (payment_date, payment_amount, payment_method, 
                   payment_status, order_id, user_id) 
                   VALUES (TO_DATE(:payment_date, 'YYYY-MM-DD'), :payment_amount, 
                   :payment_method, :payment_status, :order_id, :user_id)";
    $stmt = oci_parse($conn, $insert_sql);
    
    oci_bind_by_name($stmt, ":payment_date", $payment['payment_date']);
    oci_bind_by_name($stmt, ":payment_amount", $payment['payment_amount']);
    oci_bind_by_name($stmt, ":payment_method", $payment['payment_method']);
    oci_bind_by_name($stmt, ":payment_status", $payment['payment_status']);
    oci_bind_by_name($stmt, ":order_id", $payment['order_id']);
    oci_bind_by_name($stmt, ":user_id", $payment['user_id']);
    
    if (oci_execute($stmt)) {
        echo "Inserted payment for order {$payment['order_id']} with status {$payment['payment_status']}<br>";
    } else {
        $e = oci_error($stmt);
        echo "Failed to insert payment<br>Error: " . $e['message'] . "<br>";
    }
    
    oci_free_statement($stmt);
}

oci_close($conn);
} else {
    echo "Could not connect to database.<br>";
}

