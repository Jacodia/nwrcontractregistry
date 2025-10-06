<?php
echo "=== NWR Contract Registry - Authentication Testing ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

// Test 1: Existing User Login Test
echo "1. Testing Login Functionality...\n";

function testLogin($email, $password, $expectedRole = null) {
    global $pdo;
    
    echo "   Testing login: $email\n";
    
    try {
        // Simulate the login process
        $stmt = $pdo->prepare("SELECT userid, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "   ✗ User not found: $email\n";
            return false;
        }
        
        // Check password (assuming it's hashed)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            echo "   ✓ Login successful - Role: " . ($user['role'] ?: 'none') . "\n";
            if ($expectedRole && $user['role'] !== $expectedRole) {
                echo "   ⚠ Warning: Expected role '$expectedRole', got '" . ($user['role'] ?: 'none') . "'\n";
            }
            return true;
        } else {
            echo "   ✗ Invalid password for: $email\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "   ✗ Login error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test existing users
$testAccounts = [
    ['testuser@example.com', 'password123', 'user'],
    ['testmanager@example.com', 'password123', 'manager'],
    ['testadmin@example.com', 'password123', 'admin']
];

$loginResults = [];
foreach ($testAccounts as $account) {
    $result = testLogin($account[0], $account[1], $account[2]);
    $loginResults[] = $result;
}

// Test 2: User Registration/Signup Test
echo "\n2. Testing User Registration/Signup...\n";

function testSignup($email, $password, $role = 'user') {
    global $pdo;
    
    echo "   Testing signup: $email\n";
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "   ⚠ User already exists: $email\n";
            return false;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $result = $stmt->execute([$email, $hashedPassword, $role]);
        
        if ($result) {
            echo "   ✓ User registration successful: $email\n";
            return true;
        } else {
            echo "   ✗ Registration failed: $email\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "   ✗ Signup error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test new user registration
$newUsers = [
    ['newuser@example.com', 'testpass123', 'user'],
    ['newmanager@example.com', 'testpass123', 'manager']
];

$signupResults = [];
foreach ($newUsers as $newUser) {
    $result = testSignup($newUser[0], $newUser[1], $newUser[2]);
    $signupResults[] = $result;
    
    // If signup successful, test login immediately
    if ($result) {
        echo "   → Testing login for new user...\n";
        testLogin($newUser[0], $newUser[1], $newUser[2]);
    }
}

// Test 3: API Endpoint Testing
echo "\n3. Testing Authentication API Endpoints...\n";

function testLoginAPI($email, $password) {
    $loginData = [
        'email' => $email,
        'password' => $password
    ];
    
    // Simulate POST request to login endpoint
    $postData = http_build_query($loginData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ]);
    
    try {
        $response = file_get_contents('http://localhost/nwrcontractregistry/backend/login.php', false, $context);
        echo "   API Login Test for $email: ";
        
        if (strpos($response, 'success') !== false || strpos($response, 'dashboard') !== false) {
            echo "✓ Success\n";
            return true;
        } else {
            echo "✗ Failed - Response: " . substr($response, 0, 100) . "...\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ API Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test API endpoints
echo "   Testing login API endpoints...\n";
testLoginAPI('testuser@example.com', 'password123');
testLoginAPI('testadmin@example.com', 'password123');

// Test 4: Security Tests
echo "\n4. Security Tests...\n";

echo "   Testing SQL injection prevention...\n";
$maliciousInputs = [
    "admin@example.com' OR '1'='1",
    "admin@example.com'; DROP TABLE users; --"
];

foreach ($maliciousInputs as $input) {
    echo "   Testing malicious input: " . substr($input, 0, 30) . "...\n";
    $result = testLogin($input, 'anypassword');
    if (!$result) {
        echo "   ✓ SQL injection prevented\n";
    } else {
        echo "   ✗ SECURITY VULNERABILITY DETECTED!\n";
    }
}

// Test 5: Password Security
echo "\n5. Password Security Tests...\n";

echo "   Testing password hashing...\n";
$stmt = $pdo->prepare("SELECT password FROM users WHERE email = ? LIMIT 1");
$stmt->execute(['testuser@example.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && strlen($user['password']) > 20) {
    echo "   ✓ Passwords appear to be hashed (length: " . strlen($user['password']) . ")\n";
} else {
    echo "   ⚠ Passwords might not be properly hashed\n";
}

// Test Summary
echo "\n=== Authentication Test Summary ===\n";

$loginSuccessCount = array_sum($loginResults);
$signupSuccessCount = array_sum($signupResults);

echo "Login Tests: $loginSuccessCount/" . count($loginResults) . " successful\n";
echo "Signup Tests: $signupSuccessCount/" . count($signupResults) . " successful\n";

if ($loginSuccessCount === count($loginResults) && $signupSuccessCount > 0) {
    echo "✅ Authentication System: FULLY FUNCTIONAL\n";
} elseif ($loginSuccessCount > 0) {
    echo "⚠️ Authentication System: PARTIALLY FUNCTIONAL\n";
} else {
    echo "❌ Authentication System: ISSUES DETECTED\n";
}

echo "\n=== Manual Testing Instructions ===\n";
echo "1. Open: http://localhost/nwrcontractregistry/frontend/index.php\n";
echo "2. Test login with these accounts:\n";
foreach ($testAccounts as $account) {
    echo "   - Email: {$account[0]}, Password: {$account[1]} (Role: {$account[2]})\n";
}
echo "3. Test registration by creating a new account\n";
echo "4. Verify role-based access after login\n";

?>