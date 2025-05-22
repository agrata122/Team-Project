<?php
session_start();
require '../../backend/connect.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$warning_msg = [];
$success_msg = [];

// Get current user's trader type
$trader_type = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = oci_parse($conn, "SELECT shop_category FROM shops WHERE user_id = :user_id");
    oci_bind_by_name($user_query, ':user_id', $user_id);
    oci_execute($user_query);
    $user_data = oci_fetch_assoc($user_query);
    if ($user_data) {
        $trader_type = $user_data['SHOP_CATEGORY'];
    }
}

// Fetch shops based on trader type
$shop_query = oci_parse($conn, "
    SELECT s.shop_id, s.shop_name, s.shop_category 
    FROM shops s 
    JOIN users u ON s.user_id = u.user_id 
    WHERE u.user_id = :user_id
");
oci_bind_by_name($shop_query, ':user_id', $user_id);
oci_execute($shop_query);

$shops = [];
while ($row = oci_fetch_assoc($shop_query)) {
    $shops[] = [
        'shop_id' => $row['SHOP_ID'],
        'shop_name' => $row['SHOP_NAME'],
        'category' => $row['SHOP_CATEGORY']
    ];
}

if (isset($_POST['add'])) {
    if (!isset($_SESSION['user_id'])) {
        $warning_msg[] = 'User is not logged in.';
    } else {
        $product_name = filter_var($_POST['product_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $price = filter_var($_POST['price'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $stock = filter_var($_POST['stock'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $min_order = filter_var($_POST['min_order'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $max_order = filter_var($_POST['max_order'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $allergy_information = filter_var($_POST['allergy_information'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $product_status = filter_var($_POST['product_status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shop_id = $_POST['shop_id'];
        $rfid_tag = filter_var($_POST['rfid_tag'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $product_category_name = '';
        foreach ($shops as $shop) {
            if ($shop['shop_id'] == $shop_id) {
                $product_category_name = $shop['category'];
                break;
            }
        }

        $image = $_FILES['product_image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $rename = uniqid('product_') . '.' . $ext;
        $image_tmp_name = $_FILES['product_image']['tmp_name'];
        $image_size = $_FILES['product_image']['size'];
        $image_folder = 'uploaded_files/' . $rename;

        if ($image_size > 2000000) {
            $warning_msg[] = 'Image size is too large! Maximum size is 2MB.';
        } else {
            $sql = "INSERT INTO product (
                        product_name, description, price, stock, min_order, max_order,
                        allergy_information, product_image, add_date, product_status,
                        shop_id, product_category_name, rfid_tag
                    ) VALUES (
                        :product_name, :description, :price, :stock, :min_order, :max_order,
                        :allergy_information, :product_image, TO_DATE(:add_date, 'YYYY-MM-DD'), 
                        CASE 
                            WHEN :product_status = 'In Stock' THEN :product_status || ' ' || :rfid_tag || ' '
                            ELSE :product_status
                        END,
                        :shop_id, :product_category_name, :rfid_tag
                    )";

            $stmt = oci_parse($conn, $sql);
            $add_date = date('Y-m-d');

            oci_bind_by_name($stmt, ':product_name', $product_name);
            oci_bind_by_name($stmt, ':description', $description);
            oci_bind_by_name($stmt, ':price', $price);
            oci_bind_by_name($stmt, ':stock', $stock);
            oci_bind_by_name($stmt, ':min_order', $min_order);
            oci_bind_by_name($stmt, ':max_order', $max_order);
            oci_bind_by_name($stmt, ':allergy_information', $allergy_information);
            oci_bind_by_name($stmt, ':product_image', $rename);
            oci_bind_by_name($stmt, ':add_date', $add_date);
            oci_bind_by_name($stmt, ':product_status', $product_status);
            oci_bind_by_name($stmt, ':shop_id', $shop_id);
            oci_bind_by_name($stmt, ':product_category_name', $product_category_name);
            oci_bind_by_name($stmt, ':rfid_tag', $rfid_tag);

            $result = oci_execute($stmt);
            if ($result) {
                move_uploaded_file($image_tmp_name, $image_folder);
                $success_msg[] = 'Product added successfully!';
            } else {
                $e = oci_error($stmt);
                $warning_msg[] = 'Database error: ' . $e['message'];
            }
            oci_free_statement($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/add_product.css">
</head>
<body>
    <div class="form-container">
        <!-- Dashboard Button -->
        <a href="traderdashboard.php" class="dashboard-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="form-header with-dashboard-btn">
            <h3>Add New Product</h3>
            <p>Complete the form below to add a new product to your inventory</p>
        </div>

        <?php if(!empty($warning_msg)): ?>
            <div class="message warning-msg">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $warning_msg[0]; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($success_msg)): ?>
            <div class="message success-msg">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg[0]; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Basic Information Section -->
                <div class="full-width">
                    <div class="form-section-title">Basic Information</div>
                </div>
                
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label>Product Name <span>*</span></label>
                        <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label>Price (£) <span>*</span></label>
                        <input type="number" name="price" class="form-control" placeholder="Enter product price" required min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Shop <span>*</span></label>
                        <select name="shop_id" class="form-control" required>
                            <option value="">Select Shop</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?php echo $shop['shop_id']; ?>"><?php echo htmlspecialchars($shop['shop_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Status <span>*</span></label>
                        <select name="product_status" class="form-control" required>
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Limited Stock">Limited Stock</option>
                        </select>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="form-group">
                        <label>Stock Quantity <span>*</span></label>
                        <input type="number" name="stock" class="form-control" placeholder="Enter stock quantity" required min="0">
                    </div>

                    <div class="form-group">
                        <label>Minimum Order <span>*</span></label>
                        <input type="number" name="min_order" class="form-control" placeholder="Enter minimum order quantity" required min="1">
                    </div>

                    <div class="form-group">
                        <label>Maximum Order <span>*</span></label>
                        <input type="number" name="max_order" class="form-control" placeholder="Enter maximum order quantity" required min="1">
                    </div>

                    <div class="form-group">
                        <label>Product Image <span>*</span></label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Select an image file (Max: 2MB)
                                <input type="file" name="product_image" required accept="image/*">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="full-width">
                    <div class="form-section-title">Additional Information</div>
                </div>

                <!-- Description Field -->
                <div class="full-width">
                    <div class="form-group">
                        <label>Description <span>*</span></label>
                        <textarea name="description" class="form-control" placeholder="Provide a detailed description of your product" required></textarea>
                    </div>
                </div>

                <!-- Allergy Information Field -->
                <div class="full-width">
                    <div class="form-group">
                        <label>Allergy Information</label>
                        <textarea name="allergy_information" class="form-control" placeholder="List any allergens or dietary information (e.g., contains nuts, gluten-free, suitable for vegetarians)"></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="button" class="submit-btn" id="enable-rfid">🔄 Scan via RFID</button>
                <p id="scan-status" style="color: green; display: none;">Scanning RFID...</p>

                <button type="submit" class="submit-btn" name="add">
                    <i class="fas fa-plus-circle"></i> Add Product
                </button>
            </div>
        </form>
    </div>

    <script>
        let scanning = false;

        document.getElementById('enable-rfid').addEventListener('click', () => {
            scanning = true;
            document.getElementById('scan-status').style.display = 'block';
            document.getElementById('scan-status').textContent = "🔄 Scanning RFID...";

            // Trigger Python script via PHP backend
            fetch('../../trigger_rfid.php')
                .then(() => pollRFID());
        });

        function pollRFID() {
            if (!scanning) return;

            fetch('../../rfid_scan.json?' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    if (data && data.data && data.data.product_name) {
                        // Fill the form
                        document.querySelector('[name="product_name"]').value = data.data.product_name;
                        document.querySelector('[name="description"]').value = data.data.description;
                        document.querySelector('[name="price"]').value = data.data.price;
                        document.querySelector('[name="stock"]').value = data.data.stock;
                        document.querySelector('[name="shop_id"]').value = data.data.shop_id;

                        document.getElementById('scan-status').textContent = "✔️ RFID scanned successfully!";
                        scanning = false;
                    } else {
                        setTimeout(pollRFID, 1000);
                    }
                })
                .catch(() => setTimeout(pollRFID, 1000));
        }
    </script>
</body>
</html>