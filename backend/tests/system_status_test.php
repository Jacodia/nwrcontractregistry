<?php

echo "=== NWR Contract Registry - System Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Environment Configuration
echo "1. Testing Environment Configuration...\n";
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
    echo "   ✓ .env file loaded successfully\n";
    echo "   ✓ Database: " . $_ENV['DB_NAME'] . "\n";
    echo "   ✓ Host: " . $_ENV['DB_HOST'] . "\n";
} catch (Exception $e) {
    echo "   ✗ Environment Error: " . $e->getMessage() . "\n";
}

// Test 2: Database Connection
echo "\n2. Testing Database Connection...\n";
try {
    require_once __DIR__ . '/../config/db.php';
    echo "   ✓ Database connection successful\n";
    
    // Test database tables
    $tables = ['users', 'contracts', 'contract_types'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Database Error: " . $e->getMessage() . "\n";
}

// Test 3: Test Users
echo "\n3. Checking Test Users...\n";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT email, role FROM users WHERE email LIKE 'test%'");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            foreach ($users as $user) {
                echo "   ✓ " . $user['email'] . " (" . $user['role'] . ")\n";
            }
        } else {
            echo "   ⚠ No test users found\n";
        }
    } catch (Exception $e) {
        echo "   ✗ User check failed: " . $e->getMessage() . "\n";
    }
}

// Test 4: File Upload Directory
echo "\n4. Testing File System...\n";
$uploadDir = __DIR__ . '/../uploads';
if (is_dir($uploadDir)) {
    echo "   ✓ Upload directory exists\n";
    if (is_writable($uploadDir)) {
        echo "   ✓ Upload directory is writable\n";
    } else {
        echo "   ✗ Upload directory is not writable\n";
    }
} else {
    echo "   ✗ Upload directory missing\n";
}

// Test 5: Sample Contracts
echo "\n5. Checking Sample Contracts...\n";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✓ Total contracts in database: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT parties, expiryDate FROM contracts ORDER BY contractid DESC LIMIT 3");
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   Recent contracts:\n";
            foreach ($contracts as $contract) {
                echo "     - " . $contract['parties'] . " (expires: " . $contract['expiryDate'] . ")\n";
            }
        }
    } catch (Exception $e) {
        echo "   ✗ Contract check failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "System Status: ";
if (isset($pdo)) {
    echo "✓ OPERATIONAL\n";
    echo "\nNext steps:\n";
    echo "1. Access the application at: http://localhost/nwrcontractregistry/frontend/\n";
    echo "2. Login with test credentials (see dashboard for details)\n";
    echo "3. Test contract management features\n";
} else {
    echo "✗ DATABASE ISSUES DETECTED\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check MySQL service is running\n";
    echo "2. Verify database credentials in .env file\n";
    echo "3. Ensure PHP MySQL extensions are enabled\n";
}

?>