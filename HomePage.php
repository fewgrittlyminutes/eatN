<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eat@N | Discover Campus Food Easily</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Custom Food Theme CSS -->
  <link rel="stylesheet" href="css/food-theme.css">
  
  <!-- SEO Meta Tags -->
  <meta name="description" content="Discover the best food options at NSBM Green University. From fresh meals to quick snacks, find everything you need at Eat@N.">
  <meta name="keywords" content="NSBM food, campus dining, student meals, food delivery, university restaurants">
  <meta name="author" content="Eat@N Team">
  
  <!-- Open Graph Meta Tags -->
  <meta property="og:title" content="Eat@N - Campus Food Discovery">
  <meta property="og:description" content="Your one-stop solution for discovering the best food options at NSBM Green University">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
  
  <!-- Favicon -->
  <link rel="icon" href="images/favicon.ico" type="image/x-icon">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-food">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 animate-fade-in-up">
        <h1 class="display-1 mb-4">
          Discover Campus Food 
          <span class="text-warning">Easily</span>
        </h1>
        <p class="lead mb-4">
          Your one-stop solution for exploring the best food options at NSBM Green University. 
          From fresh meals to quick snacks, find everything you need right here!
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="#featured-shops" class="btn btn-food btn-lg">
            <i class="fas fa-utensils me-2"></i>Explore Restaurants
          </a>
          <a href="Gallery.html" class="btn btn-outline-food btn-lg">
            <i class="fas fa-images me-2"></i>View Gallery
          </a>
        </div>
      </div>
      <div class="col-lg-6 text-center">
        <img src="images/NSBM.jpg" alt="NSBM Campus Food" class="img-fluid rounded-food-lg shadow-food-heavy" style="max-height: 400px;">
      </div>
    </div>
  </div>
</section>

<!-- Quick Stats Section -->
<section class="section-food bg-food-light">
  <div class="container">
    <div class="row text-center">
      <div class="col-md-3 mb-4">
        <div class="p-4">
          <i class="fas fa-store display-4 text-primary mb-3"></i>
          <h3 class="h2 text-primary">5+</h3>
          <p class="text-muted">Restaurants</p>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="p-4">
          <i class="fas fa-hamburger display-4 text-primary mb-3"></i>
          <h3 class="h2 text-primary">100+</h3>
          <p class="text-muted">Menu Items</p>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="p-4">
          <i class="fas fa-users display-4 text-primary mb-3"></i>
          <h3 class="h2 text-primary">1000+</h3>
          <p class="text-muted">Happy Students</p>
        </div>
      </div>
      <div class="col-md-3 mb-4">
        <div class="p-4">
          <i class="fas fa-clock display-4 text-primary mb-3"></i>
          <h3 class="h2 text-primary">7AM-8PM</h3>
          <p class="text-muted">Service Hours</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Shops Section -->
<section id="featured-shops" class="section-food">
  <div class="container">
    <div class="section-title">
      <h2>Featured Restaurants</h2>
      <p class="section-subtitle">
        Discover our handpicked selection of campus dining destinations, each offering unique flavors and experiences
      </p>
    </div>
    
    <div class="row g-4">
      <!-- THE EDGE Shop -->
      <div class="col-lg-4 col-md-6">
        <div class="card-food card-edge h-100">
          <div class="position-relative">
            <img src="images/Edge.jpeg" class="card-img-top" alt="THE EDGE Restaurant">
            <div class="menu-item-badge">Popular</div>
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">
              <i class="fas fa-building me-2"></i>THE EDGE
            </h5>
            <p class="card-text flex-grow-1">
              Your premium dining destination featuring fresh meals, student-friendly prices, and a modern atmosphere perfect for both quick bites and relaxed dining.
            </p>
            <div class="mt-3">
              <a href="THE EDGE.html" class="btn btn-secondary-food">
                <i class="fas fa-eye me-2"></i>View Restaurant
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Finagle Shop -->
      <div class="col-lg-4 col-md-6">
        <div class="card-food card-finagle h-100">
          <div class="position-relative">
            <img src="images/Finagle.jpg" class="card-img-top" alt="Finagle Café">
            <div class="menu-item-badge">Hot Deals</div>
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">
              <i class="fas fa-coffee me-2"></i>Finagle Café
            </h5>
            <p class="card-text flex-grow-1">
              Artisanal baked goods, specialty coffee, and hearty hot meals. Famous for our signature shawarma and freshly baked pastries that students love.
            </p>
            <div class="mt-3">
              <a href="finagle.php" class="btn btn-food">
                <i class="fas fa-shopping-cart me-2"></i>Order Now
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Vending Machine -->
      <div class="col-lg-4 col-md-6">
        <div class="card-food card-vending h-100">
          <div class="position-relative">
            <img src="images/VenMach4.jpeg" class="card-img-top" alt="Smart Vending Machine">
            <div class="menu-item-badge">24/7</div>
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">
              <i class="fas fa-shopping-cart me-2"></i>Smart Vending
            </h5>
            <p class="card-text flex-grow-1">
              Quick snacks and refreshing drinks available 24/7. Perfect for those late-night study sessions or when you need a quick energy boost between classes.
            </p>
            <div class="mt-3">
              <a href="VendingMachine.html" class="btn btn-outline-food">
                <i class="fas fa-list me-2"></i>View Items
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- View All Restaurants Button -->
    <div class="text-center mt-5">
      <a href="#all-restaurants" class="btn btn-secondary-food btn-lg">
        <i class="fas fa-utensils me-2"></i>View All Restaurants
      </a>
    </div>
  </div>
</section>

<!-- Popular Picks Section -->
<section class="container my-5">
  <h2 class="section-title">What’s Popular</h2>
  <div class="row text-center">
    <!-- Tandoor Card -->
    <div class="col-md-6 mb-4 d-flex">
      <div class="custom-card flex-fill">
        <img src="images/E4.jpeg" class="img-fluid mb-3" alt="Popular 1">
        <h5 class="text-white">Tandoor</h5>
        <p class="text-white">Tandoor is the most visited shop at THE EDGE.&nbsp;</p>
        <a href="tandoor.php" class="btn btn-danger">View Menu</a>
      </div>
    </div>

    <!-- Shawarma Card -->
    <div class="col-md-6 mb-4 d-flex">
      <div class="custom-card flex-fill">
        <img src="images/SHAW.jpg" class="img-fluid mb-3" alt="Popular 2">
        <h5 class="text-white">Shawarma</h5>
        <p class="text-white">The Finagle shawarma is the highest rated food item at NSBM.&nbsp;&nbsp;</p>
        <a href="finagle.php" class="btn btn-danger">Buy Now</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Add animation class to elements when they come into view
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fade-in-up');
      }
    });
  }, observerOptions);

  // Observe all card elements
  document.querySelectorAll('.card-food, .section-title').forEach(el => {
    observer.observe(el);
  });
</script>
</body>
</html>
