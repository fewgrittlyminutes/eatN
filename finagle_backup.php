<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$error = '';
$success = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $login_required = true;
} else {
    $login_required = false;
}

// Fetch shop details (shop_id = 1 for Finagle)
$shop_id = 1;
$shop = null;
try {
    $stmt = $conn->prepare("SELECT shop_name, description FROM shops WHERE shop_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing shop query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $shop = $result->fetch_assoc();
    } else {
        $shop = ['shop_name' => 'Finagle Café', 'description' => 'Artisanal café serving fresh baked goods, specialty coffee, and hearty meals'];
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error loading shop details: " . $e->getMessage();
    $shop = ['shop_name' => 'Finagle Café', 'description' => 'Artisanal café serving fresh baked goods, specialty coffee, and hearty meals'];
}

// Fetch menu items from the database for this shop
$menu_items = [];
try {
    $stmt = $conn->prepare("SELECT item_name, price, image_url FROM menu_item WHERE shop_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing menu query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error loading menu items: " . $e->getMessage();
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$login_required) {
    try {
        // Validate and sanitize input
        $item_name = filter_var($_POST['item_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $item_price = filter_var($_POST['item_price'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $quantity = filter_var($_POST['quantity'] ?? '', FILTER_SANITIZE_NUMBER_INT);
        $customer_name = filter_var($_POST['customer_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $user_id = $_SESSION['user_id'];

        // Validation
        if (empty($item_name)) {
            throw new Exception("Please select an item");
        }
        if (empty($item_price) || !is_numeric($item_price) || $item_price <= 0) {
            throw new Exception("Invalid item price");
        }
        if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0 || $quantity > 10) {
            throw new Exception("Quantity must be between 1 and 10");
        }
        if (empty($customer_name)) {
            throw new Exception("Customer name is required");
        }

        $total_price = $item_price * $quantity;

        // Insert order into database
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shop_id, item_name, quantity, total_price, customer_name, order_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Error preparing order query: " . $conn->error);
        }

        $stmt->bind_param("iisids", $user_id, $shop_id, $item_name, $quantity, $total_price, $customer_name);
        
        if ($stmt->execute()) {
            $success = "Order placed successfully! Your order for " . htmlspecialchars($item_name) . " (Qty: " . $quantity . ") has been received.";
        } else {
            throw new Exception("Error executing order query: " . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// If login is required and user is trying to order, redirect to login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $login_required) {
    header("Location: Login.php?error=Please login to place an order&redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finagle Café | Eat@N</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Custom Food Theme CSS -->
  <link rel="stylesheet" href="css/food-theme.css">
  
  <!-- SEO Meta Tags -->
  <meta name="description" content="Order from Finagle Café at NSBM. Fresh baked goods, specialty coffee, and delicious meals including our famous shawarma.">
  <meta name="keywords" content="Finagle café, NSBM food, coffee, shawarma, baked goods, student meals">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Restaurant Hero Section -->
<section class="hero-food" style="background: linear-gradient(135deg, var(--accent-red) 0%, #ff4757 100%);">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h1 class="display-2 mb-4">
          <i class="fas fa-coffee me-3"></i>
          <?php echo htmlspecialchars($shop['shop_name']); ?>
        </h1>
        <p class="lead mb-4">
          <?php echo htmlspecialchars($shop['description']); ?>
        </p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <span class="badge bg-light text-dark p-2">
            <i class="fas fa-clock me-2"></i>Open: 7:00 AM - 8:00 PM
          </span>
          <span class="badge bg-light text-dark p-2">
            <i class="fas fa-star me-2"></i>4.8/5 Rating
          </span>
          <span class="badge bg-light text-dark p-2">
            <i class="fas fa-truck me-2"></i>Quick Service
          </span>
        </div>
        <?php if ($login_required): ?>
          <div class="alert alert-warning">
            <i class="fas fa-info-circle me-2"></i>
            Please <a href="Login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="alert-link">login</a> to place an order
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6 text-center">
        <img src="images/Finagle.jpg" alt="Finagle Café" class="img-fluid rounded-food-lg shadow-food-heavy" style="max-height: 400px;">
      </div>
    </div>
  </div>
</section>

<!-- Alerts Section -->
<?php if (!empty($error) || !empty($success)): ?>
<section class="py-4">
  <div class="container">
    <?php if (!empty($error)): ?>
      <div class="alert alert-food-error alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-food-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- Menu Section -->
<section class="section-food">
  <div class="container">
    <div class="section-title">
      <h2><i class="fas fa-utensils me-3"></i>Our Menu</h2>
      <p class="section-subtitle">
        Discover our delicious selection of freshly prepared items, from artisanal coffee to hearty meals
      </p>
    </div>

    <?php if (empty($menu_items)): ?>
      <div class="text-center py-5">
        <i class="fas fa-utensils display-4 text-muted mb-3"></i>
        <h4 class="text-muted">Menu Coming Soon</h4>
        <p class="text-muted">We're updating our menu. Please check back later!</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($menu_items as $index => $item): ?>
          <div class="col-lg-4 col-md-6">
            <div class="menu-item-card position-relative">
              <!-- Item Image -->
              <?php if (!empty($item['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                     style="height: 200px; object-fit: cover;">
              <?php else: ?>
                <div class="bg-food-light d-flex align-items-center justify-content-center" style="height: 200px;">
                  <i class="fas fa-utensils display-4 text-muted"></i>
                </div>
              <?php endif; ?>
              
              <!-- Price Badge -->
              <div class="menu-item-price">
                Rs. <?php echo number_format($item['price'], 2); ?>
              </div>
              
              <!-- Popular Badge for first few items -->
              <?php if ($index < 2): ?>
                <div class="menu-item-badge">Popular</div>
              <?php endif; ?>
              
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                
                <?php if (!$login_required): ?>
                  <!-- Order Form -->
                  <form method="POST" class="mt-3">
                    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>">
                    <input type="hidden" name="item_price" value="<?php echo $item['price']; ?>">
                    
                    <div class="row g-2 align-items-end">
                      <div class="col-6">
                        <label for="quantity_<?php echo $index; ?>" class="form-label-food">Quantity</label>
                        <select name="quantity" id="quantity_<?php echo $index; ?>" class="form-control-food" required>
                          <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                          <?php endfor; ?>
                        </select>
                      </div>
                      <div class="col-6">
                        <button type="button" class="btn btn-food w-100" onclick="showOrderModal('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>, this.form)">
                          <i class="fas fa-shopping-cart me-1"></i>Order
                        </button>
                      </div>
                    </div>
                  </form>
                <?php else: ?>
                  <div class="mt-3">
                    <a href="Login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-food w-100">
                      <i class="fas fa-sign-in-alt me-2"></i>Login to Order
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
    }
    .menu-section h2 {
      text-align: center;
      font-weight: bold;
      margin-bottom: 40px;
      border-bottom: 3px solid #dc3545;
      display: inline-block;
      padding-bottom: 8px;
    }
    .menu-item {
      background: #111;
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    .menu-item img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .menu-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    .menu-item h5 {
      color: #dc3545;
      font-weight: bold;
      margin-top: 10px;
    }
    .menu-item p {
      color: #ccc;
    }
    .menu-item .price {
      font-weight: bold;
      color: #dc3545;
      font-size: 1.1rem;
    }
    .menu-item .btn-order {
      margin-top: 10px;
      background: #dc3545;
      color: #fff;
      border: none;
      transition: background 0.3s ease;
    }
    .menu-item .btn-order:hover {
      background: #a71d2a;
    }
    .footer {
      background: #000;
      color: #fff;
      padding: 40px 0;
      text-align: center;
      border-top: 3px solid #dc3545;
    }
    .footer a {
      color: #fff;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer a:hover {
      color: #dc3545;
    }
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="HomePage.php">Eat@N</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="HomePage.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="Gallery.html">Gallery</a></li>
        <li class="nav-item"><a class="nav-link" href="AboutUs.html">About Us</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="btn btn-danger ms-2" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-danger ms-2" href="Login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<!-- Hero -->
<section class="page-hero">
  <div class="container">
    <h1><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
    <p class="lead"><?php echo htmlspecialchars($shop['description']); ?></p>
  </div>
</section>
<!-- Food Menu -->
<section class="container menu-section">
  <h2>Food Menu</h2>
  <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success text-center"><?php echo $success; ?></div>
  <?php endif; ?>
  <div class="row g-4 mt-3">
    <?php foreach ($menu_items as $item): ?>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
          <h5><?php echo htmlspecialchars($item['item_name']); ?></h5>
          <p><?php echo htmlspecialchars($item['item_name']) === 'Shawarma' ? 'Savory wrap, grilled meats, fresh toppings, pure delight.' : (htmlspecialchars($item['item_name']) === 'Hot-Dog' ? 'Classic comfort, juicy sausage, perfect bun, satisfying.' : (htmlspecialchars($item['item_name']) === 'Spaghetti' ? 'Twirled pasta, rich sauce, Italian comfort, delicious.' : (htmlspecialchars($item['item_name']) === 'Kottu' ? 'Chopped roti, stir-fried, spicy, flavorful.' : 'Layers of pasta, cheese, sauce, baked goodness.'))); ?></p>
          <p class="price">Rs. <?php echo number_format($item['price'], 2); ?></p>
          <button class="btn btn-order w-100" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">Order Now</button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($menu_items)): ?>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/SHAW.jpg" alt="Shawarma">
          <h5>Shawarma</h5>
          <p>Savory wrap, grilled meats, fresh toppings, pure delight.</p>
          <p class="price">Rs. 480</p>
          <button class="btn btn-order w-100" onclick="openOrder('Shawarma', 480)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/HOT.jpg" alt="Hot-Dog">
          <h5>Hot-Dog</h5>
          <p>Classic comfort, juicy sausage, perfect bun, satisfying.</p>
          <p class="price">Rs. 270</p>
          <button class="btn btn-order w-100" onclick="openOrder('Hot-Dog', 270)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/SPAG.jpg" alt="Spaghetti">
          <h5>Spaghetti</h5>
          <p>Twirled pasta, rich sauce, Italian comfort, delicious.</p>
          <p class="price">Rs. 520</p>
          <button class="btn btn-order w-100" onclick="openOrder('Spaghetti', 520)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/KOT.jpg" alt="Kottu">
          <h5>Kottu</h5>
          <p>Chopped roti, stir-fried, spicy, flavorful.</p>
          <p class="price">Rs. 550</p>
          <button class="btn btn-order w-100" onclick="openOrder('Kottu', 550)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/LAS.jpg" alt="Lasagna">
          <h5>Lasagna</h5>
          <p>Layers of pasta, cheese, sauce, baked goodness.</p>
          <p class="price">Rs. 650</p>
          <button class="btn btn-order w-100" onclick="openOrder('Lasagna', 650)">Order Now</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<!-- Drinks -->
<section class="container menu-section">
  <h2>Drinks</h2>
  <div class="row g-4 mt-3">
    <?php foreach ($menu_items as $item): ?>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
          <h5><?php echo htmlspecialchars($item['item_name']); ?></h5>
          <p><?php echo htmlspecialchars($item['item_name']) === 'Water (350ml)' ? 'Refreshing mineral water, chilled.' : (htmlspecialchars($item['item_name']) === 'Iced Coffee' ? 'Perfectly chilled.' : 'Nestea available.'); ?></p>
          <p class="price">Rs. <?php echo number_format($item['price'], 2); ?></p>
          <button class="btn btn-order w-100" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">Order Now</button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($menu_items)): ?>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/WAT.jpg" alt="Water (350ml)">
          <h5>Water (350ml)</h5>
          <p>Refreshing mineral water, chilled.</p>
          <p class="price">Rs. 160</p>
          <button class="btn btn-order w-100" onclick="openOrder('Water (350ml)', 160)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/ICED.jpg" alt="Iced Coffee">
          <h5>Iced Coffee</h5>
          <p>Perfectly chilled.</p>
          <p class="price">Rs. 250</p>
          <button class="btn btn-order w-100" onclick="openOrder('Iced Coffee', 250)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/NES.jpg" alt="Nescafe">
          <h5>Nescafe</h5>
          <p>Nestea available.</p>
          <p class="price">Rs. 120</p>
          <button class="btn btn-order w-100" onclick="openOrder('Nescafe', 120)">Order Now</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger" id="orderModalLabel">Confirm Your Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="orderItem" class="fw-bold"></p>
        <form id="orderForm" method="post" action="">
          <input type="hidden" id="itemName" name="item_name">
          <input type="hidden" id="itemPrice" name="item_price">
          <div class="mb-3">
            <label for="customerName" class="form-label">Your Name</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" id="customerName" name="customer_name" required>
          </div>
          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" class="form-control bg-dark text-white border-secondary" id="quantity" name="quantity" min="1" value="1" required oninput="updateTotal()">
          </div>
          <p class="fw-bold text-danger">Total: Rs. <span id="totalPrice">0</span></p>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="orderForm" class="btn btn-danger">Place Order</button>
      </div>
    </div>
  </div>
</div>
<!-- Footer -->
<footer class="footer">
  <div class="container">
    <p class="mb-1">© 2025 Eat@N - All Rights Reserved</p>
    <p>
      <a href="PrivacyPolicy.html">Privacy Policy</a> |
      <a href="ContactUs.html">Contact Us</a>
    </p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
  let currentOrderPrice = 0;

  function openOrder(itemName, itemPrice) {
    try {
      document.getElementById("orderItem").textContent = itemName + " - Rs. " + itemPrice;
      document.getElementById("itemName").value = itemName;
      document.getElementById("itemPrice").value = itemPrice;
      document.getElementById("quantity").value = 1;
      currentOrderPrice = parseFloat(itemPrice);
      updateTotal();
      const myModal = new bootstrap.Modal(document.getElementById('orderModal'), {
        keyboard: false
      });
      myModal.show();
    } catch (error) {
      console.error("Error in openOrder:", error);
      alert("Error opening order modal. Please try again.");
    }
  }

  function updateTotal() {
    try {
      const qty = parseInt(document.getElementById("quantity").value) || 1;
      const total = currentOrderPrice * qty;
      document.getElementById("totalPrice").textContent = total.toFixed(2);
    } catch (error) {
      console.error("Error in updateTotal:", error);
    }
  }
</script>
</body>
</html>