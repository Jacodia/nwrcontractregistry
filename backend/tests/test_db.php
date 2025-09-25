<?php
require_once 'ContractNotifier.php';

echo "Testing database connection and contract data...\n";

$host = 'localhost';
$db   = 'nwr_crdb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Database connection successful!\n";
    
    // Check total contracts
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM contracts');
    $result = $stmt->fetch();
    echo "Total contracts in database: " . $result['total'] . "\n";
    
    // Check contracts with expiry dates
    $stmt = $pdo->query('SELECT parties, expiryDate FROM contracts LIMIT 5');
    $contracts = $stmt->fetchAll();
    echo "Sample contracts:\n";
    foreach($contracts as $contract) {
        echo "- {$contract['parties']}: expires on {$contract['expiryDate']}\n";
    }
    
    // Check today's date and upcoming expiries
    $today = new DateTime();
    echo "Today's date: " . $today->format('Y-m-d') . "\n";
    
    // Check contracts expiring in next 90 days
    $stmt = $pdo->prepare("
        SELECT parties, expiryDate, DATEDIFF(expiryDate, :today) as days_until_expiry
        FROM contracts 
        WHERE DATEDIFF(expiryDate, :today) BETWEEN 0 AND 90
        ORDER BY expiryDate
    ");
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $upcoming = $stmt->fetchAll();
    
    echo "Contracts expiring in next 90 days:\n";
    foreach($upcoming as $contract) {
        echo "- {$contract['parties']}: expires in {$contract['days_until_expiry']} days ({$contract['expiryDate']})\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>