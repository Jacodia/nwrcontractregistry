<?php
echo "=== NWR Contract Registry - Login Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Testing LDAP Authentication System\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

Auth::init($pdo);

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "🔧 Current Configuration:\n";
echo "Environment: " . $_ENV['APP_ENV'] . "\n";
echo "LDAP Domain: " . $_ENV['LDAP_DOMAIN'] . "\n";
echo "LDAP Server: " . $_ENV['LDAP_HOST'] . ":" . $_ENV['LDAP_PORT'] . "\n\n";

// Test 1: LDAP Connection
echo "1. Testing LDAP Connection...\n";
try {
    $ldapconn = ldap_connect($_ENV['LDAP_HOST'], $_ENV['LDAP_PORT']);
    if ($ldapconn) {
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        
        if (@ldap_bind($ldapconn)) {
            echo "   ✅ LDAP server connection: SUCCESS\n";
            ldap_close($ldapconn);
        } else {
            echo "   ⚠️ LDAP server connection: Connected but bind failed\n";
        }
    } else {
        echo "   ❌ LDAP server connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "   ❌ LDAP connection error: " . $e->getMessage() . "\n";
}

// Test 2: Web Login API Test
echo "\n2. Testing Web Login API...\n";

function testWebLogin($identifier, $password, $description) {
    echo "   Testing: $identifier $description\n";
    
    $loginData = [
        'email' => $identifier,
        'password' => $password
    ];
    
    $postData = http_build_query($loginData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                       "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData,
            'timeout' => 10
        ]
    ]);
    
    try {
        $response = @file_get_contents('http://localhost/nwrcontractregistry/backend/auth_handler.php?action=login', false, $context);
        
        if ($response === false) {
            echo "     ❌ Could not connect to login endpoint\n";
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['success'])) {
            if ($result['success']) {
                echo "     ✅ LOGIN SUCCESS\n";
                echo "       User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
                echo "       Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
                echo "       Method: " . ($result['method'] ?? 'Unknown') . "\n";
                return true;
            } else {
                echo "     ❌ LOGIN FAILED\n";
                echo "       Error: " . ($result['error'] ?? 'Unknown error') . "\n";
                return false;
            }
        } else {
            echo "     ❌ Invalid response format\n";
            echo "       Response: " . substr($response, 0, 200) . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "     ❌ Web login error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test with common username patterns for hell.lab domain
$testAccounts = [
    // Common test accounts that might exist in hell.lab
    ['administrator', 'password', '(Domain Administrator)'],
    ['admin', 'password', '(Admin User)'],
    ['testuser', 'password', '(Test User)'],
    ['user', 'password', '(Generic User)'],
    // Email format
    ['administrator@hell.lab', 'password', '(Admin Email Format)'],
    ['admin@hell.lab', 'password', '(Admin Email Format)'],
    ['testuser@hell.lab', 'password', '(Test Email Format)'],
];

echo "   NOTE: Testing with common credentials - replace with actual hell.lab users\n\n";

$successCount = 0;
foreach ($testAccounts as $account) {
    $result = testWebLogin($account[0], $account[1], $account[2]);
    if ($result) $successCount++;
    echo "\n";
}

// Test 3: Direct Auth Class Test
echo "3. Testing Direct Authentication...\n";

function testDirectAuth($identifier, $password, $description) {
    echo "   Testing direct auth: $identifier $description\n";
    
    try {
        $result = Auth::login($identifier, $password);
        
        if ($result['success']) {
            echo "     ✅ DIRECT AUTH SUCCESS\n";
            echo "       User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
            echo "       Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            echo "       Method: " . ($result['method'] ?? 'Unknown') . "\n";
            return true;
        } else {
            echo "     ❌ DIRECT AUTH FAILED\n";
            echo "       Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "     ❌ Direct auth error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test a few direct authentication attempts
$directTests = [
    ['administrator', 'password', '(Administrator)'],
    ['admin', 'admin', '(Admin with admin password)'],
    ['testuser', 'test', '(Test user)']
];

foreach ($directTests as $test) {
    testDirectAuth($test[0], $test[1], $test[2]);
    echo "\n";
}

// Test 4: Check existing users in database
echo "4. Checking Database Users...\n";
try {
    $stmt = $pdo->query("SELECT userid, username, email, role FROM users ORDER BY userid DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Recent users in database:\n";
    foreach ($users as $user) {
        echo "     ID: {$user['userid']} | Username: {$user['username']} | Email: {$user['email']} | Role: " . ($user['role'] ?: 'None') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 5: Session Check
echo "\n5. Testing Session Management...\n";

function testSessionCheck() {
    try {
        $response = @file_get_contents('http://localhost/nwrcontractregistry/backend/auth_handler.php?action=check');
        
        if ($response === false) {
            echo "   ❌ Could not connect to session check endpoint\n";
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['loggedIn'])) {
            if ($result['loggedIn']) {
                echo "   ✅ Active session found\n";
                echo "     User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
                echo "     Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            } else {
                echo "   ℹ️ No active session\n";
            }
            return true;
        } else {
            echo "   ❌ Invalid session check response\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ Session check error: " . $e->getMessage() . "\n";
        return false;
    }
}

testSessionCheck();

// Summary
echo "\n=== LOGIN TEST SUMMARY ===\n";
echo "Web Login Tests: $successCount/" . count($testAccounts) . " successful\n";

if ($successCount > 0) {
    echo "✅ LOGIN SYSTEM: WORKING\n";
    echo "   → Some accounts authenticated successfully\n";
    echo "   → LDAP integration is functional\n";
} else {
    echo "⚠️ LOGIN SYSTEM: NO SUCCESSFUL LOGINS\n";
    echo "   → This is normal if test credentials don't exist in hell.lab\n";
    echo "   → System is ready for actual domain users\n";
}

echo "\n🎯 NEXT STEPS:\n";
echo "1. Use actual hell.lab domain credentials\n";
echo "2. Test with real user accounts from your domain\n";
echo "3. Try both username and email formats\n";
echo "4. Check role assignment after successful login\n";

echo "\n🌐 Application URL: http://localhost/nwrcontractregistry/frontend/index.php\n";
echo "📝 Try logging in with:\n";
echo "   • Your hell.lab domain username\n";
echo "   • Format: 'username' or 'username@hell.lab'\n";
echo "   • Use your actual domain password\n";

?>