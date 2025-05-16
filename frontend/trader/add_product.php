<?php
    require '../../backend/db_connection.php';

    $conn = getDBConnection();
    if(!$conn) {
        die("Database connection failed");
    }

    if(isset($_POST['add'])){
        $product_name = $_POST['product_name'];
        $product_name = filter_var($product_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $description = $_POST['description'];
        $description = filter_var($description, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $price = $_POST['price'];
        $price = filter_var($price, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $stock = $_POST['stock'];
        $stock = filter_var($stock, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $min_order = $_POST['min_order'];
        $min_order = filter_var($min_order, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $max_order = $_POST['max_order'];
        $max_order = filter_var($max_order, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $product_status = $_POST['product_status'];
        $product_status = filter_var($product_status, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $image = $_FILES['product_image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $rename = create_unique_id().'.'.$ext;
        $image_tmp_name = $_FILES['product_image']['tmp_name'];
        $image_size = $_FILES['product_image']['size'];
        $image_folder = 'uploaded_files/'.$rename;

        if($image_size > 2000000){
            $warning_msg[] = 'Image size is too large!';
         }else{
            $add_product = $conn->prepare("INSERT INTO `product`(product_name, description, price, stock, min_order, max_order, product_image, add_date, product_status) VALUES(?,?,?,?,?,?,?,?,?)");
            $add_date = date('Y-m-d');
            
            $add_product->execute([$product_name, $description, $price, $stock, $min_order, $max_order, $rename, $add_date, $product_status]);
            
            move_uploaded_file($image_tmp_name, $image_folder);
            $success_msg[] = 'Product added!';
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
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 20px;
    }
    
    .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .form-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .form-header h3 {
        margin: 0;
        color: #333;
    }
    
    .form-header p {
        margin: 5px 0 0;
        color: #666;
        font-size: 14px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #444;
    }
    
    .form-group label span {
        color: red;
    }
    
    .form-control {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }
    
    .submit-btn {
        grid-column: span 2;
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .submit-btn:hover {
        background-color: #45a049;
    }
    
    .file-input-wrapper {
        margin-top: 5px;
    }
    
    .file-input-label {
        display: block;
        padding: 8px 10px;
        background-color: #f0f0f0;
        border: 1px dashed #ccc;
        border-radius: 4px;
        text-align: center;
        cursor: pointer;
    }
    
    .file-input-label input[type="file"] {
        display: none;
    }
    
    .message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    
    .warning-msg {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    
    .success-msg {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
</style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h3>Product Information</h3>
            <p>Add new product to your inventory</p>
        </div>

        <?php if(!empty($warning_msg)): ?>
            <div class="message warning-msg">
                <?php echo $warning_msg[0]; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($success_msg)): ?>
            <div class="message success-msg">
                <?php echo $success_msg[0]; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label>Product Name <span>*</span></label>
                        <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required maxlength="50">
                    </div>

                    <div class="form-group">
                        <label>Description <span>*</span></label>
                        <textarea name="description" class="form-control" placeholder="Enter product description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Price <span>*</span></label>
                        <input type="number" name="price" class="form-control" placeholder="Enter product price" required min="0" step="0.01">
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
                        <label>Product Status <span>*</span></label>
                        <select name="product_status" class="form-control" required>
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Image <span>*</span></label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose an image file
                                <input type="file" name="product_image" required accept="image/*">
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" name="add">Add Product</button>
            </div>
        </form>
    </div>
</body>
</html>


