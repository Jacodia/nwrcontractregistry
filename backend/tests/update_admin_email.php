<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Update the admin email to a real one
    $stmt = $pdo->prepare("UPDATE users SET email = 'uraniathomas23@gmail.com' WHERE userid = 1");
    $stmt->execute();
    
    echo "Updated admin email successfully.\n";
    
    // Verify the update
    $stmt = $pdo->query("SELECT userid, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current users:\n";
    foreach($users as $user) {
        echo "User ID: {$user['userid']}, Email: {$user['email']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>