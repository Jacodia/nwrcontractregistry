<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $today = new DateTime();
    
    // Add test contracts for 30, 60, and 90 days from today
    $date30 = (clone $today)->modify('+30 days')->format('Y-m-d');
    $date60 = (clone $today)->modify('+60 days')->format('Y-m-d');
    $date90 = (clone $today)->modify('+90 days')->format('Y-m-d');
    
    // Insert test contracts
    $stmt = $pdo->prepare("
        INSERT INTO contracts (parties, expiryDate, manager_id, typeOfContract) VALUES
        ('Test Contract 30 Days', :date30, 2, 'Service Agreement'),
        ('Test Contract 60 Days', :date60, 1, 'License Agreement'),
        ('Test Contract 90 Days', :date90, 2, 'Maintenance Contract')
    ");
    
    $stmt->execute([
        'date30' => $date30,
        'date60' => $date60,
        'date90' => $date90
    ]);
    
    echo "Added test contracts:\n";
    echo "- Test Contract 30 Days: expires on $date30 (30 days from today)\n";
    echo "- Test Contract 60 Days: expires on $date60 (60 days from today)\n";
    echo "- Test Contract 90 Days: expires on $date90 (90 days from today)\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>