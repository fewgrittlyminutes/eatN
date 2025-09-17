<?php
session_start();
require_once 'includes/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: HomePage.php");
    exit;
}

// Handle success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success = filter_var($_GET['success'], FILTER_SANITIZE_SPECIAL_CHARS);
}
if (isset($_GET['error'])) {
    $error = filter_var($_GET['error'], FILTER_SANITIZE_SPECIAL_CHARS);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate and sanitize input
        $username = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($username)) {
            throw new Exception("Username is required");
        }
        if (empty($password)) {
            throw new Exception("Password is required");
        }
        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long");
        }

        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, username, password, account_type FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['account_type'] = $user['account_type'];

                // Log successful login
                error_log("Successful login for user: " . $user['username']);

                // Redirect based on account type or redirect parameter
                $redirect_url = $_GET['redirect'] ?? '';
                if (!empty($redirect_url)) {
                    header("Location: " . $redirect_url);
                } elseif ($user['account_type'] === 'Admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: HomePage.php");
                }
                exit;
            } else {
                throw new Exception("Invalid username or password");
            }
        } else {
            throw new Exception("Invalid username or password");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Eat@N</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Food Theme CSS -->
    <link rel="stylesheet" href="css/food-theme.css">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Login to your Eat@N account to order delicious food from NSBM campus restaurants.">
    <meta name="keywords" content="login, eat@n, NSBM food, student portal, food ordering">
    
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--accent-red) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('images/food-pattern.png') repeat;
            opacity: 0.1;
        }
        
        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            position: relative;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--dark-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-orange) 0%, var(--accent-red) 100%);
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .brand-logo {
            width: 80px;
            height: 80px;
            background: var(--primary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .form-floating .form-control {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-md);
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            z-index: 10;
        }
        
        .login-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-links a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-links a:hover {
            color: var(--accent-red);
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-header,
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="brand-logo">
                    <i class="fas fa-utensils fa-2x text-white"></i>
                </div>
                <h2 class="mb-2">Welcome Back!</h2>
                <p class="mb-0 opacity-75">Sign in to your Eat@N account</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Alerts -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-food-error alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-food-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" id="loginForm" novalidate>
                    <?php if (isset($_GET['redirect'])): ?>
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    
                    <!-- Username Field -->
                    <div class="form-floating mb-3">
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Username"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               required>
                        <label for="username">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                        <div class="invalid-feedback">
                            Please enter your username (at least 3 characters).
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Password"
                               required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                        <div class="invalid-feedback">
                            Please enter your password (at least 6 characters).
                        </div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe">
                            Remember me on this device
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-food w-100 py-3 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                    </button>
                </form>
                
                <!-- Links -->
                <div class="login-links">
                    <p class="mb-2">
                        Don't have an account? 
                        <a href="SignUp.php">Create one here</a>
                    </p>
                    <p class="mb-0">
                        <a href="#" onclick="showForgotPassword()">Forgot your password?</a>
                    </p>
                </div>
                
                <!-- Back to Home -->
                <div class="text-center mt-4 pt-3 border-top">
                    <a href="HomePage.php" class="btn btn-outline-food">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Reset validation states
            username.classList.remove('is-invalid');
            password.classList.remove('is-invalid');
            
            // Validate username
            if (!username.value.trim() || username.value.trim().length < 3) {
                username.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (!password.value || password.value.length < 6) {
                password.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Password toggle function
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Forgot password function
        function showForgotPassword() {
            alert('Please contact the administrator at admin@eatn.nsbm.ac.lk to reset your password.');
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
        
        // Add focus effects
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>