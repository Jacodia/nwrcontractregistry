<?php
// Quick database and functionality test
require_once '../config/db.php';

echo "<h2>🔍 Quick System Check</h2>";

// Test 1: Database Connection
echo "<h3>Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "✅ Database connected successfully<br>";
    echo "👥 Total users in database: $userCount<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: List existing users
echo "<h3>Existing Users</h3>";
try {
    $stmt = $pdo->query("SELECT userid, username, email, role FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['userid']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No users found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error fetching users: " . $e->getMessage() . "<br>";
}

// Test 3: List existing contracts
echo "<h3>Existing Contracts</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contracts");
    $contractCount = $stmt->fetch()['count'];
    echo "📋 Total contracts in database: $contractCount<br>";
    
    if ($contractCount > 0) {
        $stmt = $pdo->query("SELECT contractid, parties, typeOfContract, expiryDate FROM contracts ORDER BY contractid DESC LIMIT 5");
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Parties</th><th>Type</th><th>Expiry Date</th></tr>";
        foreach ($contracts as $contract) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contract['contractid']) . "</td>";
            echo "<td>" . htmlspecialchars($contract['parties']) . "</td>";
            echo "<td>" . htmlspecialchars($contract['typeOfContract']) . "</td>";
            echo "<td>" . htmlspecialchars($contract['expiryDate']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error fetching contracts: " . $e->getMessage() . "<br>";
}

// Test 4: Authentication endpoints
echo "<h3>Authentication Endpoints</h3>";
$endpoints = [
    'Login Handler' => '../login.php',
    'Auth Handler' => '../auth_handler.php',
    'Logout Handler' => '../logout.php'
];

foreach ($endpoints as $name => $file) {
    if (file_exists($file)) {
        echo "✅ $name: File exists<br>";
    } else {
        echo "❌ $name: File missing<br>";
    }
}

// Test 5: Test account suggestion
echo "<h3>Test Account Suggestions</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>For testing, you can:</strong><br>";
echo "1. <strong>Create a new test user</strong> using the registration form<br>";
echo "2. <strong>Use existing users</strong> if any are shown above<br>";
echo "3. <strong>Test admin features</strong> by creating an admin user<br>";
echo "<br><strong>Default test credentials to try:</strong><br>";
echo "Email: admin@nwr.com | Password: admin123<br>";
echo "Email: manager@nwr.com | Password: manager123<br>";
echo "Email: user@nwr.com | Password: user123<br>";
echo "</div>";

echo "<h3>Quick Links</h3>";
echo "<a href='../../../frontend/index.php' style='margin-right: 15px;'>🔑 Login Page</a>";
echo "<a href='comprehensive_test.html' style='margin-right: 15px;'>🧪 Comprehensive Tests</a>";
echo "<a href='test_file_upload_page.html' style='margin-right: 15px;'>📁 File Upload Test</a>";
?>