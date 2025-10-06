<?php
// Test auth_handler directly
echo "<h2>🔐 Authentication Handler Test</h2>";

echo "<h3>1. Test Auth Status Check</h3>";
try {
    $response = file_get_contents('http://localhost:8080/nwrcontractregistry/backend/auth_handler.php?action=check');
    echo "<strong>Response:</strong><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<strong>Parsed JSON:</strong><br>";
        echo "Logged in: " . ($data['loggedIn'] ? 'Yes' : 'No') . "<br>";
        if (isset($data['user'])) {
            echo "User: " . htmlspecialchars($data['user']['username']) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>2. Test Login Form</h3>";
?>
<form method="post" action="../auth_handler.php?action=login" style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
    <div style="margin: 10px 0;">
        <label>Email:</label><br>
        <input type="email" name="email" value="testuser@example.com" style="padding: 5px; width: 200px;">
    </div>
    <div style="margin: 10px 0;">
        <label>Password:</label><br>
        <input type="password" name="password" value="password123" style="padding: 5px; width: 200px;">
    </div>
    <button type="submit" style="background: #0758aa; color: white; padding: 10px 20px; border: none; border-radius: 4px;">Test Login</button>
</form>

<?php
echo "<h3>3. Available Test Users</h3>";
try {
    require_once '../config/db.php';
    $stmt = $pdo->query("SELECT username, email, role FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Username</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Note:</strong> Use password 'password123' for any test user</p>";
    } else {
        echo "<p>No users found. <a href='create_test_users.php'>Create test users</a></p>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>Quick Links</h3>";
echo "<a href='../../../frontend/index.php' style='margin-right: 15px; padding: 10px; background: #0758aa; color: white; text-decoration: none; border-radius: 4px;'>🔑 Main Login Page</a>";
echo "<a href='create_test_users.php' style='margin-right: 15px; padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>👥 Create Test Users</a>";
?>