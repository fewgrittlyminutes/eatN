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

// Fetch all users
$users = [];
$stmt = $conn->prepare("SELECT id, username, email, account_type FROM users ORDER BY id");
if (!$stmt) {
    $error = "<div class='alert alert-danger text-center'>Error preparing users query: " . $conn->error . "</div>";
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eat@N | View Users</title>
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

    .users-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      border: none;
      overflow: hidden;
    }

    .card-header-users {
      background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
      color: white;
      padding: 1.5rem;
      border: none;
    }

    .table {
      margin-bottom: 0;
    }

    .table th {
      background: linear-gradient(135deg, rgba(255, 149, 5, 0.1), rgba(34, 197, 94, 0.1));
      color: var(--primary-orange);
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

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      margin-right: 10px;
    }

    .account-badge {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.875rem;
    }

    .badge-admin {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
      color: #dc2626;
    }

    .badge-customer {
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2));
      color: #15803d;
    }

    .btn-admin {
      background: linear-gradient(135deg, var(--primary-orange), var(--secondary-green));
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-admin:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 149, 5, 0.4);
      color: white;
      text-decoration: none;
    }

    .stats-row {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-item {
      text-align: center;
      padding: 1rem;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: var(--primary-orange);
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #6b7280;
      font-weight: 500;
    }

    .icon-users {
      width: 3rem;
      height: 3rem;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2));
      color: #15803d;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }

    .icon-admins {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
      color: #dc2626;
    }

    .icon-customers {
      background: linear-gradient(135deg, rgba(255, 149, 5, 0.2), rgba(251, 146, 60, 0.2));
      color: #ea580c;
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
          <i class="fas fa-users me-3"></i>User Management
        </h1>
        <p class="lead mb-0">View and manage all registered users</p>
      </div>
      <div class="col-md-4 text-end">
        <i class="fas fa-users-cog fa-4x opacity-75"></i>
      </div>
    </div>
  </div>
</section>

<div class="container">
  <!-- Error Display -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
  <?php endif; ?>

  <!-- User Statistics -->
  <div class="stats-row">
    <div class="row">
      <div class="col-md-4">
        <div class="stat-item">
          <div class="icon-users icon-users">
            <i class="fas fa-users fa-lg"></i>
          </div>
          <div class="stat-number"><?php echo count($users); ?></div>
          <p class="stat-label">Total Users</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <div class="icon-users icon-admins">
            <i class="fas fa-user-shield fa-lg"></i>
          </div>
          <div class="stat-number">
            <?php echo count(array_filter($users, function($user) { return $user['account_type'] === 'Admin'; })); ?>
          </div>
          <p class="stat-label">Administrators</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-item">
          <div class="icon-users icon-customers">
            <i class="fas fa-user fa-lg"></i>
          </div>
          <div class="stat-number">
            <?php echo count(array_filter($users, function($user) { return $user['account_type'] === 'Customer'; })); ?>
          </div>
          <p class="stat-label">Customers</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Users Table -->
  <div class="users-card">
    <div class="card-header-users">
      <h4 class="mb-0">
        <i class="fas fa-list me-2"></i>All Registered Users
      </h4>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th><i class="fas fa-hashtag me-2"></i>User ID</th>
            <th><i class="fas fa-user me-2"></i>User Details</th>
            <th><i class="fas fa-envelope me-2"></i>Email Address</th>
            <th><i class="fas fa-shield-alt me-2"></i>Account Type</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="fas fa-info-circle me-2"></i>No users found in the system
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <tr>
                <td class="fw-bold text-primary-orange">#<?php echo htmlspecialchars($user['id']); ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="user-avatar">
                      <?php echo strtoupper(substr(htmlspecialchars($user['username']), 0, 1)); ?>
                    </div>
                    <div>
                      <div class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
                      <small class="text-muted">Username</small>
                    </div>
                  </div>
                </td>
                <td>
                  <i class="fas fa-envelope text-muted me-2"></i>
                  <?php echo htmlspecialchars($user['email']); ?>
                </td>
                <td>
                  <span class="account-badge <?php echo $user['account_type'] === 'Admin' ? 'badge-admin' : 'badge-customer'; ?>">
                    <i class="fas fa-<?php echo $user['account_type'] === 'Admin' ? 'shield-alt' : 'user'; ?> me-1"></i>
                    <?php echo htmlspecialchars($user['account_type']); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="text-center mt-4 mb-5">
    <a href="admin.php" class="btn-admin">
      <i class="fas fa-arrow-left me-2"></i>Back to Admin Dashboard
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>