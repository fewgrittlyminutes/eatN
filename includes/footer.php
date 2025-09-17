<!-- Footer -->
<footer class="footer-food mt-5">
  <div class="container">
    <div class="row">
      <!-- Brand Section -->
      <div class="col-lg-4 col-md-6 mb-4">
        <h5 class="mb-3">
          <i class="fas fa-utensils me-2"></i>
          Eat@N
        </h5>
        <p class="mb-3">
          Your one-stop solution for discovering the best food options at NSBM Green University. 
          From fresh meals to quick snacks, we've got you covered!
        </p>
        <div class="social-links">
          <a href="#" class="me-3" title="Facebook">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="#" class="me-3" title="Instagram">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="me-3" title="Twitter">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="#" title="YouTube">
            <i class="fab fa-youtube"></i>
          </a>
        </div>
      </div>
      
      <!-- Quick Links -->
      <div class="col-lg-2 col-md-6 mb-4">
        <h6 class="mb-3">Quick Links</h6>
        <ul class="list-unstyled">
          <li class="mb-2">
            <a href="HomePage.php">
              <i class="fas fa-angle-right me-2"></i>Home
            </a>
          </li>
          <li class="mb-2">
            <a href="AboutUs.html">
              <i class="fas fa-angle-right me-2"></i>About Us
            </a>
          </li>
          <li class="mb-2">
            <a href="Gallery.html">
              <i class="fas fa-angle-right me-2"></i>Gallery
            </a>
          </li>
          <li class="mb-2">
            <a href="ContactUs.html">
              <i class="fas fa-angle-right me-2"></i>Contact Us
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Restaurants -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 class="mb-3">Our Restaurants</h6>
        <ul class="list-unstyled">
          <li class="mb-2">
            <a href="finagle.php">
              <i class="fas fa-coffee me-2"></i>Finagle Café
            </a>
          </li>
          <li class="mb-2">
            <a href="tandoor.php">
              <i class="fas fa-fire me-2"></i>Tandoor
            </a>
          </li>
          <li class="mb-2">
            <a href="serenity.php">
              <i class="fas fa-leaf me-2"></i>Serenity
            </a>
          </li>
          <li class="mb-2">
            <a href="THE EDGE.html">
              <i class="fas fa-building me-2"></i>THE EDGE
            </a>
          </li>
          <li class="mb-2">
            <a href="VendingMachine.html">
              <i class="fas fa-shopping-cart me-2"></i>Vending Machine
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Contact Info -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 class="mb-3">Contact Info</h6>
        <div class="contact-info">
          <div class="mb-2">
            <i class="fas fa-map-marker-alt me-2"></i>
            <small>NSBM Green University<br>Mahenwatta, Pitipana<br>Homagama 10206</small>
          </div>
          <div class="mb-2">
            <i class="fas fa-phone me-2"></i>
            <small>+94 11 544 5000</small>
          </div>
          <div class="mb-2">
            <i class="fas fa-envelope me-2"></i>
            <small>info@eatn.nsbm.ac.lk</small>
          </div>
          <div class="mb-2">
            <i class="fas fa-clock me-2"></i>
            <small>Mon - Fri: 7:00 AM - 8:00 PM<br>Sat - Sun: 8:00 AM - 6:00 PM</small>
          </div>
        </div>
      </div>
    </div>
    
    <hr class="my-4" style="border-color: rgba(255, 107, 53, 0.3);">
    
    <!-- Bottom Footer -->
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="mb-0">
          © <?php echo date('Y'); ?> Eat@N - All Rights Reserved
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="PrivacyPolicy.html" class="me-3">Privacy Policy</a>
        <a href="#" class="me-3">Terms of Service</a>
        <a href="#">Cookie Policy</a>
      </div>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-food position-fixed" 
        style="bottom: 20px; right: 20px; z-index: 1000; display: none; border-radius: 50%; width: 50px; height: 50px;">
  <i class="fas fa-arrow-up"></i>
</button>

<style>
  /* Footer specific styles that complement the main CSS */
  .footer-food a {
    transition: all 0.3s ease;
  }
  
  .footer-food .social-links a {
    display: inline-block;
    width: 40px;
    height: 40px;
    background: rgba(255, 107, 53, 0.2);
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    transition: all 0.3s ease;
  }
  
  .footer-food .social-links a:hover {
    background: var(--primary-orange, #ff6b35);
    color: white;
    transform: translateY(-3px);
  }
  
  .contact-info i {
    color: var(--primary-orange, #ff6b35);
    width: 20px;
  }
  
  #backToTop {
    transition: all 0.3s ease;
  }
  
  #backToTop:hover {
    transform: translateY(-3px);
  }
</style>

<script>
  // Back to Top Button Functionality
  document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.getElementById('backToTop');
    
    // Show/hide back to top button based on scroll position
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        backToTopButton.style.display = 'block';
      } else {
        backToTopButton.style.display = 'none';
      }
    });
    
    // Smooth scroll to top when button is clicked
    backToTopButton.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  });
</script>