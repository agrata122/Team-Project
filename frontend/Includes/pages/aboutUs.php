<?php
// aboutus.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About Us – FresGrub</title>
  <link rel="stylesheet" href="/E-commerce/frontend/assets/CSS/aboutus.css">
</head>
<body>
    <header>
    <?php
include '../../Includes/header.php'; 
?>
        
    </header>

  <div class="container">

    <!-- Hero -->
    <section class="hero">
      <h1>Trust in our experience</h1>
    </section>

    <!-- About Intro -->
    <section class="about-intro">
      <div>
        <p>At FresGrub, we have been proudly serving our community, connecting customers with the freshest local products. Our commitment to quality and convenience drives us to go the extra mile, ensuring an exceptional shopping experience. We are passionate about supporting local traders and continuously improving to bring even more value to our customers.</p>
      </div>
      <div>
        <img src="/E-commerce/frontend/assets/Images/about-intro.jpg" alt="Intro Image">
      </div>
    </section>

    <!-- Our Story -->
    <section class="story">
      <h2>Our Story</h2>
      <div class="story-content">
        <div class="story-text">
          <p>When our local butchers, bakers, fishmongers, greengrocers, and deli owners saw national chains edging into our beloved suburb, they decided to innovate, not compete. FresGrub was created to unite their expertise under one digital roof, ensuring that busy locals could access high‑quality, ethically sourced products without sacrificing the personal touch that makes small businesses special.</p>
        </div>
        <div>
          <img src="/E-commerce/frontend/assets/Images/story.png" alt="Our Story">
        </div>
      </div>
    </section>

    <!-- Promise -->
    <section class="promise">
      <h3>The FresGrub Promise</h3>
      <ul>
        <li><strong>Curated Quality:</strong> Every product is handpicked by our traders, with no overlaps or compromises.</li>
        <li><strong>Click & Collect Convenience:</strong> Order by midnight, collect the next day. Our 24‑hour model fits your schedule.</li>
        <li><strong>Transparency:</strong> Know exactly who grew, baked, or caught what you buy. Meet the faces behind your food.</li>
      </ul>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
      <h2>What Our Clients Say?</h2>
      <div class="cards">
        <div class="card">
          <img class="avatar" src="/E-commerce/frontend/assets/Images/james.png" alt="James T">
          <p>FresGrub has made shopping local so much easier! I can now get fresh produce from my favorite local shops without rushing before they close.</p>
          <div class="author">James T.</div>
        </div>
        <div class="card">
          <img class="avatar" src="/E-commerce/frontend/assets/Images/robert.png" alt="Robert">
          <p>FresGrub has given my business a whole new way to reach customers. I can now sell my fresh baked goods beyond my regular hours.</p>
          <div class="author">Robert L., Local Baker</div>
        </div>
        <div class="card">
          <img class="avatar" src="/E-commerce/frontend/assets/Images/emma.png" alt="Emma R">
          <p>I love how convenient FresGrub is! I can browse and buy from multiple stores in one go. Fresh, local, and hassle‑free!</p>
          <div class="author">Emma R.</div>
        </div>
      </div>
    </section>

    <!-- Community Gallery -->
    <section class="community">
      <h2>Our Community</h2>
      <div class="grid">
        <img src="/E-commerce/frontend/assets/Images/1.jpg" alt="Community 1">
        <img src="/E-commerce/frontend/assets/Images/2.jpg" alt="Community 2">
        <img src="/E-commerce/frontend/assets/Images/3.jpg" alt="Community 3">
        <img src="/E-commerce/frontend/assets/Images/4.jpg" alt="Community 4">
        <img src="/E-commerce/frontend/assets/Images/5.jpg" alt="Community 5">
        <img src="/E-commerce/frontend/assets/Images/6.jpg" alt="Community 6">
      </div>
    </section>

  </div>

</body>
<?php
include '../../Includes/footer.php';
?>
</html>
