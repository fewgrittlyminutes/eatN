<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_page_no_ext = pathinfo($current_page, PATHINFO_FILENAME);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$account_type = $is_logged_in ? $_SESSION['account_type'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-food fixed-top">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center" href="HomePage.php">
      <i class="fas fa-utensils me-2"></i>
      Eat@N
    </a>
    
    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Navigation Links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page_no_ext == 'HomePage') ? 'active' : ''; ?>" 
             href="HomePage.php">
            <i class="fas fa-home me-1"></i>Home
          </a>
        </li>
        
        <!-- Restaurants Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo (in_array($current_page_no_ext, ['cuisin', 'finagle', 'freshjuice', 'serenity', 'tandoor']) ? 'active' : ''); ?>" 
             href="#" id="restaurantsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-store me-1"></i>Restaurants
          </a>
          <ul class="dropdown-menu" aria-labelledby="restaurantsDropdown">
            <li><a class="dropdown-item" href="finagle.php">
              <i class="fas fa-coffee me-2"></i>Finagle Café
            </a></li>
            <li><a class="dropdown-item" href="tandoor.php">
              <i class="fas fa-fire me-2"></i>Tandoor
            </a></li>
            <li><a class="dropdown-item" href="serenity.php">
              <i class="fas fa-leaf me-2"></i>Serenity
            </a></li>
            <li><a class="dropdown-item" href="freshjuice.php">
              <i class="fas fa-glass-whiskey me-2"></i>Fresh Juice
            </a></li>
            <li><a class="dropdown-item" href="cuisin.php">
              <i class="fas fa-hamburger me-2"></i>Cuisin
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="THE EDGE.html">
              <i class="fas fa-building me-2"></i>THE EDGE
            </a></li>
            <li><a class="dropdown-item" href="VendingMachine.html">
              <i class="fas fa-shopping-cart me-2"></i>Vending Machine
            </a></li>
          </ul>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page_no_ext == 'Gallery') ? 'active' : ''; ?>" 
             href="Gallery.html">
            <i class="fas fa-images me-1"></i>Gallery
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page_no_ext == 'AboutUs') ? 'active' : ''; ?>" 
             href="AboutUs.html">
            <i class="fas fa-info-circle me-1"></i>About Us
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page_no_ext == 'ContactUs') ? 'active' : ''; ?>" 
             href="ContactUs.html">
            <i class="fas fa-phone me-1"></i>Contact
          </a>
        </li>
      </ul>
      
      <!-- User Authentication Section -->
      <ul class="navbar-nav">
        <?php if ($is_logged_in): ?>
          <!-- User is logged in -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle me-2"></i>
              <span class="d-none d-md-inline">Welcome, <?php echo htmlspecialchars($username); ?></span>
              <span class="d-md-none">Account</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><h6 class="dropdown-header">
                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($username); ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($account_type); ?></small>
              </h6></li>
              <li><hr class="dropdown-divider"></li>
              <?php if ($account_type === 'Admin'): ?>
                <li><a class="dropdown-item" href="admin.php">
                  <i class="fas fa-cog me-2"></i>Admin Panel
                </a></li>
                <li><a class="dropdown-item" href="view_users.php">
                  <i class="fas fa-users me-2"></i>View Users
                </a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="HomePage.php">
                <i class="fas fa-home me-2"></i>Dashboard
              </a></li>
              <li><a class="dropdown-item text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <!-- User is not logged in -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($current_page_no_ext == 'Login') ? 'active' : ''; ?>" 
               href="Login.php">
              <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-food ms-2" href="SignUp.php">
              <i class="fas fa-user-plus me-1"></i>Sign Up
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Add padding to body to account for fixed navbar -->
<style>
  body { 
    padding-top: 80px; 
  }
  
  /* Custom dropdown styles */
  .dropdown-menu {
    background: white;
    border: none;
    border-radius: var(--border-radius-md, 12px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    border-top: 3px solid var(--primary-orange, #ff6b35);
  }
  
  .dropdown-item {
    padding: 0.75rem 1.25rem;
    color: var(--gray-800, #343a40);
    transition: all 0.3s ease;
  }
  
  .dropdown-item:hover {
    background: var(--primary-orange, #ff6b35);
    color: white;
    transform: translateX(5px);
  }
  
  .dropdown-item:focus {
    background: var(--primary-orange, #ff6b35);
    color: white;
  }
  
  .dropdown-header {
    color: var(--dark-brown, #3c2415);
    font-weight: 600;
    padding: 1rem 1.25rem 0.5rem;
  }
  
  .dropdown-divider {
    margin: 0.5rem 0;
    border-color: var(--gray-200, #e9ecef);
  }
  
  /* Mobile responsiveness */
  @media (max-width: 991.98px) {
    .navbar-nav .dropdown-menu {
      background: transparent;
      border: none;
      box-shadow: none;
      padding-left: 1rem;
    }
    
    .navbar-nav .dropdown-item {
      color: var(--cream, #faf0e6);
      padding: 0.5rem 1rem;
    }
    
    .navbar-nav .dropdown-item:hover {
      background: rgba(255, 107, 53, 0.2);
      transform: none;
    }
    
    body {
      padding-top: 76px;
    }
  }
  
  /* Navbar notification badge (for future use) */
  .notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: var(--accent-red, #d73527);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
  }
</style>