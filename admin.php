<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'Admin') {
    header("Location: Login.php?error=Access denied. Please log in as an admin.");
    exit;
}

// Fetch admin's shop name (optional, for reference)
$admin_shop_name = "All Shops"; // Default if no specific shop
$stmt = $conn->prepare("SELECT u.shop_id, s.shop_name FROM users u LEFT JOIN shops s ON u.shop_id = s.shop_id WHERE u.id = ?");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_shop_name = $row['shop_name'] ?: "All Shops";
    }
    $stmt->close();
}

// Handle menu item addition
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_item') {
    $shop_id = filter_var($_POST['shop_id'], FILTER_SANITIZE_NUMBER_INT);
    $item_name = filter_var($_POST['item_name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $image_url = filter_var($_POST['image_url'] ?? '', FILTER_SANITIZE_URL);

    if (empty($item_name) || empty($price) || empty($shop_id)) {
        $error = "Item name, price, and shop ID are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO menu_item (shop_id, item_name, price, image_url) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Error preparing insert query: " . $conn->error;
        } else {
            $stmt->bind_param("isds", $shop_id, $item_name, $price, $image_url);
            if ($stmt->execute()) {
                $success = "Item '$item_name' added successfully!";
            } else {
                $error = "Error adding item: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle menu item deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_item') {
    $item_id = filter_var($_POST['item_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM menu_item WHERE id = ?");
    if (!$stmt) {
        $error = "Error preparing delete query: " . $conn->error;
    } else {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $success = "Item deleted successfully!";
            // Refresh the page to update the menu items list
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Error deleting item: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    if (in_array($status, ['Pending', 'Completed'])) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if (!$stmt) {
            $error = "Error preparing status update query: " . $conn->error;
        } else {
            $stmt->bind_param("si", $status, $order_id);
            if ($stmt->execute()) {
                $success = "Order status updated successfully!";
                // Refresh the page to update the orders list
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "Invalid status value.";
    }
}

// Fetch shops for dropdown
$shops = [];
$stmt = $conn->prepare("SELECT shop_id, shop_name FROM shops");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
    $stmt->close();
}

// Fetch menu items and orders
$menu_items = [];
$stmt = $conn->prepare("SELECT id, shop_id, item_name, price, image_url FROM menu_item");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
    $stmt->close();
}

$orders = [];
$stmt = $conn->prepare("SELECT o.id, o.shop_id, o.item_name, o.item_price, o.quantity, o.total_price, o.customer_name, o.status, o.order_date, s.shop_name FROM orders o LEFT JOIN shops s ON o.shop_id = s.shop_id ORDER BY o.order_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eat@N | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/food-theme.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-stats {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-orange);
        }
        
        .table {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tr:hover {
            background-color: rgba(255, 149, 5, 0.05);
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--primary-orange);
            margin-bottom: 2rem;
        }
        
        .nav-tabs .nav-link {
            background-color: white;
            color: var(--primary-orange);
            border: 2px solid transparent;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
            color: white;
            border: 2px solid var(--primary-orange);
        }
        
        .nav-tabs .nav-link:hover {
            background-color: rgba(255, 149, 5, 0.1);
        }
        
        .tab-pane {
            background-color: white;
            padding: 2rem;
            border-radius: 0 12px 12px 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 149, 5, 0.25);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 149, 5, 0.4);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, var(--accent-red), #dc2626);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: #15803d;
            border-left: 4px solid #22c55e;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-pending {
            background: linear-gradient(135deg, rgba(255, 149, 5, 0.2), rgba(251, 146, 60, 0.2));
            color: #ea580c;
        }
        
        .status-completed {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2));
            color: #15803d;
        }
        
        .card-admin {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        
        .card-header-admin {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
            color: white;
            padding: 1.5rem;
            border: none;
        }
        
        .icon-admin {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .icon-orders {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2));
            color: #15803d;
        }
        
        .icon-menu {
            background: linear-gradient(135deg, rgba(255, 149, 5, 0.2), rgba(251, 146, 60, 0.2));
            color: #ea580c;
        }
        
        .icon-revenue {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
            color: #dc2626;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Admin Header -->
<section class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold mb-2">
                    <i class="fas fa-cogs me-3"></i>Admin Panel
                </h1>
                <p class="lead mb-0">Managing <?php echo htmlspecialchars($admin_shop_name); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-user-shield fa-4x opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Admin Statistics -->
    <div class="admin-stats">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-admin icon-orders">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                    </div>
                    <div class="stat-number"><?php echo count($orders); ?></div>
                    <p class="text-muted mb-0">Total Orders</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-admin icon-menu">
                        <i class="fas fa-utensils fa-lg"></i>
                    </div>
                    <div class="stat-number"><?php echo count($menu_items); ?></div>
                    <p class="text-muted mb-0">Menu Items</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-admin icon-revenue">
                        <i class="fas fa-chart-line fa-lg"></i>
                    </div>
                    <div class="stat-number">Rs. <?php echo number_format(array_sum(array_column($orders, 'total_price')), 2); ?></div>
                    <p class="text-muted mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Filter -->
    <div class="card-admin mb-4">
        <div class="card-header-admin">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter by Shop
            </h5>
        </div>
        <div class="card-body">
            <select id="shopSelect" class="form-select" onchange="filterShop()">
                <option value="all">All Shops</option>
                <?php foreach ($shops as $shop): ?>
                    <option value="<?php echo $shop['shop_id']; ?>"><?php echo htmlspecialchars($shop['shop_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <?php foreach ($shops as $index => $shop): ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                   id="shop-tab-<?php echo $shop['shop_id']; ?>" 
                   data-bs-toggle="tab" 
                   href="#shop<?php echo $shop['shop_id']; ?>" 
                   role="tab" 
                   aria-controls="shop<?php echo $shop['shop_id']; ?>" 
                   aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                   onclick="selectShop(<?php echo $shop['shop_id']; ?>)">
                   <i class="fas fa-store me-2"></i><?php echo htmlspecialchars($shop['shop_name']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content" id="myTabContent">
        <?php foreach ($shops as $shop): ?>
            <div class="tab-pane fade <?php echo $shop === reset($shops) ? 'show active' : ''; ?>" 
                 id="shop<?php echo $shop['shop_id']; ?>" 
                 role="tabpanel" 
                 aria-labelledby="shop-tab-<?php echo $shop['shop_id']; ?>" 
                 data-shop-id="<?php echo $shop['shop_id']; ?>">
                
                <div class="card-admin">
                    <div class="card-header-admin">
                        <h4 class="mb-0">
                            <i class="fas fa-utensils me-2"></i><?php echo htmlspecialchars($shop['shop_name']); ?> Menu Management
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Add Menu Item Form -->
                        <div class="mb-4 p-4 bg-light rounded">
                            <h5 class="text-primary-orange mb-3">
                                <i class="fas fa-plus-circle me-2"></i>Add New Menu Item
                            </h5>
                            <form method="post">
                                <input type="hidden" name="action" value="add_item">
                                <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="item_name_<?php echo $shop['shop_id']; ?>" class="form-label">Item Name</label>
                                        <input type="text" name="item_name" 
                                               id="item_name_<?php echo $shop['shop_id']; ?>" 
                                               class="form-control" 
                                               placeholder="Enter item name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="price_<?php echo $shop['shop_id']; ?>" class="form-label">Price (Rs.)</label>
                                        <input type="number" name="price" 
                                               id="price_<?php echo $shop['shop_id']; ?>" 
                                               class="form-control" 
                                               step="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="image_url_<?php echo $shop['shop_id']; ?>" class="form-label">Image URL</label>
                                        <input type="url" name="image_url" 
                                               id="image_url_<?php echo $shop['shop_id']; ?>" 
                                               class="form-control" 
                                               placeholder="https://example.com/image.jpg" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn-admin w-100">
                                            <i class="fas fa-plus me-2"></i>Add Item
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Menu Items Table -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-utensils me-2"></i>Item</th>
                                        <th><i class="fas fa-tag me-2"></i>Price</th>
                                        <th><i class="fas fa-image me-2"></i>Image</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menu_items as $item): ?>
                                        <?php if ($item['shop_id'] == $shop['shop_id']): ?>
                                            <tr data-shop-id="<?php echo $item['shop_id']; ?>">
                                                <td class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td class="text-success fw-bold">Rs. <?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                                         class="rounded" style="max-width: 80px; height: 60px; object-fit: cover;">
                                                </td>
                                                <td>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete_item">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="btn-delete" 
                                                                onclick="return confirm('Are you sure you want to delete this item?')">
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php 
                                    $hasItems = false;
                                    foreach ($menu_items as $item) {
                                        if ($item['shop_id'] == $shop['shop_id']) {
                                            $hasItems = true;
                                            break;
                                        }
                                    }
                                    if (!$hasItems): 
                                    ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle me-2"></i>No menu items found for this shop
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Orders Management Section -->
    <div class="card-admin mt-5">
        <div class="card-header-admin">
            <h4 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>Orders Management
            </h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-2"></i>Order ID</th>
                            <th><i class="fas fa-store me-2"></i>Shop</th>
                            <th><i class="fas fa-utensils me-2"></i>Item</th>
                            <th><i class="fas fa-tag me-2"></i>Price</th>
                            <th><i class="fas fa-sort-numeric-up me-2"></i>Qty</th>
                            <th><i class="fas fa-calculator me-2"></i>Total</th>
                            <th><i class="fas fa-user me-2"></i>Customer</th>
                            <th><i class="fas fa-flag me-2"></i>Status</th>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-shop-id="<?php echo $order['shop_id']; ?>">
                                <td class="fw-bold text-primary-orange">#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['shop_name'] ?: 'N/A'); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($order['item_name']); ?></td>
                                <td>Rs. <?php echo number_format($order['item_price'], 2); ?></td>
                                <td class="text-center"><?php echo $order['quantity']; ?></td>
                                <td class="fw-bold text-success">Rs. <?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-muted"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i>No orders found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-5 mb-5">
        <a href="view_users.php" class="btn-admin">
            <i class="fas fa-users me-2"></i>Manage Users
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle Add / Update form submissions
        document.querySelectorAll("form:not([action*='delete_item']):not([action*='update_status'])").forEach(form => {
            form.addEventListener("submit", function (e) {
                const inputs = form.querySelectorAll("input[required]");
                let valid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                    }
                });
                if (!valid) {
                    e.preventDefault();
                    alert("⚠️ Please enter valid details!");
                }
            });
        });

        // Filter shop content based on dropdown selection
        function filterShop() {
            const selectedShopId = document.getElementById('shopSelect').value;
            const shopTabs = document.querySelectorAll('.tab-pane');
            const orderRows = document.querySelectorAll('tbody tr');

            shopTabs.forEach(tab => {
                const tabShopId = tab.getAttribute('data-shop-id');
                if (selectedShopId === 'all' || selectedShopId === tabShopId) {
                    new bootstrap.Tab(tab.querySelector('a')).show();
                    tab.style.display = 'block';
                } else {
                    tab.style.display = 'none';
                }
            });

            orderRows.forEach(row => {
                const rowShopId = row.getAttribute('data-shop-id');
                if (selectedShopId === 'all' || selectedShopId === rowShopId) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Sync tab selection with dropdown
        function selectShop(shopId) {
            document.getElementById('shopSelect').value = shopId;
            filterShop();
        }

        // Initialize with default (first shop or all)
        document.addEventListener('DOMContentLoaded', function() {
            filterShop();
        });
    </script>
</body>
</html>