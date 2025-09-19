<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch shop details (shop_id = 5 for Tandoor)
$shop_id = 5;
$shop = null;
$stmt = $conn->prepare("SELECT shop_name, description FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $shop = $result->fetch_assoc();
} else {
    $shop = ['shop_name' => 'Tandoor', 'description' => 'Authentic Indian Flavors Loved in Sri Lanka'];
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
        $status = 'Pending'; // Default status
        
        // Fixed the bind_param parameters to match the database structure
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shop_id, item_name, item_price, quantity, total_price, customer_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            // Corrected the parameter types: user_id(int), shop_id(int), item_name(string), item_price(double), quantity(int), total_price(double), customer_name(string), status(string)
            $stmt->bind_param("iisidiss", $user_id, $shop_id, $item_name, $item_price, $quantity, $total_price, $customer_name, $status);
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
    /* Ensure consistent image sizes for the first version */
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
    /* Ensure consistent image sizes for the second version */
    .menu-item img {
      width: 300px !important;
      height: 200px !important;
      object-fit: cover;
    }
    /* Existing styles from the second version */
    .page-hero {
      padding: 120px 0 60px;
      text-align: center;
      background: #111;
      border-bottom: 3px solid #dc3545;
    }
    .page-hero h1 {
      font-weight: bold;
      color: #dc3545;
    }
    .menu-section {
      margin: 60px 0;
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

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section (First Version) -->
<section class="hero-food py-5">
  <div class="hero-overlay">
    <div class="container text-center text-white">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
          <p class="lead mb-4"><?php echo htmlspecialchars($shop['description']); ?></p>
          <div class="hero-stats d-flex justify-content-center gap-4 mt-4">
            <div class="stat-item">
              <i class="fas fa-fire fa-2x mb-2"></i>
              <p class="mb-0">Authentic Spices</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-crown fa-2x mb-2"></i>
              <p class="mb-0">Royal Recipes</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-mortar-pestle fa-2x mb-2"></i>
              <p class="mb-0">Traditional Cooking</p>
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

<!-- Indian Tandoor Menu -->
<section class="py-5">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Authentic Indian Tandoor Menu</h2>
        <p class="lead text-muted">Experience the rich flavors of traditional Indian cuisine</p>
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
                  'Masala Dosa' => 'Traditional South Indian crispy crepe with spiced potato filling',
                  'Butter Chicken' => 'Tender chicken in rich, creamy tomato-based curry',
                  'Naan Bread' => 'Soft, pillowy flatbread baked fresh in our tandoor',
                  'Biriyani' => 'Fragrant basmati rice layered with aromatic spices and meat',
                  'Paneer Tikka' => 'Marinated cottage cheese grilled to perfection',
                  'Tandoori Chicken' => 'Chicken marinated in yogurt and spices, cooked in clay oven'
                ];
                echo $descriptions[htmlspecialchars($item['item_name'])] ?? 'Authentic Indian dish prepared with traditional spices';
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
              <img src="images/MAS.jpg" alt="Masala Dosa" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Masala Dosa', 400)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Masala Dosa</h5>
              <p class="menu-description text-muted">Traditional South Indian crispy crepe with spiced potato filling</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 400.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Masala Dosa', 400)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/BUT.jpg" alt="Butter Chicken" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Butter Chicken', 850)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Butter Chicken</h5>
              <p class="menu-description text-muted">Tender chicken in rich, creamy tomato-based curry</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 850.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Butter Chicken', 850)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/IDLI.jpg" alt="Idli with Sambar" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Idli with Sambar', 300)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Idli with Sambar</h5>
              <p class="menu-description text-muted">Steamed rice cakes served with sambar and chutney</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 300.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Idli with Sambar', 300)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/BIRI2.jpg" alt=" Chicken Biriyani" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Chicken Biriyani', 650)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Chicken Biriyani</h5>
              <p class="menu-description text-muted">Fragrant basmati rice layered with aromatic spices and meat</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 650.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Chicken Biriyani', 650)">
                  <i class="fas fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
          <div class="menu-card card-food h-100">
            <div class="menu-image-container">
              <img src="images/PAN.jpg" alt="Paneer Butter Masala" class="menu-image">
              <div class="menu-overlay">
                <button class="btn-food" onclick="openOrder('Paneer Tikka', 780)">
                  <i class="fas fa-shopping-cart me-2"></i>Order Now
                </button>
              </div>
            </div>
            <div class="menu-content p-4">
              <h5 class="menu-title">Paneer Butter Masala</h5>
              <p class="menu-description text-muted">Soft paneer cubes in rich butter gravy</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="menu-price">Rs. 780.00</span>
                <button class="btn-food-outline btn-sm" onclick="openOrder('Paneer Butter Masala', 780)">
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

<!-- Drinks Menu (Second Version) -->
<section class="container menu-section">
  <h2>Drinks</h2>
  <div class="row g-4 mt-3">
    <?php foreach ($menu_items as $item): ?>
      <?php if (in_array(htmlspecialchars($item['item_name']), ['Water (350ml)', 'Coca-Cola (330ml)', 'Fanta (330ml)'])): ?>
        <div class="col-md-4">
          <div class="menu-item text-center p-3">
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
            <h5><?php echo htmlspecialchars($item['item_name']); ?></h5>
            <p><?php echo htmlspecialchars($item['item_name']) === 'Water (350ml)' ? 'Available chilled or lukewarm.' : (htmlspecialchars($item['item_name']) === 'Coca-Cola (330ml)' ? 'Classic fizzy refreshment.' : 'Orange-flavored sparkling drink.'); ?></p>
            <p class="price">Rs. <?php echo number_format($item['price'], 2); ?></p>
            <button class="btn btn-order w-100" onclick="openOrder('<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['price']; ?>)">Order Now</button>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!array_filter($menu_items, fn($item) => in_array(htmlspecialchars($item['item_name']), ['Water (350ml)', 'Coca-Cola (330ml)', 'Fanta (330ml)']))): ?>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/WAT.jpg" alt="Water (350ml)">
          <h5>Water (350ml)</h5>
          <p>Available chilled or lukewarm.</p>
          <p class="price">Rs. 160</p>
          <button class="btn btn-order w-100" onclick="openOrder('Water (350ml)', 160)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/COCA.jpg" alt="Coca-Cola (330ml)">
          <h5>Coca-Cola (330ml)</h5>
          <p>Classic fizzy refreshment.</p>
          <p class="price">Rs. 220</p>
          <button class="btn btn-order w-100" onclick="openOrder('Coca-Cola (330ml)', 220)">Order Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="menu-item text-center p-3">
          <img src="images/FAN.jpg" alt="Fanta (330ml)">
          <h5>Fanta (330ml)</h5>
          <p>Orange-flavored sparkling drink.</p>
          <p class="price">Rs. 220</p>
          <button class="btn btn-order w-100" onclick="openOrder('Fanta (330ml)', 220)">Order Now</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Tandoor Experience -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">The Tandoor Experience</h2>
        <p class="lead text-muted">Discover the authentic taste of traditional Indian cooking</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-fire fa-3x text-accent-red mb-3"></i>
          <h5>Clay Oven Cooking</h5>
          <p class="text-muted">Traditional tandoor ovens for authentic flavors</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-pepper-hot fa-3x text-primary-orange mb-3"></i>
          <h5>Authentic Spices</h5>
          <p class="text-muted">Hand-ground spices imported from India</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-chef-hat fa-3x text-secondary-green mb-3"></i>
          <h5>Master Chefs</h5>
          <p class="text-muted">Experienced chefs trained in traditional methods</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card text-center p-4">
          <i class="fas fa-award fa-3x text-primary-orange mb-3"></i>
          <h5>Award Winning</h5>
          <p class="text-muted">Recognized for authentic Indian cuisine</p>
        </div>
      </div>
    </div>
  </div>
</section>



<!-- Order Modal (First Version) -->
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