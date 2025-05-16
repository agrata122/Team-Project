<?php
require 'C:\xampp\htdocs\E-commerce\backend\db_connection.php';

$conn = getDBConnection();
if(!$conn) {
    die("Database connection failed");
}

// Ensure user has a unique ID
if(isset($_COOKIE['user_id'])){
    $user_id = $_COOKIE['user_id'];
}else{
    setcookie('user_id', uniqid(), time() + 60*60*24*30, "/");
    $user_id = $_COOKIE['user_id'];
}

// Handle Add to Cart request
if(isset($_POST['add_to_cart'])){
   $product_id = $_POST['product_id'];
   $qty = $_POST['qty'];

   $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);
   $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);

   // Check if user has an existing cart
   $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $check_cart->execute([$user_id]);

   if($check_cart->rowCount() == 0){
       // Create a new cart for the user
       $insert_cart = $conn->prepare("INSERT INTO `cart` (user_id, add_date) VALUES (?, NOW())");
       $insert_cart->execute([$user_id]);
       $cart_id = $conn->lastInsertId();
   } else {
       // Get the existing cart ID
       $cart = $check_cart->fetch(PDO::FETCH_ASSOC);
       $cart_id = $cart['cart_id'];
   }

   // Check if product is already in the cart
   $check_product = $conn->prepare("SELECT * FROM `product_cart` WHERE cart_id = ? AND product_id = ?");
   $check_product->execute([$cart_id, $product_id]);

   if($check_product->rowCount() > 0){
      $message = "Product already in cart!";
      $messageType = "error"; // Use 'success' or 'error' to define colors
      
  
   } else {
       // Add product to product_cart table
       $insert_product = $conn->prepare("INSERT INTO `product_cart` (cart_id, product_id, quantity) VALUES (?, ?, ?)");
       $insert_product->execute([$cart_id, $product_id, $qty]);

       // Update the stock
       $update_stock = $conn->prepare("UPDATE `product` SET stock = stock - ? WHERE product_id = ?");
       $update_stock->execute([$qty, $product_id]);

       $message = "Product added to cart successfully!";
       $messageType = "success";
       
   }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Products</title>
   <style>
   * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
   }

   body {
      background-color: #f5f5f5;
      padding: 20px;
   }

   .heading {
      text-align: center;
      font-size: 2.5rem;
      margin-bottom: 30px;
      color: #333;
   }

   .products .box-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      max-width: 1200px;
      margin: auto;
   }

   .products .box {
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.3s;
   }

   .products .box:hover {
      transform: translateY(-5px);
   }

   .products .image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 15px;
   }

   .products .name {
      font-size: 1.4rem;
      font-weight: bold;
      margin-bottom: 10px;
      color: #222;
   }

   .products .description {
      font-size: 0.95rem;
      color: #555;
      margin-bottom: 15px;
   }

   .products .flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
   }

   .products .price {
      font-size: 1.2rem;
      color: #28a745;
      font-weight: bold;
   }

   .products .stock {
      font-size: 0.9rem;
      color: #999;
   }

   .products .qty {
      width: 60px;
      padding: 5px;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      text-align: center;
   }

   .btn, .delete-btn {
      display: inline-block;
      padding: 10px 15px;
      font-size: 1rem;
      border: none;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
   }

   .btn {
      background-color: #007bff;
      color: white;
      margin-right: 10px;
   }

   .btn:hover {
      background-color: #0056b3;
   }

   .delete-btn {
      background-color: #ffc107;
      color: #000;
   }

   .delete-btn:hover {
      background-color: #e0a800;
   }

   .empty {
      text-align: center;
      color: #777;
      font-size: 1.2rem;
      margin-top: 50px;
   }
</style>

   <!-- Toastify CSS -->
   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

   <!-- Toastify JS -->
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

</head>
<body>

<section class="products">
   <h1 class="heading">All Products</h1>
   <div class="box-container">

   <?php 
      $select_products = $conn->prepare("SELECT * FROM `product`");
      $select_products->execute();
      if($select_products->rowCount() > 0){
         while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="POST" class="box">
      <img src="/E-commerce/frontend/trader/uploaded_files/<?= $fetch_product['product_image']; ?>" class="image" alt="">
      <h3 class="name"><?= $fetch_product['product_name'] ?></h3>
      <input type="hidden" name="product_id" value="<?= $fetch_product['product_id']; ?>">
      <p class="description"><?= $fetch_product['description'] ?></p>
      <div class="flex">
         <p class="price">RS. <?= $fetch_product['price'] ?></p>
         <p class="stock">stock: <?= $fetch_product['stock'] ?></p>
         <input type="number" name="qty" required min="1" max="<?= $fetch_product['stock'] ?>" value="1" class="qty">
      </div>
      <input type="submit" name="add_to_cart" value="Add to Cart" class="btn">
      <a href="/E-commerce/frontend/Includes/cart/shopping_cart.php?get_id=<?= $fetch_product['product_id']; ?>" class="delete-btn">View Details</a>
   </form>
   <?php
         }
      }else{
         echo '<p class="empty">No products found!</p>';
      }
   ?>
   
   </div>
</section>

<script>
   document.addEventListener("DOMContentLoaded", function () {
       // Get message from PHP
       let message = "<?= isset($message) ? $message : ''; ?>";
       let messageType = "<?= isset($messageType) ? $messageType : ''; ?>";

       // Check if there's a message to show
       if (message !== "") {
           Toastify({
               text: message,
               duration: 3000,
               close: true,
               gravity: 'top',
               position: 'right',
               backgroundColor: messageType === "success" ? "green" : "red",
           }).showToast();
       }
   });
</script>


</body>
</html>
