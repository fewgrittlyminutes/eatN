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

// If no menu items in database, use default items
if (empty($menu_items)) {
    $menu_items = [
        ['item_name' => 'Shawarma', 'price' => 480, 'image_url' => 'images/SHAW.jpg'],
        ['item_name' => 'Hot-Dog', 'price' => 270, 'image_url' => 'images/HOT.jpg'],
        ['item_name' => 'Spaghetti', 'price' => 520, 'image_url' => 'images/SPAG.jpg'],
        ['item_name' => 'Kottu', 'price' => 550, 'image_url' => 'images/KOT.jpg'],
        ['item_name' => 'Lasagna', 'price' => 650, 'image_url' => 'images/LAS.jpg'],
        ['item_name' => 'Water (350ml)', 'price' => 160, 'image_url' => 'images/WAT.jpg'],
        ['item_name' => 'Iced Coffee', 'price' => 250, 'image_url' => 'images/ICED.jpg'],
        ['item_name' => 'Nescafe', 'price' => 120, 'image_url' => 'images/NES.jpg']
    ];
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
        if (strlen($customer_name) < 2) {
            throw new Exception("Please enter a valid name (at least 2 characters)");
        }

        $total_price = $item_price * $quantity;

        // Insert order into database
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shop_id, item_name, item_price, quantity, total_price, customer_name, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Error preparing order query: " . $conn->error);
        }

        $stmt->bind_param("iisidds", $user_id, $shop_id, $item_name, $item_price, $quantity, $total_price, $customer_name);
        
        if ($stmt->execute()) {
            $success = "Order placed successfully! Your order for " . htmlspecialchars($item_name) . " (Qty: " . $quantity . ") has been received. Total: Rs. " . number_format($total_price, 2);
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

// Item descriptions
$item_descriptions = [
    'Shawarma' => 'Savory wrap with grilled meats, fresh vegetables, and our special sauce',
    'Hot-Dog' => 'Classic comfort food with juicy sausage in a perfect bun',
    'Spaghetti' => 'Twirled pasta with rich tomato sauce and Italian herbs',
    'Kottu' => 'Chopped roti stir-fried with vegetables and spices',
    'Lasagna' => 'Layers of pasta, cheese, and meat sauce baked to perfection',
    'Water (350ml)' => 'Refreshing mineral water, perfectly chilled',
    'Iced Coffee' => 'Rich coffee served cold with ice - perfect refreshment',
    'Nescafe' => 'Classic instant coffee, hot and aromatic'
];
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
              <p class="card-text text-muted mb-3">
                <?php echo $item_descriptions[$item['item_name']] ?? 'Delicious and freshly prepared'; ?>
              </p>
              
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
  </div>
</section>

<!-- Order Confirmation Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-primary" id="orderModalLabel">
          <i class="fas fa-shopping-cart me-2"></i>Confirm Your Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="orderForm" method="post">
        <div class="modal-body">
          <div class="text-center mb-4">
            <i class="fas fa-utensils display-4 text-primary mb-3"></i>
            <h6 class="text-muted">Order Summary</h6>
          </div>
          
          <input type="hidden" id="modalItemName" name="item_name">
          <input type="hidden" id="modalItemPrice" name="item_price">
          
          <div class="row mb-3">
            <div class="col-sm-4"><strong>Item:</strong></div>
            <div class="col-sm-8" id="modalItemDisplay"></div>
          </div>
          
          <div class="row mb-3">
            <div class="col-sm-4"><strong>Price:</strong></div>
            <div class="col-sm-8">Rs. <span id="modalPriceDisplay"></span></div>
          </div>
          
          <div class="mb-3">
            <label for="modalQuantity" class="form-label-food">Quantity</label>
            <select class="form-control-food" id="modalQuantity" name="quantity" onchange="updateModalTotal()">
              <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="modalCustomerName" class="form-label-food">Your Name</label>
            <input type="text" class="form-control-food" id="modalCustomerName" name="customer_name" 
                   placeholder="Enter your full name" required>
          </div>
          
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Total Amount:</h6>
            <h5 class="text-primary mb-0">Rs. <span id="modalTotalPrice">0.00</span></h5>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary-food" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Cancel
          </button>
          <button type="submit" class="btn btn-food">
            <i class="fas fa-shopping-cart me-2"></i>Place Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
  let currentOrderPrice = 0;

  function showOrderModal(itemName, itemPrice, form) {
    try {
      // Get quantity from the form
      const quantity = form.querySelector('select[name="quantity"]').value;
      
      // Set modal values
      document.getElementById('modalItemName').value = itemName;
      document.getElementById('modalItemPrice').value = itemPrice;
      document.getElementById('modalItemDisplay').textContent = itemName;
      document.getElementById('modalPriceDisplay').textContent = itemPrice.toFixed(2);
      document.getElementById('modalQuantity').value = quantity;
      
      // Clear customer name
      document.getElementById('modalCustomerName').value = '';
      
      currentOrderPrice = parseFloat(itemPrice);
      updateModalTotal();
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('orderModal'));
      modal.show();
    } catch (error) {
      console.error('Error showing order modal:', error);
      alert('Error opening order form. Please try again.');
    }
  }

  function updateModalTotal() {
    try {
      const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
      const total = currentOrderPrice * quantity;
      document.getElementById('modalTotalPrice').textContent = total.toFixed(2);
    } catch (error) {
      console.error('Error updating total:', error);
    }
  }

  // Auto-dismiss alerts after 5 seconds
  document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      setTimeout(function() {
        if (alert && alert.parentNode) {
          const bootstrapAlert = new bootstrap.Alert(alert);
          bootstrapAlert.close();
        }
      }, 5000);
    });
  });

  // Form validation
  document.getElementById('orderForm').addEventListener('submit', function(e) {
    const customerName = document.getElementById('modalCustomerName').value.trim();
    if (!customerName) {
      e.preventDefault();
      alert('Please enter your name');
      document.getElementById('modalCustomerName').focus();
      return false;
    }
    
    if (customerName.length < 2) {
      e.preventDefault();
      alert('Please enter a valid name (at least 2 characters)');
      document.getElementById('modalCustomerName').focus();
      return false;
    }
  });
</script>
</body>
</html>