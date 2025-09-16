<?php
session_start();
require_once 'config/db.php';

// Redirect logged-in users
if (isset($_SESSION['user_email'])) {
    header("Location: ../frontend/pages/dashboard.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true); // prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];

                header("Location: ../frontend/pages/dashboard.html");
                exit();
            } else {
                $_SESSION['error'] = "Invalid email or password.";
                header("Location: ../frontend/index.php");
                exit();
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = "Something went wrong. Please try again later.";
            header("Location: ../frontend/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please enter email and password.";
        header("Location: ../frontend/index.php");
        exit();
    }
}
?>
