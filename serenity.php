<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch shop details (shop_id = 2 for Serenity Inn)
$shop_id = 2;
$shop = null;
$stmt = $conn->prepare("SELECT shop_name, description FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $shop = $result->fetch_assoc();
} else {
    $shop = ['shop_name' => 'SERENITY INN', 'description' => 'Food that tastes like home'];
}
$stmt->close();

// Fetch menu items from the database for this shop
$menu_items = [];
$stmt = $conn->prepare("SELECT item_name, price, image_url FROM menu_item WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}
$stmt->close();

// Initialize variables
$error = '';
$success = '';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php?error=Please login to place an order");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = filter_var($_POST['item_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $item_price = filter_var($_POST['item_price'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $quantity = filter_var($_POST['quantity'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    $customer_name = filter_var($_POST['customer_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $user_id = $_SESSION['user_id'];

    if (empty($item_name) || empty($item_price) || empty($quantity) || empty($customer_name)) {
        $error = "All fields are required";
    } elseif ($quantity <= 0) {
        $error = "Quantity must be greater than 0";
    } else {
        $total_price = $item_price * $quantity;
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shop_id, item_name, item_price, quantity, total_price, customer_name, order_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, current_timestamp(), 'Pending')");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("iisddis", $user_id, $shop_id, $item_name, $item_price, $quantity, $total_price, $customer_name);
            if ($stmt->execute()) {
                $success = "Order for $quantity × $item_name placed successfully at {$shop['shop_name']}! Total: Rs. $total_price";
            } else {
                $error = "Error placing order: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eat@N | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="css/food-theme.css" rel="stylesheet">
  <style>
    .menu-image {
      width: 300px !important;
      height: 200px !important;
      object-fit: cover;
    }
    .menu-image-container {
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-food py-5">
  <div class="hero-overlay">
    <div class="container text-center text-white">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
          <p class="lead mb-4"><?php echo htmlspecialchars($shop['description']); ?></p>
          <div class="hero-stats d-flex justify-content-center gap-4 mt-4">
            <div class="stat-item">
              <i class="fas fa-home fa-2x mb-2"></i>
              <p class="mb-0">Homestyle</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-heart fa-2x mb-2"></i>
              <p class="mb-0">Made with Love</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-clock fa-2x mb-2"></i>
              <p class="mb-0">Fresh Daily</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Messages -->
<?php if ($error): ?>
<section class="py-3">
  <div class="container">
    <div class="alert alert-danger text-center">
      <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($success): ?>
<section class="py-3">
  <div class="container">
    <div class="alert alert-success text-center">
      <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Comfort Food Menu -->
<section class="py-5">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Comfort Food Menu</h2>
        <p class="lead text-muted">Delicious home-style meals that warm your heart</p>
      </div>
    </div>
    
    <div class="row g-4">
      <?php foreach ($menu_items as $item): ?>
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                   alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                   class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
              <p class="menu-description text-muted">
                <?php 
                $descriptions = [
                  'Kottu' => 'Traditional Sri Lankan stir-fried bread with vegetables and meat',
                  'Fried Rice' => 'Aromatic rice stir-fried with fresh vegetables and spices',
                  'Pasta' => 'Italian-style pasta with rich sauce and premium ingredients',
                  'Biriyani' => 'Fragrant basmati rice cooked with tender meat and aromatic spices',
                  'Lasagna' => 'Layered pasta with meat sauce, cheese, and béchamel',
                  'Spaghetti' => 'Classic Italian spaghetti with homemade sauce'
                ];
                echo $descriptions[htmlspecialchars($item['item_name'])] ?? 'Delicious comfort food made with love';
                ?>
              </p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. <?php echo number_format($item['price'], 2); ?></span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      
      <?php if (empty($menu_items)): ?>
        <!-- Default Menu Items -->
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/KOT.jpg" alt="Kottu" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Kottu', 700)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Kottu</h5>
              <p class="menu-description text-muted">Our most popular dish</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 700.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Kottu', 700)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/FR.jpg" alt="Fried Rice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Fried Rice', 550)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Fried Rice</h5>
              <p class="menu-description text-muted">Aromatic rice stir-fried with fresh vegetables and spices</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 550.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Fried Rice', 550)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/PAS.jpg" alt="Pasta" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Pasta', 850)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Pasta</h5>
              <p class="menu-description text-muted">Italian-style pasta with rich sauce and premium ingredients</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 850.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Pasta', 850)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/BIRI.jpg" alt="Biriyani" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Biriyani', 620)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Biriyani</h5>
              <p class="menu-description text-muted">Fragrant basmati rice cooked with tender meat and aromatic spices</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 620.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Biriyani', 620)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/RNC.jpg" alt="Rice N Curry" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Rice N Curry', 350)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Rice N Curry</h5>
              <p class="menu-description text-muted">Chicken/Fish curry available</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 350.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Rice N Curry', 350)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
      <?php endif; ?>
    </div>
  </div>
</section>



<!-- Restaurant Features -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Why Choose Serenity Inn?</h2>
        <p class="lead text-muted">Experience the comfort of home-cooked meals</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-home fa-3x text-primary-orange mb-3"></i>
          <h5>Homestyle Cooking</h5>
          <p class="text-muted">Recipes passed down through generations</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-chef-hat fa-3x text-secondary-green mb-3"></i>
          <h5>Expert Chefs</h5>
          <p class="text-muted">Skilled chefs with years of experience</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-leaf fa-3x text-accent-red mb-3"></i>
          <h5>Fresh Ingredients</h5>
          <p class="text-muted">Daily sourced fresh ingredients</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-clock fa-3x text-primary-orange mb-3"></i>
          <h5>Quick Service</h5>
          <p class="text-muted">Fast preparation without compromising quality</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title text-primary-orange" id="orderModalLabel">
          <i class="fas fa-shopping-cart me-2"></i>Confirm Your Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="order-summary p-3 bg-light rounded mb-4">
          <h6 class="text-secondary-green mb-2">Order Details</h6>
          <p id="orderItem" class="fw-bold mb-0"></p>
        </div>
        
        <form id="orderForm" method="post" action="">
          <input type="hidden" id="itemName" name="item_name">
          <input type="hidden" id="itemPrice" name="item_price">
          
          <div class="mb-3">
            <label for="customerName" class="form-label">
              <i class="fas fa-user me-2 text-primary-orange"></i>Your Name
            </label>
            <input type="text" class="form-control" id="customerName" name="customer_name" 
                   placeholder="Enter your full name" required>
          </div>
          
          <div class="mb-3">
            <label for="quantity" class="form-label">
              <i class="fas fa-sort-numeric-up me-2 text-secondary-green"></i>Quantity
            </label>
            <div class="input-group">
              <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity()">
                <i class="fas fa-minus"></i>
              </button>
              <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                     min="1" value="1" required oninput="updateTotal()">
              <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity()">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          
          <div class="total-section p-3 bg-accent-red text-white rounded">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-bold">Total Amount:</span>
              <span class="fs-5 fw-bold">Rs. <span id="totalPrice">0</span></span>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn-food-outline" data-bs-dismiss="modal">
          <i class="fas fa-times me-2"></i>Cancel
        </button>
        <button type="submit" form="orderForm" class="btn-food">
          <i class="fas fa-check me-2"></i>Place Order
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

  function increaseQuantity() {
    const quantityInput = document.getElementById("quantity");
    quantityInput.value = parseInt(quantityInput.value) + 1;
    updateTotal();
  }

  function decreaseQuantity() {
    const quantityInput = document.getElementById("quantity");
    if (parseInt(quantityInput.value) > 1) {
      quantityInput.value = parseInt(quantityInput.value) - 1;
      updateTotal();
    }
  }
</script>
</body>
</html>