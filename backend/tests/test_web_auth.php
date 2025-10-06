<?php
echo "=== Testing Web-based Authentication ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

function testWebSignup($username, $email, $password) {
    echo "Testing web signup: $email (username: $username)\n";
    
    $signupData = [
        'username' => $username,
        'email' => $email,
        'password' => $password
    ];
    
    $postData = http_build_query($signupData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                       "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData
        ]
    ]);
    
    try {
        $response = @file_get_contents('http://localhost/nwrcontractregistry/backend/auth_handler.php?action=register', false, $context);
        
        if ($response === false) {
            echo "   ✗ Could not connect to registration endpoint\n";
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "   ✓ Web signup successful!\n";
            echo "     User ID: " . ($result['user']['id'] ?? 'Unknown') . "\n";
            echo "     Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            return true;
        } else {
            echo "   ✗ Web signup failed\n";
            echo "     Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            echo "     Response: " . substr($response, 0, 200) . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ Web signup error: " . $e->getMessage() . "\n";
        return false;
    }
}

function testWebLogin($email, $password) {
    echo "Testing web login: $email\n";
    
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
        $response = @file_get_contents('http://localhost/nwrcontractregistry/backend/auth_handler.php?action=login', false, $context);
        
        if ($response === false) {
            echo "   ✗ Could not connect to login endpoint\n";
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "   ✓ Web login successful!\n";
            echo "     User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
            echo "     Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            return true;
        } else {
            echo "   ✗ Web login failed\n";
            echo "     Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ Web login error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test new user signup
$timestamp = date('YmdHis');
$testEmail = "webtest{$timestamp}@example.com";
$testUsername = "webtest{$timestamp}";
$testPassword = "testpass123";

echo "1. Testing Web Signup...\n";
$signupResult = testWebSignup($testUsername, $testEmail, $testPassword);

if ($signupResult) {
    echo "\n2. Testing Web Login with new account...\n";
    $loginResult = testWebLogin($testEmail, $testPassword);
} else {
    echo "\n2. Testing Web Login with existing account...\n";
    $loginResult = testWebLogin('testuser@example.com', 'password123');
}

echo "\n3. Testing existing accounts...\n";
$existingAccounts = [
    ['testuser@example.com', 'password123'],
    ['testmanager@example.com', 'password123'],
    ['testadmin@example.com', 'password123']
];

foreach ($existingAccounts as $account) {
    testWebLogin($account[0], $account[1]);
}

echo "\n=== Web Authentication Test Summary ===\n";

if ($signupResult && $loginResult) {
    echo "✅ BOTH LOGIN AND SIGNUP WORKING PERFECTLY!\n";
} elseif ($loginResult) {
    echo "⚠️ LOGIN WORKS, SIGNUP MAY HAVE ISSUES\n";
} else {
    echo "❌ AUTHENTICATION ISSUES DETECTED\n";
}

echo "\n🌐 Ready for manual testing at:\n";
echo "http://localhost/nwrcontractregistry/frontend/index.php\n";

?>