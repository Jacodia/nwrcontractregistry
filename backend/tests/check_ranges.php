<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $today = new DateTime();
    
    echo "Current contracts by range:\n\n";
    
    // 30-day range (1-30 days)
    $stmt = $pdo->prepare("
        SELECT c.parties, c.expiryDate, u.email, DATEDIFF(c.expiryDate, :today) as days_left
        FROM contracts c
        INNER JOIN users u ON c.manager_id = u.userid
        WHERE DATEDIFF(c.expiryDate, :today) BETWEEN 1 AND 30
        ORDER BY c.expiryDate ASC
    ");
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $contracts30 = $stmt->fetchAll();
    
    echo "30-day range (1-30 days): " . count($contracts30) . " contracts\n";
    foreach($contracts30 as $contract) {
        echo "  - {$contract['parties']}: {$contract['days_left']} days ({$contract['email']})\n";
    }
    
    // 60-day range (31-60 days)
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $stmt = $pdo->prepare("
        SELECT c.parties, c.expiryDate, u.email, DATEDIFF(c.expiryDate, :today) as days_left
        FROM contracts c
        INNER JOIN users u ON c.manager_id = u.userid
        WHERE DATEDIFF(c.expiryDate, :today) BETWEEN 31 AND 60
        ORDER BY c.expiryDate ASC
    ");
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $contracts60 = $stmt->fetchAll();
    
    echo "\n60-day range (31-60 days): " . count($contracts60) . " contracts\n";
    foreach($contracts60 as $contract) {
        echo "  - {$contract['parties']}: {$contract['days_left']} days ({$contract['email']})\n";
    }
    
    // 90-day range (61-90 days)
    $stmt = $pdo->prepare("
        SELECT c.parties, c.expiryDate, u.email, DATEDIFF(c.expiryDate, :today) as days_left
        FROM contracts c
        INNER JOIN users u ON c.manager_id = u.userid
        WHERE DATEDIFF(c.expiryDate, :today) BETWEEN 61 AND 90
        ORDER BY c.expiryDate ASC
    ");
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $contracts90 = $stmt->fetchAll();
    
    echo "\n90-day range (61-90 days): " . count($contracts90) . " contracts\n";
    foreach($contracts90 as $contract) {
        echo "  - {$contract['parties']}: {$contract['days_left']} days ({$contract['email']})\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>