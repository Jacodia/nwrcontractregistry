<?php
echo "=== PHP → Active Directory LDAP Connection Guide ===\n";
echo "Using your current .env configuration\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

require_once __DIR__ . '/../../vendor/autoload.php';

// Load your current .env configuration
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "📋 CURRENT .ENV LDAP CONFIGURATION\n";
echo "==================================\n";
echo "LDAP_HOST: " . $_ENV['LDAP_HOST'] . "\n";
echo "LDAP_PORT: " . $_ENV['LDAP_PORT'] . "\n";
echo "LDAP_DOMAIN: " . $_ENV['LDAP_DOMAIN'] . "\n";
echo "LDAP_BASE_DN: " . $_ENV['LDAP_BASE_DN'] . "\n";
echo "LDAP_USE_TLS: " . $_ENV['LDAP_USE_TLS'] . "\n\n";

// Check for potential configuration issues
echo "🔍 CONFIGURATION ANALYSIS\n";
echo "=========================\n";

$issues = [];
$recommendations = [];

// Check port vs TLS configuration
if ($_ENV['LDAP_PORT'] == '636' && $_ENV['LDAP_USE_TLS'] === 'false') {
    $issues[] = "Port 636 is typically for LDAPS (secure), but TLS is disabled";
    $recommendations[] = "Either use port 389 with TLS=false, or port 636 with TLS=true";
}

if ($_ENV['LDAP_PORT'] == '389' && $_ENV['LDAP_USE_TLS'] === 'true') {
    $recommendations[] = "Good: Port 389 with TLS (StartTLS) is a secure configuration";
}

if ($_ENV['LDAP_PORT'] == '636' && $_ENV['LDAP_USE_TLS'] === 'true') {
    $recommendations[] = "Good: Port 636 with TLS is secure LDAPS configuration";
}

if (empty($issues)) {
    echo "✅ Configuration looks good\n";
} else {
    foreach ($issues as $issue) {
        echo "⚠️ Issue: $issue\n";
    }
}

foreach ($recommendations as $rec) {
    echo "💡 $rec\n";
}

echo "\n🔌 PHP LDAP CONNECTION PROCESS\n";
echo "==============================\n\n";

echo "Step-by-step how PHP connects to your AD:\n\n";

echo "1. 🌐 LDAP Connection Establishment\n";
echo "   PHP Code: ldap_connect('{$_ENV['LDAP_HOST']}', {$_ENV['LDAP_PORT']})\n";
echo "   Target: {$_ENV['LDAP_HOST']}:{$_ENV['LDAP_PORT']}\n";
echo "   Protocol: " . ($_ENV['LDAP_PORT'] == '636' ? 'LDAPS (SSL)' : 'LDAP') . "\n\n";

echo "2. ⚙️ LDAP Options Configuration\n";
echo "   ldap_set_option(\$conn, LDAP_OPT_PROTOCOL_VERSION, 3)\n";
echo "   ldap_set_option(\$conn, LDAP_OPT_REFERRALS, 0)\n";
echo "   ldap_set_option(\$conn, LDAP_OPT_NETWORK_TIMEOUT, 10)\n\n";

if ($_ENV['LDAP_USE_TLS'] === 'true') {
    echo "3. 🔒 TLS/StartTLS Initialization\n";
    echo "   ldap_start_tls(\$conn)\n";
    echo "   Encrypts communication after initial connection\n\n";
}

echo "4. 🔑 Authentication Process\n";
echo "   Method: Direct bind with user credentials\n";
echo "   Format: username@{$_ENV['LDAP_DOMAIN']} or sAMAccountName\n";
echo "   Base DN: {$_ENV['LDAP_BASE_DN']}\n\n";

echo "5. 📋 User Information Retrieval\n";
echo "   Search Base: {$_ENV['LDAP_BASE_DN']}\n";
echo "   Filter: (&(objectClass=user)(sAMAccountName=username))\n";
echo "   Attributes: sAMAccountName, mail, displayName, memberOf\n\n";

echo "📝 ACTUAL PHP CODE IMPLEMENTATION\n";
echo "=================================\n\n";

// Show the actual code that runs
echo "Here's the actual PHP code that connects to your AD:\n\n";

echo "```php\n";
echo "// 1. Load configuration\n";
echo "\$ldap_host = '{$_ENV['LDAP_HOST']}';\n";
echo "\$ldap_port = {$_ENV['LDAP_PORT']};\n";
echo "\$ldap_domain = '{$_ENV['LDAP_DOMAIN']}';\n";
echo "\$ldap_base_dn = '{$_ENV['LDAP_BASE_DN']}';\n";
echo "\$use_tls = " . ($_ENV['LDAP_USE_TLS'] === 'true' ? 'true' : 'false') . ";\n\n";

echo "// 2. Establish connection\n";
echo "\$ldapconn = ldap_connect(\$ldap_host, \$ldap_port);\n";
echo "if (!\$ldapconn) {\n";
echo "    throw new Exception('Could not connect to LDAP server');\n";
echo "}\n\n";

echo "// 3. Set options\n";
echo "ldap_set_option(\$ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);\n";
echo "ldap_set_option(\$ldapconn, LDAP_OPT_REFERRALS, 0);\n";
echo "ldap_set_option(\$ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 10);\n\n";

if ($_ENV['LDAP_USE_TLS'] === 'true') {
    echo "// 4. Start TLS (if enabled)\n";
    echo "if (!\ldap_start_tls(\$ldapconn)) {\n";
    echo "    throw new Exception('Could not start TLS');\n";
    echo "}\n\n";
}

echo "// 5. Authenticate user\n";
echo "\$username = 'testuser';  // User input\n";
echo "\$password = 'password';  // User input\n";
echo "\$userDN = \$username . '@{$_ENV['LDAP_DOMAIN']}';\n\n";

echo "if (ldap_bind(\$ldapconn, \$userDN, \$password)) {\n";
echo "    // Authentication successful\n";
echo "    echo 'Login successful!';\n";
echo "} else {\n";
echo "    // Authentication failed\n";
echo "    echo 'Invalid credentials';\n";
echo "}\n";
echo "```\n\n";

echo "🧪 TESTING YOUR CURRENT CONFIGURATION\n";
echo "=====================================\n\n";

function testLDAPConnection() {
    try {
        $ldap_host = $_ENV['LDAP_HOST'];
        $ldap_port = $_ENV['LDAP_PORT'];
        $use_tls = $_ENV['LDAP_USE_TLS'] === 'true';
        
        echo "Testing connection to: $ldap_host:$ldap_port\n";
        
        // Attempt connection
        $ldapconn = ldap_connect($ldap_host, $ldap_port);
        if (!$ldapconn) {
            echo "❌ Connection failed: Could not connect\n";
            return false;
        }
        
        echo "✅ Initial connection established\n";
        
        // Set options
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 10);
        
        echo "✅ LDAP options configured\n";
        
        // Test TLS if enabled
        if ($use_tls) {
            echo "Testing TLS/StartTLS...\n";
            if (ldap_start_tls($ldapconn)) {
                echo "✅ TLS/StartTLS successful\n";
            } else {
                echo "⚠️ TLS/StartTLS failed: " . ldap_error($ldapconn) . "\n";
            }
        } else {
            echo "ℹ️ TLS disabled (plain connection)\n";
        }
        
        // Test anonymous bind
        echo "Testing anonymous bind...\n";
        if (@ldap_bind($ldapconn)) {
            echo "✅ Anonymous bind successful\n";
        } else {
            echo "⚠️ Anonymous bind failed: " . ldap_error($ldapconn) . "\n";
            echo "   This is normal if anonymous access is disabled\n";
        }
        
        ldap_close($ldapconn);
        return true;
        
    } catch (Exception $e) {
        echo "❌ Connection test failed: " . $e->getMessage() . "\n";
        return false;
    }
}

$connectionResult = testLDAPConnection();

echo "\n📊 CONNECTION TEST RESULTS\n";
echo "==========================\n";

if ($connectionResult) {
    echo "✅ LDAP connection test: PASSED\n";
    echo "   Your PHP can connect to Active Directory\n";
    echo "   Ready for user authentication testing\n";
} else {
    echo "❌ LDAP connection test: FAILED\n";
    echo "   Check network connectivity and AD server status\n";
}

echo "\n🔧 TROUBLESHOOTING TIPS\n";
echo "=======================\n\n";

echo "If connection fails:\n\n";

echo "1. 🌐 Network Connectivity\n";
echo "   • Test: telnet localhost {$_ENV['LDAP_PORT']}\n";
echo "   • Verify AD server is running\n";
echo "   • Check firewall settings\n\n";

echo "2. 🔒 Port & Security Configuration\n";
if ($_ENV['LDAP_PORT'] == '636') {
    echo "   • Port 636 = LDAPS (SSL encrypted)\n";
    echo "   • Requires SSL certificate\n";
    echo "   • Try port 389 for testing\n";
} else {
    echo "   • Port 389 = Standard LDAP\n";
    echo "   • Can use StartTLS for encryption\n";
    echo "   • Most common configuration\n";
}
echo "\n";

echo "3. 📋 Configuration Verification\n";
echo "   • Domain: {$_ENV['LDAP_DOMAIN']}\n";
echo "   • Base DN: {$_ENV['LDAP_BASE_DN']}\n";
echo "   • Ensure DC components match your domain\n\n";

echo "4. 🔑 Authentication Testing\n";
echo "   • Try with known AD user accounts\n";
echo "   • Use format: username@{$_ENV['LDAP_DOMAIN']}\n";
echo "   • Verify account is enabled and not locked\n\n";

echo "🎯 RECOMMENDED CONFIGURATION\n";
echo "============================\n\n";

echo "For testing hell.lab domain, recommend:\n";
echo "LDAP_HOST=ldap://localhost\n";
echo "LDAP_PORT=389\n";
echo "LDAP_USE_TLS=false\n";
echo "LDAP_DOMAIN=hell.lab\n";
echo "LDAP_BASE_DN=DC=hell,DC=lab\n\n";

echo "For production (more secure):\n";
echo "LDAP_HOST=ldap://localhost\n"; 
echo "LDAP_PORT=389\n";
echo "LDAP_USE_TLS=true\n";
echo "LDAP_DOMAIN=hell.lab\n";
echo "LDAP_BASE_DN=DC=hell,DC=lab\n\n";

echo "🌐 Ready to test at: http://localhost/nwrcontractregistry/frontend/index.php\n";

?>