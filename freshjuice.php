<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch shop details (shop_id = 4 for Fresh Juice)
$shop_id = 4;
$shop = null;
$stmt = $conn->prepare("SELECT shop_name, description FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $shop = $result->fetch_assoc();
} else {
    $shop = ['shop_name' => 'Fresh Juice', 'description' => 'The best juice at NSBM'];
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
      width: 300px; /* Set fixed width */
      height: 200px; /* Set fixed height */
      object-fit: cover; /* Ensure images scale proportionally without distortion */
      display: block;
      margin: 0 auto; /* Center the image if needed */
    }
    .menu-image-container {
      overflow: hidden; /* Hide any overflow to maintain clean appearance */
      display: flex;
      justify-content: center; /* Center the image horizontally */
      align-items: center; /* Center the image vertically */
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
              <i class="fas fa-glass-cheers fa-2x mb-2"></i>
              <p class="mb-0">Fresh Daily</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-leaf fa-2x mb-2"></i>
              <p class="mb-0">Natural Ingredients</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-heart fa-2x mb-2"></i>
              <p class="mb-0">Healthy Choice</p>
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

<!-- Fresh Juice Menu -->
<section class="py-5">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Fresh Juice Selection</h2>
        <p class="lead text-muted">Nutritious and refreshing juices made from the finest fruits</p>
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
                  'Orange Juice' => 'Fresh squeezed oranges packed with vitamin C',
                  'Apple Juice' => 'Crisp apple juice for natural energy',
                  'Pineapple Juice' => 'Tropical pineapple juice with natural enzymes',
                  'Lime Juice' => 'Refreshing lime juice perfect for hot days',
                  'Mixed Fruit Juice' => 'Blend of seasonal fruits for ultimate nutrition'
                ];
                echo $descriptions[htmlspecialchars($item['item_name'])] ?? 'Freshly squeezed with natural goodness';
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
              <img src="images/Ora.jpg" alt="Orange Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Orange Juice', 250)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Orange Juice</h5>
              <p class="menu-description text-muted">Fresh squeezed oranges packed with vitamin C</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 250.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Orange Juice', 250)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/APP.jpg" alt="Apple Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Apple Juice', 220)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Apple Juice</h5>
              <p class="menu-description text-muted">Crisp apple juice for natural energy</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 220.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Apple Juice', 220)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/PINE.jpg" alt="Pineapple Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Pineapple Juice', 240)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Pineapple Juice</h5>
              <p class="menu-description text-muted">Tropical pineapple juice with natural enzymes</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 240.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Pineapple Juice', 240)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/LIME.png" alt="Lime Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Lime Juice', 180)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Lime Juice</h5>
              <p class="menu-description text-muted">Refreshing lime juice perfect for hot days</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 180.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Lime Juice', 180)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/MIX.jpg" alt="Mixed Fruit Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Mixed Fruit Juice', 300)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Mixed Fruit Juice</h5>
              <p class="menu-description text-muted">Blend of seasonal fruits for ultimate nutrition</p>
              <div class="d-fle justify-content-between align-items-center">
                <span class="menu-price">Rs. 300.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Mixed Fruit Juice', 300)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/WM.jpg" alt="Watermelon Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Watermelon Juice', 200)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Watermelon Juice</h5>
              <p class="menu-description text-muted">Light, sweet and refreshing</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 200.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Watermelon Juice', 200)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/PAP.jpg" alt="Papaya Juice" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Papaya Juice', 230)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Papaya Juice</h5>
              <p class="menu-description text-muted">Natural sweetness with health benefits.</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 230.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Papaya Juice', 230)">
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

<!-- Health Benefits Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Why Choose Fresh Juice?</h2>
        <p class="lead text-muted">Natural goodness in every sip</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-heartbeat fa-3x text-accent-red mb-3"></i>
          <h5>Heart Healthy</h5>
          <p class="text-muted">Natural antioxidants support cardiovascular health</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-shield-alt fa-3x text-secondary-green mb-3"></i>
          <h5>Immune Boost</h5>
          <p class="text-muted">Packed with vitamins to strengthen immunity</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-bolt fa-3x text-primary-orange mb-3"></i>
          <h5>Natural Energy</h5>
          <p class="text-muted">Natural sugars provide sustained energy</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-tint fa-3x text-secondary-green mb-3"></i>
          <h5>Hydration</h5>
          <p class="text-muted">Perfect way to stay hydrated and refreshed</p>
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
          
          <div class="total-section p-3 bg-secondary-green text-white rounded">
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