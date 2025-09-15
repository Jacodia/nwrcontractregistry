<?php
// Start session (optional if you want to track logged-in users)
session_start();

// Include the DB connection
require_once 'config/db.php'; // path to your db.php file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $email = $_POST['email'];
        $password = $_POST['password']; // Do NOT hash here

        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM login WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Verify password against stored hash
                if (password_verify($password, $user['password'])) {

                    // Optional: store user in session
                    $_SESSION['user_email'] = $user['email'];

                    // Redirect to dashboard.html in frontend/pages/
                    header("Location: ../frontend/pages/dashboard.html");
                    exit(); // stop execution

                } else {
                    echo "Incorrect password.";
                }
            } else {
                echo "User not found.";
            }

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

    } else {
        echo "Email and password are required.";
    }
}
?>
