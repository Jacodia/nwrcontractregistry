<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "🗃️ USERS TABLE STRUCTURE\n";
    echo "=======================\n\n";
    
    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "Field: " . $col['Field'] . "\n";
        echo "Type: " . $col['Type'] . "\n";
        echo "Null: " . $col['Null'] . "\n";
        echo "Default: " . ($col['Default'] ?? 'NULL') . "\n";
        echo "Extra: " . ($col['Extra'] ?? 'None') . "\n";
        echo "---\n";
    }
    
    echo "\n🧪 TESTING USER CREATION\n";
    echo "========================\n\n";
    
    // Test the exact query that's failing
    echo "Testing INSERT query...\n";
    
    $username = 'RDoe';
    $email = 'RDoe@hell.lab';
    $password = 'LDAP_AUTH';
    $role = 'user';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $password, $role]);
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            echo "✅ User creation successful! User ID: $userId\n";
            
            // Clean up test user
            $pdo->prepare("DELETE FROM users WHERE userid = ?")->execute([$userId]);
            echo "🧹 Test user cleaned up\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ SQL Error: " . $e->getMessage() . "\n";
        echo "Error Code: " . $e->getCode() . "\n";
    }
    
    echo "\n🔍 CHECKING FOR EXISTING USER\n";
    echo "=============================\n\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['RDoe@hell.lab']);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "User already exists:\n";
        foreach ($existingUser as $key => $value) {
            echo "$key: $value\n";
        }
    } else {
        echo "No existing user found with email RDoe@hell.lab\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}