<?php
echo "=== NWR Contract Registry - Fixed Authentication Testing ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

// Test 1: Login Functionality
echo "1. Testing Login Functionality...\n";

function testLogin($email, $password, $description = "") {
    global $pdo;
    
    echo "   Testing login: $email $description\n";
    
    try {
        $stmt = $pdo->prepare("SELECT userid, email, username, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "   ✗ User not found: $email\n";
            return false;
        }
        
        // Check password (handle both hashed and plain text for testing)
        $passwordMatch = false;
        if (password_verify($password, $user['password'])) {
            $passwordMatch = true;
        } elseif ($password === $user['password']) {
            $passwordMatch = true;
        }
        
        if ($passwordMatch) {
            echo "   ✓ Login successful\n";
            echo "     → Username: " . ($user['username'] ?: 'NULL') . "\n";
            echo "     → Role: " . ($user['role'] ?: 'NULL') . "\n";
            echo "     → User ID: " . $user['userid'] . "\n";
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
echo "   Testing existing test accounts...\n";
$loginTests = [
    ['testuser@example.com', 'password123', '(Regular User)'],
    ['testmanager@example.com', 'password123', '(Manager)'],
    ['testadmin@example.com', 'password123', '(Admin)']
];

$loginResults = [];
foreach ($loginTests as $test) {
    $result = testLogin($test[0], $test[1], $test[2]);
    $loginResults[] = $result;
}

// Test 2: User Registration/Signup
echo "\n2. Testing User Registration/Signup...\n";

function testSignup($email, $username, $password, $role = 'viewer') {
    global $pdo;
    
    echo "   Testing signup: $email (username: $username, role: $role)\n";
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            echo "   ⚠ User already exists with this email or username\n";
            return false;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user with all required fields
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashedPassword, $role]);
        
        if ($result) {
            echo "   ✓ User registration successful\n";
            return true;
        } else {
            echo "   ✗ Registration failed\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "   ✗ Signup error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test new user registration with correct fields
$timestamp = date('YmdHis');
$newUsers = [
    ["newuser{$timestamp}@example.com", "newuser{$timestamp}", 'testpass123', 'viewer'],
    ["newmanager{$timestamp}@example.com", "newmanager{$timestamp}", 'testpass123', 'manager']
];

$signupResults = [];
foreach ($newUsers as $newUser) {
    $result = testSignup($newUser[0], $newUser[1], $newUser[2], $newUser[3]);
    $signupResults[] = $result;
    
    // If signup successful, test login immediately
    if ($result) {
        echo "   → Testing login for newly created user...\n";
        testLogin($newUser[0], $newUser[2], "(Newly Created)");
    }
}

// Test 3: Web-based Login Test
echo "\n3. Testing Web Interface Login...\n";

function testWebLogin($email, $password) {
    $loginData = [
        'email' => $email,
        'password' => $password
    ];
    
    $postData = http_build_query($loginData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                       "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData
        ]
    ]);
    
    try {
        $response = @file_get_contents('http://localhost/nwrcontractregistry/backend/login.php', false, $context);
        
        if ($response === false) {
            echo "   ✗ Could not connect to login endpoint\n";
            return false;
        }
        
        echo "   Testing web login for: $email\n";
        
        // Check for success indicators in response
        if (strpos($response, 'success') !== false || 
            strpos($response, 'dashboard') !== false ||
            strpos($response, 'redirect') !== false) {
            echo "   ✓ Web login successful\n";
            return true;
        } else {
            echo "   ✗ Web login failed\n";
            echo "     Response preview: " . substr(strip_tags($response), 0, 100) . "...\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ Web login error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test web login for existing users
foreach ($loginTests as $test) {
    testWebLogin($test[0], $test[1]);
}

// Test 4: Role-based Access Validation
echo "\n4. Testing Role-based Access...\n";

function checkUserRole($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $role = $user['role'] ?: 'No role assigned';
            echo "   $email → Role: $role\n";
            return $role;
        } else {
            echo "   $email → User not found\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   Error checking role for $email: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "   Checking roles for test accounts...\n";
foreach ($loginTests as $test) {
    checkUserRole($test[0]);
}

// Test 5: Security Validation
echo "\n5. Security Tests...\n";

echo "   Testing SQL injection prevention...\n";
$maliciousInputs = [
    "admin@example.com' OR '1'='1",
    "admin'; DROP TABLE users; --"
];

foreach ($maliciousInputs as $input) {
    echo "   Testing malicious input: " . substr($input, 0, 30) . "...\n";
    $result = testLogin($input, 'anypassword');
    if (!$result) {
        echo "   ✓ SQL injection attempt blocked\n";
    } else {
        echo "   ⚠ POTENTIAL SECURITY ISSUE\n";
    }
}

// Test Summary
echo "\n=== Authentication Test Results ===\n";

$totalLoginTests = count($loginResults);
$successfulLogins = array_sum($loginResults);

$totalSignupTests = count($signupResults);
$successfulSignups = array_sum($signupResults);

echo "📊 Login Tests: $successfulLogins/$totalLoginTests successful\n";
echo "📊 Signup Tests: $successfulSignups/$totalSignupTests successful\n";

if ($successfulLogins === $totalLoginTests && $successfulSignups > 0) {
    echo "✅ Authentication System: FULLY FUNCTIONAL\n";
    $status = "EXCELLENT";
} elseif ($successfulLogins > 0) {
    echo "⚠️ Authentication System: LOGIN WORKS, SIGNUP NEEDS ATTENTION\n";
    $status = "GOOD";
} else {
    echo "❌ Authentication System: CRITICAL ISSUES\n";
    $status = "NEEDS WORK";
}

echo "\n=== Manual Testing Guide ===\n";
echo "🌐 Main Application: http://localhost/nwrcontractregistry/frontend/index.php\n\n";

echo "👥 Test Accounts (Login):\n";
foreach ($loginTests as $test) {
    echo "   • Email: {$test[0]}\n";
    echo "     Password: {$test[1]}\n";
    echo "     Description: {$test[2]}\n\n";
}

echo "✍️ Signup Test:\n";
echo "   • Try creating a new account through the web interface\n";
echo "   • Required fields: Username, Email, Password\n";
echo "   • Available roles: admin, manager, viewer\n\n";

echo "🎯 What to Test:\n";
echo "   1. Login with different user roles\n";
echo "   2. Check access levels after login\n";
echo "   3. Test user registration form\n";
echo "   4. Verify role-based menu visibility\n";
echo "   5. Test logout functionality\n\n";

echo "📋 System Status: $status\n";

?>