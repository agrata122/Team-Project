<?php
session_start();
require_once '../../../backend/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = getDBConnection();

    if ($conn) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":email", $email);

        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);

            if ($row && password_verify($password, $row['PASSWORD'])) {
                if (strtolower($row['STATUS']) !== 'active') {
                    $_SESSION['approval_pending'] = true; // Set a session flag for the popup
                    oci_free_statement($stmt);
                    oci_close($conn);
                    header("Location: login.php"); // Redirect back to login page
                    exit();
                }

                $_SESSION['user_id'] = $row['USER_ID'];
                $_SESSION['role'] = $row['ROLE'];

                // If there's a guest cart, merge it with the user's cart
                if (isset($_COOKIE['guest_id'])) {
                    $guest_id = $_COOKIE['guest_id'];
                    
                    // Get guest cart items
                    $guestCartQuery = "SELECT c.cart_id, pc.product_id, pc.quantity 
                                      FROM cart c 
                                      JOIN product_cart pc ON c.cart_id = pc.cart_id 
                                      WHERE c.user_id = :guest_id";
                    $guestCartStmt = oci_parse($conn, $guestCartQuery);
                    oci_bind_by_name($guestCartStmt, ':guest_id', $guest_id, -1, SQLT_CHR);
                    oci_execute($guestCartStmt);

                    // Get or create user's cart
                    $userCartQuery = "SELECT cart_id FROM cart WHERE user_id = :user_id";
                    $userCartStmt = oci_parse($conn, $userCartQuery);
                    oci_bind_by_name($userCartStmt, ':user_id', $row['USER_ID']);
                    oci_execute($userCartStmt);
                    $userCart = oci_fetch_assoc($userCartStmt);

                    if (!$userCart) {
                        // Create new cart for user
                        $createCartQuery = "INSERT INTO cart (user_id) VALUES (:user_id) RETURNING cart_id INTO :new_cart_id";
                        $createCartStmt = oci_parse($conn, $createCartQuery);
                        $new_cart_id = null;
                        oci_bind_by_name($createCartStmt, ':user_id', $row['USER_ID']);
                        oci_bind_by_name($createCartStmt, ':new_cart_id', $new_cart_id, 32, SQLT_INT);
                        oci_execute($createCartStmt);
                        $user_cart_id = $new_cart_id;
                    } else {
                        $user_cart_id = $userCart['CART_ID'];
                    }

                    // Merge guest cart items into user's cart
                    while ($guestItem = oci_fetch_assoc($guestCartStmt)) {
                        // Check if product already exists in user's cart
                        $checkProductQuery = "SELECT * FROM product_cart 
                                            WHERE cart_id = :cart_id AND product_id = :product_id";
                        $checkProductStmt = oci_parse($conn, $checkProductQuery);
                        oci_bind_by_name($checkProductStmt, ':cart_id', $user_cart_id);
                        oci_bind_by_name($checkProductStmt, ':product_id', $guestItem['PRODUCT_ID']);
                        oci_execute($checkProductStmt);

                        if (oci_fetch($checkProductStmt)) {
                            // Update quantity if product exists
                            $updateQtyQuery = "UPDATE product_cart 
                                             SET quantity = quantity + :qty 
                                             WHERE cart_id = :cart_id AND product_id = :product_id";
                            $updateQtyStmt = oci_parse($conn, $updateQtyQuery);
                            oci_bind_by_name($updateQtyStmt, ':qty', $guestItem['QUANTITY']);
                            oci_bind_by_name($updateQtyStmt, ':cart_id', $user_cart_id);
                            oci_bind_by_name($updateQtyStmt, ':product_id', $guestItem['PRODUCT_ID']);
                            oci_execute($updateQtyStmt);
                        } else {
                            // Insert new product if it doesn't exist
                            $insertProductQuery = "INSERT INTO product_cart (cart_id, product_id, quantity) 
                                                 VALUES (:cart_id, :product_id, :qty)";
                            $insertProductStmt = oci_parse($conn, $insertProductQuery);
                            oci_bind_by_name($insertProductStmt, ':cart_id', $user_cart_id);
                            oci_bind_by_name($insertProductStmt, ':product_id', $guestItem['PRODUCT_ID']);
                            oci_bind_by_name($insertProductStmt, ':qty', $guestItem['QUANTITY']);
                            oci_execute($insertProductStmt);
                        }
                    }

                    // Delete guest cart
                    $deleteGuestCartQuery = "DELETE FROM cart WHERE user_id = :guest_id";
                    $deleteGuestCartStmt = oci_parse($conn, $deleteGuestCartQuery);
                    oci_bind_by_name($deleteGuestCartStmt, ':guest_id', $guest_id, -1, SQLT_CHR);
                    oci_execute($deleteGuestCartStmt);

                    // Remove guest cookie
                    setcookie('guest_id', '', time() - 3600, '/');
                }

                // Role-based redirect
                if ($row['ROLE'] === 'admin') {
                    oci_free_statement($stmt);
                    oci_close($conn);
                    header("Location: ../../admin/admindashboard.php");
                    exit();
                } elseif ($row['ROLE'] === 'customer') {
                    oci_free_statement($stmt);
                    oci_close($conn);
                    header("Location: homepage.php");
                    exit();
                } elseif ($row['ROLE'] === 'trader') {
                    // Fetch shops for trader (Oracle 11g compatible syntax)
                    $shop_sql = "SELECT shop_category, shop_name, shop_id FROM shops WHERE user_id = :user_id AND ROWNUM <= 2";
                    $shop_stmt = oci_parse($conn, $shop_sql);
                    oci_bind_by_name($shop_stmt, ":user_id", $row['USER_ID']);

                    if (oci_execute($shop_stmt)) {
                        $shops = [];
                        while ($shop = oci_fetch_assoc($shop_stmt)) {
                            $shops[] = $shop;
                        }

                        oci_free_statement($shop_stmt);
                        oci_free_statement($stmt);
                        oci_close($conn);

                        if (count($shops) > 0) {
                            $_SESSION['shop_category'] = $shops[0]['SHOP_CATEGORY'];
                            $_SESSION['shops'] = $shops;
                            header("Location: ../../trader/traderdashboard.php");
                            exit();
                        } else {
                            // Set error message in session instead of directly echoing
                            $_SESSION['login_error'] = "Trader account has no shops assigned.";
                            header("Location: login.php");
                            exit();
                        }
                    } else {
                        $e = oci_error($shop_stmt);
                        // Set error message in session instead of directly echoing
                        $_SESSION['login_error'] = "Failed to fetch trader shops: " . $e['message'];
                        oci_free_statement($shop_stmt);
                        oci_free_statement($stmt);
                        oci_close($conn);
                        header("Location: login.php");
                        exit();
                    }
                }
            } else {
                // Set error message in session instead of directly echoing
                $_SESSION['login_error'] = "Invalid email or password.";
                header("Location: login.php");
                exit();
            }
        } else {
            $e = oci_error($stmt);
            // Set error message in session instead of directly echoing
            $_SESSION['login_error'] = "Failed to execute user query: " . $e['message'];
            header("Location: login.php");
            exit();
        }

        oci_free_statement($stmt);
        oci_close($conn);
    } else {
        // Set error message in session instead of directly echoing
        $_SESSION['login_error'] = "Database connection failed.";
        header("Location: login.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FresGrub Login</title>
    <link rel="stylesheet" href="../../assets/CSS/LoginPage.css">
    <style>
        /* Add this style for the modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            text-align: center;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <!-- The Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Account Approval Pending</h3>
            <p>Your account is not yet approved by the admin. Please wait for approval.</p>
            <button onclick="document.getElementById('approvalModal').style.display='none'">OK</button>
        </div>
    </div>

    <!-- Error Message Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Login Error</h3>
            <p id="errorMessage"></p>
            <button onclick="document.getElementById('errorModal').style.display='none'">OK</button>
        </div>
    </div>

    <div class="container">
        <div class="login-side">
            <div class="logo">
                <img src="../../assets/Images/logo.png" alt="FresGrub Logo">
            </div>
            <h2>Log in →</h2>
             
            <form action="" method="POST">
                <div class="form-group">
                    <label>Email address or user name</label>
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="password-toggle" id="passwordToggle" type="button">Show</span>
                    </div>
                </div>
                <div class="options">
                    <label for="remember" class="remember-label">
                        <input type="checkbox" id="remember" name="remember"> Remember me
                    </label>
                    <a href="forgot_password.php">Forgot your password?</a>
                </div>
                <button type="submit">LOG IN</button>
            </form>  

            <p class="signup-link">Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
        <div class="image-side">
            <img src="../../assets/Images/login-picture.png" alt="Fresh Grocery">
        </div>
    </div>

    <script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');

        passwordToggle.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                passwordToggle.textContent = 'Show';
            }
        });

        // Modal handling
        const approvalModal = document.getElementById('approvalModal');
        const errorModal = document.getElementById('errorModal');
        const closeButtons = document.getElementsByClassName('close');
        
        // Close modal when clicking on X
        for (let i = 0; i < closeButtons.length; i++) {
            closeButtons[i].onclick = function() {
                if (approvalModal.style.display === 'block') {
                    approvalModal.style.display = 'none';
                }
                if (errorModal.style.display === 'block') {
                    errorModal.style.display = 'none';
                }
            }
        }
        
        // Show approval modal if needed
        <?php if (isset($_SESSION['approval_pending'])): ?>
            document.getElementById('approvalModal').style.display = 'block';
            <?php unset($_SESSION['approval_pending']); ?>
        <?php endif; ?>
        
        // Show error modal if there's an error
        <?php if (isset($_SESSION['login_error'])): ?>
            document.getElementById('errorMessage').textContent = '<?php echo $_SESSION['login_error']; ?>';
            document.getElementById('errorModal').style.display = 'block';
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target === approvalModal) {
                approvalModal.style.display = 'none';
            }
            if (event.target === errorModal) {
                errorModal.style.display = 'none';
            }
        }
    });
    </script>
</body>
</html>