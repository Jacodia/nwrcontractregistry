<?php
// backend/login.php - Updated to work with new auth system
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

Auth::init($pdo);

// Redirect already logged-in users
if (Auth::isLoggedIn()) {
    header("Location: ../frontend/pages/dashboard.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $result = Auth::login($email, $password);
        
        if ($result['success']) {
            header("Location: ../frontend/pages/dashboard.html");
            exit();
        } else {
            $_SESSION['error'] = $result['error'];
            header("Location: ../frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please enter email and password.";
        header("Location: ../frontend/index.php");
        exit();
    }
}

// If not POST request, redirect to login page
header("Location: ../frontend/index.php");
exit();
?>
