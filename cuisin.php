<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch shop details (shop_id = 3 for Sri Lankan Cuisine)
$shop_id = 3;
$shop = null;
$stmt = $conn->prepare("SELECT shop_name, description FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $shop = $result->fetch_assoc();
} else {
    $shop = ['shop_name' => 'Sri Lankan Cuisine', 'description' => 'Traditional Sri Lankan meals'];
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
    .menu-item .btn-order:hover {
      background: #a71d2a;
    }
    /* Uniform image dimensions */
    .menu-image {
      width: 100%;
      height: 200px; /* Fixed height for all images */
      object-fit: cover; /* Maintain aspect ratio, crop if needed */
      object-position: center; /* Center the image */
    }
    .menu-image-container {
      width: 100%;
      overflow: hidden; /* Ensure no overflow if image is larger */
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
              <i class="fas fa-utensils fa-2x mb-2"></i>
              <p class="mb-0">Authentic Flavors</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-leaf fa-2x mb-2"></i>
              <p class="mb-0">Fresh Ingredients</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-clock fa-2x mb-2"></i>
              <p class="mb-0">Quick Service</p>
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

<!-- Food Menu -->
<section class="py-5">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Traditional Food Menu</h2>
        <p class="lead text-muted">Authentic Sri Lankan cuisine prepared with love</p>
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
                  'Rice N Curry' => 'Traditional rice with chicken/fish curry',
                  'String Hoppers' => 'Steamed rice noodles with curry',
                  'Egg Hoppers' => 'Bowl-shaped pancakes with egg and lunu miris',
                  'Hoppers' => 'Traditional bowl-shaped pancakes with lunu miris',
                  'Pittu' => 'Steamed cylinders of rice flour with curry'
                ];
                echo $descriptions[htmlspecialchars($item['item_name'])] ?? 'Delicious Sri Lankan specialty';
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
              <img src="images/RNC2.jpg" alt="Rice N Curry" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Rice N Curry', 300)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Rice N Curry</h5>
              <p class="menu-description text-muted">Traditional rice with chicken/fish curry</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 300.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Rice N Curry', 300)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/SH.jpg" alt="String Hoppers" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('String Hoppers', 350)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">String Hoppers</h5>
              <p class="menu-description text-muted">Steamed rice noodles with curry</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 350.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('String Hoppers', 350)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/EH.jpg" alt="Egg Hoppers" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Egg Hoppers', 120)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Egg Hoppers</h5>
              <p class="menu-description text-muted">Bowl-shaped pancakes with egg and lunu miris</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 120.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Egg Hoppers', 120)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/HOp.jpg" alt="Hoppers" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Hoppers', 80)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Hoppers</h5>
              <p class="menu-description text-muted">Traditional bowl-shaped pancakes with lunu miris</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 80.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Hoppers', 80)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/PIT.jpg" alt="Pittu" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Pittu', 200)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Pittu</h5>
              <p class="menu-description text-muted">Steamed cylinders of rice flour with curry</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 200.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Pittu', 200)">
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

<!-- Drinks Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Refreshing Drinks</h2>
        <p class="lead text-muted">Stay hydrated with our beverage selection</p>
      </div>
    </div>
    
    <div class="row g-4 justify-content-center">
      <?php 
      $water_found = false;
      foreach ($menu_items as $item): 
        if (htmlspecialchars($item['item_name']) === 'Water (350ml)'):
          $water_found = true;
      ?>
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
              <p class="menu-description text-muted">Pure drinking water - lukewarm and cold available</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. <?php echo number_format($item['price'], 2); ?></span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php 
        endif;
      endforeach; 
      
      if (!$water_found):
      ?>
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/WAT.jpg" alt="Water (350ml)" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Water (350ml)', 160)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Water (350ml)</h5>
              <p class="menu-description text-muted">Pure drinking water - lukewarm and cold available</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 160.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Water (350ml)', 160)">
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
          
          <div class="total-section p-3 bg-primary-orange text-white rounded">
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