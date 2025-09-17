<?php
require_once 'includes/connection.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = filter_var($_POST['full_name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $username = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING);
    $account_type = $_POST['account_type'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($username) || empty($account_type) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!in_array($account_type, ['Student', 'Admin'])) {
        $error = "Invalid account type";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
            $stmt->close();
        } else {
            // Use lower cost for shorter hash
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            error_log("Generated password hash length: " . strlen($hashed_password));
            if (strlen($hashed_password) > 100) {
                $error = "Password hash too long for database";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, account_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $username, $hashed_password, $account_type);
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    header("Location: Login.php?success=Signup successful! Please login.");
                    exit;
                } else {
                    $error = "Error: " . $conn->error;
                }
                $stmt->close();
            }
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
  <title>Eat@N | Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="css/food-theme.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-food py-5">
  <div class="hero-overlay">
    <div class="container text-center text-white">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h1 class="display-4 fw-bold mb-3">Join Eat@N</h1>
          <p class="lead mb-4">Create your account and start exploring the best food options at NSBM Green University.</p>
          <div class="hero-stats d-flex justify-content-center gap-4 mt-4">
            <div class="stat-item">
              <i class="fas fa-user-plus fa-2x mb-2"></i>
              <p class="mb-0">Easy Registration</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-utensils fa-2x mb-2"></i>
              <p class="mb-0">Order Food</p>
            </div>
            <div class="stat-item">
              <i class="fas fa-star fa-2x mb-2"></i>
              <p class="mb-0">Best Experience</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Sign Up Section -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card-food p-5">
          <div class="text-center mb-4">
            <i class="fas fa-user-plus fa-3x text-primary-orange mb-3"></i>
            <h2 class="section-title">Create Account</h2>
            <p class="text-muted">Fill in your details to get started</p>
          </div>

          <?php
          if ($error) {
              echo "<div class='alert alert-danger text-center'><i class='fas fa-exclamation-triangle me-2'></i>$error</div>";
          }
          if ($success) {
              echo "<div class='alert alert-success text-center'><i class='fas fa-check-circle me-2'></i>$success</div>";
          }
          ?>

          <form action="" method="post" class="needs-validation" novalidate>
            <div class="row g-3">
              <div class="col-12">
                <label for="full_name" class="form-label">
                  <i class="fas fa-user me-2 text-primary-orange"></i>Full Name
                </label>
                <input type="text" class="form-control" id="full_name" name="full_name" 
                       placeholder="Enter your full name" required>
                <div class="invalid-feedback">Please provide your full name.</div>
              </div>

              <div class="col-12">
                <label for="email" class="form-label">
                  <i class="fas fa-envelope me-2 text-secondary-green"></i>Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Enter your email address" required>
                <div class="invalid-feedback">Please provide a valid email address.</div>
              </div>

              <div class="col-12">
                <label for="username" class="form-label">
                  <i class="fas fa-at me-2 text-accent-red"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Choose a unique username" required>
                <div class="invalid-feedback">Please choose a username.</div>
              </div>

              <div class="col-12">
                <label for="account_type" class="form-label">
                  <i class="fas fa-users me-2 text-primary-orange"></i>Account Type
                </label>
                <select class="form-select" id="account_type" name="account_type" required>
                  <option value="" disabled selected>-- Select Your Role --</option>
                  <option value="Student">Student</option>
                  <option value="Admin">Admin</option>
                </select>
                <div class="invalid-feedback">Please select your account type.</div>
              </div>

              <div class="col-md-6">
                <label for="password" class="form-label">
                  <i class="fas fa-lock me-2 text-secondary-green"></i>Password
                </label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password" 
                         placeholder="Create a strong password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Please create a password.</div>
              </div>

              <div class="col-md-6">
                <label for="confirm_password" class="form-label">
                  <i class="fas fa-lock me-2 text-accent-red"></i>Confirm Password
                </label>
                <div class="input-group">
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                         placeholder="Re-enter your password" required>
                  <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Please confirm your password.</div>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="terms" required>
                  <label class="form-check-label" for="terms">
                    I agree to the <a href="PrivacyPolicy.html" target="_blank" class="text-decoration-none">Terms of Service</a> 
                    and <a href="PrivacyPolicy.html" target="_blank" class="text-decoration-none">Privacy Policy</a>
                  </label>
                  <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                </div>
              </div>

              <div class="col-12">
                <button type="submit" class="btn-food w-100">
                  <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
              </div>
            </div>
          </form>

          <div class="text-center mt-4">
            <p class="mb-0">Already have an account? 
              <a href="Login.php" class="text-decoration-none fw-bold">
                <i class="fas fa-sign-in-alt me-1"></i>Sign In Here
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Benefits Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col-12">
        <h2 class="section-title">Why Join Eat@N?</h2>
        <p class="lead text-muted">Discover the benefits of being part of our community</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-shipping-fast fa-3x text-primary-orange mb-3"></i>
          <h5>Fast Delivery</h5>
          <p class="text-muted">Quick and reliable food delivery across campus</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-tags fa-3x text-secondary-green mb-3"></i>
          <h5>Exclusive Offers</h5>
          <p class="text-muted">Special discounts and deals for registered users</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-history fa-3x text-accent-red mb-3"></i>
          <h5>Order History</h5>
          <p class="text-muted">Track your orders and reorder your favorites</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="benefit-card text-center p-4">
          <i class="fas fa-heart fa-3x text-primary-orange mb-3"></i>
          <h5>Favorites</h5>
          <p class="text-muted">Save your favorite restaurants and dishes</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password Toggle Functionality
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    if (password.type === 'password') {
        password.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        password.type = 'password';
        icon.className = 'fas fa-eye';
    }
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const confirmPassword = document.getElementById('confirm_password');
    const icon = this.querySelector('i');
    if (confirmPassword.type === 'password') {
        confirmPassword.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        confirmPassword.type = 'password';
        icon.className = 'fas fa-eye';
    }
});

// Form Validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    document.getElementById('confirm_password').setCustomValidity("Passwords don't match");
                } else {
                    document.getElementById('confirm_password').setCustomValidity('');
                }
                
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
</body>
</html>