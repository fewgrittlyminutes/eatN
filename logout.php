<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php?error=You are not logged in");
    exit;
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header("Location: Login.php?success=You have been successfully logged out");
exit;
?>