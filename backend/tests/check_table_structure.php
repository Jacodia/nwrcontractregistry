<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE contracts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Contracts table structure:\n";
    foreach($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>