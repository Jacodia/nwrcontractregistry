<?php
$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $today = new DateTime();
    
    // Add test contracts for different ranges
    $date5 = (clone $today)->modify('+5 days')->format('Y-m-d');    // In 30-day range (1-30)
    $date15 = (clone $today)->modify('+15 days')->format('Y-m-d');  // In 30-day range (1-30)
    $date35 = (clone $today)->modify('+35 days')->format('Y-m-d');  // In 60-day range (31-60)
    $date45 = (clone $today)->modify('+45 days')->format('Y-m-d');  // In 60-day range (31-60)
    $date65 = (clone $today)->modify('+65 days')->format('Y-m-d');  // In 90-day range (61-90)
    $date75 = (clone $today)->modify('+75 days')->format('Y-m-d');  // In 90-day range (61-90)
    
    // Insert test contracts for ranges
    $stmt = $pdo->prepare("
        INSERT INTO contracts (parties, expiryDate, manager_id, typeOfContract) VALUES
        ('Range Test 30A - 5 days', :date5, 1, 'Test Contract'),
        ('Range Test 30B - 15 days', :date15, 2, 'Test Contract'),
        ('Range Test 60A - 35 days', :date35, 1, 'Test Contract'),
        ('Range Test 60B - 45 days', :date45, 2, 'Test Contract'),
        ('Range Test 90A - 65 days', :date65, 1, 'Test Contract'),
        ('Range Test 90B - 75 days', :date75, 2, 'Test Contract')
    ");
    
    $stmt->execute([
        'date5' => $date5,
        'date15' => $date15,
        'date35' => $date35,
        'date45' => $date45,
        'date65' => $date65,
        'date75' => $date75
    ]);
    
    echo "Added range test contracts:\n";
    echo "30-day range (1-30 days):\n";
    echo "  - Range Test 30A - 5 days: expires on $date5\n";
    echo "  - Range Test 30B - 15 days: expires on $date15\n";
    echo "60-day range (31-60 days):\n";
    echo "  - Range Test 60A - 35 days: expires on $date35\n";
    echo "  - Range Test 60B - 45 days: expires on $date45\n";
    echo "90-day range (61-90 days):\n";
    echo "  - Range Test 90A - 65 days: expires on $date65\n";
    echo "  - Range Test 90B - 75 days: expires on $date75\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>