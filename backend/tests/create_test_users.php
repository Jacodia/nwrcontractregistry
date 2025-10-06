<?php
// Create test users for testing the system
require_once '../config/db.php';

echo "<h2>🛠️ Test User Creation</h2>";

// Test users to create
$testUsers = [
    [
        'username' => 'testuser',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'role' => 'user'
    ],
    [
        'username' => 'testmanager',
        'email' => 'testmanager@example.com',  
        'password' => 'password123',
        'role' => 'manager'
    ],
    [
        'username' => 'testadmin',
        'email' => 'testadmin@example.com',
        'password' => 'password123',
        'role' => 'admin'
    ]
];

foreach ($testUsers as $user) {
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        
        if ($stmt->fetch()) {
            echo "⚠️ User {$user['username']} ({$user['email']}) already exists<br>";
            continue;
        }
        
        // Create new user
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$user['username'], $user['email'], $hashedPassword, $user['role']])) {
            $userId = $pdo->lastInsertId();
            echo "✅ Created {$user['role']} user: {$user['username']} (ID: $userId)<br>";
            echo "&nbsp;&nbsp;&nbsp;Email: {$user['email']} | Password: {$user['password']}<br>";
        } else {
            echo "❌ Failed to create user: {$user['username']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error creating user {$user['username']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Test Credentials Summary</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Use these credentials for testing:</strong><br><br>";
echo "<strong>Regular User:</strong><br>";
echo "Email: testuser@example.com<br>";
echo "Password: password123<br>";
echo "Access: Dashboard only<br><br>";

echo "<strong>Manager:</strong><br>";
echo "Email: testmanager@example.com<br>";
echo "Password: password123<br>";
echo "Access: Dashboard + Contract Management<br><br>";

echo "<strong>Administrator:</strong><br>";
echo "Email: testadmin@example.com<br>";
echo "Password: password123<br>";
echo "Access: All features including User Management<br>";
echo "</div>";

echo "<h3>Quick Test Links</h3>";
echo "<a href='../../../frontend/index.php' style='margin-right: 15px; padding: 10px; background: #0758aa; color: white; text-decoration: none; border-radius: 4px;'>🔑 Test Login</a>";
echo "<a href='comprehensive_test.html' style='margin-right: 15px; padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>🧪 Run All Tests</a>";
?>