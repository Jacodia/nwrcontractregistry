<?php
/**
 * Simple User Authentication Test
 * Test authentication with actual hell.lab domain users
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

Auth::init($pdo);

echo "🔐 USER AUTHENTICATION TEST\n";
echo "===========================\n\n";

if ($argc < 3) {
    echo "Usage: php test_user_auth.php [username] [password]\n";
    echo "Example: php test_user_auth.php administrator mypassword\n\n";
    
    echo "Common test accounts to try:\n";
    echo "• administrator (built-in Windows admin)\n";
    echo "• nwr-admin (if created)\n";
    echo "• nwr-manager (if created)\n";
    echo "• nwr-user (if created)\n";
    echo "• Any existing domain user\n\n";
    
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

echo "Testing authentication for: $username@hell.lab\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "1. 🔍 Testing LDAP Authentication...\n";
    
    $result = Auth::authenticateLDAP($username, $password);
    
    if ($result) {
        echo "   ✅ LDAP Authentication: SUCCESS\n";
        echo "   User details retrieved from Active Directory\n\n";
        
        echo "2. 🏗️ Testing Auto-Provisioning...\n";
        
        $user = Auth::login($username, $password);
        
        if ($user) {
            echo "   ✅ Auto-Provisioning: SUCCESS\n";
            echo "   User created/updated in local database\n\n";
            
            echo "3. 📋 User Information:\n";
            echo "   Database ID: {$user['id']}\n";
            echo "   Username: {$user['username']}\n";
            echo "   Email: {$user['email']}\n";
            echo "   Role: {$user['role']}\n";
            echo "   Created: {$user['created_at']}\n\n";
            
            echo "🎉 COMPLETE SUCCESS!\n";
            echo "User can now log into the web application.\n";
            
        } else {
            echo "   ❌ Auto-Provisioning: FAILED\n";
            echo "   LDAP auth worked but database creation failed\n";
        }
        
    } else {
        echo "   ❌ LDAP Authentication: FAILED\n";
        echo "   Possible reasons:\n";
        echo "   • Invalid username or password\n";
        echo "   • User doesn't exist in hell.lab domain\n";
        echo "   • Account is disabled or locked\n";
        echo "   • Network connectivity issue\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Next Steps:\n";
echo "1. If successful: Test login at http://localhost/nwrcontractregistry/frontend/\n";
echo "2. If failed: Check user exists in Active Directory\n";
echo "3. Create test users using the PowerShell script if needed\n";