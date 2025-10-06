<?php
echo "=== PHP MySQL Extensions Test ===\n";

// Check if PDO is loaded
if (extension_loaded('pdo')) {
    echo "✓ PDO extension is loaded\n";
} else {
    echo "✗ PDO extension is NOT loaded\n";
}

// Check if pdo_mysql is loaded
if (extension_loaded('pdo_mysql')) {
    echo "✓ pdo_mysql extension is loaded\n";
} else {
    echo "✗ pdo_mysql extension is NOT loaded\n";
}

// Check if mysqli is loaded
if (extension_loaded('mysqli')) {
    echo "✓ mysqli extension is loaded\n";
} else {
    echo "✗ mysqli extension is NOT loaded\n";
}

// List available PDO drivers
echo "\n=== Available PDO Drivers ===\n";
$drivers = PDO::getAvailableDrivers();
if (empty($drivers)) {
    echo "No PDO drivers available\n";
} else {
    foreach ($drivers as $driver) {
        echo "- $driver\n";
    }
}

// Test basic PDO MySQL connection
echo "\n=== Testing Database Connection ===\n";
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
    
    $host = $_ENV['DB_HOST'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $db = $_ENV['DB_NAME'];
    
    echo "Attempting to connect to: mysql:host=$host;dbname=$db\n";
    echo "Username: $user\n";
    
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connection successful!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $result['version'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>