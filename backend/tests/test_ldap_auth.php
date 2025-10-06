<?php
echo "=== NWR Contract Registry - LDAP Authentication Test ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

Auth::init($pdo);

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "1. LDAP Configuration Check...\n";
echo "   LDAP Host: " . $_ENV['LDAP_HOST'] . "\n";
echo "   LDAP Port: " . $_ENV['LDAP_PORT'] . "\n";
echo "   LDAP Domain: " . $_ENV['LDAP_DOMAIN'] . "\n";
echo "   LDAP Base DN: " . $_ENV['LDAP_BASE_DN'] . "\n";
echo "   Use TLS: " . ($_ENV['LDAP_USE_TLS'] === 'true' ? 'Yes' : 'No') . "\n";
echo "   Environment: " . $_ENV['APP_ENV'] . "\n\n";

// Test LDAP connection
echo "2. Testing LDAP Connection...\n";

function testLDAPConnection() {
    try {
        $ldap_host = $_ENV['LDAP_HOST'];
        $ldap_port = $_ENV['LDAP_PORT'];
        $use_tls = $_ENV['LDAP_USE_TLS'] === 'true';
        
        echo "   Attempting to connect to: $ldap_host:$ldap_port\n";
        
        $ldapconn = ldap_connect($ldap_host, $ldap_port);
        if (!$ldapconn) {
            echo "   ✗ Failed to connect to LDAP server\n";
            return false;
        }
        
        // Set LDAP options
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 10);
        
        if ($use_tls) {
            echo "   Attempting to start TLS...\n";
            if (!ldap_start_tls($ldapconn)) {
                echo "   ⚠ Could not start TLS: " . ldap_error($ldapconn) . "\n";
                // Continue without TLS for testing
            } else {
                echo "   ✓ TLS started successfully\n";
            }
        }
        
        echo "   ✓ LDAP connection established\n";
        
        // Test basic bind (anonymous)
        echo "   Testing anonymous bind...\n";
        $bind = @ldap_bind($ldapconn);
        if ($bind) {
            echo "   ✓ Anonymous bind successful\n";
        } else {
            echo "   ⚠ Anonymous bind failed: " . ldap_error($ldapconn) . "\n";
        }
        
        ldap_close($ldapconn);
        return true;
        
    } catch (Exception $e) {
        echo "   ✗ LDAP connection error: " . $e->getMessage() . "\n";
        return false;
    }
}

$ldapConnectionWorks = testLDAPConnection();

// Test LDAP authentication with sample users
echo "\n3. Testing LDAP Authentication...\n";

function testLDAPAuth($email, $password, $description = "") {
    echo "   Testing LDAP auth: $email $description\n";
    
    try {
        $result = Auth::login($email, $password);
        
        if ($result['success']) {
            echo "   ✓ LDAP authentication successful\n";
            echo "     → User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
            echo "     → Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            echo "     → Method: " . ($result['method'] ?? 'Unknown') . "\n";
            return true;
        } else {
            echo "   ✗ LDAP authentication failed\n";
            echo "     → Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ LDAP auth error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test with domain users (you'll need to replace these with actual LDAP users)
$ldapTestUsers = [
    // Format: [email/username, password, description]
    // Examples - replace with your actual test users:
    ['testuser@hell.lab', 'password123', '(Test User)'],
    ['admin@hell.lab', 'adminpass', '(Admin User)'],
    // You can also test with just username if domain is configured
    ['testuser', 'password123', '(Username only)']
];

echo "   NOTE: Testing with sample credentials - replace with actual LDAP users\n";
foreach ($ldapTestUsers as $user) {
    testLDAPAuth($user[0], $user[1], $user[2]);
}

// Test web-based LDAP authentication
echo "\n4. Testing Web API with LDAP...\n";

function testWebLDAPAuth($email, $password) {
    echo "   Testing web LDAP auth: $email\n";
    
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
            echo "   ✓ Web LDAP authentication successful!\n";
            echo "     → User: " . ($result['user']['username'] ?? 'Unknown') . "\n";
            echo "     → Role: " . ($result['user']['role'] ?? 'Unknown') . "\n";
            return true;
        } else {
            echo "   ✗ Web LDAP authentication failed\n";
            echo "     → Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "   ✗ Web LDAP auth error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test the first user via web API
if (!empty($ldapTestUsers)) {
    testWebLDAPAuth($ldapTestUsers[0][0], $ldapTestUsers[0][1]);
}

// Check LDAP PHP extension
echo "\n5. PHP LDAP Extension Check...\n";
if (extension_loaded('ldap')) {
    echo "   ✓ PHP LDAP extension is loaded\n";
    
    // Get LDAP info
    $ldapInfo = ldap_get_option(null, LDAP_OPT_API_INFO, $info);
    if ($ldapInfo) {
        echo "   → LDAP API Version: " . ($info['api_version'] ?? 'Unknown') . "\n";
        echo "   → LDAP Protocol Version: " . ($info['protocol_version'] ?? 'Unknown') . "\n";
    }
} else {
    echo "   ✗ PHP LDAP extension is NOT loaded\n";
    echo "   → You need to enable the LDAP extension in PHP\n";
}

// Summary and recommendations
echo "\n=== LDAP Test Summary ===\n";

if (!extension_loaded('ldap')) {
    echo "❌ CRITICAL: PHP LDAP extension not loaded\n";
    echo "\nTo fix:\n";
    echo "1. Edit C:\\php\\php.ini\n";
    echo "2. Uncomment: extension=ldap\n";
    echo "3. Restart IIS: iisreset\n";
} elseif ($ldapConnectionWorks) {
    echo "✅ LDAP Configuration: READY\n";
    echo "⚠️  Next Steps:\n";
    echo "1. Replace test credentials with actual LDAP users from hell.lab domain\n";
    echo "2. Test with real user accounts\n";
    echo "3. Configure LDAP group mappings if needed\n";
} else {
    echo "⚠️  LDAP Configuration: NEEDS ATTENTION\n";
    echo "\nTroubleshooting:\n";
    echo "1. Verify LDAP server is running on hell.lab\n";
    echo "2. Check network connectivity to LDAP server\n";
    echo "3. Verify LDAP_HOST and LDAP_PORT settings\n";
    echo "4. Check firewall settings\n";
}

echo "\n=== Configuration Notes ===\n";
echo "🔧 Current LDAP Settings:\n";
echo "   Domain: hell.lab\n";
echo "   Server: " . $_ENV['LDAP_HOST'] . ":" . $_ENV['LDAP_PORT'] . "\n";
echo "   Base DN: " . $_ENV['LDAP_BASE_DN'] . "\n";
echo "   TLS: " . ($_ENV['LDAP_USE_TLS'] === 'true' ? 'Enabled' : 'Disabled') . "\n";

echo "\n📝 To test with real users:\n";
echo "1. Use actual usernames from your hell.lab domain\n";
echo "2. Try both email format (user@hell.lab) and username format\n";
echo "3. Ensure users exist in the AD/LDAP directory\n";

echo "\n🌐 Application URL: http://localhost/nwrcontractregistry/frontend/index.php\n";

?>