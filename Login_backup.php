<?php
session_start();
require_once 'includes/connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, account_type FROM users WHERE username = ?");
        if (!$stmt) {
            $error = "Error preparing query: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['account_type'] = $user['account_type'];

                    if ($user['account_type'] === 'Admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: HomePage.php");
                    }
                    exit;
                } else {
                    $error = "Invalid username or password";
                }
            } else {
                $error = "Invalid username or password";
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
    <title>Eat@N | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            background-color: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #111;
            border: 1px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.6);
        }
        .form-control {
            background-color: #222;
            border: 1px solid #444;
            color: #fff;
        }
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
        }
        .btn-danger {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <h2 class="text-center mb-4" style="color: #dc3545;">Login</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Login</button>
                    </form>
                    <p class="text-center mt-3">Don't have an account? <a href="SignUp.php" style="color: #dc3545;">Sign Up</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>