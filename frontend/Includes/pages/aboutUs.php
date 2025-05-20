<?php
// aboutus.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About Us – FresGrub</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      color: #333;
      line-height: 1.6;
      background-color: #fff;
    }

    img {
      max-width: 100%;
      display: block;
    }

    .container {
      width: 90%;
      max-width: 1200px;
      margin: auto;
    }

    /* HERO */
    .hero {
      position: relative;
      height: 350px;
      background: url('/E-commerce/frontend/assets/Images/hero-about.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero h1 {
      color: #fff;
      font-size: 2.5rem;
      text-shadow: 0 2px 5px rgba(0,0,0,0.7);
    }

    /* ABOUT INTRO */
    .about-intro {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #f7f0ef;
      padding: 2rem;
      border-radius: 12px;
      margin: 2rem 0;
      gap: 2rem;
    }

    .about-intro p {
      font-size: 1rem;
    }

    .about-intro img {
      border-radius: 12px;
    }

    /* OUR STORY */
    .story {
      margin: 3rem 0;
      text-align: center;
    }

    .story-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      margin-top: 2rem;
      gap: 2rem;
    }

    .story-text {
      background: #d9f1d6;
      padding: 1.5rem;
      border-radius: 12px;
      text-align: left;
    }

    .story-text p {
      font-size: 1rem;
    }

    /* PROMISE SECTION */
    .promise {
      background: #ececec;
      padding: 2rem;
      border-radius: 12px;
      margin-bottom: 3rem;
    }

    .promise h3 {
      margin-bottom: 1rem;
    }

    .promise ul {
      padding-left: 1.2rem;
    }

    .promise li {
      margin-bottom: 0.75rem;
    }

    /* TESTIMONIALS */
    .testimonials {
      margin: 3rem 0;
      text-align: center;
    }

    .testimonials .cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
      margin-top: 2rem;
    }

    .testimonials .card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 12px;
      padding: 1rem;
      text-align: left;
    }

    .card img.avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      float: left;
      margin-right: 0.75rem;
    }

    .card p {
      font-size: 0.95rem;
    }

    .card .author {
      font-weight: bold;
      margin-top: 1rem;
    }

    /* COMMUNITY GRID */
    .community {
      margin: 3rem 0;
      text-align: center;
    }

    .community .grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-top: 2rem;
    }

    .community .grid img {
      border-radius: 8px;
    }

    /* ========== RESPONSIVE BREAKPOINTS ========== */
    @media (max-width: 1024px) {
      .about-intro, .story-content {
        grid-template-columns: 1fr;
        text-align: center;
      }

      .story-text {
        text-align: left;
      }

      .testimonials .cards {
        grid-template-columns: 1fr 1fr;
      }

      .community .grid {
        grid-template-columns: 1fr 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
      .hero {
        height: 220px;
      }

      .hero h1 {
        font-size: 1.8rem;
      }

      .about-intro, .story-content {
        gap: 1rem;
      }

      .testimonials .cards {
        grid-template-columns: 1fr;
      }

      .community .grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 480px) {
      .community .grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
