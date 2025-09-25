<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    echo "Checking contracts and their managers:\n";
    
    $stmt = $pdo->prepare("
        SELECT c.parties, c.expiryDate, c.manager_id, u.email AS manager_email
        FROM contracts c
        LEFT JOIN users u ON c.manager_id = u.userid
        WHERE DATEDIFF(c.expiryDate, CURDATE()) = 5
    ");
    $stmt->execute();
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($contracts as $contract) {
        echo "Contract: {$contract['parties']}\n";
        echo "Expiry: {$contract['expiryDate']}\n";
        echo "Manager ID: {$contract['manager_id']}\n";
        echo "Manager Email: " . ($contract['manager_email'] ?? 'NO EMAIL FOUND') . "\n";
        echo "---\n";
    }
    
    // Also check all users
    echo "\nAll users in database:\n";
    $stmt = $pdo->query("SELECT userid, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($users as $user) {
        echo "User ID: {$user['userid']}, Email: {$user['email']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>